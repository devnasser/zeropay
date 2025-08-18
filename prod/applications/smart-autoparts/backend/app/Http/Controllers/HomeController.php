<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Services\RecommendationService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request)
    {
        // Featured Categories
        $featuredCategories = Category::active()
            ->featured()
            ->main()
            ->ordered()
            ->take(8)
            ->get();

        // Featured Products
        $featuredProducts = Product::active()
            ->featured()
            ->inStock()
            ->with(['shop', 'category'])
            ->take(12)
            ->get();

        // New Arrivals
        $newArrivals = Product::active()
            ->where('is_new', true)
            ->inStock()
            ->with(['shop', 'category'])
            ->latest()
            ->take(8)
            ->get();

        // Best Sellers
        $bestSellers = Product::active()
            ->inStock()
            ->with(['shop', 'category'])
            ->orderBy('sales_count', 'desc')
            ->take(8)
            ->get();

        // Top Rated Shops
        $topShops = Shop::active()
            ->verified()
            ->orderBy('rating', 'desc')
            ->take(6)
            ->get();

        // Seasonal Recommendations
        $seasonalProducts = $this->recommendationService->getSeasonalRecommendations(8);

        // Personalized Recommendations for logged in users
        $personalizedProducts = null;
        if (auth()->check()) {
            $personalizedProducts = $this->recommendationService->getPersonalizedRecommendations(auth()->user(), 12);
        }

        // Statistics
        $stats = [
            'products_count' => Product::active()->count(),
            'shops_count' => Shop::active()->verified()->count(),
            'categories_count' => Category::active()->count(),
            'brands_count' => Product::active()->distinct('brand')->count('brand'),
        ];

        return view('home', compact(
            'featuredCategories',
            'featuredProducts',
            'newArrivals',
            'bestSellers',
            'topShops',
            'seasonalProducts',
            'personalizedProducts',
            'stats'
        ));
    }

    public function setLocale($locale)
    {
        if (in_array($locale, config('app.locales'))) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }

        return redirect()->back();
    }

    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }
}
