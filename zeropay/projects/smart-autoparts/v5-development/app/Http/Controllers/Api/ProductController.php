<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RedisCacheService;
use App\Services\SecurityService;
use App\Services\PerformanceMonitorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    protected $cache;
    protected $security;
    protected $monitor;
    
    public function __construct(
        RedisCacheService $cache,
        SecurityService $security,
        PerformanceMonitorService $monitor
    ) {
        $this->cache = $cache;
        $this->security = $security;
        $this->monitor = $monitor;
        
        $this->middleware('rate.limit:api');
    }
    
    /**
     * Get all products with advanced filtering
     */
    public function index(Request $request): JsonResponse
    {
        $timerId = $this->monitor->startTimer('products_index');
        
        // Sanitize inputs
        $filters = $this->security->sanitizeInput($request->all(), [
            'search' => 'alphanumeric',
            'category_id' => 'int',
            'shop_id' => 'int',
            'min_price' => 'float',
            'max_price' => 'float',
            'brand' => 'alphanumeric',
            'sort' => 'alpha',
            'per_page' => 'int'
        ]);
        
        $cacheKey = 'products:' . md5(json_encode($filters));
        
        $result = $this->cache->remember($cacheKey, function() use ($filters) {
            $query = Product::query()
                ->where('is_active', true)
                ->with(['shop:id,name,slug', 'category:id,name']);
            
            // Apply filters
            if (!empty($filters['search'])) {
                $query->where(function($q) use ($filters) {
                    $q->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$filters['search']}%"])
                      ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$filters['search']}%"])
                      ->orWhere('sku', 'LIKE', "%{$filters['search']}%");
                });
            }
            
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }
            
            if (!empty($filters['shop_id'])) {
                $query->where('shop_id', $filters['shop_id']);
            }
            
            if (!empty($filters['min_price'])) {
                $query->where('price', '>=', $filters['min_price']);
            }
            
            if (!empty($filters['max_price'])) {
                $query->where('price', '<=', $filters['max_price']);
            }
            
            if (!empty($filters['brand'])) {
                $query->where('brand', $filters['brand']);
            }
            
            // Apply sorting
            $sortField = $filters['sort'] ?? 'created_at';
            $sortDirection = 'desc';
            
            switch ($sortField) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('views_count', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('rating', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
            
            // Select only needed columns
            $query->select([
                'id', 'name', 'slug', 'price', 'sale_price', 
                'images', 'rating', 'brand', 'model', 'year',
                'quantity', 'shop_id', 'category_id', 'created_at'
            ]);
            
            return $query->paginate($filters['per_page'] ?? 20);
        }, 600, ['products']);
        
        $duration = $this->monitor->endTimer($timerId);
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'meta' => [
                'execution_time' => $duration . 'ms',
                'cached' => $duration < 10
            ]
        ]);
    }
    
    /**
     * Get single product details
     */
    public function show($id): JsonResponse
    {
        $timerId = $this->monitor->startTimer('product_show');
        
        $product = $this->cache->remember("product:{$id}", function() use ($id) {
            return Product::with([
                'shop:id,name,slug,logo,rating',
                'category:id,name,slug',
                'reviews' => function($query) {
                    $query->latest()->limit(10)
                        ->with('user:id,name');
                }
            ])->findOrFail($id);
        }, 3600, ['products']);
        
        // Increment view counter asynchronously
        dispatch(function() use ($id) {
            Product::where('id', $id)->increment('views_count');
        })->afterResponse();
        
        $duration = $this->monitor->endTimer($timerId);
        
        return response()->json([
            'success' => true,
            'data' => $product,
            'meta' => [
                'execution_time' => $duration . 'ms'
            ]
        ]);
    }
    
    /**
     * Get similar products
     */
    public function similar($id): JsonResponse
    {
        $product = Product::select('category_id', 'brand', 'price')->find($id);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
        
        $similar = $this->cache->remember("similar:{$id}", function() use ($product, $id) {
            return Product::where('id', '!=', $id)
                ->where('is_active', true)
                ->where(function($query) use ($product) {
                    $query->where('category_id', $product->category_id)
                          ->orWhere('brand', $product->brand);
                })
                ->whereBetween('price', [
                    $product->price * 0.7,
                    $product->price * 1.3
                ])
                ->select(['id', 'name', 'slug', 'price', 'images', 'rating'])
                ->with('shop:id,name')
                ->limit(12)
                ->get();
        }, 1800, ['products']);
        
        return response()->json([
            'success' => true,
            'data' => $similar
        ]);
    }
    
    /**
     * Search products with auto-complete
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $query = $this->security->sanitizeInput(['q' => $request->q], ['q' => 'alphanumeric'])['q'];
        
        $suggestions = $this->cache->remember("search:{$query}", function() use ($query) {
            return Product::where('is_active', true)
                ->where(function($q) use ($query) {
                    $q->whereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$query}%"])
                      ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$query}%"])
                      ->orWhere('brand', 'LIKE', "{$query}%")
                      ->orWhere('model', 'LIKE', "{$query}%");
                })
                ->select(['id', 'name', 'brand', 'model', 'images'])
                ->limit(10)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'text' => $product->name['ar'] ?? $product->name['en'],
                        'brand' => $product->brand,
                        'model' => $product->model,
                        'image' => $product->images[0] ?? null
                    ];
                });
        }, 300, ['search']);
        
        return response()->json([
            'success' => true,
            'data' => $suggestions
        ]);
    }
    
    /**
     * Get featured products
     */
    public function featured(): JsonResponse
    {
        $featured = $this->cache->remember('featured_products', function() {
            return Product::where('is_featured', true)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->select(['id', 'name', 'slug', 'price', 'sale_price', 'images', 'rating'])
                ->with('shop:id,name,slug')
                ->orderBy('priority', 'desc')
                ->limit(20)
                ->get();
        }, 1800, ['products']);
        
        return response()->json([
            'success' => true,
            'data' => $featured
        ]);
    }
    
    /**
     * Get deals and offers
     */
    public function deals(): JsonResponse
    {
        $deals = $this->cache->remember('product_deals', function() {
            return Product::where('is_active', true)
                ->whereNotNull('sale_price')
                ->where('sale_price', '>', 0)
                ->whereRaw('sale_price < price')
                ->select([
                    'id', 'name', 'slug', 'price', 'sale_price', 
                    'images', 'rating',
                    DB::raw('ROUND((price - sale_price) / price * 100) as discount_percentage')
                ])
                ->with('shop:id,name')
                ->orderBy('discount_percentage', 'desc')
                ->limit(20)
                ->get();
        }, 600, ['products', 'deals']);
        
        return response()->json([
            'success' => true,
            'data' => $deals
        ]);
    }
}