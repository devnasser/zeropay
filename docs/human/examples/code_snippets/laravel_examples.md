# 🚀 أمثلة Laravel متقدمة

## 1. خدمة التكامل الحكومي - مثال حقيقي

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\GovernmentRequest;

class GovernmentIntegrationService
{
    private $apiUrl;
    private $apiKey;
    
    public function __construct()
    {
        $this->apiUrl = config('services.government.url');
        $this->apiKey = config('services.government.key');
    }
    
    /**
     * التحقق من الهوية الوطنية
     */
    public function verifyNationalId($nationalId)
    {
        // تخزين مؤقت للنتائج لتقليل الطلبات
        return Cache::remember("gov_id_{$nationalId}", 3600, function () use ($nationalId) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/verify/national-id', [
                'national_id' => $nationalId
            ]);
            
            if ($response->successful()) {
                // تسجيل الطلب الناجح
                GovernmentRequest::create([
                    'type' => 'national_id_verification',
                    'request_data' => ['national_id' => $nationalId],
                    'response_data' => $response->json(),
                    'status' => 'success'
                ]);
                
                return $response->json();
            }
            
            throw new \Exception('فشل التحقق من الهوية');
        });
    }
    
    /**
     * الحصول على تفاصيل الرخصة التجارية
     */
    public function getBusinessLicense($licenseNumber)
    {
        return Cache::remember("gov_license_{$licenseNumber}", 7200, function () use ($licenseNumber) {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])
                ->get($this->apiUrl . '/business/license/' . $licenseNumber);
            
            return $response->json();
        });
    }
}
```

## 2. خدمة المدفوعات المتقدمة

```php
<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * معالجة دفعة آمنة مع إدارة المعاملات
     */
    public function processPayment(Order $order, array $paymentData)
    {
        return DB::transaction(function () use ($order, $paymentData) {
            try {
                // إنشاء سجل الدفع
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'amount' => $order->total_amount,
                    'currency' => 'SAR',
                    'method' => $paymentData['method'],
                    'status' => 'pending'
                ]);
                
                // معالجة حسب نوع الدفع
                switch ($paymentData['method']) {
                    case 'credit_card':
                        $result = $this->processCreditCard($payment, $paymentData);
                        break;
                    case 'bank_transfer':
                        $result = $this->processBankTransfer($payment, $paymentData);
                        break;
                    case 'cash_on_delivery':
                        $result = $this->processCOD($payment, $order);
                        break;
                    default:
                        throw new \Exception('طريقة دفع غير مدعومة');
                }
                
                // تحديث حالة الدفع
                $payment->update([
                    'status' => $result['status'],
                    'transaction_id' => $result['transaction_id'] ?? null,
                    'processed_at' => now()
                ]);
                
                // تحديث حالة الطلب
                if ($result['status'] === 'completed') {
                    $order->update(['status' => 'paid']);
                    
                    // إرسال إشعار للعميل
                    $order->user->notify(new PaymentSuccessNotification($payment));
                }
                
                return $payment;
                
            } catch (\Exception $e) {
                Log::error('Payment processing failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
                
                throw $e;
            }
        });
    }
    
    private function processCreditCard($payment, $data)
    {
        // تشفير بيانات البطاقة
        $encryptedCard = encrypt($data['card_number']);
        
        // استدعاء بوابة الدفع
        // ... كود التكامل مع بوابة الدفع
        
        return [
            'status' => 'completed',
            'transaction_id' => 'TRX' . time()
        ];
    }
}
```

## 3. نظام التوصيات الذكي

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    /**
     * الحصول على توصيات مخصصة للمستخدم
     */
    public function getPersonalizedRecommendations(User $user, $limit = 10)
    {
        return Cache::remember("user_recommendations_{$user->id}", 3600, function () use ($user, $limit) {
            // تحليل سلوك المستخدم
            $userBehavior = $this->analyzeUserBehavior($user);
            
            // الحصول على المنتجات المشابهة
            $recommendations = Product::query()
                ->whereIn('category_id', $userBehavior['preferred_categories'])
                ->whereNotIn('id', $userBehavior['purchased_products'])
                ->withAvg('reviews', 'rating')
                ->having('reviews_avg_rating', '>=', 4)
                ->orderByDesc('reviews_avg_rating')
                ->limit($limit)
                ->get();
            
            // إضافة نقاط التوصية
            return $recommendations->map(function ($product) use ($userBehavior) {
                $score = $this->calculateRecommendationScore($product, $userBehavior);
                $product->recommendation_score = $score;
                return $product;
            })->sortByDesc('recommendation_score');
        });
    }
    
    private function analyzeUserBehavior(User $user)
    {
        return [
            'preferred_categories' => $user->orders()
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->groupBy('products.category_id')
                ->pluck('products.category_id')
                ->toArray(),
                
            'purchased_products' => $user->orders()
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->pluck('order_items.product_id')
                ->toArray(),
                
            'average_price' => $user->orders()
                ->avg('total_amount'),
                
            'purchase_frequency' => $user->orders()
                ->whereBetween('created_at', [now()->subMonths(6), now()])
                ->count()
        ];
    }
    
    private function calculateRecommendationScore($product, $userBehavior)
    {
        $score = 0;
        
        // نقاط للسعر المناسب
        if (abs($product->price - $userBehavior['average_price']) < 100) {
            $score += 30;
        }
        
        // نقاط للتقييمات العالية
        $score += $product->reviews_avg_rating * 10;
        
        // نقاط للمنتجات الجديدة
        if ($product->created_at->gt(now()->subDays(30))) {
            $score += 20;
        }
        
        return $score;
    }
}
```

