<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueryOptimizerService;
use App\Services\RedisCacheService;
use App\Services\PerformanceMonitorService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize-performance {--full : Run full optimization}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application performance';

    /**
     * Execute the console command.
     */
    public function handle(
        QueryOptimizerService $queryOptimizer,
        RedisCacheService $cache,
        PerformanceMonitorService $monitor,
        InventoryService $inventory
    ) {
        $this->info('🚀 Starting performance optimization...');
        
        $startTime = microtime(true);
        
        // 1. Clear and warm cache
        $this->task('Optimizing cache', function() use ($cache) {
            Redis::flushdb();
            $cache->warmup();
            return true;
        });
        
        // 2. Optimize database
        $this->task('Optimizing database', function() use ($queryOptimizer) {
            // Analyze tables
            DB::statement('ANALYZE TABLE products, orders, users, shops, categories');
            
            // Get optimization recommendations
            $report = $queryOptimizer->analyzeIndexUsage();
            
            foreach ($report as $table => $data) {
                if (!empty($data['recommendations'])) {
                    foreach ($data['recommendations'] as $sql) {
                        try {
                            DB::statement($sql);
                            $this->line("  ✓ {$sql}");
                        } catch (\Exception $e) {
                            $this->error("  ✗ Failed: {$sql}");
                        }
                    }
                }
            }
            
            return true;
        });
        
        // 3. Clean expired data
        $this->task('Cleaning expired data', function() use ($inventory) {
            // Clean expired reservations
            $cleaned = $inventory->cleanExpiredReservations();
            $this->line("  ✓ Cleaned {$cleaned} expired reservations");
            
            // Clean old notifications
            $deleted = DB::table('notifications')
                ->where('created_at', '<', now()->subDays(30))
                ->whereNotNull('read_at')
                ->delete();
            $this->line("  ✓ Deleted {$deleted} old notifications");
            
            // Clean old logs
            $deleted = DB::table('activity_log')
                ->where('created_at', '<', now()->subDays(90))
                ->delete();
            $this->line("  ✓ Deleted {$deleted} old activity logs");
            
            return true;
        });
        
        // 4. Optimize routes and config
        if ($this->option('full')) {
            $this->task('Optimizing Laravel', function() {
                Artisan::call('route:cache');
                Artisan::call('config:cache');
                Artisan::call('view:cache');
                return true;
            });
        }
        
        // 5. Generate performance report
        $this->task('Generating performance report', function() use ($monitor) {
            $report = $monitor->generateReport();
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Database Connections', $report['database']['active_connections'] ?? 'N/A'],
                    ['Slow Queries', $report['database']['slow_queries'] ?? 'N/A'],
                    ['Cache Hit Rate', ($report['redis']['hit_rate'] ?? 0) . '%'],
                    ['Memory Usage', $report['application']['memory_usage_mb'] . ' MB'],
                ]
            );
            
            return true;
        });
        
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->newLine();
        $this->info("✨ Optimization completed in {$duration} seconds!");
        
        // Show insights
        $insights = $monitor->getInsights();
        if (!empty($insights)) {
            $this->newLine();
            $this->warn('⚠️  Performance Insights:');
            foreach ($insights as $insight) {
                $this->line("  • {$insight['message']}");
                $this->line("    → {$insight['recommendation']}");
            }
        }
        
        return 0;
    }
}