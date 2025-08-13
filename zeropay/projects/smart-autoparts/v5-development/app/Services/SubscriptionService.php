<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    protected $paymentService;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    
    /**
     * Get available subscription plans
     */
    public function getPlans()
    {
        return [
            'basic' => [
                'id' => 'basic',
                'name' => ['ar' => 'الباقة الأساسية', 'en' => 'Basic Plan'],
                'price' => 0,
                'duration' => 'monthly',
                'features' => [
                    'products' => 50,
                    'commission' => 15,
                    'support' => 'email',
                    'analytics' => 'basic',
                    'api_access' => false,
                    'custom_domain' => false,
                    'priority_listing' => false,
                    'marketing_tools' => false
                ]
            ],
            'professional' => [
                'id' => 'professional',
                'name' => ['ar' => 'الباقة الاحترافية', 'en' => 'Professional Plan'],
                'price' => 99,
                'duration' => 'monthly',
                'features' => [
                    'products' => 500,
                    'commission' => 12,
                    'support' => 'priority',
                    'analytics' => 'advanced',
                    'api_access' => true,
                    'custom_domain' => false,
                    'priority_listing' => true,
                    'marketing_tools' => true
                ]
            ],
            'enterprise' => [
                'id' => 'enterprise',
                'name' => ['ar' => 'الباقة المؤسسية', 'en' => 'Enterprise Plan'],
                'price' => 299,
                'duration' => 'monthly',
                'features' => [
                    'products' => 'unlimited',
                    'commission' => 8,
                    'support' => 'dedicated',
                    'analytics' => 'enterprise',
                    'api_access' => true,
                    'custom_domain' => true,
                    'priority_listing' => true,
                    'marketing_tools' => true
                ]
            ]
        ];
    }
    
    /**
     * Subscribe vendor to a plan
     */
    public function subscribe(Vendor $vendor, string $planId, array $paymentData = [])
    {
        $plans = $this->getPlans();
        
        if (!isset($plans[$planId])) {
            throw new \Exception('Invalid subscription plan');
        }
        
        $plan = $plans[$planId];
        
        DB::beginTransaction();
        
        try {
            // Process payment if not free plan
            if ($plan['price'] > 0) {
                $payment = $this->paymentService->processSubscriptionPayment(
                    $vendor,
                    $plan['price'],
                    $paymentData
                );
                
                if (!$payment['success']) {
                    throw new \Exception('Payment failed: ' . $payment['message']);
                }
            }
            
            // Cancel current subscription if exists
            $this->cancelCurrentSubscription($vendor);
            
            // Create new subscription
            $subscription = VendorSubscription::create([
                'vendor_id' => $vendor->id,
                'plan' => $planId,
                'price' => $plan['price'],
                'features' => $plan['features'],
                'starts_at' => now(),
                'expires_at' => $this->calculateExpiryDate($plan['duration']),
                'status' => 'active',
                'payment_method' => $paymentData['method'] ?? 'free',
                'auto_renew' => $paymentData['auto_renew'] ?? true
            ]);
            
            // Update vendor
            $vendor->update([
                'subscription_plan' => $planId,
                'subscription_expires_at' => $subscription->expires_at,
                'commission_rate' => $plan['features']['commission']
            ]);
            
            // Send confirmation email
            $this->sendSubscriptionConfirmation($vendor, $subscription);
            
            DB::commit();
            
            return [
                'success' => true,
                'subscription' => $subscription,
                'message' => 'Subscription activated successfully'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upgrade subscription plan
     */
    public function upgrade(Vendor $vendor, string $newPlanId)
    {
        $currentPlan = $vendor->subscription_plan;
        $plans = $this->getPlans();
        
        if ($currentPlan === $newPlanId) {
            throw new \Exception('Already subscribed to this plan');
        }
        
        // Calculate prorated amount
        $proratedAmount = $this->calculateProratedAmount(
            $vendor,
            $plans[$currentPlan],
            $plans[$newPlanId]
        );
        
        return $this->subscribe($vendor, $newPlanId, [
            'prorated_amount' => $proratedAmount
        ]);
    }
    
    /**
     * Cancel subscription
     */
    public function cancel(Vendor $vendor, bool $immediate = false)
    {
        $subscription = $vendor->subscriptions()
            ->where('status', 'active')
            ->first();
        
        if (!$subscription) {
            throw new \Exception('No active subscription found');
        }
        
        if ($immediate) {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'expires_at' => now()
            ]);
            
            $vendor->update([
                'subscription_plan' => 'basic',
                'subscription_expires_at' => null
            ]);
        } else {
            // Cancel at end of billing period
            $subscription->update([
                'status' => 'pending_cancellation',
                'auto_renew' => false,
                'cancelled_at' => now()
            ]);
        }
        
        $this->sendCancellationConfirmation($vendor, $subscription);
        
        return [
            'success' => true,
            'message' => $immediate 
                ? 'Subscription cancelled immediately' 
                : 'Subscription will be cancelled at end of billing period'
        ];
    }
    
    /**
     * Process subscription renewals
     */
    public function processRenewals()
    {
        $expiringSubscriptions = VendorSubscription::with('vendor')
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('expires_at', '<=', now()->addDays(3))
            ->get();
        
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($expiringSubscriptions as $subscription) {
            $results['processed']++;
            
            try {
                $result = $this->renewSubscription($subscription);
                
                if ($result['success']) {
                    $results['succeeded']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'vendor_id' => $subscription->vendor_id,
                        'error' => $result['message']
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'vendor_id' => $subscription->vendor_id,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Renew a subscription
     */
    protected function renewSubscription(VendorSubscription $subscription)
    {
        $vendor = $subscription->vendor;
        $plans = $this->getPlans();
        $plan = $plans[$subscription->plan];
        
        // Process payment
        $payment = $this->paymentService->processSubscriptionRenewal(
            $vendor,
            $plan['price']
        );
        
        if (!$payment['success']) {
            // Send payment failure notification
            $this->sendPaymentFailureNotification($vendor, $subscription);
            
            // Downgrade to basic plan
            $this->downgradeToBasic($vendor);
            
            return [
                'success' => false,
                'message' => 'Payment failed, downgraded to basic plan'
            ];
        }
        
        // Extend subscription
        $subscription->update([
            'expires_at' => $this->calculateExpiryDate($plan['duration']),
            'last_renewed_at' => now()
        ]);
        
        $vendor->update([
            'subscription_expires_at' => $subscription->expires_at
        ]);
        
        // Send renewal confirmation
        $this->sendRenewalConfirmation($vendor, $subscription);
        
        return [
            'success' => true,
            'message' => 'Subscription renewed successfully'
        ];
    }
    
    /**
     * Check feature availability
     */
    public function hasFeature(Vendor $vendor, string $feature)
    {
        $plans = $this->getPlans();
        $plan = $plans[$vendor->subscription_plan] ?? $plans['basic'];
        
        return $plan['features'][$feature] ?? false;
    }
    
    /**
     * Get subscription usage
     */
    public function getUsage(Vendor $vendor)
    {
        $plans = $this->getPlans();
        $plan = $plans[$vendor->subscription_plan] ?? $plans['basic'];
        $features = $plan['features'];
        
        return [
            'products' => [
                'used' => $vendor->products()->count(),
                'limit' => $features['products'],
                'percentage' => $features['products'] === 'unlimited' 
                    ? 0 
                    : ($vendor->products()->count() / $features['products']) * 100
            ],
            'storage' => [
                'used' => $this->calculateStorageUsage($vendor),
                'limit' => $features['storage'] ?? 'unlimited',
                'percentage' => 0 // Implement storage calculation
            ],
            'api_calls' => [
                'used' => $this->getApiUsage($vendor),
                'limit' => $features['api_calls'] ?? 'unlimited',
                'percentage' => 0 // Implement API usage calculation
            ]
        ];
    }
    
    /**
     * Calculate prorated amount for plan changes
     */
    protected function calculateProratedAmount(Vendor $vendor, array $currentPlan, array $newPlan)
    {
        $subscription = $vendor->subscriptions()
            ->where('status', 'active')
            ->first();
        
        if (!$subscription) {
            return $newPlan['price'];
        }
        
        $daysRemaining = now()->diffInDays($subscription->expires_at);
        $totalDays = $subscription->starts_at->diffInDays($subscription->expires_at);
        
        $unusedAmount = ($currentPlan['price'] / $totalDays) * $daysRemaining;
        $newAmount = ($newPlan['price'] / $totalDays) * $daysRemaining;
        
        return max(0, $newAmount - $unusedAmount);
    }
    
    /**
     * Calculate expiry date based on duration
     */
    protected function calculateExpiryDate(string $duration)
    {
        switch ($duration) {
            case 'monthly':
                return now()->addMonth();
            case 'quarterly':
                return now()->addMonths(3);
            case 'yearly':
                return now()->addYear();
            default:
                return now()->addMonth();
        }
    }
    
    /**
     * Cancel current active subscription
     */
    protected function cancelCurrentSubscription(Vendor $vendor)
    {
        $vendor->subscriptions()
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
    }
    
    /**
     * Downgrade vendor to basic plan
     */
    protected function downgradeToBasic(Vendor $vendor)
    {
        $vendor->update([
            'subscription_plan' => 'basic',
            'subscription_expires_at' => null,
            'commission_rate' => 15
        ]);
        
        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan' => 'basic',
            'price' => 0,
            'features' => $this->getPlans()['basic']['features'],
            'starts_at' => now(),
            'expires_at' => null,
            'status' => 'active'
        ]);
    }
    
    /**
     * Calculate storage usage for vendor
     */
    protected function calculateStorageUsage(Vendor $vendor)
    {
        // Implement storage calculation logic
        return 0;
    }
    
    /**
     * Get API usage for vendor
     */
    protected function getApiUsage(Vendor $vendor)
    {
        // Implement API usage tracking
        return 0;
    }
    
    /**
     * Send subscription confirmation email
     */
    protected function sendSubscriptionConfirmation(Vendor $vendor, VendorSubscription $subscription)
    {
        // Implement email notification
    }
    
    /**
     * Send cancellation confirmation email
     */
    protected function sendCancellationConfirmation(Vendor $vendor, VendorSubscription $subscription)
    {
        // Implement email notification
    }
    
    /**
     * Send renewal confirmation email
     */
    protected function sendRenewalConfirmation(Vendor $vendor, VendorSubscription $subscription)
    {
        // Implement email notification
    }
    
    /**
     * Send payment failure notification
     */
    protected function sendPaymentFailureNotification(Vendor $vendor, VendorSubscription $subscription)
    {
        // Implement email notification
    }
}