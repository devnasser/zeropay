@extends('layouts.app')

@section('title', __('Home'))

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">{{ __('Find Quality Auto Parts') }}</h1>
            <p class="text-xl mb-8">{{ __('Largest selection of genuine and aftermarket parts in Saudi Arabia') }}</p>
            
            <!-- Search Bar -->
            <form action="{{ route('products.search') }}" method="GET" class="max-w-2xl mx-auto">
                <div class="flex bg-white rounded-lg overflow-hidden shadow-xl">
                    <input type="text" name="q" placeholder="{{ __('Search by part name, number, or car model...') }}" 
                           class="flex-1 px-6 py-4 text-gray-700 focus:outline-none">
                    <button type="submit" class="bg-yellow-500 px-8 py-4 hover:bg-yellow-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Statistics -->
<section class="py-8 bg-gray-100">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-blue-600">{{ number_format($stats['products_count']) }}</div>
                <div class="text-gray-600">{{ __('Products') }}</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-green-600">{{ number_format($stats['shops_count']) }}</div>
                <div class="text-gray-600">{{ __('Verified Shops') }}</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($stats['categories_count']) }}</div>
                <div class="text-gray-600">{{ __('Categories') }}</div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-orange-600">{{ number_format($stats['brands_count']) }}</div>
                <div class="text-gray-600">{{ __('Brands') }}</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Categories -->
<section class="py-16">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold mb-8 text-center">{{ __('Shop by Category') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($featuredCategories as $category)
                <a href="{{ route('categories.show', $category) }}" class="group">
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                        <div class="aspect-w-1 aspect-h-1 bg-gray-200">
                            @if($category->image)
                                <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-full h-full object-cover group-hover:scale-110 transition">
                            @else
                                <div class="flex items-center justify-center h-full">
                                    <i class="{{ $category->icon ?? 'fas fa-cog' }} text-4xl text-gray-400"></i>
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg">{{ $category->name }}</h3>
                            <p class="text-sm text-gray-600">{{ $category->products_count }} {{ __('Products') }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold mb-8 text-center">{{ __('Featured Products') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($featuredProducts as $product)
                @include('components.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>

<!-- Personalized Recommendations (for logged in users) -->
@auth
    @if($personalizedProducts && $personalizedProducts->count() > 0)
        <section class="py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold mb-8 text-center">{{ __('Recommended for You') }}</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach($personalizedProducts as $product)
                        @include('components.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endauth

<!-- New Arrivals -->
<section class="py-16">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">{{ __('New Arrivals') }}</h2>
            <a href="{{ route('products.index', ['sort' => 'newest']) }}" class="text-blue-600 hover:underline">
                {{ __('View All') }} →
            </a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($newArrivals as $product)
                @include('components.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>

<!-- Top Shops -->
<section class="py-16 bg-gray-100">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold mb-8 text-center">{{ __('Top Rated Shops') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($topShops as $shop)
                <a href="{{ route('shops.show', $shop) }}" class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                    <div class="h-48 bg-gray-200">
                        @if($shop->cover_image)
                            <img src="{{ $shop->cover_image }}" alt="{{ $shop->name }}" class="w-full h-full object-cover">
                        @endif
                    </div>
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-xl mb-2">{{ $shop->name }}</h3>
                                <p class="text-gray-600 text-sm mb-2">{{ $shop->products_count }} {{ __('Products') }}</p>
                                <div class="flex items-center">
                                    <div class="flex text-yellow-400">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= $shop->rating ? 'fill-current' : 'text-gray-300' }}" viewBox="0 0 20 20">
                                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                    <span class="ml-2 text-sm text-gray-600">({{ $shop->reviews_count }})</span>
                                </div>
                            </div>
                            @if($shop->is_verified)
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                                    {{ __('Verified') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-blue-600 text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-4">{{ __('Become a Seller') }}</h2>
        <p class="text-xl mb-8">{{ __('Join thousands of shops selling on our platform') }}</p>
        <a href="{{ route('register') }}?type=shop_owner" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
            {{ __('Start Selling') }}
        </a>
    </div>
</section>
@endsection