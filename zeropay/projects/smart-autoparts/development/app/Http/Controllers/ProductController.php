<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request)
    {
        $query = Product::active()->with(['shop', 'category']);

        // Category filter
        if ($request->has('category')) {
            $category = Category::where('slug', $request->category)->firstOrFail();
            $categoryIds = $category->getAllChildrenIds();
            $query->whereIn('category_id', $categoryIds);
        }

        // Brand filter
        if ($request->has('brand')) {
            $query->where('brand', $request->brand);
        }

        // Price range filter
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Condition filter
        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }

        // In stock filter
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        // Sorting
        switch ($request->get('sort', 'featured')) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->latest();
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'popular':
                $query->orderBy('sales_count', 'desc');
                break;
            default:
                $query->orderBy('is_featured', 'desc')->latest();
        }

        $products = $query->paginate(24);

        // Get filter data
        $categories = Category::active()->main()->ordered()->get();
        $brands = Product::active()->distinct('brand')->pluck('brand')->filter();
        $priceRange = [
            'min' => Product::active()->min('price') ?? 0,
            'max' => Product::active()->max('price') ?? 10000,
        ];

        return view('products.index', compact('products', 'categories', 'brands', 'priceRange'));
    }

    public function show(Product $product)
    {
        if (!$product->is_active) {
            abort(404);
        }

        // Increment views
        $product->incrementViews();

        // Load relationships
        $product->load(['shop', 'category', 'reviews.user']);

        // Get recommendations
        $relatedProducts = $this->recommendationService->getProductRecommendations($product, 8);
        $complementaryProducts = $this->recommendationService->getComplementaryProducts($product, 6);

        // Get breadcrumbs
        $breadcrumbs = $this->getBreadcrumbs($product);

        return view('products.show', compact('product', 'relatedProducts', 'complementaryProducts', 'breadcrumbs'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query) {
            return redirect()->route('products.index');
        }

        $products = Product::active()
            ->with(['shop', 'category'])
            ->where(function ($q) use ($query) {
                $q->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_EXTRACT(description, '$.ar') LIKE ?", ["%{$query}%"])
                  ->orWhereRaw("JSON_EXTRACT(description, '$.en') LIKE ?", ["%{$query}%"])
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('barcode', 'LIKE', "%{$query}%")
                  ->orWhere('brand', 'LIKE', "%{$query}%")
                  ->orWhere('model', 'LIKE', "%{$query}%");
            })
            ->paginate(24);

        return view('products.search', compact('products', 'query'));
    }

    private function getBreadcrumbs(Product $product)
    {
        $breadcrumbs = collect([
            ['name' => __('Home'), 'url' => route('home')],
            ['name' => __('Products'), 'url' => route('products.index')],
        ]);

        if ($product->category) {
            $category = $product->category;
            $categoryPath = collect();

            while ($category) {
                $categoryPath->prepend([
                    'name' => $category->name,
                    'url' => route('categories.show', $category)
                ]);
                $category = $category->parent;
            }

            $breadcrumbs = $breadcrumbs->merge($categoryPath);
        }

        $breadcrumbs->push(['name' => $product->name, 'url' => null]);

        return $breadcrumbs;
    }
}