## 4. تحسين الاستعلامات المعقدة

```php
<?php

// بدلاً من N+1 queries
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->user->name; // استعلام إضافي لكل طلب!
}

// الحل الأمثل
$orders = Order::with(['user', 'items.product'])->get();
foreach ($orders as $order) {
    echo $order->user->name; // لا استعلامات إضافية
}

// استعلام معقد محسن
$topProducts = Product::query()
    ->select('products.*')
    ->selectRaw('COUNT(DISTINCT order_items.order_id) as order_count')
    ->selectRaw('SUM(order_items.quantity) as total_sold')
    ->selectRaw('AVG(reviews.rating) as avg_rating')
    ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
    ->leftJoin('reviews', 'products.id', '=', 'reviews.product_id')
    ->groupBy('products.id')
    ->having('order_count', '>', 10)
    ->orderByDesc('total_sold')
    ->limit(20)
    ->get();
```

## 5. Middleware مخصص للأمان

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\RateLimiter;

class SecurityMiddleware
{
    public function handle($request, Closure $next)
    {
        // التحقق من معدل الطلبات
        $key = 'security:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 100)) {
            return response()->json([
                'message' => 'تم تجاوز الحد المسموح من الطلبات'
            ], 429);
        }
        
        RateLimiter::hit($key, 60);
        
        // التحقق من رؤوس الأمان
        if (!$request->hasHeader('X-Requested-With')) {
            return response()->json([
                'message' => 'طلب غير صالح'
            ], 400);
        }
        
        // تسجيل النشاط المشبوه
        if ($this->isSuspiciousRequest($request)) {
            Log::warning('Suspicious request detected', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent()
            ]);
        }
        
        $response = $next($request);
        
        // إضافة رؤوس الأمان
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        return $response;
    }
    
    private function isSuspiciousRequest($request)
    {
        $suspiciousPatterns = [
            '/\.\./i',           // محاولة الوصول للمجلدات الأعلى
            '/union.*select/i',  // SQL injection
            '/<script/i',        // XSS
            '/eval\(/i',         // تنفيذ كود
        ];
        
        $input = $request->all();
        $inputString = json_encode($input);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                return true;
            }
        }
        
        return false;
    }
}
```

---
*أمثلة Laravel متقدمة - مشروع Zero - نمط الأسطورة ⚔️*