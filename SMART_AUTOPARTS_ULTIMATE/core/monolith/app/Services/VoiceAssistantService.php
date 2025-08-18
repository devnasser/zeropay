<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class VoiceAssistantService
{
    protected $commands;
    protected $arabicNumbers = [
        'واحد' => 1, 'اثنين' => 2, 'ثلاثة' => 3, 'أربعة' => 4, 'خمسة' => 5,
        'ستة' => 6, 'سبعة' => 7, 'ثمانية' => 8, 'تسعة' => 9, 'عشرة' => 10
    ];

    public function __construct()
    {
        $this->commands = [
            'search' => ['ابحث', 'بحث', 'اريد', 'أريد', 'اعطني', 'أعطني', 'وين', 'أين'],
            'cart' => ['سلة', 'عربة', 'مشتريات', 'طلب'],
            'add' => ['اضف', 'أضف', 'ضيف', 'حط', 'ضع'],
            'remove' => ['احذف', 'أحذف', 'شيل', 'ازل', 'أزل'],
            'checkout' => ['ادفع', 'أدفع', 'اكمل', 'أكمل', 'اتمم', 'أتمم'],
            'help' => ['مساعدة', 'ساعد', 'ساعدني', 'help'],
        ];
    }

    public function processCommand(string $transcript, $user = null)
    {
        $transcript = $this->normalizeArabic($transcript);
        $words = explode(' ', $transcript);
        
        // Detect command type
        $commandType = $this->detectCommandType($words);
        
        switch ($commandType) {
            case 'search':
                return $this->handleSearch($transcript, $words);
            
            case 'add_to_cart':
                return $this->handleAddToCart($transcript, $words, $user);
            
            case 'view_cart':
                return $this->handleViewCart($user);
            
            case 'checkout':
                return $this->handleCheckout($user);
            
            case 'help':
                return $this->handleHelp();
            
            default:
                return $this->handleUnknownCommand($transcript);
        }
    }

    protected function detectCommandType($words)
    {
        foreach ($words as $word) {
            if (in_array($word, $this->commands['search'])) {
                return 'search';
            }
            if (in_array($word, $this->commands['add'])) {
                return 'add_to_cart';
            }
            if (in_array($word, $this->commands['cart'])) {
                return 'view_cart';
            }
            if (in_array($word, $this->commands['checkout'])) {
                return 'checkout';
            }
            if (in_array($word, $this->commands['help'])) {
                return 'help';
            }
        }
        
        return 'unknown';
    }

    protected function handleSearch($transcript, $words)
    {
        // Extract search terms
        $searchTerms = $this->extractSearchTerms($transcript);
        
        if (empty($searchTerms)) {
            return [
                'type' => 'error',
                'message' => 'لم أفهم ما تبحث عنه. حاول مرة أخرى',
                'speak' => 'لم أفهم ما تبحث عنه. حاول مرة أخرى'
            ];
        }
        
        // Search products
        $products = Product::active()
            ->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$term}%"])
                          ->orWhereRaw("JSON_EXTRACT(description, '$.ar') LIKE ?", ["%{$term}%"])
                          ->orWhere('brand', 'LIKE', "%{$term}%")
                          ->orWhere('model', 'LIKE', "%{$term}%");
                }
            })
            ->limit(5)
            ->get();
        
        if ($products->isEmpty()) {
            return [
                'type' => 'no_results',
                'message' => 'لم أجد منتجات مطابقة',
                'speak' => 'عذراً، لم أجد أي منتجات تطابق بحثك'
            ];
        }
        
        $speakText = "وجدت {$products->count()} منتج. ";
        foreach ($products->take(3) as $index => $product) {
            $speakText .= "منتج رقم " . ($index + 1) . ": {$product->name} بسعر {$product->price} ريال. ";
        }
        
        return [
            'type' => 'search_results',
            'products' => $products,
            'message' => "وجدت {$products->count()} منتج",
            'speak' => $speakText,
            'actions' => [
                'view_products' => true,
                'add_to_cart' => true
            ]
        ];
    }

    protected function handleAddToCart($transcript, $words, $user)
    {
        // Extract quantity
        $quantity = $this->extractQuantity($words);
        
        // Extract product reference
        $productRef = $this->extractProductReference($transcript);
        
        if (!$productRef) {
            return [
                'type' => 'clarification_needed',
                'message' => 'أي منتج تريد إضافته؟',
                'speak' => 'أي منتج تريد إضافته إلى السلة؟',
                'context' => 'add_to_cart'
            ];
        }
        
        // Find product
        $product = $this->findProductByReference($productRef);
        
        if (!$product) {
            return [
                'type' => 'error',
                'message' => 'لم أجد المنتج المطلوب',
                'speak' => 'عذراً، لم أتمكن من العثور على المنتج'
            ];
        }
        
        // Add to cart logic would go here
        
        return [
            'type' => 'cart_added',
            'product' => $product,
            'quantity' => $quantity,
            'message' => "تم إضافة {$quantity} من {$product->name} إلى السلة",
            'speak' => "تم إضافة {$quantity} من {$product->name} إلى سلة المشتريات",
            'actions' => [
                'view_cart' => true,
                'continue_shopping' => true,
                'checkout' => true
            ]
        ];
    }

    protected function handleViewCart($user)
    {
        if (!$user) {
            return [
                'type' => 'auth_required',
                'message' => 'يجب تسجيل الدخول أولاً',
                'speak' => 'يجب عليك تسجيل الدخول لعرض سلة المشتريات'
            ];
        }
        
        $cartItems = $user->cart()->with('product')->get();
        
        if ($cartItems->isEmpty()) {
            return [
                'type' => 'empty_cart',
                'message' => 'سلة المشتريات فارغة',
                'speak' => 'سلة المشتريات فارغة. هل تريد البحث عن منتجات؟'
            ];
        }
        
        $total = $cartItems->sum(fn($item) => $item->quantity * $item->price);
        $speakText = "لديك {$cartItems->count()} منتج في السلة. ";
        
        foreach ($cartItems as $item) {
            $speakText .= "{$item->quantity} من {$item->product->name}. ";
        }
        
        $speakText .= "المجموع {$total} ريال.";
        
        return [
            'type' => 'cart_view',
            'items' => $cartItems,
            'total' => $total,
            'message' => "عدد المنتجات: {$cartItems->count()}",
            'speak' => $speakText,
            'actions' => [
                'checkout' => true,
                'clear_cart' => true,
                'continue_shopping' => true
            ]
        ];
    }

    protected function handleCheckout($user)
    {
        if (!$user) {
            return [
                'type' => 'auth_required',
                'message' => 'يجب تسجيل الدخول أولاً',
                'speak' => 'يجب عليك تسجيل الدخول لإكمال الطلب'
            ];
        }
        
        return [
            'type' => 'checkout_start',
            'message' => 'جاري تحويلك لصفحة الدفع',
            'speak' => 'جيد. سأحولك الآن لصفحة إكمال الطلب والدفع',
            'redirect' => route('checkout.index')
        ];
    }

    protected function handleHelp()
    {
        $helpText = "يمكنك استخدام الأوامر التالية: ";
        $helpText .= "للبحث: قل 'ابحث عن' متبوعاً باسم المنتج. ";
        $helpText .= "لإضافة منتج: قل 'أضف' متبوعاً باسم أو رقم المنتج. ";
        $helpText .= "لعرض السلة: قل 'اعرض السلة' أو 'سلة المشتريات'. ";
        $helpText .= "للدفع: قل 'أكمل الطلب' أو 'ادفع'. ";
        
        return [
            'type' => 'help',
            'message' => 'قائمة الأوامر الصوتية',
            'speak' => $helpText,
            'commands' => [
                'بحث' => 'ابحث عن [اسم المنتج]',
                'إضافة' => 'أضف [المنتج] إلى السلة',
                'عرض' => 'اعرض السلة',
                'دفع' => 'أكمل الطلب'
            ]
        ];
    }

    protected function handleUnknownCommand($transcript)
    {
        // Try to understand intent using keywords
        $suggestions = $this->getSuggestions($transcript);
        
        return [
            'type' => 'unknown',
            'message' => 'لم أفهم طلبك',
            'speak' => 'عذراً، لم أفهم طلبك. هل تريد البحث عن منتج أو عرض السلة؟',
            'suggestions' => $suggestions,
            'original_transcript' => $transcript
        ];
    }

    protected function normalizeArabic($text)
    {
        // Normalize Arabic text
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        $text = str_replace('ة', 'ه', $text);
        $text = str_replace('ى', 'ي', $text);
        $text = trim($text);
        
        return $text;
    }

    protected function extractSearchTerms($transcript)
    {
        // Remove command words
        foreach ($this->commands['search'] as $cmd) {
            $transcript = str_replace($cmd, '', $transcript);
        }
        
        // Remove common words
        $stopWords = ['في', 'من', 'عن', 'على', 'إلى', 'ال'];
        foreach ($stopWords as $word) {
            $transcript = str_replace($word, '', $transcript);
        }
        
        // Extract meaningful terms
        $terms = array_filter(explode(' ', trim($transcript)));
        
        return $terms;
    }

    protected function extractQuantity($words)
    {
        foreach ($words as $word) {
            if (is_numeric($word)) {
                return (int) $word;
            }
            if (isset($this->arabicNumbers[$word])) {
                return $this->arabicNumbers[$word];
            }
        }
        
        return 1; // Default quantity
    }

    protected function extractProductReference($transcript)
    {
        // Look for product number pattern
        if (preg_match('/منتج\s*رقم\s*(\d+)/', $transcript, $matches)) {
            return ['type' => 'number', 'value' => $matches[1]];
        }
        
        // Look for product name
        $searchTerms = $this->extractSearchTerms($transcript);
        if (!empty($searchTerms)) {
            return ['type' => 'name', 'value' => implode(' ', $searchTerms)];
        }
        
        return null;
    }

    protected function findProductByReference($reference)
    {
        if ($reference['type'] === 'number') {
            // Assuming this refers to a product in recent search results
            // In real implementation, would check session/cache
            return Product::find($reference['value']);
        }
        
        // Search by name
        return Product::active()
            ->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$reference['value']}%"])
            ->first();
    }

    protected function getSuggestions($transcript)
    {
        $suggestions = [];
        
        // Check if it seems like a search query
        if (strpos($transcript, 'فلتر') !== false || strpos($transcript, 'زيت') !== false) {
            $suggestions[] = 'ابحث عن ' . $transcript;
        }
        
        // Check if it mentions cart
        if (strpos($transcript, 'طلب') !== false || strpos($transcript, 'شراء') !== false) {
            $suggestions[] = 'اعرض السلة';
            $suggestions[] = 'أكمل الطلب';
        }
        
        return $suggestions;
    }
}