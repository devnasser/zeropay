<?php

/**
 * Cache Manager - نظام التخزين المؤقت المحلي
 * بديل مجاني لـ Redis/Memcached باستخدام SQLite و File Cache
 */

interface CacheInterface {
    public function get(string $key);
    public function set(string $key, $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function flush(): bool;
    public function exists(string $key): bool;
}

/**
 * SQLite Cache - للبيانات المهيكلة
 */
class SqliteCache implements CacheInterface {
    private $db;
    private $tableName = 'cache';
    
    public function __construct(string $dbPath) {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initTable();
    }
    
    private function initTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                expires_at INTEGER NOT NULL,
                created_at INTEGER DEFAULT (strftime('%s', 'now'))
            )
        ");
        
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_expires ON {$this->tableName}(expires_at)");
    }
    
    public function get(string $key) {
        $this->cleanExpired();
        
        $stmt = $this->db->prepare("
            SELECT value FROM {$this->tableName} 
            WHERE key = :key AND expires_at > strftime('%s', 'now')
        ");
        
        $stmt->execute([':key' => $key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? unserialize($result['value']) : null;
    }
    
    public function set(string $key, $value, int $ttl = 3600): bool {
        $expiresAt = time() + $ttl;
        $serialized = serialize($value);
        
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO {$this->tableName} (key, value, expires_at)
            VALUES (:key, :value, :expires_at)
        ");
        
        return $stmt->execute([
            ':key' => $key,
            ':value' => $serialized,
            ':expires_at' => $expiresAt
        ]);
    }
    
    public function delete(string $key): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->tableName} WHERE key = :key");
        return $stmt->execute([':key' => $key]);
    }
    
    public function flush(): bool {
        return $this->db->exec("DELETE FROM {$this->tableName}") !== false;
    }
    
    public function exists(string $key): bool {
        return $this->get($key) !== null;
    }
    
    private function cleanExpired() {
        $this->db->exec("DELETE FROM {$this->tableName} WHERE expires_at <= strftime('%s', 'now')");
    }
}

/**
 * File Cache - للملفات والبيانات الكبيرة
 */
class FileCache implements CacheInterface {
    private $cacheDir;
    private $defaultTtl = 3600;
    
    public function __construct(string $cacheDir) {
        $this->cacheDir = rtrim($cacheDir, '/');
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    private function getPath(string $key): string {
        $hash = md5($key);
        $dir = $this->cacheDir . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/' . $hash . '.cache';
    }
    
    public function get(string $key) {
        $path = $this->getPath($key);
        
        if (!file_exists($path)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($path));
        
        if ($data['expires_at'] < time()) {
            unlink($path);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set(string $key, $value, int $ttl = 3600): bool {
        $path = $this->getPath($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        return file_put_contents($path, serialize($data), LOCK_EX) !== false;
    }
    
    public function delete(string $key): bool {
        $path = $this->getPath($key);
        return file_exists($path) && unlink($path);
    }
    
    public function flush(): bool {
        $this->deleteDirectory($this->cacheDir);
        mkdir($this->cacheDir, 0755, true);
        return true;
    }
    
    public function exists(string $key): bool {
        return $this->get($key) !== null;
    }
    
    private function deleteDirectory(string $dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

/**
 * Memory Cache - للبيانات المؤقتة السريعة
 */
class MemoryCache implements CacheInterface {
    private static $data = [];
    private static $expires = [];
    
    public function get(string $key) {
        if (!isset(self::$data[$key])) {
            return null;
        }
        
        if (self::$expires[$key] < time()) {
            unset(self::$data[$key], self::$expires[$key]);
            return null;
        }
        
        return self::$data[$key];
    }
    
    public function set(string $key, $value, int $ttl = 3600): bool {
        self::$data[$key] = $value;
        self::$expires[$key] = time() + $ttl;
        return true;
    }
    
    public function delete(string $key): bool {
        unset(self::$data[$key], self::$expires[$key]);
        return true;
    }
    
    public function flush(): bool {
        self::$data = [];
        self::$expires = [];
        return true;
    }
    
    public function exists(string $key): bool {
        return $this->get($key) !== null;
    }
}

/**
 * Multi-tier Cache Manager
 */
class CacheManager {
    private $tiers = [];
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0
    ];
    
    public function __construct() {
        // Tier 1: Memory (الأسرع)
        $this->tiers[] = new MemoryCache();
        
        // Tier 2: SQLite (للبيانات المهيكلة)
        $this->tiers[] = new SqliteCache('/workspace/.cache/app-cache.db');
        
        // Tier 3: File (للملفات الكبيرة)
        $this->tiers[] = new FileCache('/workspace/.cache/files');
    }
    
    public function get(string $key) {
        foreach ($this->tiers as $index => $cache) {
            $value = $cache->get($key);
            
            if ($value !== null) {
                $this->stats['hits']++;
                
                // ترقية إلى الطبقات الأعلى
                for ($i = 0; $i < $index; $i++) {
                    $this->tiers[$i]->set($key, $value, 3600);
                }
                
                return $value;
            }
        }
        
        $this->stats['misses']++;
        return null;
    }
    
    public function set(string $key, $value, int $ttl = 3600): bool {
        $this->stats['writes']++;
        
        // كتابة في جميع الطبقات
        $success = true;
        foreach ($this->tiers as $cache) {
            $success = $cache->set($key, $value, $ttl) && $success;
        }
        
        return $success;
    }
    
    public function delete(string $key): bool {
        $success = true;
        foreach ($this->tiers as $cache) {
            $success = $cache->delete($key) && $success;
        }
        return $success;
    }
    
    public function flush(): bool {
        $success = true;
        foreach ($this->tiers as $cache) {
            $success = $cache->flush() && $success;
        }
        $this->stats = ['hits' => 0, 'misses' => 0, 'writes' => 0];
        return $success;
    }
    
    public function getStats(): array {
        $hitRate = $this->stats['hits'] + $this->stats['misses'] > 0
            ? round($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses']) * 100, 2)
            : 0;
            
        return array_merge($this->stats, ['hit_rate' => $hitRate . '%']);
    }
}

// مثال على الاستخدام
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "🧪 اختبار نظام التخزين المؤقت\n\n";
    
    $cache = new CacheManager();
    
    // اختبار الكتابة
    echo "📝 كتابة البيانات...\n";
    $cache->set('user:1', ['name' => 'أحمد', 'email' => 'ahmad@example.com']);
    $cache->set('products', range(1, 100));
    
    // اختبار القراءة
    echo "📖 قراءة البيانات...\n";
    var_dump($cache->get('user:1'));
    
    // الإحصائيات
    echo "\n📊 الإحصائيات:\n";
    print_r($cache->getStats());
}