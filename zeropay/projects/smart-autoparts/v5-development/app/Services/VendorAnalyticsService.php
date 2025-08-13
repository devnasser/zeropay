<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VendorAnalyticsService
{
    protected $analyticsService;
    protected $aiService;
    
    public function __construct(AnalyticsService $analyticsService, AIService $aiService = null)
    {
        $this->analyticsService = $analyticsService;
        $this->aiService = $aiService;
    }
    
    /**
     * Get comprehensive vendor dashboard analytics
     */
    public function getVendorDashboard(Vendor $vendor, $period = 'month')
    {
        $cacheKey = "vendor_dashboard_{$vendor->id}_{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($vendor, $period) {
            $startDate = $this->getStartDate($period);
            
            return [
                'overview' => $this->getOverviewStats($vendor, $startDate),
                'sales' => $this->getSalesAnalytics($vendor, $startDate),
                'products' => $this->getProductAnalytics($vendor, $startDate),
                'customers' => $this->getCustomerAnalytics($vendor, $startDate),
                'performance' => $this->getPerformanceMetrics($vendor, $startDate),
                'predictions' => $this->getPredictions($vendor),
                'recommendations' => $this->getActionableInsights($vendor),
                'comparison' => $this->getMarketComparison($vendor),
                'trends' => $this->getTrendAnalysis($vendor, $startDate)
            ];
        });
    }
    
    /**
     * Get overview statistics
     */
    protected function getOverviewStats(Vendor $vendor, $startDate)
    {
        $currentPeriod = Order::where('shop_id', $vendor->shop_id)
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled');
            
        $previousPeriod = Order::where('shop_id', $vendor->shop_id)
            ->whereBetween('created_at', [
                $startDate->copy()->subMonth(),
                $startDate
            ])
            ->where('status', '!=', 'cancelled');
        
        $currentRevenue = $currentPeriod->sum('total');
        $previousRevenue = $previousPeriod->sum('total');
        $revenueGrowth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;
        
        return [
            'total_revenue' => $currentRevenue,
            'revenue_growth' => round($revenueGrowth, 2),
            'total_orders' => $currentPeriod->count(),
            'average_order_value' => $currentPeriod->avg('total') ?? 0,
            'total_customers' => $currentPeriod->distinct('user_id')->count('user_id'),
            'conversion_rate' => $this->calculateConversionRate($vendor, $startDate),
            'commission_earned' => $vendor->total_commission,
            'pending_payout' => $vendor->balance,
            'subscription_status' => [
                'plan' => $vendor->subscription_plan,
                'expires_at' => $vendor->subscription_expires_at,
                'days_remaining' => $vendor->subscription_expires_at 
                    ? now()->diffInDays($vendor->subscription_expires_at) 
                    : null
            ]
        ];
    }
    
    /**
     * Get detailed sales analytics
     */
    protected function getSalesAnalytics(Vendor $vendor, $startDate)
    {
        $orders = Order::where('shop_id', $vendor->shop_id)
            ->where('created_at', '>=', $startDate)
            ->with(['items.product', 'user']);
        
        return [
            'by_status' => $this->getSalesByStatus($orders->clone()),
            'by_payment_method' => $this->getSalesByPaymentMethod($orders->clone()),
            'by_day' => $this->getSalesByDay($orders->clone()),
            'by_hour' => $this->getSalesByHour($orders->clone()),
            'by_category' => $this->getSalesByCategory($vendor, $startDate),
            'top_products' => $this->getTopSellingProducts($vendor, $startDate, 10),
            'revenue_breakdown' => [
                'product_sales' => $orders->clone()->sum('subtotal'),
                'shipping_collected' => $orders->clone()->sum('shipping'),
                'tax_collected' => $orders->clone()->sum('tax'),
                'discounts_given' => $orders->clone()->sum('discount'),
                'net_revenue' => $orders->clone()->sum('total')
            ]
        ];
    }
    
    /**
     * Get product performance analytics
     */
    protected function getProductAnalytics(Vendor $vendor, $startDate)
    {
        $products = Product::where('shop_id', $vendor->shop_id);
        
        return [
            'total_products' => $products->count(),
            'active_products' => $products->clone()->where('is_active', true)->count(),
            'out_of_stock' => $products->clone()->where('quantity', 0)->count(),
            'low_stock' => $products->clone()->whereBetween('quantity', [1, 10])->count(),
            'performance' => $this->getProductPerformance($vendor, $startDate),
            'categories' => $this->getProductsByCategory($vendor),
            'price_distribution' => $this->getPriceDistribution($vendor),
            'inventory_value' => $this->calculateInventoryValue($vendor),
            'turnover_rate' => $this->calculateInventoryTurnover($vendor, $startDate)
        ];
    }
    
    /**
     * Get customer analytics
     */
    protected function getCustomerAnalytics(Vendor $vendor, $startDate)
    {
        $customers = Order::where('shop_id', $vendor->shop_id)
            ->where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->with('user');
        
        return [
            'total_customers' => $customers->count('user_id'),
            'new_customers' => $this->getNewCustomers($vendor, $startDate),
            'returning_customers' => $this->getReturningCustomers($vendor, $startDate),
            'customer_lifetime_value' => $this->calculateCLV($vendor),
            'top_customers' => $this->getTopCustomers($vendor, $startDate, 10),
            'customer_segments' => $this->getCustomerSegments($vendor),
            'satisfaction_score' => $this->getCustomerSatisfaction($vendor),
            'retention_rate' => $this->calculateRetentionRate($vendor, $startDate)
        ];
    }
    
    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics(Vendor $vendor, $startDate)
    {
        return [
            'order_fulfillment' => [
                'average_processing_time' => $this->getAverageProcessingTime($vendor, $startDate),
                'on_time_delivery_rate' => $this->getOnTimeDeliveryRate($vendor, $startDate),
                'cancellation_rate' => $this->getCancellationRate($vendor, $startDate),
                'return_rate' => $this->getReturnRate($vendor, $startDate)
            ],
            'customer_service' => [
                'response_time' => $this->getAverageResponseTime($vendor),
                'resolution_rate' => $this->getResolutionRate($vendor),
                'customer_complaints' => $this->getComplaintCount($vendor, $startDate),
                'review_response_rate' => $this->getReviewResponseRate($vendor)
            ],
            'quality_metrics' => [
                'average_rating' => $vendor->shop->rating,
                'total_reviews' => $vendor->shop->reviews_count,
                'positive_feedback_rate' => $this->getPositiveFeedbackRate($vendor),
                'product_quality_score' => $this->getProductQualityScore($vendor)
            ],
            'operational_efficiency' => [
                'inventory_accuracy' => $this->getInventoryAccuracy($vendor),
                'stock_availability' => $this->getStockAvailability($vendor),
                'order_accuracy' => $this->getOrderAccuracy($vendor),
                'cost_per_order' => $this->calculateCostPerOrder($vendor)
            ]
        ];
    }
    
    /**
     * Get AI-powered predictions
     */
    protected function getPredictions(Vendor $vendor)
    {
        if (!$this->aiService) {
            return $this->getBasicPredictions($vendor);
        }
        
        return [
            'sales_forecast' => $this->aiService->predictSales($vendor, 30),
            'demand_forecast' => $this->aiService->predictDemand($vendor),
            'inventory_recommendations' => $this->aiService->getInventoryRecommendations($vendor),
            'pricing_suggestions' => $this->aiService->getPricingStrategy($vendor),
            'growth_opportunities' => $this->aiService->identifyGrowthOpportunities($vendor),
            'risk_assessment' => $this->aiService->assessBusinessRisks($vendor)
        ];
    }
    
    /**
     * Get actionable insights and recommendations
     */
    protected function getActionableInsights(Vendor $vendor)
    {
        $insights = [];
        
        // Low performing products
        $lowPerformers = $this->identifyLowPerformingProducts($vendor);
        if ($lowPerformers->count() > 0) {
            $insights[] = [
                'type' => 'warning',
                'category' => 'products',
                'message' => "You have {$lowPerformers->count()} low-performing products",
                'action' => 'Consider running promotions or updating product listings',
                'priority' => 'high',
                'potential_impact' => 'Increase sales by up to 15%'
            ];
        }
        
        // Stock recommendations
        $stockAlerts = $this->getStockAlerts($vendor);
        foreach ($stockAlerts as $alert) {
            $insights[] = [
                'type' => $alert['type'],
                'category' => 'inventory',
                'message' => $alert['message'],
                'action' => $alert['action'],
                'priority' => $alert['priority'],
                'potential_impact' => $alert['impact']
            ];
        }
        
        // Pricing opportunities
        $pricingOpportunities = $this->identifyPricingOpportunities($vendor);
        foreach ($pricingOpportunities as $opportunity) {
            $insights[] = [
                'type' => 'opportunity',
                'category' => 'pricing',
                'message' => $opportunity['message'],
                'action' => $opportunity['action'],
                'priority' => 'medium',
                'potential_impact' => $opportunity['impact']
            ];
        }
        
        // Customer engagement
        if ($this->getReviewResponseRate($vendor) < 80) {
            $insights[] = [
                'type' => 'improvement',
                'category' => 'engagement',
                'message' => 'Your review response rate is below optimal',
                'action' => 'Respond to customer reviews to improve trust',
                'priority' => 'medium',
                'potential_impact' => 'Improve conversion rate by 10%'
            ];
        }
        
        // Subscription upgrade
        if ($this->shouldRecommendUpgrade($vendor)) {
            $insights[] = [
                'type' => 'growth',
                'category' => 'subscription',
                'message' => 'You\'re reaching your plan limits',
                'action' => 'Upgrade to Professional plan for better features',
                'priority' => 'low',
                'potential_impact' => 'Save 3% on commission fees'
            ];
        }
        
        return $insights;
    }
    
    /**
     * Get market comparison
     */
    protected function getMarketComparison(Vendor $vendor)
    {
        $category = $vendor->shop->products()->first()->category_id ?? null;
        
        if (!$category) {
            return null;
        }
        
        $competitors = Vendor::whereHas('shop.products', function ($query) use ($category) {
            $query->where('category_id', $category);
        })
        ->where('id', '!=', $vendor->id)
        ->limit(10)
        ->get();
        
        $vendorMetrics = $this->getVendorMetrics($vendor);
        $marketAverage = $this->getMarketAverageMetrics($competitors);
        
        return [
            'position' => $this->calculateMarketPosition($vendor, $competitors),
            'metrics_comparison' => [
                'revenue' => [
                    'vendor' => $vendorMetrics['revenue'],
                    'market_avg' => $marketAverage['revenue'],
                    'percentile' => $this->calculatePercentile($vendorMetrics['revenue'], $competitors, 'revenue')
                ],
                'conversion_rate' => [
                    'vendor' => $vendorMetrics['conversion_rate'],
                    'market_avg' => $marketAverage['conversion_rate'],
                    'percentile' => $this->calculatePercentile($vendorMetrics['conversion_rate'], $competitors, 'conversion_rate')
                ],
                'average_order_value' => [
                    'vendor' => $vendorMetrics['aov'],
                    'market_avg' => $marketAverage['aov'],
                    'percentile' => $this->calculatePercentile($vendorMetrics['aov'], $competitors, 'aov')
                ],
                'customer_satisfaction' => [
                    'vendor' => $vendorMetrics['rating'],
                    'market_avg' => $marketAverage['rating'],
                    'percentile' => $this->calculatePercentile($vendorMetrics['rating'], $competitors, 'rating')
                ]
            ],
            'competitive_advantages' => $this->identifyCompetitiveAdvantages($vendor, $marketAverage),
            'improvement_areas' => $this->identifyImprovementAreas($vendor, $marketAverage)
        ];
    }
    
    /**
     * Get trend analysis
     */
    protected function getTrendAnalysis(Vendor $vendor, $startDate)
    {
        $periods = $this->generatePeriods($startDate);
        $trends = [];
        
        foreach ($periods as $period) {
            $metrics = $this->getPeriodMetrics($vendor, $period['start'], $period['end']);
            $trends[] = array_merge($period, $metrics);
        }
        
        return [
            'data' => $trends,
            'insights' => $this->analyzeTrends($trends),
            'seasonality' => $this->detectSeasonality($vendor),
            'growth_rate' => $this->calculateGrowthRate($trends)
        ];
    }
    
    // Helper methods
    
    protected function getStartDate($period)
    {
        switch ($period) {
            case 'week':
                return now()->startOfWeek();
            case 'month':
                return now()->startOfMonth();
            case 'quarter':
                return now()->startOfQuarter();
            case 'year':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }
    
    protected function calculateConversionRate(Vendor $vendor, $startDate)
    {
        // Simplified conversion rate calculation
        $views = Product::where('shop_id', $vendor->shop_id)
            ->where('updated_at', '>=', $startDate)
            ->sum('views_count');
        
        $orders = Order::where('shop_id', $vendor->shop_id)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        return $views > 0 ? round(($orders / $views) * 100, 2) : 0;
    }
    
    protected function getBasicPredictions(Vendor $vendor)
    {
        // Basic predictions without AI service
        $historicalData = Order::where('shop_id', $vendor->shop_id)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        if ($historicalData->count() < 3) {
            return null;
        }
        
        // Simple linear projection
        $revenues = $historicalData->pluck('revenue')->toArray();
        $avgGrowth = 0;
        
        for ($i = 1; $i < count($revenues); $i++) {
            if ($revenues[$i-1] > 0) {
                $growth = ($revenues[$i] - $revenues[$i-1]) / $revenues[$i-1];
                $avgGrowth += $growth;
            }
        }
        
        $avgGrowth = $avgGrowth / (count($revenues) - 1);
        $lastRevenue = end($revenues);
        
        return [
            'next_month_revenue' => round($lastRevenue * (1 + $avgGrowth), 2),
            'growth_trend' => $avgGrowth > 0 ? 'increasing' : 'decreasing',
            'confidence' => 'low',
            'based_on' => 'historical_trend'
        ];
    }
    
    protected function shouldRecommendUpgrade(Vendor $vendor)
    {
        if ($vendor->subscription_plan === 'enterprise') {
            return false;
        }
        
        $usage = (new SubscriptionService(new PaymentService()))->getUsage($vendor);
        
        return $usage['products']['percentage'] > 80 || 
               $vendor->total_sales > 50000 ||
               $vendor->products()->count() > 40;
    }
}