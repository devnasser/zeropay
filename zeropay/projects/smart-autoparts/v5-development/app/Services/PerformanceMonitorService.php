<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class PerformanceMonitorService
{
    protected $metrics = [];
    protected $startTime;
    protected $memoryStart;
    
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage(true);
    }
    
    /**
     * Record a performance metric
     */
    public function record(string $metric, float $value, array $tags = [])
    {
        $key = 'perf:' . $metric . ':' . date('Y-m-d-H');
        
        Redis::pipeline(function ($pipe) use ($key, $value, $tags) {
            $pipe->lpush($key, json_encode([
                'value' => $value,
                'timestamp' => microtime(true),
                'tags' => $tags
            ]));
            $pipe->ltrim($key, 0, 999); // Keep last 1000 entries
            $pipe->expire($key, 86400); // 24 hours
        });
        
        $this->metrics[$metric][] = $value;
    }
    
    /**
     * Start timing an operation
     */
    public function startTimer(string $operation): string
    {
        $timerId = uniqid($operation . '_');
        Redis::set('timer:' . $timerId, microtime(true), 'EX', 300);
        return $timerId;
    }
    
    /**
     * End timing and record the duration
     */
    public function endTimer(string $timerId, array $tags = []): float
    {
        $startTime = Redis::get('timer:' . $timerId);
        if (!$startTime) {
            return 0;
        }
        
        $duration = (microtime(true) - $startTime) * 1000; // milliseconds
        $operation = explode('_', $timerId)[0];
        
        $this->record($operation . '_duration', $duration, $tags);
        Redis::del('timer:' . $timerId);
        
        return $duration;
    }
    
    /**
     * Monitor database performance
     */
    public function monitorDatabase(): array
    {
        $stats = [];
        
        // Connection count
        $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0];
        $stats['active_connections'] = $connections->Value;
        
        // Slow queries
        $slowQueries = DB::select("SHOW STATUS LIKE 'Slow_queries'")[0];
        $stats['slow_queries'] = $slowQueries->Value;
        
        // Query cache hit rate
        $qcHits = DB::select("SHOW STATUS LIKE 'Qcache_hits'")[0];
        $qcInserts = DB::select("SHOW STATUS LIKE 'Qcache_inserts'")[0];
        $hitRate = ($qcHits->Value > 0) ? 
            round(($qcHits->Value / ($qcHits->Value + $qcInserts->Value)) * 100, 2) : 0;
        $stats['query_cache_hit_rate'] = $hitRate;
        
        $this->record('db_connections', $stats['active_connections']);
        $this->record('db_slow_queries', $stats['slow_queries']);
        $this->record('db_cache_hit_rate', $hitRate);
        
        return $stats;
    }
    
    /**
     * Monitor Redis performance
     */
    public function monitorRedis(): array
    {
        try {
            $info = Redis::info();
            
            $stats = [
                'memory_used_mb' => round($info['used_memory'] / 1048576, 2),
                'connected_clients' => $info['connected_clients'],
                'ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? 0,
                'hit_rate' => $this->calculateRedisHitRate($info),
                'evicted_keys' => $info['evicted_keys'] ?? 0,
            ];
            
            foreach ($stats as $metric => $value) {
                $this->record('redis_' . $metric, $value);
            }
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Redis monitoring failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Monitor application performance
     */
    public function monitorApplication(): array
    {
        $stats = [
            'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
            'cpu_usage' => $this->getCpuUsage(),
            'request_duration' => (microtime(true) - $this->startTime) * 1000,
            'loaded_files' => count(get_included_files()),
        ];
        
        foreach ($stats as $metric => $value) {
            $this->record('app_' . $metric, $value);
        }
        
        return $stats;
    }
    
    /**
     * Get performance insights
     */
    public function getInsights(): array
    {
        $insights = [];
        
        // Memory usage insights
        $memoryUsage = memory_get_usage(true) / 1048576;
        if ($memoryUsage > 128) {
            $insights[] = [
                'type' => 'warning',
                'metric' => 'memory',
                'message' => 'High memory usage detected: ' . round($memoryUsage, 2) . 'MB',
                'recommendation' => 'Consider optimizing large data operations or increasing memory limit'
            ];
        }
        
        // Database insights
        $dbStats = $this->monitorDatabase();
        if ($dbStats['slow_queries'] > 10) {
            $insights[] = [
                'type' => 'critical',
                'metric' => 'database',
                'message' => 'High number of slow queries: ' . $dbStats['slow_queries'],
                'recommendation' => 'Review and optimize slow queries, add indexes where needed'
            ];
        }
        
        // Redis insights
        $redisStats = $this->monitorRedis();
        if (!empty($redisStats) && $redisStats['hit_rate'] < 80) {
            $insights[] = [
                'type' => 'warning',
                'metric' => 'cache',
                'message' => 'Low cache hit rate: ' . $redisStats['hit_rate'] . '%',
                'recommendation' => 'Review cache strategy and TTL values'
            ];
        }
        
        return $insights;
    }
    
    /**
     * Generate performance report
     */
    public function generateReport(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'duration_ms' => (microtime(true) - $this->startTime) * 1000,
            'memory_used_mb' => round((memory_get_usage(true) - $this->memoryStart) / 1048576, 2),
            'database' => $this->monitorDatabase(),
            'redis' => $this->monitorRedis(),
            'application' => $this->monitorApplication(),
            'insights' => $this->getInsights(),
            'metrics_summary' => $this->getMetricsSummary()
        ];
    }
    
    protected function calculateRedisHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
    
    protected function getCpuUsage(): float
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();
            return round($load[0] * 100, 2); // 1 minute average
        }
        return 0;
    }
    
    protected function getMetricsSummary(): array
    {
        $summary = [];
        
        foreach ($this->metrics as $metric => $values) {
            if (empty($values)) continue;
            
            $summary[$metric] = [
                'count' => count($values),
                'min' => min($values),
                'max' => max($values),
                'avg' => round(array_sum($values) / count($values), 2),
                'sum' => array_sum($values)
            ];
        }
        
        return $summary;
    }
}