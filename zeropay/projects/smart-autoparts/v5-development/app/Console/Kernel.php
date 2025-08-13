<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Clean expired inventory reservations every 15 minutes
        $schedule->call(function() {
            app(\App\Services\InventoryService::class)->cleanExpiredReservations();
        })->everyFifteenMinutes();
        
        // Run performance optimization daily at 3 AM
        $schedule->command('app:optimize-performance')->dailyAt('03:00');
        
        // Generate performance reports every hour
        $schedule->call(function() {
            $monitor = app(\App\Services\PerformanceMonitorService::class);
            $report = $monitor->generateReport();
            
            // Save to database for historical tracking
            \DB::table('performance_metrics')->insert([
                'metric' => 'hourly_report',
                'value' => 1,
                'tags' => json_encode($report),
                'recorded_at' => now()
            ]);
        })->hourly();
        
        // Clean old data weekly
        $schedule->call(function() {
            // Clean old performance metrics
            \DB::table('performance_metrics')
                ->where('recorded_at', '<', now()->subDays(30))
                ->delete();
            
            // Clean old inventory logs
            \DB::table('inventory_logs')
                ->where('created_at', '<', now()->subDays(90))
                ->delete();
        })->weekly();
        
        // Check for low stock products every 6 hours
        $schedule->call(function() {
            $products = \App\Models\Product::where('is_active', true)
                ->where('quantity', '<=', 10)
                ->with('shop.owner')
                ->get();
            
            foreach ($products as $product) {
                app(\App\Services\NotificationService::class)->send(
                    $product->shop->owner,
                    'low_stock_reminder',
                    [
                        'product_name' => $product->name['ar'] ?? $product->name['en'],
                        'quantity' => $product->quantity,
                        'sku' => $product->sku
                    ]
                );
            }
        })->everySixHours();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}