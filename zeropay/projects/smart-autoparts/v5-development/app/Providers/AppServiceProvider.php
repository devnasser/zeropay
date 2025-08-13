<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\QueryOptimizerService;
use App\Services\RedisCacheService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Redis Cache Service
        $this->app->singleton(RedisCacheService::class, function ($app) {
            return new RedisCacheService();
        });
        
        // Register Query Optimizer Service
        $this->app->singleton(QueryOptimizerService::class, function ($app) {
            return new QueryOptimizerService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Start Query Monitoring
        if (config('app.debug')) {
            $this->app->make(QueryOptimizerService::class)->monitor();
        }
        
        // Warm up critical caches
        if (!app()->runningInConsole()) {
            $this->app->make(RedisCacheService::class)->warmup();
        }
    }
}
