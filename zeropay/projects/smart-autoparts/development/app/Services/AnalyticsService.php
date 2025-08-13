<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats($period = 'month')
    {
        return Cache::remember("dashboard_stats_{$period}", 300, function () use ($period) {
            $startDate = $this->getStartDate($period);
            
            return [
                'revenue' => $this->getRevenue($startDate),
                'orders' => $this->getOrderStats($startDate),
                'customers' => $this->getCustomerStats($startDate),
                'products' => $this->getProductStats($startDate),
                'conversion_rate' => $this->getConversionRate($startDate),
                'average_order_value' => $this->getAverageOrderValue($startDate),
                'top_products' => $this->getTopProducts($startDate, 5),
                'top_categories' => $this->getTopCategories($startDate, 5),
                'sales_by_hour' => $this->getSalesByHour($startDate),
                'customer_retention' => $this->getCustomerRetention($startDate),
            ];
        });
    }

    /**
     * Get revenue statistics
     */
    protected function getRevenue($startDate)
    {
        $current = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->sum('total');

        $previous = Order::whereBetween('created_at', [
                $startDate->copy()->subMonth(),
                $startDate
            ])
            ->where('payment_status', 'paid')
            ->sum('total');

        $growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;

        return [
            'current' => $current,
            'previous' => $previous,
            'growth' => round($growth, 2),
            'chart_data' => $this->getRevenueChartData($startDate)
        ];
    }

    /**
     * Get order statistics
     */
    protected function getOrderStats($startDate)
    {
        $stats = Order::where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = "shipped" THEN 1 ELSE 0 END) as shipped,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled
            ')
            ->first();

        return [
            'total' => $stats->total,
            'by_status' => [
                'pending' => $stats->pending,
                'processing' => $stats->processing,
                'shipped' => $stats->shipped,
                'delivered' => $stats->delivered,
                'cancelled' => $stats->cancelled,
            ],
            'fulfillment_rate' => $stats->total > 0 
                ? round(($stats->delivered / $stats->total) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get customer statistics
     */
    protected function getCustomerStats($startDate)
    {
        $newCustomers = User::where('created_at', '>=', $startDate)
            ->where('type', 'customer')
            ->count();

        $returningCustomers = Order::where('created_at', '>=', $startDate)
            ->whereHas('user', function ($query) use ($startDate) {
                $query->where('created_at', '<', $startDate);
            })
            ->distinct('user_id')
            ->count('user_id');

        $activeCustomers = Order::where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'new' => $newCustomers,
            'returning' => $returningCustomers,
            'active' => $activeCustomers,
            'retention_rate' => $activeCustomers > 0 
                ? round(($returningCustomers / $activeCustomers) * 100, 2) 
                : 0
        ];
    }

    /**
     * Get product statistics
     */
    protected function getProductStats($startDate)
    {
        $totalProducts = Product::active()->count();
        
        $soldProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.created_at', '>=', $startDate)
            ->sum('order_items.quantity');

        $outOfStock = Product::where('quantity', 0)->count();
        
        $lowStock = Product::where('quantity', '>', 0)
            ->where('quantity', '<=', 10)
            ->count();

        return [
            'total' => $totalProducts,
            'sold' => $soldProducts,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
        ];
    }

    /**
     * Get conversion rate
     */
    protected function getConversionRate($startDate)
    {
        // Get unique visitors (simplified - in real app, use analytics service)
        $visitors = DB::table('sessions')
            ->where('last_activity', '>=', $startDate->timestamp)
            ->count();

        $orders = Order::where('created_at', '>=', $startDate)->count();

        $rate = $visitors > 0 ? ($orders / $visitors) * 100 : 0;

        return round($rate, 2);
    }

    /**
     * Get average order value
     */
    protected function getAverageOrderValue($startDate)
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->avg('total') ?? 0;
    }

    /**
     * Get top selling products
     */
    protected function getTopProducts($startDate, $limit = 5)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', $startDate)
            ->groupBy('order_items.product_id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->selectRaw('
                order_items.product_id,
                JSON_EXTRACT(products.name, "$.ar") as name,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.quantity * order_items.price) as total_revenue
            ')
            ->get();
    }

    /**
     * Get top categories
     */
    protected function getTopCategories($startDate, $limit = 5)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.created_at', '>=', $startDate)
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->selectRaw('
                categories.id,
                JSON_EXTRACT(categories.name, "$.ar") as name,
                COUNT(DISTINCT orders.id) as order_count,
                SUM(order_items.quantity * order_items.price) as total_revenue
            ')
            ->get();
    }

    /**
     * Get sales by hour
     */
    protected function getSalesByHour($startDate)
    {
        return Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as order_count,
                SUM(total) as revenue
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->hour => [
                    'orders' => $item->order_count,
                    'revenue' => $item->revenue
                ]];
            });
    }

    /**
     * Get customer retention data
     */
    protected function getCustomerRetention($startDate)
    {
        $cohorts = [];
        
        for ($i = 0; $i < 6; $i++) {
            $cohortStart = $startDate->copy()->subMonths($i)->startOfMonth();
            $cohortEnd = $cohortStart->copy()->endOfMonth();
            
            $newCustomers = User::whereBetween('created_at', [$cohortStart, $cohortEnd])
                ->where('type', 'customer')
                ->pluck('id');
            
            if ($newCustomers->isEmpty()) {
                continue;
            }
            
            $retention = [];
            
            for ($j = 0; $j <= $i; $j++) {
                $checkStart = $cohortStart->copy()->addMonths($j)->startOfMonth();
                $checkEnd = $checkStart->copy()->endOfMonth();
                
                $retained = Order::whereIn('user_id', $newCustomers)
                    ->whereBetween('created_at', [$checkStart, $checkEnd])
                    ->distinct('user_id')
                    ->count('user_id');
                
                $retention[$j] = round(($retained / $newCustomers->count()) * 100, 2);
            }
            
            $cohorts[$cohortStart->format('Y-m')] = $retention;
        }
        
        return $cohorts;
    }

    /**
     * Get revenue chart data
     */
    protected function getRevenueChartData($startDate)
    {
        $days = $startDate->diffInDays(now());
        
        if ($days <= 30) {
            // Daily data
            return Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($days <= 365) {
            // Weekly data
            return Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->selectRaw('YEARWEEK(created_at) as week, SUM(total) as revenue')
                ->groupBy('week')
                ->orderBy('week')
                ->get();
        } else {
            // Monthly data
            return Order::where('created_at', '>=', $startDate)
                ->where('payment_status', 'paid')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as revenue')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        }
    }

    /**
     * Get start date based on period
     */
    protected function getStartDate($period)
    {
        switch ($period) {
            case 'today':
                return Carbon::today();
            case 'week':
                return Carbon::now()->startOfWeek();
            case 'month':
                return Carbon::now()->startOfMonth();
            case 'quarter':
                return Carbon::now()->startOfQuarter();
            case 'year':
                return Carbon::now()->startOfYear();
            default:
                return Carbon::now()->startOfMonth();
        }
    }

    /**
     * Generate predictive analytics
     */
    public function getPredictiveAnalytics()
    {
        return Cache::remember('predictive_analytics', 3600, function () {
            return [
                'sales_forecast' => $this->generateSalesForecast(),
                'inventory_predictions' => $this->predictInventoryNeeds(),
                'customer_churn_risk' => $this->calculateChurnRisk(),
                'seasonal_trends' => $this->analyzeSeasonalTrends(),
                'price_optimization' => $this->suggestPriceOptimization(),
            ];
        });
    }

    /**
     * Generate sales forecast
     */
    protected function generateSalesForecast()
    {
        // Simplified linear regression forecast
        $historicalData = Order::where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        if ($historicalData->count() < 3) {
            return null;
        }

        // Calculate trend
        $revenues = $historicalData->pluck('revenue')->toArray();
        $avgGrowth = 0;
        
        for ($i = 1; $i < count($revenues); $i++) {
            $growth = ($revenues[$i] - $revenues[$i-1]) / $revenues[$i-1];
            $avgGrowth += $growth;
        }
        
        $avgGrowth = $avgGrowth / (count($revenues) - 1);
        
        // Forecast next 3 months
        $lastRevenue = end($revenues);
        $forecast = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $forecastRevenue = $lastRevenue * (1 + $avgGrowth);
            $forecast[] = [
                'month' => now()->addMonths($i)->format('Y-m'),
                'revenue' => round($forecastRevenue, 2),
                'confidence' => max(0.7 - ($i * 0.1), 0.4) // Confidence decreases with time
            ];
            $lastRevenue = $forecastRevenue;
        }
        
        return $forecast;
    }

    /**
     * Predict inventory needs
     */
    protected function predictInventoryNeeds()
    {
        return Product::active()
            ->whereRaw('quantity <= 20')
            ->get()
            ->map(function ($product) {
                // Calculate average daily sales
                $avgDailySales = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('order_items.product_id', $product->id)
                    ->where('orders.created_at', '>=', now()->subDays(30))
                    ->avg('order_items.quantity') ?? 0;
                
                $daysUntilStockout = $avgDailySales > 0 
                    ? round($product->quantity / $avgDailySales) 
                    : 999;
                
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_stock' => $product->quantity,
                    'avg_daily_sales' => round($avgDailySales, 2),
                    'days_until_stockout' => $daysUntilStockout,
                    'recommended_reorder' => $daysUntilStockout <= 7,
                    'recommended_quantity' => ceil($avgDailySales * 30) // 30-day supply
                ];
            })
            ->filter(function ($item) {
                return $item['days_until_stockout'] <= 14;
            })
            ->sortBy('days_until_stockout')
            ->values();
    }

    /**
     * Calculate customer churn risk
     */
    protected function calculateChurnRisk()
    {
        $riskCustomers = User::where('type', 'customer')
            ->whereHas('orders', function ($query) {
                $query->where('created_at', '>=', now()->subMonths(6));
            })
            ->with(['orders' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->get()
            ->map(function ($customer) {
                $lastOrder = $customer->orders->first();
                $daysSinceLastOrder = $lastOrder 
                    ? now()->diffInDays($lastOrder->created_at) 
                    : 999;
                
                $avgDaysBetweenOrders = $this->calculateAvgDaysBetweenOrders($customer);
                
                $churnRisk = 0;
                
                // Risk factors
                if ($daysSinceLastOrder > $avgDaysBetweenOrders * 2) {
                    $churnRisk += 40;
                }
                
                if ($customer->orders->count() == 1) {
                    $churnRisk += 30;
                }
                
                if ($daysSinceLastOrder > 90) {
                    $churnRisk += 30;
                }
                
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'last_order_days_ago' => $daysSinceLastOrder,
                    'total_orders' => $customer->orders->count(),
                    'total_spent' => $customer->orders->sum('total'),
                    'churn_risk_score' => min($churnRisk, 100),
                    'risk_level' => $churnRisk >= 70 ? 'high' : ($churnRisk >= 40 ? 'medium' : 'low')
                ];
            })
            ->filter(function ($item) {
                return $item['churn_risk_score'] >= 40;
            })
            ->sortByDesc('churn_risk_score')
            ->values();

        return [
            'high_risk_count' => $riskCustomers->where('risk_level', 'high')->count(),
            'medium_risk_count' => $riskCustomers->where('risk_level', 'medium')->count(),
            'customers' => $riskCustomers->take(10)
        ];
    }

    /**
     * Calculate average days between orders
     */
    protected function calculateAvgDaysBetweenOrders($customer)
    {
        $orders = $customer->orders->sortBy('created_at');
        
        if ($orders->count() < 2) {
            return 30; // Default to 30 days
        }
        
        $totalDays = 0;
        $intervals = 0;
        
        for ($i = 1; $i < $orders->count(); $i++) {
            $days = $orders[$i]->created_at->diffInDays($orders[$i-1]->created_at);
            $totalDays += $days;
            $intervals++;
        }
        
        return $intervals > 0 ? round($totalDays / $intervals) : 30;
    }

    /**
     * Analyze seasonal trends
     */
    protected function analyzeSeasonalTrends()
    {
        // Analyze sales by month for the past 2 years
        $monthlyData = Order::where('created_at', '>=', now()->subYears(2))
            ->selectRaw('
                MONTH(created_at) as month,
                YEAR(created_at) as year,
                COUNT(*) as order_count,
                SUM(total) as revenue
            ')
            ->groupBy('year', 'month')
            ->get()
            ->groupBy('month');

        $trends = [];
        
        foreach ($monthlyData as $month => $data) {
            $avgRevenue = $data->avg('revenue');
            $avgOrders = $data->avg('order_count');
            
            $trends[$month] = [
                'month_name' => Carbon::create()->month($month)->format('F'),
                'avg_revenue' => round($avgRevenue, 2),
                'avg_orders' => round($avgOrders),
                'trend' => $this->calculateTrend($avgRevenue, $monthlyData->avg(fn($d) => $d->avg('revenue')))
            ];
        }
        
        return collect($trends)->sortByDesc('avg_revenue')->values();
    }

    /**
     * Suggest price optimization
     */
    protected function suggestPriceOptimization()
    {
        return Product::active()
            ->with(['orderItems' => function ($query) {
                $query->whereHas('order', function ($q) {
                    $q->where('created_at', '>=', now()->subMonths(3));
                });
            }])
            ->get()
            ->map(function ($product) {
                $salesVelocity = $product->orderItems->sum('quantity');
                $competitorPrice = $this->getCompetitorPrice($product);
                $elasticity = $this->calculatePriceElasticity($product);
                
                $suggestion = null;
                $suggestedPrice = $product->price;
                
                if ($salesVelocity < 10 && $product->price > $competitorPrice) {
                    $suggestion = 'decrease';
                    $suggestedPrice = max($product->price * 0.95, $competitorPrice * 0.98);
                } elseif ($salesVelocity > 50 && $elasticity < 0.5) {
                    $suggestion = 'increase';
                    $suggestedPrice = min($product->price * 1.05, $competitorPrice * 1.1);
                }
                
                if (!$suggestion) {
                    return null;
                }
                
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_price' => $product->price,
                    'suggested_price' => round($suggestedPrice, 2),
                    'price_change' => round((($suggestedPrice - $product->price) / $product->price) * 100, 2),
                    'reason' => $suggestion === 'decrease' ? 'Low sales velocity' : 'High demand with low elasticity',
                    'expected_impact' => $this->calculateExpectedImpact($product, $suggestedPrice)
                ];
            })
            ->filter()
            ->sortByDesc(fn($item) => abs($item['expected_impact']))
            ->take(10)
            ->values();
    }

    protected function calculateTrend($value, $average)
    {
        $percentage = (($value - $average) / $average) * 100;
        
        if ($percentage > 10) return 'high';
        if ($percentage < -10) return 'low';
        return 'normal';
    }

    protected function getCompetitorPrice($product)
    {
        // In real implementation, this would fetch from competitor APIs or scraped data
        return $product->price * rand(90, 110) / 100;
    }

    protected function calculatePriceElasticity($product)
    {
        // Simplified elasticity calculation
        return rand(3, 15) / 10;
    }

    protected function calculateExpectedImpact($product, $newPrice)
    {
        $priceChange = ($newPrice - $product->price) / $product->price;
        $elasticity = $this->calculatePriceElasticity($product);
        $quantityChange = -$priceChange * $elasticity;
        
        $currentRevenue = $product->orderItems->sum(fn($item) => $item->quantity * $item->price);
        $expectedRevenue = $currentRevenue * (1 + $priceChange) * (1 + $quantityChange);
        
        return $expectedRevenue - $currentRevenue;
    }
}