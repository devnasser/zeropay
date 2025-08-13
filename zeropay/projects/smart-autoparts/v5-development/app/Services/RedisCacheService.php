<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class RedisCacheService
{
    protected $defaultTTL = 3600; // 1 hour
    protected $prefix = 'sap:'; // Smart AutoParts
    
    /**
     * Cache patterns for different data types
     */
    protected $cachePatterns = [
        'product' => ['ttl' => 3600, 'tags' => ['products']],
        'category' => ['ttl' => 7200, 'tags' => ['categories']],
        'user_cart' => ['ttl' => 1800, 'tags' => ['cart', 'user']],
        'shop_stats' => ['ttl' => 300, 'tags' => ['analytics']],
        'search_results' => ['ttl' => 600, 'tags' => ['search']],
    ];
    
    /**
     * Get or set cache with automatic serialization
     */
    public function remember(string $key, callable $callback, int $ttl = null, array $tags = [])
    {
        $fullKey = $this->prefix . $key;
        
        try {
            $cached = Redis::get($fullKey);
            
            if ($cached !== null) {
                $this->recordHit($key);
                return unserialize($cached);
            }
            
            $value = $callback();
            
            if ($value !== null) {
                $this->set($key, $value, $ttl, $tags);
            }
            
            $this->recordMiss($key);
            return $value;
            
        } catch (\Exception $e) {
            Log::error('Redis Cache Error', ['key' => $key, 'error' => $e->getMessage()]);
            return $callback(); // Fallback to direct execution
        }
    }
    
    /**
     * Set cache value
     */
    public function set(string $key, $value, int $ttl = null, array $tags = []): bool
    {
        $fullKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultTTL;
        
        try {
            $serialized = serialize($value);
            Redis::setex($fullKey, $ttl, $serialized);
            
            // Add to tags for easy invalidation
            foreach ($tags as $tag) {
                Redis::sadd($this->prefix . 'tag:' . $tag, $fullKey);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Redis Set Error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get cache value
     */
    public function get(string $key)
    {
        $fullKey = $this->prefix . $key;
        
        try {
            $cached = Redis::get($fullKey);
            return $cached ? unserialize($cached) : null;
        } catch (\Exception $e) {
            Log::error('Redis Get Error', ['key' => $key, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Delete cache by key
     */
    public function forget(string $key): bool
    {
        $fullKey = $this->prefix . $key;
        
        try {
            return Redis::del($fullKey) > 0;
        } catch (\Exception $e) {
            Log::error('Redis Delete Error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Clear cache by tags
     */
    public function flushTags(array $tags): int
    {
        $deleted = 0;
        
        foreach ($tags as $tag) {
            $tagKey = $this->prefix . 'tag:' . $tag;
            $keys = Redis::smembers($tagKey);
            
            if (!empty($keys)) {
                $deleted += Redis::del($keys);
                Redis::del($tagKey);
            }
        }
        
        return $deleted;
    }
    
    /**
     * Cache warmup for critical data
     */
    public function warmup()
    {
        $warmupTasks = [
            'categories' => function() {
                return \App\Models\Category::with('children')
                    ->where('is_active', true)
                    ->get();
            },
            'featured_products' => function() {
                return \App\Models\Product::where('is_featured', true)
                    ->where('is_active', true)
                    ->with(['shop:id,name', 'category:id,name'])
                    ->limit(20)
                    ->get();
            },
            'top_shops' => function() {
                return \App\Models\Shop::where('is_active', true)
                    ->where('is_verified', true)
                    ->orderBy('rating', 'desc')
                    ->limit(10)
                    ->get();
            }
        ];
        
        foreach ($warmupTasks as $key => $task) {
            $pattern = $this->cachePatterns[$key] ?? ['ttl' => 3600, 'tags' => []];
            $this->remember($key, $task, $pattern['ttl'], $pattern['tags']);
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            $info = Redis::info();
            $dbSize = Redis::dbsize();
            
            return [
                'keys' => $dbSize,
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'hit_rate' => $this->calculateHitRate(),
                'uptime' => $info['uptime_in_seconds'] ?? 0,
                'connected_clients' => $info['connected_clients'] ?? 0,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to retrieve Redis stats'];
        }
    }
    
    /**
     * Distributed lock implementation
     */
    public function lock(string $key, callable $callback, int $timeout = 5)
    {
        $lockKey = $this->prefix . 'lock:' . $key;
        $lockValue = uniqid();
        
        try {
            // Try to acquire lock
            if (Redis::set($lockKey, $lockValue, 'EX', $timeout, 'NX')) {
                try {
                    return $callback();
                } finally {
                    // Release lock only if we own it
                    $script = "
                        if redis.call('get', KEYS[1]) == ARGV[1] then
                            return redis.call('del', KEYS[1])
                        else
                            return 0
                        end
                    ";
                    Redis::eval($script, 1, $lockKey, $lockValue);
                }
            }
            
            throw new \Exception('Could not acquire lock for key: ' . $key);
            
        } catch (\Exception $e) {
            Log::error('Redis Lock Error', ['key' => $key, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    protected function recordHit(string $key)
    {
        Redis::hincrby($this->prefix . 'stats', 'hits', 1);
        Redis::hincrby($this->prefix . 'stats:' . date('Y-m-d'), 'hits', 1);
    }
    
    protected function recordMiss(string $key)
    {
        Redis::hincrby($this->prefix . 'stats', 'misses', 1);
        Redis::hincrby($this->prefix . 'stats:' . date('Y-m-d'), 'misses', 1);
    }
    
    protected function calculateHitRate(): float
    {
        $stats = Redis::hgetall($this->prefix . 'stats');
        $hits = $stats['hits'] ?? 0;
        $misses = $stats['misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}