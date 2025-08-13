<?php

namespace App\GraphQL\DataLoaders;

use App\Models\Product;
use GraphQL\Deferred;
use Illuminate\Support\Collection;

class ProductLoader
{
    protected static $instance;
    protected $queue = [];
    protected $cache = [];
    
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Load product by ID with batching
     */
    public function load($id)
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }
        
        $this->queue[$id] = $id;
        
        return new Deferred(function () use ($id) {
            $this->loadMany();
            return $this->cache[$id] ?? null;
        });
    }
    
    /**
     * Load multiple products by IDs
     */
    public function loadMany()
    {
        if (empty($this->queue)) {
            return;
        }
        
        $ids = array_values($this->queue);
        $this->queue = [];
        
        // Batch load with optimized query
        $products = Product::with([
            'shop:id,name,slug,rating',
            'category:id,name,slug',
            'reviews' => function ($query) {
                $query->latest()->limit(5);
            }
        ])
        ->whereIn('id', $ids)
        ->get()
        ->keyBy('id');
        
        // Cache results
        foreach ($ids as $id) {
            $this->cache[$id] = $products->get($id);
        }
    }
    
    /**
     * Load products by shop with batching
     */
    public function loadByShop($shopId, $limit = 20)
    {
        $cacheKey = "shop_{$shopId}_products_{$limit}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        return new Deferred(function () use ($shopId, $limit, $cacheKey) {
            $products = Product::where('shop_id', $shopId)
                ->active()
                ->with(['category:id,name,slug'])
                ->latest()
                ->limit($limit)
                ->get();
                
            $this->cache[$cacheKey] = $products;
            return $products;
        });
    }
    
    /**
     * Load related products with intelligent batching
     */
    public function loadRelated($product, $limit = 6)
    {
        $cacheKey = "related_{$product->id}_{$limit}";
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        return new Deferred(function () use ($product, $limit, $cacheKey) {
            $related = Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->active()
                ->inStock()
                ->with(['shop:id,name,slug,rating'])
                ->orderBy('rating', 'desc')
                ->limit($limit)
                ->get();
                
            $this->cache[$cacheKey] = $related;
            return $related;
        });
    }
    
    /**
     * Clear loader cache
     */
    public function clearCache()
    {
        $this->cache = [];
        $this->queue = [];
    }
    
    /**
     * Warm cache with frequently accessed products
     */
    public function warmCache()
    {
        // Load featured products
        $featured = Product::featured()
            ->active()
            ->with(['shop', 'category'])
            ->limit(20)
            ->get();
            
        foreach ($featured as $product) {
            $this->cache[$product->id] = $product;
        }
        
        // Load best sellers
        $bestSellers = Product::select('products.*')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->groupBy('products.id')
            ->orderByRaw('COUNT(order_items.id) DESC')
            ->limit(20)
            ->get();
            
        foreach ($bestSellers as $product) {
            $this->cache[$product->id] = $product;
        }
    }
}