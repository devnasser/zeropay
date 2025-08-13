<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index()
    {
        $user = auth()->user();
        
        // Statistics
        $stats = [
            'total_orders' => $user->orders()->count(),
            'pending_orders' => $user->orders()->pending()->count(),
            'total_spent' => $user->orders()->paid()->sum('total'),
            'loyalty_points' => $user->loyalty_points,
            'wallet_balance' => $user->wallet_balance,
        ];
        
        // Recent orders
        $recentOrders = $user->orders()
            ->with(['shop', 'items'])
            ->latest()
            ->take(5)
            ->get();
        
        // Personalized recommendations
        $recommendations = $this->recommendationService->getPersonalizedRecommendations($user, 8);
        
        // Price drop alerts
        $priceDrops = $this->recommendationService->getPriceDropRecommendations($user, 4);
        
        return view('customer.dashboard', compact('stats', 'recentOrders', 'recommendations', 'priceDrops'));
    }
}