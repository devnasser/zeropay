<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SecurityService
{
    protected $encryptionKey;
    protected $suspiciousPatterns = [
        'sql_injection' => [
            '/union.*select/i',
            '/select.*from.*information_schema/i',
            '/\bor\b.*=.*\bor\b/i',
            '/\bdrop\s+table\b/i',
            '/\binsert\s+into\b.*\bvalues\b/i',
            '/\bupdate\b.*\bset\b/i',
            '/\bdelete\s+from\b/i',
            '/\bexec\b|\bexecute\b/i',
            '/\bscript\b.*\balert\b/i',
            '/\b(sleep|benchmark|waitfor)\b/i'
        ],
        'xss' => [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>/i',
            '/<link[^>]*>/i',
            '/document\.(cookie|write)/i',
            '/window\.(location|open)/i'
        ],
        'path_traversal' => [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/%2e%2e%2f/i',
            '/%252e%252e%252f/i',
            '/\.\.[\/\\\]/',
            '/etc\/passwd/',
            '/windows\/system32/i'
        ],
        'command_injection' => [
            '/;\s*(ls|cat|rm|mv|cp|wget|curl|nc|bash|sh)\b/i',
            '/\|\s*(ls|cat|rm|mv|cp|wget|curl|nc|bash|sh)\b/i',
            '/`[^`]*`/',
            '/\$\([^)]*\)/',
            '/&&\s*(ls|cat|rm|mv|cp|wget|curl|nc|bash|sh)\b/i'
        ]
    ];
    
    public function __construct()
    {
        $this->encryptionKey = config('app.key');
    }
    
    /**
     * Validate and sanitize input data
     */
    public function sanitizeInput($input, array $rules = []): array
    {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            // Skip if null
            if ($value === null) {
                $sanitized[$key] = null;
                continue;
            }
            
            // Apply specific rules if defined
            if (isset($rules[$key])) {
                $sanitized[$key] = $this->applyRule($value, $rules[$key]);
            } else {
                // Default sanitization
                $sanitized[$key] = $this->defaultSanitize($value);
            }
            
            // Check for malicious patterns
            if ($this->detectMaliciousInput($sanitized[$key])) {
                $this->logSecurityIncident('malicious_input', [
                    'field' => $key,
                    'value' => substr($sanitized[$key], 0, 100)
                ]);
                unset($sanitized[$key]);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Detect malicious input patterns
     */
    public function detectMaliciousInput($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        foreach ($this->suspiciousPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Generate secure tokens
     */
    public function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash sensitive data
     */
    public function hashData(string $data): string
    {
        return hash_hmac('sha256', $data, $this->encryptionKey);
    }
    
    /**
     * Verify hashed data
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($this->hashData($data), $hash);
    }
    
    /**
     * Encrypt sensitive data
     */
    public function encrypt(string $data): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public function decrypt(string $encryptedData): ?string
    {
        try {
            $data = base64_decode($encryptedData);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
            return $decrypted !== false ? $decrypted : null;
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Validate API key
     */
    public function validateApiKey(string $apiKey): bool
    {
        // Check format
        if (!preg_match('/^[a-zA-Z0-9]{64}$/', $apiKey)) {
            return false;
        }
        
        // Check in cache first
        $cacheKey = 'api_key:' . substr($apiKey, 0, 8);
        $cached = Redis::get($cacheKey);
        
        if ($cached !== null) {
            return $cached === '1';
        }
        
        // Validate against database
        $valid = \App\Models\ApiKey::where('key', $apiKey)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
        
        // Cache result
        Redis::setex($cacheKey, 300, $valid ? '1' : '0');
        
        return $valid;
    }
    
    /**
     * Check for brute force attempts
     */
    public function checkBruteForce(string $identifier, string $action = 'login'): bool
    {
        $key = 'brute_force:' . $action . ':' . $identifier;
        $attempts = Redis::incr($key);
        
        if ($attempts === 1) {
            Redis::expire($key, 900); // 15 minutes
        }
        
        $limits = [
            'login' => 5,
            'password_reset' => 3,
            'api_auth' => 10,
            'payment' => 3
        ];
        
        $limit = $limits[$action] ?? 5;
        
        if ($attempts > $limit) {
            $this->logSecurityIncident('brute_force', [
                'identifier' => $identifier,
                'action' => $action,
                'attempts' => $attempts
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Reset brute force counter
     */
    public function resetBruteForce(string $identifier, string $action = 'login'): void
    {
        $key = 'brute_force:' . $action . ':' . $identifier;
        Redis::del($key);
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken(string $token, string $sessionToken): bool
    {
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * Generate OTP
     */
    public function generateOTP(string $identifier, int $length = 6, int $ttl = 300): string
    {
        $otp = str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        $key = 'otp:' . $identifier;
        
        Redis::setex($key, $ttl, $otp);
        
        return $otp;
    }
    
    /**
     * Verify OTP
     */
    public function verifyOTP(string $identifier, string $otp): bool
    {
        $key = 'otp:' . $identifier;
        $storedOtp = Redis::get($key);
        
        if ($storedOtp && $storedOtp === $otp) {
            Redis::del($key);
            return true;
        }
        
        return false;
    }
    
    /**
     * Log security incident
     */
    protected function logSecurityIncident(string $type, array $data): void
    {
        $incident = [
            'type' => $type,
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data
        ];
        
        Log::channel('security')->warning('Security Incident', $incident);
        
        // Store in Redis for monitoring
        $key = 'security:incidents:' . date('Y-m-d');
        Redis::lpush($key, json_encode($incident));
        Redis::expire($key, 604800); // 7 days
    }
    
    /**
     * Apply sanitization rule
     */
    protected function applyRule($value, string $rule)
    {
        switch ($rule) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'alpha':
                return preg_replace('/[^a-zA-Z]/', '', $value);
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $value);
            case 'phone':
                return preg_replace('/[^0-9+\-()]/', '', $value);
            default:
                return $this->defaultSanitize($value);
        }
    }
    
    /**
     * Default sanitization
     */
    protected function defaultSanitize($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'defaultSanitize'], $value);
        }
        
        if (!is_string($value)) {
            return $value;
        }
        
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Strip tags but keep content
        $value = strip_tags($value);
        
        // Convert special characters
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return trim($value);
    }
    
    /**
     * Get security report
     */
    public function getSecurityReport(): array
    {
        $today = date('Y-m-d');
        $incidents = Redis::lrange('security:incidents:' . $today, 0, -1);
        
        $report = [
            'date' => $today,
            'total_incidents' => count($incidents),
            'incidents_by_type' => [],
            'top_ips' => [],
            'recent_incidents' => []
        ];
        
        $ipCounts = [];
        
        foreach ($incidents as $incident) {
            $data = json_decode($incident, true);
            
            // Count by type
            $type = $data['type'];
            $report['incidents_by_type'][$type] = ($report['incidents_by_type'][$type] ?? 0) + 1;
            
            // Count by IP
            $ip = $data['ip'];
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
            
            // Add to recent incidents (last 10)
            if (count($report['recent_incidents']) < 10) {
                $report['recent_incidents'][] = $data;
            }
        }
        
        // Get top 5 IPs
        arsort($ipCounts);
        $report['top_ips'] = array_slice($ipCounts, 0, 5, true);
        
        return $report;
    }
}