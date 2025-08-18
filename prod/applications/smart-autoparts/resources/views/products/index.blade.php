@extends('layouts.app')

@section('title', __('Products'))

@section('content')
<div class="bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('All Products') }}</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">{{ $products->total() }} {{ __('Products') }}</span>
                <select onchange="window.location.href = updateQueryString('sort', this.value)" 
                        class="border-gray-300 rounded-md">
                    <option value="featured" {{ request('sort') == 'featured' ? 'selected' : '' }}>{{ __('Featured') }}</option>
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>{{ __('Popular') }}</option>
                    <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>{{ __('Top Rated') }}</option>
                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>{{ __('Price: Low to High') }}</option>
                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>{{ __('Price: High to Low') }}</option>
                </select>
            </div>
        </div>

        <div class="flex gap-8">
            <!-- Filters Sidebar -->
            <aside class="w-64 flex-shrink-0">
                <form id="filterForm" method="GET">
                    <!-- Categories -->
                    <div class="mb-8">
                        <h3 class="font-semibold mb-4">{{ __('Categories') }}</h3>
                        <ul class="space-y-2">
                            @foreach($categories as $category)
                                <li>
                                    <label class="flex items-center">
                                        <input type="radio" name="category" value="{{ $category->slug }}"
                                               {{ request('category') == $category->slug ? 'checked' : '' }}
                                               onchange="document.getElementById('filterForm').submit()">
                                        <span class="ml-2">{{ $category->name }}</span>
                                        <span class="ml-auto text-gray-500 text-sm">({{ $category->products_count }})</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Brands -->
                    @if($brands->count() > 0)
                        <div class="mb-8">
                            <h3 class="font-semibold mb-4">{{ __('Brands') }}</h3>
                            <select name="brand" class="w-full border-gray-300 rounded-md" 
                                    onchange="document.getElementById('filterForm').submit()">
                                <option value="">{{ __('All Brands') }}</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>
                                        {{ $brand }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Price Range -->
                    <div class="mb-8">
                        <h3 class="font-semibold mb-4">{{ __('Price Range') }}</h3>
                        <div class="space-y-4">
                            <input type="number" name="min_price" placeholder="{{ __('Min') }}" 
                                   value="{{ request('min_price') }}"
                                   class="w-full border-gray-300 rounded-md">
                            <input type="number" name="max_price" placeholder="{{ __('Max') }}" 
                                   value="{{ request('max_price') }}"
                                   class="w-full border-gray-300 rounded-md">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
                                {{ __('Apply') }}
                            </button>
                        </div>
                    </div>

                    <!-- Other Filters -->
                    <div class="mb-8">
                        <h3 class="font-semibold mb-4">{{ __('Other Filters') }}</h3>
                        <label class="flex items-center mb-2">
                            <input type="checkbox" name="in_stock" value="1" 
                                   {{ request('in_stock') ? 'checked' : '' }}
                                   onchange="document.getElementById('filterForm').submit()">
                            <span class="ml-2">{{ __('In Stock Only') }}</span>
                        </label>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['category', 'brand', 'min_price', 'max_price', 'in_stock']))
                        <a href="{{ route('products.index') }}" class="text-blue-600 hover:underline">
                            {{ __('Clear All Filters') }}
                        </a>
                    @endif
                </form>
            </aside>

            <!-- Products Grid -->
            <div class="flex-1">
                @if($products->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach($products as $product)
                            @include('components.product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $products->withQueryString()->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No products found') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Try adjusting your filters') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function updateQueryString(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    return url.toString();
}
</script>
@endsection