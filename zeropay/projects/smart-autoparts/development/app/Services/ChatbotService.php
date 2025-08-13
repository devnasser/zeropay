<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotService
{
    protected $intents;
    protected $responses;
    protected $context = [];

    public function __construct()
    {
        $this->initializeIntents();
        $this->initializeResponses();
    }

    /**
     * Process user message and return bot response
     */
    public function processMessage(string $message, ?array $context = null, $user = null)
    {
        $this->context = $context ?? [];
        $message = $this->normalizeMessage($message);
        
        // Detect intent
        $intent = $this->detectIntent($message);
        
        // Extract entities
        $entities = $this->extractEntities($message, $intent);
        
        // Generate response
        $response = $this->generateResponse($intent, $entities, $user);
        
        // Update context
        $this->updateContext($intent, $entities, $response);
        
        // Log conversation
        $this->logConversation($message, $intent, $response, $user);
        
        return [
            'response' => $response,
            'intent' => $intent,
            'entities' => $entities,
            'context' => $this->context,
            'suggestions' => $this->getSuggestions($intent, $this->context)
        ];
    }

    /**
     * Initialize intent patterns
     */
    protected function initializeIntents()
    {
        $this->intents = [
            'greeting' => [
                'patterns' => ['مرحبا', 'اهلا', 'السلام عليكم', 'صباح', 'مساء', 'هاي', 'هلا'],
                'confidence' => 0.9
            ],
            'product_search' => [
                'patterns' => ['ابحث', 'اريد', 'عندك', 'متوفر', 'موجود', 'ابغى', 'ابي'],
                'confidence' => 0.8
            ],
            'price_inquiry' => [
                'patterns' => ['سعر', 'كم', 'بكم', 'تكلفة', 'قيمة', 'كم سعر'],
                'confidence' => 0.85
            ],
            'order_status' => [
                'patterns' => ['طلبي', 'طلب', 'وين وصل', 'متى يوصل', 'حالة الطلب'],
                'confidence' => 0.9
            ],
            'complaint' => [
                'patterns' => ['شكوى', 'مشكلة', 'عيب', 'خراب', 'ما يشتغل', 'تالف'],
                'confidence' => 0.85
            ],
            'return_request' => [
                'patterns' => ['ارجاع', 'استرجاع', 'رد', 'ابي ارجع', 'رجع'],
                'confidence' => 0.9
            ],
            'shipping_info' => [
                'patterns' => ['شحن', 'توصيل', 'كم يوم', 'متى يوصل', 'وقت التوصيل'],
                'confidence' => 0.85
            ],
            'payment_methods' => [
                'patterns' => ['دفع', 'كيف ادفع', 'طرق الدفع', 'تقسيط', 'مدى'],
                'confidence' => 0.85
            ],
            'warranty' => [
                'patterns' => ['ضمان', 'كفالة', 'الضمان كم', 'فترة الضمان'],
                'confidence' => 0.9
            ],
            'availability' => [
                'patterns' => ['متوفر', 'موجود', 'في المخزن', 'نفذ', 'انتهى'],
                'confidence' => 0.85
            ],
            'recommendation' => [
                'patterns' => ['انصحني', 'ايش تنصح', 'افضل', 'احسن', 'ممتاز'],
                'confidence' => 0.8
            ],
            'thanks' => [
                'patterns' => ['شكرا', 'مشكور', 'تسلم', 'الله يعطيك العافية'],
                'confidence' => 0.95
            ],
            'goodbye' => [
                'patterns' => ['وداع', 'باي', 'مع السلامة', 'الله معك', 'اشوفك'],
                'confidence' => 0.95
            ]
        ];
    }

    /**
     * Initialize response templates
     */
    protected function initializeResponses()
    {
        $this->responses = [
            'greeting' => [
                'أهلاً وسهلاً بك في Smart AutoParts! كيف أقدر أساعدك اليوم؟ 😊',
                'مرحباً بك! أنا مساعدك الذكي. كيف أقدر أخدمك؟',
                'أهلاً! تفضل كيف أقدر أساعدك في إيجاد قطع الغيار المناسبة؟'
            ],
            'product_search' => [
                'أكيد! وش نوع القطعة اللي تبحث عنها؟',
                'تمام، ممكن تحدد لي اسم القطعة أو رقمها؟',
                'أبشر! قول لي وش تحتاج بالضبط وأنا أساعدك'
            ],
            'price_inquiry' => [
                'دقيقة بس أجيب لك السعر...',
                'الآن أشوف لك السعر، ثواني معك',
                'تمام، بجيب لك تفاصيل السعر حالاً'
            ],
            'order_status' => [
                'دقيقة أشوف لك حالة طلبك...',
                'الآن أتحقق من طلبك، ممكن رقم الطلب؟',
                'أكيد! أقدر أساعدك، بس أحتاج رقم الطلب'
            ],
            'complaint' => [
                'آسف جداً على هذا الإزعاج! ممكن تشرح لي المشكلة بالتفصيل؟',
                'نعتذر عن أي إزعاج! خلني أساعدك في حل المشكلة',
                'متأسفين على هذه التجربة. ممكن توضح أكثر عشان نحل المشكلة؟'
            ],
            'thanks' => [
                'العفو! يسعدني أني قدرت أساعدك 😊',
                'تسلم! دايماً في خدمتك',
                'الله يعافيك! لا تتردد في السؤال عن أي شيء ثاني'
            ],
            'goodbye' => [
                'مع السلامة! سعدت بخدمتك 👋',
                'الله معك! نتمنى لك يوم سعيد',
                'في أمان الله! نشوفك قريباً إن شاء الله'
            ],
            'unknown' => [
                'عذراً، ما فهمت طلبك. ممكن توضح أكثر؟',
                'ممكن تعيد السؤال بطريقة ثانية؟ ما فهمت عليك زين',
                'آسف، ما قدرت أفهم. جرب تسأل بطريقة مختلفة'
            ]
        ];
    }

    /**
     * Detect intent from message
     */
    protected function detectIntent(string $message)
    {
        $scores = [];
        
        foreach ($this->intents as $intent => $data) {
            $score = 0;
            $matchedPatterns = 0;
            
            foreach ($data['patterns'] as $pattern) {
                if (strpos($message, $pattern) !== false) {
                    $score += $data['confidence'];
                    $matchedPatterns++;
                }
            }
            
            if ($matchedPatterns > 0) {
                $scores[$intent] = $score / $matchedPatterns;
            }
        }
        
        if (empty($scores)) {
            return 'unknown';
        }
        
        arsort($scores);
        $topIntent = array_key_first($scores);
        
        return $scores[$topIntent] >= 0.5 ? $topIntent : 'unknown';
    }

    /**
     * Extract entities from message
     */
    protected function extractEntities(string $message, string $intent)
    {
        $entities = [];
        
        // Extract product names
        if (in_array($intent, ['product_search', 'price_inquiry', 'availability'])) {
            $productKeywords = ['فلتر', 'زيت', 'بطارية', 'اطار', 'فرامل', 'مكينة', 'جير', 'راديتر'];
            foreach ($productKeywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $entities['product'] = $keyword;
                    break;
                }
            }
        }
        
        // Extract order number
        if ($intent === 'order_status') {
            if (preg_match('/\b\d{6,}\b/', $message, $matches)) {
                $entities['order_number'] = $matches[0];
            }
        }
        
        // Extract car info
        if (preg_match('/(كامري|كورولا|هايلكس|اكورد|النترا|سوناتا)/', $message, $matches)) {
            $entities['car_model'] = $matches[1];
        }
        
        if (preg_match('/\b(19|20)\d{2}\b/', $message, $matches)) {
            $entities['car_year'] = $matches[0];
        }
        
        // Extract price range
        if (preg_match('/(\d+)\s*(ريال|rial)/i', $message, $matches)) {
            $entities['price'] = $matches[1];
        }
        
        return $entities;
    }

    /**
     * Generate response based on intent and entities
     */
    protected function generateResponse(string $intent, array $entities, $user)
    {
        switch ($intent) {
            case 'greeting':
                return $this->getRandomResponse('greeting');
            
            case 'product_search':
                return $this->handleProductSearch($entities);
            
            case 'price_inquiry':
                return $this->handlePriceInquiry($entities);
            
            case 'order_status':
                return $this->handleOrderStatus($entities, $user);
            
            case 'complaint':
                return $this->handleComplaint($entities, $user);
            
            case 'return_request':
                return $this->handleReturnRequest($entities, $user);
            
            case 'shipping_info':
                return $this->handleShippingInfo($entities);
            
            case 'payment_methods':
                return $this->handlePaymentMethods();
            
            case 'warranty':
                return $this->handleWarrantyInfo($entities);
            
            case 'availability':
                return $this->handleAvailability($entities);
            
            case 'recommendation':
                return $this->handleRecommendation($entities, $user);
            
            case 'thanks':
                return $this->getRandomResponse('thanks');
            
            case 'goodbye':
                return $this->getRandomResponse('goodbye');
            
            default:
                return $this->getRandomResponse('unknown');
        }
    }

    /**
     * Handle product search
     */
    protected function handleProductSearch($entities)
    {
        if (!isset($entities['product'])) {
            return $this->getRandomResponse('product_search');
        }
        
        $productName = $entities['product'];
        $products = Product::active()
            ->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$productName}%"])
            ->limit(3)
            ->get();
        
        if ($products->isEmpty()) {
            return "للأسف ما لقيت {$productName} حالياً. جرب تبحث عن منتج ثاني أو تواصل معنا";
        }
        
        $response = "لقيت لك هذي المنتجات:\n\n";
        foreach ($products as $product) {
            $response .= "🔧 {$product->name}\n";
            $response .= "💰 السعر: {$product->price} ريال\n";
            $response .= "✅ متوفر: " . ($product->quantity > 0 ? 'نعم' : 'لا') . "\n\n";
        }
        
        return $response;
    }

    /**
     * Handle price inquiry
     */
    protected function handlePriceInquiry($entities)
    {
        if (!isset($entities['product'])) {
            return "ممكن تحدد لي اسم المنتج اللي تبي تعرف سعره؟";
        }
        
        $product = Product::active()
            ->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$entities['product']}%"])
            ->first();
        
        if (!$product) {
            return "ما لقيت هذا المنتج. ممكن تتأكد من الاسم؟";
        }
        
        $response = "💰 سعر {$product->name}: {$product->price} ريال\n";
        
        if ($product->sale_price) {
            $response .= "🎉 عرض خاص: {$product->sale_price} ريال (خصم {$product->discount_percentage}%)\n";
        }
        
        if ($product->quantity > 0) {
            $response .= "✅ متوفر للشحن الفوري";
        } else {
            $response .= "❌ نفذت الكمية حالياً";
        }
        
        return $response;
    }

    /**
     * Handle order status inquiry
     */
    protected function handleOrderStatus($entities, $user)
    {
        if (!$user) {
            return "عشان أقدر أشوف طلباتك، لازم تسجل دخول أول";
        }
        
        if (!isset($entities['order_number'])) {
            $lastOrder = Order::where('user_id', $user->id)
                ->latest()
                ->first();
            
            if (!$lastOrder) {
                return "ما عندك طلبات سابقة. تبي تتسوق معنا؟";
            }
            
            $entities['order_number'] = $lastOrder->order_number;
        }
        
        $order = Order::where('order_number', $entities['order_number'])
            ->where('user_id', $user->id)
            ->first();
        
        if (!$order) {
            return "ما لقيت طلب بهذا الرقم. تأكد من الرقم وحاول مرة ثانية";
        }
        
        $statusMessages = [
            'pending' => 'في انتظار التأكيد',
            'processing' => 'جاري التجهيز',
            'shipped' => 'تم الشحن',
            'delivered' => 'تم التوصيل',
            'cancelled' => 'ملغي'
        ];
        
        $response = "📦 طلب رقم: {$order->order_number}\n";
        $response .= "📊 الحالة: {$statusMessages[$order->status]}\n";
        $response .= "💰 المبلغ: {$order->total} ريال\n";
        
        if ($order->shipped_at) {
            $response .= "🚚 تاريخ الشحن: {$order->shipped_at->format('Y-m-d')}\n";
        }
        
        if ($order->status === 'shipped') {
            $response .= "📅 التوصيل المتوقع: خلال 2-3 أيام عمل";
        }
        
        return $response;
    }

    /**
     * Handle complaint
     */
    protected function handleComplaint($entities, $user)
    {
        $response = $this->getRandomResponse('complaint') . "\n\n";
        $response .= "برقم الواتساب: 0501234567\n";
        $response .= "أو الإيميل: support@smartautoparts.sa\n";
        $response .= "وراح نحل مشكلتك في أسرع وقت إن شاء الله";
        
        // Create support ticket
        if ($user) {
            // Log complaint for follow-up
            Log::info('Customer complaint', [
                'user_id' => $user->id,
                'entities' => $entities
            ]);
        }
        
        return $response;
    }

    /**
     * Handle return request
     */
    protected function handleReturnRequest($entities, $user)
    {
        if (!$user) {
            return "لازم تسجل دخول عشان تقدر ترجع المنتج";
        }
        
        $response = "سياسة الإرجاع:\n";
        $response .= "✅ يمكنك إرجاع المنتج خلال 7 أيام من استلامه\n";
        $response .= "✅ يجب أن يكون المنتج بحالته الأصلية\n";
        $response .= "✅ احتفظ بالفاتورة والعبوة الأصلية\n\n";
        $response .= "للإرجاع، تواصل معنا على: 0501234567";
        
        return $response;
    }

    /**
     * Handle shipping info
     */
    protected function handleShippingInfo($entities)
    {
        $response = "🚚 معلومات الشحن:\n\n";
        $response .= "📍 الرياض: 1-2 يوم عمل\n";
        $response .= "📍 جدة والدمام: 2-3 أيام عمل\n";
        $response .= "📍 باقي المدن: 3-5 أيام عمل\n\n";
        $response .= "💰 رسوم الشحن:\n";
        $response .= "• الطلبات فوق 200 ريال: شحن مجاني\n";
        $response .= "• الطلبات أقل من 200 ريال: 25 ريال";
        
        return $response;
    }

    /**
     * Handle payment methods
     */
    protected function handlePaymentMethods()
    {
        $response = "💳 طرق الدفع المتوفرة:\n\n";
        $response .= "• مدى\n";
        $response .= "• فيزا وماستركارد\n";
        $response .= "• Apple Pay\n";
        $response .= "• STC Pay\n";
        $response .= "• تمارا (تقسيط بدون فوائد)\n";
        $response .= "• تابي (ادفع لاحقاً)\n";
        $response .= "• الدفع عند الاستلام\n\n";
        $response .= "🔒 جميع المدفوعات آمنة ومشفرة";
        
        return $response;
    }

    /**
     * Handle warranty info
     */
    protected function handleWarrantyInfo($entities)
    {
        $response = "🛡️ معلومات الضمان:\n\n";
        
        if (isset($entities['product'])) {
            $product = Product::active()
                ->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$entities['product']}%"])
                ->first();
            
            if ($product && $product->warranty_months) {
                $response .= "ضمان {$product->name}: {$product->warranty_months} شهر\n\n";
            }
        }
        
        $response .= "• جميع المنتجات الأصلية لها ضمان\n";
        $response .= "• الضمان يشمل عيوب الصناعة\n";
        $response .= "• لا يشمل الضمان سوء الاستخدام\n";
        $response .= "• احتفظ بالفاتورة لتفعيل الضمان";
        
        return $response;
    }

    /**
     * Handle availability check
     */
    protected function handleAvailability($entities)
    {
        if (!isset($entities['product'])) {
            return "أي منتج تبي تتأكد من توفره؟";
        }
        
        $product = Product::active()
            ->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$entities['product']}%"])
            ->first();
        
        if (!$product) {
            return "ما لقيت هذا المنتج في قاعدة البيانات";
        }
        
        if ($product->quantity > 10) {
            return "✅ {$product->name} متوفر بكمية كبيرة";
        } elseif ($product->quantity > 0) {
            return "⚠️ {$product->name} متوفر بكمية محدودة ({$product->quantity} قطعة)";
        } else {
            return "❌ {$product->name} غير متوفر حالياً. ممكن نبلغك عند توفره؟";
        }
    }

    /**
     * Handle product recommendation
     */
    protected function handleRecommendation($entities, $user)
    {
        $response = "بناءً على ";
        
        if (isset($entities['car_model'])) {
            $response .= "سيارتك {$entities['car_model']}";
            if (isset($entities['car_year'])) {
                $response .= " موديل {$entities['car_year']}";
            }
        } else {
            $response .= "احتياجاتك";
        }
        
        $response .= "، أنصحك بـ:\n\n";
        
        // Get recommended products (simplified)
        $recommendations = Product::active()
            ->inRandomOrder()
            ->limit(3)
            ->get();
        
        foreach ($recommendations as $product) {
            $response .= "⭐ {$product->name}\n";
            $response .= "   💰 {$product->price} ريال\n";
            if ($product->rating >= 4) {
                $response .= "   ⭐ تقييم ممتاز ({$product->rating}/5)\n";
            }
            $response .= "\n";
        }
        
        $response .= "تبي تفاصيل أكثر عن أي منتج؟";
        
        return $response;
    }

    /**
     * Get random response from template
     */
    protected function getRandomResponse(string $type)
    {
        $responses = $this->responses[$type] ?? $this->responses['unknown'];
        return $responses[array_rand($responses)];
    }

    /**
     * Update conversation context
     */
    protected function updateContext($intent, $entities, $response)
    {
        $this->context['last_intent'] = $intent;
        $this->context['last_entities'] = $entities;
        $this->context['timestamp'] = now()->toIso8601String();
        
        // Keep product context
        if (isset($entities['product'])) {
            $this->context['current_product'] = $entities['product'];
        }
        
        // Keep order context
        if (isset($entities['order_number'])) {
            $this->context['current_order'] = $entities['order_number'];
        }
    }

    /**
     * Get conversation suggestions
     */
    protected function getSuggestions($intent, $context)
    {
        $suggestions = [];
        
        switch ($intent) {
            case 'greeting':
                $suggestions = [
                    'أبي فلتر زيت',
                    'عندك بطاريات؟',
                    'أبي أشوف طلبي'
                ];
                break;
            
            case 'product_search':
                $suggestions = [
                    'كم سعره؟',
                    'متوفر؟',
                    'عليه ضمان؟'
                ];
                break;
            
            case 'price_inquiry':
                $suggestions = [
                    'أضفه للسلة',
                    'عندك أرخص؟',
                    'فيه عروض؟'
                ];
                break;
            
            case 'order_status':
                $suggestions = [
                    'ألغي الطلب',
                    'غير العنوان',
                    'متى يوصل؟'
                ];
                break;
                
            default:
                $suggestions = [
                    'أبي أتسوق',
                    'عندي سؤال',
                    'شكراً'
                ];
        }
        
        return $suggestions;
    }

    /**
     * Log conversation for analytics
     */
    protected function logConversation($message, $intent, $response, $user)
    {
        Log::channel('chatbot')->info('Conversation', [
            'user_id' => $user?->id,
            'message' => $message,
            'intent' => $intent,
            'response' => Str::limit($response, 100),
            'timestamp' => now()
        ]);
    }

    /**
     * Normalize Arabic message
     */
    protected function normalizeMessage($message)
    {
        // Convert to lowercase
        $message = mb_strtolower($message);
        
        // Remove diacritics
        $message = preg_replace('/[\x{064B}-\x{065F}]/u', '', $message);
        
        // Normalize Arabic characters
        $replacements = [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
            'ؤ' => 'و',
            'ئ' => 'ي'
        ];
        
        $message = str_replace(array_keys($replacements), array_values($replacements), $message);
        
        // Remove extra spaces
        $message = preg_replace('/\s+/', ' ', trim($message));
        
        return $message;
    }
}