@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">{{ __('Shopping Cart') }}</h1>
    
    @if($cartItems->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2 space-y-4">
                @foreach($cartItems->groupBy('product.shop_id') as $shopId => $items)
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold text-lg mb-4">
                            {{ $items->first()->product->shop->name }}
                        </h3>
                        
                        @foreach($items as $item)
                            <div class="flex items-center py-4 border-b last:border-0" data-cart-item="{{ $item->id }}">
                                <img src="{{ $item->product->main_image }}" 
                                     alt="{{ $item->product->name }}"
                                     class="w-20 h-20 object-cover rounded">
                                
                                <div class="flex-1 mx-4">
                                    <h4 class="font-medium">{{ $item->product->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $item->product->brand }} - {{ $item->product->model }}</p>
                                    <p class="text-sm text-gray-500">SKU: {{ $item->product->sku }}</p>
                                </div>
                                
                                <div class="flex items-center space-x-4">
                                    <!-- Quantity -->
                                    <div class="flex items-center border rounded">
                                        <button onclick="updateQuantity({{ $item->id }}, -1)" 
                                                class="px-3 py-1 hover:bg-gray-100">-</button>
                                        <input type="number" 
                                               value="{{ $item->quantity }}" 
                                               min="1" 
                                               max="{{ $item->product->quantity }}"
                                               onchange="updateQuantity({{ $item->id }}, this.value, true)"
                                               class="w-16 text-center border-x">
                                        <button onclick="updateQuantity({{ $item->id }}, 1)" 
                                                class="px-3 py-1 hover:bg-gray-100">+</button>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div class="text-right">
                                        <p class="font-semibold">{{ number_format($item->price * $item->quantity, 2) }} {{ __('SAR') }}</p>
                                        @if($item->product->sale_price)
                                            <p class="text-sm text-gray-500 line-through">
                                                {{ number_format($item->product->price * $item->quantity, 2) }} {{ __('SAR') }}
                                            </p>
                                        @endif
                                    </div>
                                    
                                    <!-- Remove -->
                                    <button onclick="removeFromCart({{ $item->id }})" 
                                            class="text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            
            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h3 class="font-semibold text-lg mb-4">{{ __('Order Summary') }}</h3>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between">
                            <span>{{ __('Subtotal') }}</span>
                            <span>{{ number_format($subtotal, 2) }} {{ __('SAR') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ __('Tax') }} (15%)</span>
                            <span>{{ number_format($tax, 2) }} {{ __('SAR') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>{{ __('Shipping') }}</span>
                            <span class="text-green-600">{{ __('Calculated at checkout') }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between font-semibold text-lg">
                            <span>{{ __('Total') }}</span>
                            <span>{{ number_format($total, 2) }} {{ __('SAR') }}</span>
                        </div>
                    </div>
                    
                    @auth
                        <a href="{{ route('checkout') }}" 
                           class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 transition">
                            {{ __('Proceed to Checkout') }}
                        </a>
                    @else
                        <p class="text-sm text-gray-600 mb-4">{{ __('Please login to checkout') }}</p>
                        <a href="{{ route('login') }}" 
                           class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 transition">
                            {{ __('Login to Continue') }}
                        </a>
                    @endauth
                    
                    <a href="{{ route('products.index') }}" 
                       class="block text-center mt-4 text-blue-600 hover:underline">
                        {{ __('Continue Shopping') }}
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-16">
            <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h2 class="text-2xl font-semibold mb-2">{{ __('Your cart is empty') }}</h2>
            <p class="text-gray-600 mb-8">{{ __('Add some products to get started') }}</p>
            <a href="{{ route('products.index') }}" 
               class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                {{ __('Start Shopping') }}
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
function updateQuantity(itemId, change, absolute = false) {
    const input = document.querySelector(`[data-cart-item="${itemId}"] input[type="number"]`);
    let newQuantity = absolute ? parseInt(change) : parseInt(input.value) + change;
    
    if (newQuantity < 1) return;
    if (newQuantity > parseInt(input.max)) {
        alert('{{ __("Not enough stock available") }}');
        return;
    }
    
    fetch(`/cart/${itemId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ quantity: newQuantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || '{{ __("Error updating cart") }}');
        }
    });
}

function removeFromCart(itemId) {
    if (!confirm('{{ __("Remove this item from cart?") }}')) return;
    
    fetch(`/cart/${itemId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || '{{ __("Error removing item") }}');
        }
    });
}
</script>
@endpush
@endsection