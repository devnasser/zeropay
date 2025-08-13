<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorAnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $analyticsService;
    
    public function __construct(VendorAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }
    
    public function index(Request $request)
    {
        $vendor = auth()->user()->vendor;
        $period = $request->get('period', 'month');
        
        // Basic stats for MVP
        $stats = [
            'total_sales' => $vendor->total_sales,
            'pending_balance' => $vendor->balance,
            'total_products' => $vendor->shop->products()->count(),
            'active_products' => $vendor->shop->products()->active()->count(),
            'pending_orders' => $vendor->shop->orders()->where('status', 'pending')->count(),
            'total_orders' => $vendor->shop->orders()->count(),
            'shop_rating' => $vendor->shop->rating,
            'subscription_plan' => $vendor->subscription_plan,
        ];
        
        // Recent orders
        $recentOrders = $vendor->shop->orders()
            ->with(['user', 'items'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Low stock products
        $lowStockProducts = $vendor->shop->products()
            ->where('quantity', '>', 0)
            ->where('quantity', '<=', 10)
            ->orderBy('quantity')
            ->limit(5)
            ->get();
        
        return view('vendor.dashboard', compact(
            'vendor',
            'stats',
            'recentOrders',
            'lowStockProducts',
            'period'
        ));
    }
    
    public function products()
    {
        $vendor = auth()->user()->vendor;
        
        $products = $vendor->shop->products()
            ->with(['category', 'orderItems'])
            ->paginate(20);
        
        return view('vendor.products.index', compact('products'));
    }
    
    public function orders()
    {
        $vendor = auth()->user()->vendor;
        
        $orders = $vendor->shop->orders()
            ->with(['user', 'items.product'])
            ->latest()
            ->paginate(20);
        
        return view('vendor.orders.index', compact('orders'));
    }
    
    public function updateOrderStatus(Request $request, $orderId)
    {
        $vendor = auth()->user()->vendor;
        
        $order = $vendor->shop->orders()->findOrFail($orderId);
        
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled'
        ]);
        
        $order->update(['status' => $request->status]);
        
        // Log status change
        activity()
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->log("Order status updated to {$request->status}");
        
        return back()->with('success', 'Order status updated successfully');
    }
}