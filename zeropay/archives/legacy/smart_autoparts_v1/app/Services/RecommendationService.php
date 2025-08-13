<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RecommendationService
{
    /**
     * Get personalized recommendations for user
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 12): Collection
    {
        $cacheKey = "recommendations.user.{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user, $limit) {
            $recommendations = collect();
            
            // 1. Based on purchase history
            $recommendations = $recommendations->merge(
                $this->getBasedOnPurchaseHistory($user, ceil($limit * 0.3))
            );
            
            // 2. Based on browsing history
            $recommendations = $recommendations->merge(
                $this->getBasedOnBrowsingHistory($user, ceil($limit * 0.2))
            );
            
            // 3. Based on cart items
            $recommendations = $recommendations->merge(
                $this->getBasedOnCart($user, ceil($limit * 0.2))
            );
            
            // 4. Trending products
            $recommendations = $recommendations->merge(
                $this->getTrendingProducts(ceil($limit * 0.15))
            );
            
            // 5. New arrivals
            $recommendations = $recommendations->merge(
                $this->getNewArrivals(ceil($limit * 0.15))
            );
            
            return $recommendations->unique('id')->take($limit);
        });
    }
    
    /**
     * Get recommendations based on a specific product
     */
    public function getProductRecommendations(Product $product, int $limit = 8): Collection
    {
        $cacheKey = "recommendations.product.{$product->id}";
        
        return Cache::remember($cacheKey, 7200, function () use ($product, $limit) {
            $recommendations = collect();
            
            // 1. Same category products
            $recommendations = $recommendations->merge(
                $this->getSameCategoryProducts($product, ceil($limit * 0.3))
            );
            
            // 2. Same brand products
            $recommendations = $recommendations->merge(
                $this->getSameBrandProducts($product, ceil($limit * 0.2))
            );
            
            // 3. Frequently bought together
            $recommendations = $recommendations->merge(
                $this->getFrequentlyBoughtTogether($product, ceil($limit * 0.3))
            );
            
            // 4. Similar price range
            $recommendations = $recommendations->merge(
                $this->getSimilarPriceProducts($product, ceil($limit * 0.2))
            );
            
            return $recommendations->unique('id')->take($limit);
        });
    }
    
    /**
     * Get seasonal recommendations
     */
    public function getSeasonalRecommendations(int $limit = 12): Collection
    {
        $currentMonth = date('n');
        $season = $this->getCurrentSeason($currentMonth);
        
        return Cache::remember("recommendations.seasonal.{$season}", 14400, function () use ($season, $limit) {
            // Define seasonal products based on Saudi Arabia climate
            $seasonalCategories = [
                'summer' => ['air-conditioning', 'cooling-system', 'sun-shades'],
                'winter' => ['heating-system', 'wipers', 'fog-lights'],
                'spring' => ['air-filters', 'cleaning-products'],
                'fall' => ['battery', 'tires', 'maintenance-kits']
            ];
            
            return Product::active()
                ->whereHas('category', function ($query) use ($seasonalCategories, $season) {
                    $query->whereIn('slug', $seasonalCategories[$season] ?? []);
                })
                ->orderBy('sales_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get location-based recommendations
     */
    public function getLocationBasedRecommendations($latitude, $longitude, int $radius = 50, int $limit = 12): Collection
    {
        return Cache::remember("recommendations.location.{$latitude}.{$longitude}", 3600, function () use ($latitude, $longitude, $radius, $limit) {
            return Product::active()
                ->join('shops', 'products.shop_id', '=', 'shops.id')
                ->select('products.*')
                ->selectRaw("
                    (6371 * acos(cos(radians(?)) * cos(radians(shops.latitude)) * 
                    cos(radians(shops.longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(shops.latitude)))) AS distance
                ", [$latitude, $longitude, $latitude])
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get car-specific recommendations
     */
    public function getCarSpecificRecommendations(string $carMake, string $carModel, ?int $year = null, int $limit = 12): Collection
    {
        $cacheKey = "recommendations.car.{$carMake}.{$carModel}." . ($year ?? 'all');
        
        return Cache::remember($cacheKey, 7200, function () use ($carMake, $carModel, $year, $limit) {
            $query = Product::active()
                ->whereJsonContains('compatible_cars', [
                    'make' => $carMake,
                    'model' => $carModel
                ]);
            
            if ($year) {
                $query->where(function ($q) use ($year) {
                    $q->where('year_from', '<=', $year)
                      ->where('year_to', '>=', $year);
                });
            }
            
            return $query->orderBy('rating', 'desc')
                ->orderBy('sales_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get smart bundle recommendations
     */
    public function getBundleRecommendations(array $productIds, int $limit = 5): Collection
    {
        return Cache::remember("recommendations.bundle." . md5(implode(',', $productIds)), 3600, function () use ($productIds, $limit) {
            // Find products frequently bought together with the given products
            $bundles = DB::table('order_items as oi1')
                ->join('order_items as oi2', 'oi1.order_id', '=', 'oi2.order_id')
                ->join('products', 'oi2.product_id', '=', 'products.id')
                ->whereIn('oi1.product_id', $productIds)
                ->whereNotIn('oi2.product_id', $productIds)
                ->where('products.is_active', true)
                ->select('products.*', DB::raw('COUNT(DISTINCT oi1.order_id) as frequency'))
                ->groupBy('products.id')
                ->orderBy('frequency', 'desc')
                ->limit($limit)
                ->get();
            
            return collect($bundles)->map(function ($item) {
                return Product::hydrate([$item])->first();
            });
        });
    }
    
    /**
     * Get price drop recommendations
     */
    public function getPriceDropRecommendations(User $user, int $limit = 8): Collection
    {
        return Cache::remember("recommendations.price_drop.{$user->id}", 1800, function () use ($user, $limit) {
            // Get products from user's favorites and browsing history that have price drops
            return Product::active()
                ->where(function ($query) use ($user) {
                    $query->whereHas('favorites', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->orWhereIn('id', $this->getUserBrowsingHistory($user));
                })
                ->whereNotNull('compare_price')
                ->whereColumn('price', '<', 'compare_price')
                ->selectRaw('*, ((compare_price - price) / compare_price * 100) as discount_percentage')
                ->orderBy('discount_percentage', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get complementary product recommendations
     */
    public function getComplementaryProducts(Product $product, int $limit = 6): Collection
    {
        $complementaryMap = [
            'oil-filter' => ['engine-oil', 'air-filter'],
            'brake-pads' => ['brake-discs', 'brake-fluid'],
            'battery' => ['battery-terminals', 'battery-cleaner'],
            'tires' => ['wheel-alignment', 'tire-pressure-gauge'],
            'spark-plugs' => ['ignition-coils', 'engine-oil'],
        ];
        
        $categorySlug = $product->category->slug;
        $complementaryCategories = $complementaryMap[$categorySlug] ?? [];
        
        if (empty($complementaryCategories)) {
            return collect();
        }
        
        return Product::active()
            ->whereHas('category', function ($query) use ($complementaryCategories) {
                $query->whereIn('slug', $complementaryCategories);
            })
            ->where('id', '!=', $product->id)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get AI-powered smart recommendations using collaborative filtering
     */
    public function getSmartRecommendations(User $user, int $limit = 12): Collection
    {
        // Find similar users based on purchase patterns
        $similarUsers = $this->findSimilarUsers($user, 10);
        
        if ($similarUsers->isEmpty()) {
            return $this->getPersonalizedRecommendations($user, $limit);
        }
        
        // Get products bought by similar users but not by current user
        $userProducts = $user->orders()
            ->with('items.product')
            ->get()
            ->pluck('items')
            ->flatten()
            ->pluck('product_id')
            ->unique();
        
        return Product::active()
            ->whereHas('orderItems.order', function ($query) use ($similarUsers) {
                $query->whereIn('user_id', $similarUsers);
            })
            ->whereNotIn('id', $userProducts)
            ->select('products.*', DB::raw('COUNT(DISTINCT order_items.order_id) as popularity'))
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->groupBy('products.id')
            ->orderBy('popularity', 'desc')
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Private helper methods
     */
    private function getBasedOnPurchaseHistory(User $user, int $limit): Collection
    {
        $recentCategories = $user->orders()
            ->with('items.product.category')
            ->where('created_at', '>=', now()->subMonths(3))
            ->get()
            ->pluck('items')
            ->flatten()
            ->pluck('product.category_id')
            ->unique()
            ->take(5);
        
        return Product::active()
            ->whereIn('category_id', $recentCategories)
            ->whereNotIn('id', $this->getUserPurchasedProducts($user))
            ->orderBy('rating', 'desc')
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    private function getBasedOnBrowsingHistory(User $user, int $limit): Collection
    {
        // This would typically come from a user_product_views table
        // For now, we'll use a simplified approach
        return Product::active()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
    
    private function getBasedOnCart(User $user, int $limit): Collection
    {
        $cartProducts = $user->cart()->pluck('product_id');
        
        if ($cartProducts->isEmpty()) {
            return collect();
        }
        
        return $this->getBundleRecommendations($cartProducts->toArray(), $limit);
    }
    
    private function getTrendingProducts(int $limit): Collection
    {
        return Product::active()
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('views_count', 'desc')
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    private function getNewArrivals(int $limit): Collection
    {
        return Product::active()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('is_new', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    private function getSameCategoryProducts(Product $product, int $limit): Collection
    {
        return Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }
    
    private function getSameBrandProducts(Product $product, int $limit): Collection
    {
        if (!$product->brand) {
            return collect();
        }
        
        return Product::active()
            ->where('brand', $product->brand)
            ->where('id', '!=', $product->id)
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    private function getFrequentlyBoughtTogether(Product $product, int $limit): Collection
    {
        return $this->getBundleRecommendations([$product->id], $limit);
    }
    
    private function getSimilarPriceProducts(Product $product, int $limit): Collection
    {
        $priceRange = $product->price * 0.2; // 20% price range
        
        return Product::active()
            ->whereBetween('price', [$product->price - $priceRange, $product->price + $priceRange])
            ->where('id', '!=', $product->id)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }
    
    private function getCurrentSeason(int $month): string
    {
        // Saudi Arabia seasons
        if (in_array($month, [12, 1, 2])) {
            return 'winter';
        } elseif (in_array($month, [3, 4, 5])) {
            return 'spring';
        } elseif (in_array($month, [6, 7, 8, 9])) {
            return 'summer';
        } else {
            return 'fall';
        }
    }
    
    private function getUserBrowsingHistory(User $user): array
    {
        // Placeholder - would typically query user_product_views table
        return [];
    }
    
    private function getUserPurchasedProducts(User $user): array
    {
        return $user->orders()
            ->with('items')
            ->get()
            ->pluck('items')
            ->flatten()
            ->pluck('product_id')
            ->unique()
            ->toArray();
    }
    
    private function findSimilarUsers(User $user, int $limit): Collection
    {
        // Simple collaborative filtering based on common purchases
        $userProducts = $this->getUserPurchasedProducts($user);
        
        if (empty($userProducts)) {
            return collect();
        }
        
        return User::where('id', '!=', $user->id)
            ->whereHas('orders.items', function ($query) use ($userProducts) {
                $query->whereIn('product_id', $userProducts);
            })
            ->select('users.id', DB::raw('COUNT(DISTINCT order_items.product_id) as common_products'))
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('users.id')
            ->orderBy('common_products', 'desc')
            ->limit($limit)
            ->pluck('id');
    }
}