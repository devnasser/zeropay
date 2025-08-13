<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VendorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (!auth()->user()->hasRole('vendor') && !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized access.');
        }
        
        // Check if vendor account is active
        if (auth()->user()->hasRole('vendor')) {
            $vendor = auth()->user()->vendor;
            
            if (!$vendor || $vendor->status !== 'active') {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Your vendor account is not active.');
            }
            
            // Check subscription
            if ($vendor->subscription_expires_at && $vendor->subscription_expires_at->isPast()) {
                return redirect()->route('vendor.subscription.expired')
                    ->with('warning', 'Your subscription has expired.');
            }
        }
        
        return $next($request);
    }
}