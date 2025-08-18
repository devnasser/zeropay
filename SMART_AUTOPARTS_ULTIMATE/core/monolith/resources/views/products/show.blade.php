@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-600 hover:text-primary">{{ __('الرئيسية') }}</a></li>
            <li class="text-gray-400">/</li>
            <li><a href="{{ route('categories.show', $product->category) }}" class="text-gray-600 hover:text-primary">{{ $product->category->name }}</a></li>
            <li class="text-gray-400">/</li>
            <li class="text-gray-900">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Product Images -->
        <div>
            <div class="mb-4">
                <img src="{{ $product->main_image }}" alt="{{ $product->name }}" 
                     class="w-full h-96 object-cover rounded-lg" id="mainImage">
            </div>
            @if($product->images && count($product->images) > 1)
            <div class="grid grid-cols-4 gap-2">
                @foreach($product->images as $image)
                <img src="{{ $image }}" alt="{{ $product->name }}" 
                     class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75"
                     onclick="document.getElementById('mainImage').src='{{ $image }}'">
                @endforeach
            </div>
            @endif
        </div>

        <!-- Product Info -->
        <div>
            <h1 class="text-3xl font-bold mb-2">{{ $product->name }}</h1>
            
            <!-- Rating -->
            <div class="flex items-center mb-4">
                <div class="flex text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $product->rating)
                            <i class="fas fa-star"></i>
                        @else
                            <i class="far fa-star"></i>
                        @endif
                    @endfor
                </div>
                <span class="ml-2 text-gray-600">({{ $product->reviews_count }} {{ __('تقييم') }})</span>
            </div>

            <!-- Price -->
            <div class="mb-6">
                @if($product->sale_price)
                <div class="flex items-center space-x-2 rtl:space-x-reverse">
                    <span class="text-3xl font-bold text-primary">{{ number_format($product->sale_price, 2) }} {{ __('ريال') }}</span>
                    <span class="text-xl text-gray-500 line-through">{{ number_format($product->price, 2) }} {{ __('ريال') }}</span>
                    <span class="bg-red-500 text-white px-2 py-1 rounded text-sm">{{ $product->discount_percentage }}% {{ __('خصم') }}</span>
                </div>
                @else
                <span class="text-3xl font-bold text-primary">{{ number_format($product->price, 2) }} {{ __('ريال') }}</span>
                @endif
            </div>

            <!-- Key Features -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-600">{{ __('الماركة') }}:</span>
                        <span class="font-semibold">{{ $product->brand }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">{{ __('الموديل') }}:</span>
                        <span class="font-semibold">{{ $product->model }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">{{ __('السنة') }}:</span>
                        <span class="font-semibold">{{ $product->year_from }} - {{ $product->year_to }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">{{ __('الحالة') }}:</span>
                        <span class="font-semibold">{{ __($product->condition) }}</span>
                    </div>
                    @if($product->warranty_months)
                    <div>
                        <span class="text-gray-600">{{ __('الضمان') }}:</span>
                        <span class="font-semibold">{{ $product->warranty_months }} {{ __('شهر') }}</span>
                    </div>
                    @endif
                    <div>
                        <span class="text-gray-600">{{ __('SKU') }}:</span>
                        <span class="font-semibold">{{ $product->sku }}</span>
                    </div>
                </div>
            </div>

            <!-- Add to Cart -->
            <form action="{{ route('cart.add') }}" method="POST" class="mb-6">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                
                <div class="flex items-center space-x-4 rtl:space-x-reverse mb-4">
                    <label class="text-gray-700">{{ __('الكمية') }}:</label>
                    <div class="flex items-center border rounded">
                        <button type="button" onclick="decrementQuantity()" class="px-3 py-2 hover:bg-gray-100">-</button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ $product->quantity }}" 
                               class="w-16 text-center border-x">
                        <button type="button" onclick="incrementQuantity()" class="px-3 py-2 hover:bg-gray-100">+</button>
                    </div>
                    <span class="text-sm text-gray-600">{{ $product->quantity }} {{ __('متوفر') }}</span>
                </div>

                <div class="flex space-x-4 rtl:space-x-reverse">
                    <button type="submit" class="flex-1 bg-primary text-white py-3 px-6 rounded-lg hover:bg-primary-dark transition">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        {{ __('أضف للسلة') }}
                    </button>
                    <button type="button" class="p-3 border rounded-lg hover:bg-gray-50">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </form>

            <!-- Shop Info -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <img src="{{ $product->shop->logo }}" alt="{{ $product->shop->name }}" 
                             class="w-12 h-12 rounded-full mr-3">
                        <div>
                            <h3 class="font-semibold">{{ $product->shop->name }}</h3>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                <span>{{ $product->shop->rating }}</span>
                                @if($product->shop->is_verified)
                                <i class="fas fa-check-circle text-green-500 ml-2"></i>
                                @endif
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('shops.show', $product->shop) }}" class="text-primary hover:underline">
                        {{ __('زيارة المتجر') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Tabs -->
    <div class="mt-12">
        <div class="border-b">
            <nav class="flex space-x-8 rtl:space-x-reverse">
                <button class="tab-button py-2 px-1 border-b-2 border-primary font-semibold" 
                        onclick="showTab('description')">{{ __('الوصف') }}</button>
                <button class="tab-button py-2 px-1 border-b-2 border-transparent" 
                        onclick="showTab('specifications')">{{ __('المواصفات') }}</button>
                <button class="tab-button py-2 px-1 border-b-2 border-transparent" 
                        onclick="showTab('reviews')">{{ __('التقييمات') }}</button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="mt-6">
            <!-- Description -->
            <div id="description-tab" class="tab-content">
                <div class="prose max-w-none">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>

            <!-- Specifications -->
            <div id="specifications-tab" class="tab-content hidden">
                @if($product->specifications)
                <table class="w-full">
                    @foreach($product->specifications as $key => $value)
                    <tr class="border-b">
                        <td class="py-2 font-semibold">{{ $key }}</td>
                        <td class="py-2">{{ $value }}</td>
                    </tr>
                    @endforeach
                </table>
                @endif
            </div>

            <!-- Reviews -->
            <div id="reviews-tab" class="tab-content hidden">
                @if($product->reviews->count() > 0)
                    @foreach($product->reviews as $review)
                    <div class="border-b pb-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <img src="{{ $review->user->avatar ?? '/images/default-avatar.png' }}" 
                                     alt="{{ $review->user->name }}" class="w-10 h-10 rounded-full mr-3">
                                <div>
                                    <h4 class="font-semibold">{{ $review->user->name }}</h4>
                                    <div class="flex text-yellow-400 text-sm">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $review->rating)
                                                <i class="fas fa-star"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <span class="text-sm text-gray-600">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-700">{{ $review->comment }}</p>
                    </div>
                    @endforeach
                @else
                    <p class="text-gray-600">{{ __('لا توجد تقييمات بعد') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
    <div class="mt-12">
        <h2 class="text-2xl font-bold mb-6">{{ __('منتجات ذات صلة') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($relatedProducts as $relatedProduct)
                <x-product-card :product="$relatedProduct" />
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
function incrementQuantity() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decrementQuantity() {
    const input = document.getElementById('quantity');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}

function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-primary', 'font-semibold');
        button.classList.add('border-transparent');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.remove('hidden');
    
    // Add active class to clicked button
    event.target.classList.remove('border-transparent');
    event.target.classList.add('border-primary', 'font-semibold');
}
</script>
@endsection