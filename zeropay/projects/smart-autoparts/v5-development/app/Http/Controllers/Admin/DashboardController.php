<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Vendor;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Overview stats
        $stats = [
            'total_users' => User::count(),
            'total_vendors' => Vendor::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total'),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'active_shops' => Shop::where('is_active', true)->count(),
        ];
        
        // Revenue chart data (last 7 days)
        $revenueData = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->get();
        
        // Recent orders
        $recentOrders = Order::with(['user', 'shop'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Top vendors by sales
        $topVendors = Vendor::with('shop')
            ->orderBy('total_sales', 'desc')
            ->limit(5)
            ->get();
        
        // Pending vendor approvals
        $pendingVendors = Vendor::with(['user', 'shop'])
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->get();
        
        return view('admin.dashboard', compact(
            'stats',
            'revenueData',
            'recentOrders',
            'topVendors',
            'pendingVendors'
        ));
    }
    
    public function approveVendor(Vendor $vendor)
    {
        $vendor->update([
            'status' => 'active',
            'verified_at' => now()
        ]);
        
        // Send notification to vendor
        // Notification::send($vendor->user, new VendorApproved());
        
        return back()->with('success', 'Vendor approved successfully');
    }
    
    public function rejectVendor(Vendor $vendor)
    {
        $vendor->update(['status' => 'rejected']);
        
        // Send notification to vendor
        // Notification::send($vendor->user, new VendorRejected());
        
        return back()->with('success', 'Vendor rejected');
    }
}