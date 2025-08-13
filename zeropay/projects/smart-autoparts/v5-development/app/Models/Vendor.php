<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasTranslations;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'user_id',
        'shop_id',
        'business_name',
        'business_type',
        'registration_number',
        'tax_number',
        'bank_account',
        'commission_rate',
        'balance',
        'total_sales',
        'total_commission',
        'subscription_plan',
        'subscription_expires_at',
        'status',
        'verified_at',
        'settings',
        'documents',
        'payout_schedule',
        'minimum_payout',
        'preferred_payout_method'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'subscription_expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'settings' => 'array',
        'documents' => 'array',
        'minimum_payout' => 'decimal:2'
    ];

    protected $translatable = ['business_name'];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REJECTED = 'rejected';

    // Subscription plans
    const PLAN_BASIC = 'basic';
    const PLAN_PROFESSIONAL = 'professional';
    const PLAN_ENTERPRISE = 'enterprise';

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function products()
    {
        return $this->through('shop')->has('products');
    }

    public function orders()
    {
        return $this->through('shop')->has('orders');
    }

    public function payouts()
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(VendorSubscription::class);
    }

    public function commissions()
    {
        return $this->hasMany(VendorCommission::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function ($q) {
                        $q->whereNull('subscription_expires_at')
                          ->orWhere('subscription_expires_at', '>', now());
                    });
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeByPlan($query, $plan)
    {
        return $query->where('subscription_plan', $plan);
    }

    // Methods
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE && 
               (!$this->subscription_expires_at || $this->subscription_expires_at->isFuture());
    }

    public function calculateCommission($amount)
    {
        return $amount * ($this->commission_rate / 100);
    }

    public function addSale($order)
    {
        $commission = $this->calculateCommission($order->total);
        
        $this->increment('total_sales', $order->total);
        $this->increment('total_commission', $commission);
        $this->increment('balance', $order->total - $commission);
        
        // Record commission
        $this->commissions()->create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'commission' => $commission,
            'rate' => $this->commission_rate,
            'status' => 'pending'
        ]);
    }

    public function requestPayout($amount = null)
    {
        if (!$amount) {
            $amount = $this->balance;
        }
        
        if ($amount < $this->minimum_payout) {
            throw new \Exception('Amount is below minimum payout threshold');
        }
        
        if ($amount > $this->balance) {
            throw new \Exception('Insufficient balance');
        }
        
        $payout = $this->payouts()->create([
            'amount' => $amount,
            'method' => $this->preferred_payout_method,
            'status' => 'pending',
            'requested_at' => now()
        ]);
        
        $this->decrement('balance', $amount);
        
        return $payout;
    }

    public function upgradeSubscription($plan)
    {
        $prices = [
            self::PLAN_BASIC => 0,
            self::PLAN_PROFESSIONAL => 99,
            self::PLAN_ENTERPRISE => 299
        ];
        
        $this->subscriptions()->create([
            'plan' => $plan,
            'price' => $prices[$plan],
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => 'active'
        ]);
        
        $this->update([
            'subscription_plan' => $plan,
            'subscription_expires_at' => now()->addMonth()
        ]);
    }

    public function getCommissionRateAttribute($value)
    {
        // Dynamic commission based on plan
        $planRates = [
            self::PLAN_BASIC => 15,
            self::PLAN_PROFESSIONAL => 12,
            self::PLAN_ENTERPRISE => 8
        ];
        
        return $planRates[$this->subscription_plan] ?? $value;
    }

    public function getDashboardStats()
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        return [
            'total_sales' => $this->total_sales,
            'current_balance' => $this->balance,
            'total_commission' => $this->total_commission,
            'monthly_sales' => $this->orders()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->sum('total'),
            'pending_orders' => $this->orders()
                ->where('status', 'pending')
                ->count(),
            'total_products' => $this->products()->count(),
            'active_products' => $this->products()
                ->where('is_active', true)
                ->count(),
            'average_rating' => $this->shop->rating,
            'total_reviews' => $this->shop->reviews_count
        ];
    }
}