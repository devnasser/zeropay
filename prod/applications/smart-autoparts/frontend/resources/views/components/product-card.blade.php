<div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition group">
    <a href="{{ route('products.show', $product) }}">
        <div class="aspect-w-1 aspect-h-1 bg-gray-200 relative">
            @if($product->images && count($product->images) > 0)
                <img src="{{ $product->images[0] }}" alt="{{ $product->name }}" 
                     class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
            @else
                <div class="flex items-center justify-center h-full">
                    <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            @endif
            
            <!-- Badges -->
            <div class="absolute top-2 left-2 space-y-2">
                @if($product->is_featured)
                    <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded">{{ __('Featured') }}</span>
                @endif
                @if($product->is_new)
                    <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">{{ __('New') }}</span>
                @endif
                @if($product->discount_percentage > 0)
                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded">-{{ $product->discount_percentage }}%</span>
                @endif
            </div>
            
            <!-- Quick View Button -->
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition duration-300 flex items-center justify-center">
                <button class="bg-white text-gray-800 px-4 py-2 rounded-lg transform scale-0 group-hover:scale-100 transition duration-300">
                    {{ __('Quick View') }}
                </button>
            </div>
        </div>
    </a>
    
    <div class="p-4">
        <!-- Product Name -->
        <h3 class="font-semibold text-lg mb-2 line-clamp-2">
            <a href="{{ route('products.show', $product) }}" class="hover:text-blue-600">
                {{ $product->name }}
            </a>
        </h3>
        
        <!-- Brand & Model -->
        <p class="text-sm text-gray-600 mb-2">
            {{ $product->brand }} {{ $product->model ? '- ' . $product->model : '' }}
        </p>
        
        <!-- Rating -->
        <div class="flex items-center mb-2">
            <div class="flex text-yellow-400">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= $product->rating ? 'fill-current' : 'text-gray-300' }}" viewBox="0 0 20 20">
                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                    </svg>
                @endfor
            </div>
            <span class="ml-2 text-xs text-gray-600">({{ $product->reviews_count }})</span>
        </div>
        
        <!-- Price -->
        <div class="flex items-center justify-between mb-3">
            <div>
                @if($product->compare_price && $product->compare_price > $product->price)
                    <span class="text-gray-500 line-through text-sm">{{ number_format($product->compare_price, 2) }} {{ __('SAR') }}</span>
                @endif
                <span class="text-xl font-bold text-blue-600">{{ number_format($product->price, 2) }} {{ __('SAR') }}</span>
            </div>
            @if($product->quantity > 0)
                <span class="text-green-600 text-sm">{{ __('In Stock') }}</span>
            @else
                <span class="text-red-600 text-sm">{{ __('Out of Stock') }}</span>
            @endif
        </div>
        
        <!-- Action Buttons -->
        <div class="flex gap-2">
            @if($product->quantity > 0)
                <form action="{{ route('cart.add') }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                        {{ __('Add to Cart') }}
                    </button>
                </form>
            @else
                <button disabled class="flex-1 bg-gray-300 text-gray-500 py-2 px-4 rounded cursor-not-allowed">
                    {{ __('Out of Stock') }}
                </button>
            @endif
            
            @auth
                <button onclick="toggleFavorite({{ $product->id }})" class="p-2 border border-gray-300 rounded hover:bg-gray-100">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </button>
            @endauth
        </div>
        
        <!-- Shop Info -->
        <div class="mt-3 pt-3 border-t border-gray-200">
            <a href="{{ route('shops.show', $product->shop) }}" class="text-sm text-gray-600 hover:text-blue-600">
                {{ __('Sold by') }}: {{ $product->shop->name }}
            </a>
        </div>
    </div>
</div>