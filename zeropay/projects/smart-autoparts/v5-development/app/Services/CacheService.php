<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CacheService
{
    protected $defaultTTL = 3600; // 1 hour
    protected $prefix = 'smart_autoparts';
    
    /**
     * Smart caching with automatic invalidation
     */
    public function remember($key, $callback, $ttl = null, $tags = [])
    {
        $fullKey = $this->generateKey($key);
        $ttl = $ttl ?? $this->defaultTTL;
        
        // Try to get from cache
        $cached = Cache::tags($tags)->get($fullKey);
        
        if ($cached !== null) {
            $this->recordHit($fullKey);
            return $cached;
        }
        
        // Execute callback and cache result
        $result = $callback();
        
        if ($result !== null) {
            Cache::tags($tags)->put($fullKey, $result, $ttl);
            $this->recordMiss($fullKey);
        }
        
        return $result;
    }
    
    /**
     * Cache with warming capability
     */
    public function warm($patterns = [])
    {
        $warmingTasks = [
            'categories' => function () {
                return \App\Models\Category::active()
                    ->with('children')
                    ->get();
            },
            'featured_products' => function () {
                return \App\Models\Product::featured()
                    ->active()
                    ->with(['shop', 'category'])
                    ->limit(20)
                    ->get();
            },
            'top_shops' => function () {
                return \App\Models\Shop::verified()
                    ->active()
                    ->orderBy('rating', 'desc')
                    ->limit(10)
                    ->get();
            },
            'brands' => function () {
                return \App\Models\Product::distinct()
                    ->pluck('brand')
                    ->filter()
                    ->sort()
                    ->values();
            },
        ];
        
        foreach ($warmingTasks as $key => $task) {
            if (empty($patterns) || $this->matchesPattern($key, $patterns)) {
                $this->remember($key, $task, 7200, ['warming']);
            }
        }
    }
    
    /**
     * Intelligent cache invalidation
     */
    public function invalidate($tags = [], $patterns = [])
    {
        if (!empty($tags)) {
            Cache::tags($tags)->flush();
        }
        
        if (!empty($patterns)) {
            foreach ($patterns as $pattern) {
                $this->invalidateByPattern($pattern);
            }
        }
    }
    
    /**
     * Fragment caching for views
     */
    public function fragment($key, $view, $data = [], $ttl = null)
    {
        return $this->remember($key, function () use ($view, $data) {
            return view($view, $data)->render();
        }, $ttl, ['fragments']);
    }
    
    /**
     * Query result caching with automatic invalidation
     */
    public function query($model, $key, $callback, $ttl = null)
    {
        $modelClass = is_string($model) ? $model : get_class($model);
        $modelTag = Str::snake(class_basename($modelClass));
        
        return $this->remember(
            "{$modelTag}:{$key}",
            $callback,
            $ttl,
            [$modelTag, 'queries']
        );
    }
    
    /**
     * API response caching
     */
    public function apiResponse($endpoint, $params, $callback, $ttl = 300)
    {
        $key = "api:{$endpoint}:" . md5(serialize($params));
        
        return $this->remember($key, $callback, $ttl, ['api']);
    }
    
    /**
     * Get cache statistics
     */
    public function getStats()
    {
        $stats = Cache::get($this->prefix . ':stats', [
            'hits' => 0,
            'misses' => 0,
            'hit_rate' => 0,
            'top_keys' => [],
            'memory_usage' => 0,
        ]);
        
        $stats['hit_rate'] = $stats['hits'] + $stats['misses'] > 0
            ? round(($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100, 2)
            : 0;
            
        return $stats;
    }
    
    /**
     * Preload cache for user session
     */
    public function preloadForUser($user)
    {
        if (!$user) return;
        
        // Preload user-specific data
        $this->remember("user:{$user->id}:cart", function () use ($user) {
            return $user->cart()->with('product.shop')->get();
        }, 1800, ['user', "user:{$user->id}"]);
        
        $this->remember("user:{$user->id}:favorites", function () use ($user) {
            return $user->favorites()->pluck('product_id');
        }, 3600, ['user', "user:{$user->id}"]);
        
        $this->remember("user:{$user->id}:recent_orders", function () use ($user) {
            return $user->orders()->latest()->limit(5)->get();
        }, 1800, ['user', "user:{$user->id}"]);
    }
    
    /**
     * Clear user-specific cache
     */
    public function clearUserCache($userId)
    {
        Cache::tags(["user:{$userId}"])->flush();
    }
    
    /**
     * Distributed locking for cache operations
     */
    public function lock($key, $callback, $timeout = 10)
    {
        $lock = Cache::lock($this->generateKey("lock:{$key}"), $timeout);
        
        if ($lock->acquire()) {
            try {
                return $callback();
            } finally {
                $lock->release();
            }
        }
        
        throw new \Exception('Could not acquire lock for: ' . $key);
    }
    
    // Helper methods
    
    protected function generateKey($key)
    {
        return "{$this->prefix}:{$key}";
    }
    
    protected function recordHit($key)
    {
        $stats = Cache::get($this->prefix . ':stats', []);
        $stats['hits'] = ($stats['hits'] ?? 0) + 1;
        
        // Track top keys
        $stats['top_keys'][$key] = ($stats['top_keys'][$key] ?? 0) + 1;
        arsort($stats['top_keys']);
        $stats['top_keys'] = array_slice($stats['top_keys'], 0, 20, true);
        
        Cache::put($this->prefix . ':stats', $stats, 86400);
    }
    
    protected function recordMiss($key)
    {
        $stats = Cache::get($this->prefix . ':stats', []);
        $stats['misses'] = ($stats['misses'] ?? 0) + 1;
        Cache::put($this->prefix . ':stats', $stats, 86400);
    }
    
    protected function matchesPattern($key, $patterns)
    {
        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $key)) {
                return true;
            }
        }
        return false;
    }
    
    protected function invalidateByPattern($pattern)
    {
        // Implementation depends on cache driver
        // For Redis, we can use pattern matching
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Redis::keys($this->prefix . ':' . $pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        }
    }
}