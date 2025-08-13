<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;

class QueryOptimizerService
{
    protected $slowQueries = [];
    protected $threshold = 100; // milliseconds
    
    public function monitor()
    {
        DB::listen(function (QueryExecuted $query) {
            if ($query->time > $this->threshold) {
                $this->logSlowQuery($query);
            }
        });
    }
    
    public function optimizeProductQueries()
    {
        // Before: N+1 problem
        // $products = Product::all();
        // foreach ($products as $product) {
        //     echo $product->shop->name;
        // }
        
        // After: Eager loading
        return [
            'products_with_relations' => function($query) {
                return $query->with(['shop:id,name', 'category:id,name', 'reviews' => function($q) {
                    $q->latest()->limit(5);
                }]);
            },
            
            'featured_products' => function($query) {
                return $query->where('is_featured', true)
                    ->where('is_active', true)
                    ->where('quantity', '>', 0)
                    ->select(['id', 'name', 'slug', 'price', 'sale_price', 'images', 'rating'])
                    ->with('shop:id,name,slug')
                    ->limit(12);
            },
            
            'search_products' => function($query, $term) {
                return $query->where('is_active', true)
                    ->where(function($q) use ($term) {
                        $q->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$term}%"])
                          ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$term}%"])
                          ->orWhere('sku', 'LIKE', "%{$term}%")
                          ->orWhere('brand', 'LIKE', "%{$term}%");
                    })
                    ->select(['id', 'name', 'slug', 'price', 'images', 'brand', 'model'])
                    ->with('category:id,name');
            }
        ];
    }
    
    public function optimizeOrderQueries()
    {
        return [
            'user_orders' => function($userId) {
                return DB::table('orders')
                    ->where('user_id', $userId)
                    ->select([
                        'id', 'order_number', 'status', 'total', 'created_at',
                        DB::raw('(SELECT COUNT(*) FROM order_items WHERE order_id = orders.id) as items_count')
                    ])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
            },
            
            'shop_revenue' => function($shopId, $startDate) {
                return DB::table('orders')
                    ->where('shop_id', $shopId)
                    ->where('created_at', '>=', $startDate)
                    ->where('payment_status', 'paid')
                    ->select([
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('COUNT(*) as orders_count'),
                        DB::raw('SUM(total) as revenue'),
                        DB::raw('AVG(total) as avg_order_value')
                    ])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            }
        ];
    }
    
    public function analyzeIndexUsage()
    {
        $tables = ['products', 'orders', 'users', 'shops', 'categories'];
        $analysis = [];
        
        foreach ($tables as $table) {
            $indexes = DB::select("SHOW INDEXES FROM {$table}");
            $tableStats = DB::select("SHOW TABLE STATUS LIKE '{$table}'")[0];
            
            $analysis[$table] = [
                'indexes' => count($indexes),
                'rows' => $tableStats->Rows,
                'data_length' => $this->formatBytes($tableStats->Data_length),
                'index_length' => $this->formatBytes($tableStats->Index_length),
                'recommendations' => $this->getIndexRecommendations($table, $indexes)
            ];
        }
        
        return $analysis;
    }
    
    protected function getIndexRecommendations($table, $indexes)
    {
        $recommendations = [];
        $indexedColumns = array_column($indexes, 'Column_name');
        
        // Common queries that need indexes
        $neededIndexes = [
            'products' => ['shop_id,is_active', 'category_id,is_active', 'brand,model'],
            'orders' => ['user_id,status', 'shop_id,created_at', 'payment_status'],
            'users' => ['email', 'phone', 'type'],
        ];
        
        if (isset($neededIndexes[$table])) {
            foreach ($neededIndexes[$table] as $needed) {
                if (!in_array($needed, $indexedColumns)) {
                    $recommendations[] = "CREATE INDEX idx_{$table}_" . str_replace(',', '_', $needed) . " ON {$table}({$needed})";
                }
            }
        }
        
        return $recommendations;
    }
    
    protected function logSlowQuery(QueryExecuted $query)
    {
        $this->slowQueries[] = [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
            'timestamp' => now()
        ];
        
        Log::warning('Slow Query Detected', [
            'sql' => $query->sql,
            'time' => $query->time . 'ms',
        ]);
    }
    
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function getOptimizationReport()
    {
        return [
            'slow_queries' => $this->slowQueries,
            'total_slow_queries' => count($this->slowQueries),
            'index_analysis' => $this->analyzeIndexUsage(),
            'optimization_tips' => [
                'Use eager loading to prevent N+1 queries',
                'Add composite indexes for frequently joined columns',
                'Use select() to limit columns retrieved',
                'Cache frequently accessed data',
                'Use database views for complex queries'
            ]
        ];
    }
}