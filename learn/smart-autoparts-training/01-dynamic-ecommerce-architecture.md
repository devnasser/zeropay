# Dynamic E-commerce Architecture for Auto Parts

## 1. Dynamic Product Catalog System

### Flexible Product Schema
```php
// Dynamic Product Attributes System
class DynamicProductSchema
{
    protected array $baseAttributes = [
        'sku', 'name', 'price', 'quantity', 'brand'
    ];
    
    protected array $categorySpecificAttributes = [];
    protected array $validationRules = [];
    
    public function defineAttribute(string $name, array $config): self
    {
        $this->categorySpecificAttributes[$name] = [
            'type' => $config['type'] ?? 'string',
            'required' => $config['required'] ?? false,
            'searchable' => $config['searchable'] ?? true,
            'filterable' => $config['filterable'] ?? true,
            'validation' => $config['validation'] ?? null,
            'options' => $config['options'] ?? null, // for select/multi-select
            'unit' => $config['unit'] ?? null, // e.g., mm, kg, pieces
            'display_format' => $config['display_format'] ?? null
        ];
        
        return $this;
    }
    
    public function getSchemaForCategory(string $categorySlug): array
    {
        // Base attributes + category specific
        return match($categorySlug) {
            'engine-parts' => array_merge($this->baseAttributes, [
                'engine_type' => ['type' => 'select', 'options' => ['petrol', 'diesel', 'hybrid']],
                'displacement' => ['type' => 'number', 'unit' => 'cc'],
                'power_output' => ['type' => 'number', 'unit' => 'hp'],
                'torque' => ['type' => 'number', 'unit' => 'nm'],
                'compatibility_years' => ['type' => 'range', 'format' => 'year']
            ]),
            
            'tires' => array_merge($this->baseAttributes, [
                'width' => ['type' => 'number', 'unit' => 'mm', 'required' => true],
                'aspect_ratio' => ['type' => 'number', 'required' => true],
                'rim_diameter' => ['type' => 'number', 'unit' => 'inch', 'required' => true],
                'load_index' => ['type' => 'number'],
                'speed_rating' => ['type' => 'select', 'options' => ['H', 'V', 'W', 'Y']],
                'season' => ['type' => 'select', 'options' => ['summer', 'winter', 'all-season']]
            ]),
            
            'batteries' => array_merge($this->baseAttributes, [
                'voltage' => ['type' => 'number', 'unit' => 'V', 'required' => true],
                'capacity' => ['type' => 'number', 'unit' => 'Ah', 'required' => true],
                'cca' => ['type' => 'number', 'unit' => 'A'], // Cold Cranking Amps
                'dimensions' => ['type' => 'object', 'properties' => ['length', 'width', 'height']],
                'terminal_type' => ['type' => 'select', 'options' => ['top', 'side']]
            ]),
            
            default => $this->baseAttributes
        };
    }
}

// Dynamic Product Model
class DynamicProduct extends Model
{
    protected $casts = [
        'attributes' => 'array',
        'specifications' => 'array',
        'compatibility' => 'array',
        'images' => 'array'
    ];
    
    public function getDynamicAttribute(string $key)
    {
        return data_get($this->attributes, $key) ?? 
               data_get($this->specifications, $key);
    }
    
    public function setDynamicAttribute(string $key, $value): self
    {
        $schema = app(DynamicProductSchema::class)
            ->getSchemaForCategory($this->category->slug);
        
        if (isset($schema[$key])) {
            $this->validateAttribute($key, $value, $schema[$key]);
            data_set($this->attributes, $key, $value);
        }
        
        return $this;
    }
    
    protected function validateAttribute(string $key, $value, array $rules): void
    {
        if ($rules['required'] && empty($value)) {
            throw new \InvalidArgumentException("{$key} is required");
        }
        
        if ($rules['type'] === 'number' && !is_numeric($value)) {
            throw new \InvalidArgumentException("{$key} must be a number");
        }
        
        if ($rules['type'] === 'select' && !in_array($value, $rules['options'])) {
            throw new \InvalidArgumentException("{$key} must be one of: " . implode(', ', $rules['options']));
        }
    }
}
```

### Vehicle Compatibility System
```php
class VehicleCompatibilityEngine
{
    protected array $compatibilityMatrix = [];
    
    public function addCompatibility(Product $product, array $vehicles): void
    {
        foreach ($vehicles as $vehicle) {
            DB::table('product_vehicle_compatibility')->insert([
                'product_id' => $product->id,
                'make' => $vehicle['make'],
                'model' => $vehicle['model'],
                'year_from' => $vehicle['year_from'],
                'year_to' => $vehicle['year_to'],
                'engine_code' => $vehicle['engine_code'] ?? null,
                'variant' => $vehicle['variant'] ?? null,
                'notes' => $vehicle['notes'] ?? null,
                'confidence_score' => $this->calculateConfidence($product, $vehicle)
            ]);
        }
    }
    
    public function findCompatibleProducts(array $vehicleSpecs): Collection
    {
        $query = Product::query()
            ->join('product_vehicle_compatibility as pvc', 'products.id', '=', 'pvc.product_id')
            ->where('pvc.make', $vehicleSpecs['make'])
            ->where('pvc.model', $vehicleSpecs['model'])
            ->where('pvc.year_from', '<=', $vehicleSpecs['year'])
            ->where('pvc.year_to', '>=', $vehicleSpecs['year']);
        
        if (isset($vehicleSpecs['engine_code'])) {
            $query->where(function($q) use ($vehicleSpecs) {
                $q->where('pvc.engine_code', $vehicleSpecs['engine_code'])
                  ->orWhereNull('pvc.engine_code');
            });
        }
        
        return $query->orderBy('pvc.confidence_score', 'desc')
                    ->select('products.*', 'pvc.confidence_score', 'pvc.notes')
                    ->get();
    }
    
    protected function calculateConfidence(Product $product, array $vehicle): float
    {
        $score = 0.5; // Base score
        
        // Exact engine code match
        if (isset($vehicle['engine_code']) && !empty($vehicle['engine_code'])) {
            $score += 0.3;
        }
        
        // Specific variant match
        if (isset($vehicle['variant']) && !empty($vehicle['variant'])) {
            $score += 0.15;
        }
        
        // OEM part number match
        if ($product->oem_numbers && $this->hasOemMatch($product->oem_numbers, $vehicle)) {
            $score += 0.05;
        }
        
        return min($score, 1.0);
    }
}
```

## 2. Multi-vendor Architecture

### Vendor Management System
```php
class VendorManagementSystem
{
    protected array $vendorCapabilities = [];
    protected array $performanceMetrics = [];
    
    public function registerVendor(Shop $shop, array $capabilities): void
    {
        $this->vendorCapabilities[$shop->id] = [
            'categories' => $capabilities['categories'] ?? [],
            'brands' => $capabilities['brands'] ?? [],
            'shipping_zones' => $capabilities['shipping_zones'] ?? [],
            'fulfillment_speed' => $capabilities['fulfillment_speed'] ?? 'standard',
            'return_policy' => $capabilities['return_policy'] ?? 'standard',
            'warranty_support' => $capabilities['warranty_support'] ?? false,
            'dropshipping' => $capabilities['dropshipping'] ?? false,
            'bulk_orders' => $capabilities['bulk_orders'] ?? false
        ];
    }
    
    public function routeOrderToVendor(Order $order): ?Shop
    {
        $eligibleVendors = $this->findEligibleVendors($order);
        
        if ($eligibleVendors->isEmpty()) {
            return null;
        }
        
        // Score vendors based on multiple factors
        $scoredVendors = $eligibleVendors->map(function($vendor) use ($order) {
            return [
                'vendor' => $vendor,
                'score' => $this->calculateVendorScore($vendor, $order)
            ];
        })->sortByDesc('score');
        
        return $scoredVendors->first()['vendor'];
    }
    
    protected function calculateVendorScore(Shop $vendor, Order $order): float
    {
        $score = 0;
        
        // Price competitiveness (30%)
        $score += $this->getPriceScore($vendor, $order) * 0.3;
        
        // Delivery speed (25%)
        $score += $this->getDeliveryScore($vendor, $order) * 0.25;
        
        // Stock availability (20%)
        $score += $this->getStockScore($vendor, $order) * 0.2;
        
        // Performance history (15%)
        $score += $this->getPerformanceScore($vendor) * 0.15;
        
        // Distance/Shipping cost (10%)
        $score += $this->getShippingScore($vendor, $order) * 0.1;
        
        return $score;
    }
}
```

### Dynamic Commission System
```php
class DynamicCommissionEngine
{
    protected array $commissionRules = [];
    
    public function calculateCommission(Order $order, Shop $vendor): float
    {
        $baseRate = $this->getBaseCommissionRate($vendor);
        $categoryRate = $this->getCategoryRate($order->items);
        $volumeDiscount = $this->getVolumeDiscount($vendor);
        $performanceBonus = $this->getPerformanceBonus($vendor);
        
        $effectiveRate = $baseRate + $categoryRate - $volumeDiscount - $performanceBonus;
        
        // Apply minimum and maximum limits
        $effectiveRate = max(0.05, min(0.25, $effectiveRate));
        
        return $order->subtotal * $effectiveRate;
    }
    
    protected function getVolumeDiscount(Shop $vendor): float
    {
        $monthlySales = $vendor->orders()
            ->whereMonth('created_at', now()->month)
            ->sum('total');
        
        return match(true) {
            $monthlySales >= 1000000 => 0.03, // 3% discount for 1M+ SAR
            $monthlySales >= 500000 => 0.02,  // 2% discount for 500K+ SAR
            $monthlySales >= 100000 => 0.01,  // 1% discount for 100K+ SAR
            default => 0
        };
    }
    
    protected function getPerformanceBonus(Shop $vendor): float
    {
        $metrics = [
            'on_time_delivery' => $vendor->on_time_delivery_rate,
            'customer_satisfaction' => $vendor->average_rating,
            'return_rate' => 1 - $vendor->return_rate, // Lower is better
            'response_time' => $vendor->average_response_time <= 60 ? 1 : 0
        ];
        
        $score = array_sum($metrics) / count($metrics);
        
        return $score >= 0.9 ? 0.02 : 0; // 2% bonus for excellent performance
    }
}
```

## 3. Dynamic Pricing Engine

### AI-Powered Pricing
```php
class DynamicPricingEngine
{
    protected MachineLearningService $ml;
    protected array $pricingFactors = [];
    
    public function calculateOptimalPrice(Product $product): array
    {
        $factors = [
            'base_cost' => $product->cost,
            'competitor_prices' => $this->getCompetitorPrices($product),
            'demand_level' => $this->calculateDemandLevel($product),
            'inventory_level' => $product->quantity,
            'seasonality' => $this->getSeasonalityFactor($product),
            'market_conditions' => $this->getMarketConditions(),
            'customer_segment' => $this->getCustomerSegmentFactors()
        ];
        
        // ML model prediction
        $suggestedPrice = $this->ml->predict('pricing_model', $factors);
        
        // Apply business rules
        $finalPrice = $this->applyBusinessRules($suggestedPrice, $product);
        
        return [
            'regular_price' => $finalPrice,
            'promotional_price' => $this->calculatePromotionalPrice($finalPrice, $product),
            'bulk_pricing' => $this->calculateBulkPricing($finalPrice),
            'confidence' => $this->ml->getConfidence(),
            'factors' => $factors
        ];
    }
    
    protected function calculateDemandLevel(Product $product): float
    {
        $recentViews = $product->views()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        $recentSales = $product->orderItems()
            ->whereHas('order', fn($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->sum('quantity');
        
        $cartAdditions = $product->carts()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        // Weighted demand score
        $demandScore = ($recentViews * 0.2 + $recentSales * 0.5 + $cartAdditions * 0.3) / 100;
        
        return min($demandScore, 1.0);
    }
    
    public function applyBusinessRules(float $suggestedPrice, Product $product): float
    {
        // Minimum margin rule
        $minimumPrice = $product->cost * 1.15; // 15% minimum margin
        
        // Maximum price ceiling
        $maximumPrice = $product->cost * 3; // 300% maximum markup
        
        // Category-specific rules
        $categoryRules = $this->getCategoryPricingRules($product->category);
        
        $price = max($minimumPrice, min($maximumPrice, $suggestedPrice));
        
        // Round to nearest acceptable price point
        return $this->roundToNearestPricePoint($price);
    }
}
```

## 4. Real-time Inventory Management

### Multi-warehouse Inventory System
```php
class MultiWarehouseInventory
{
    protected array $warehouses = [];
    protected array $reorderPoints = [];
    
    public function allocateStock(Order $order): array
    {
        $allocation = [];
        $remainingItems = $order->items->keyBy('product_id');
        
        // Sort warehouses by proximity to customer
        $sortedWarehouses = $this->sortWarehousesByProximity(
            $order->shipping_address,
            $order->items->pluck('product_id')
        );
        
        foreach ($sortedWarehouses as $warehouse) {
            if ($remainingItems->isEmpty()) break;
            
            $warehouseAllocation = $this->allocateFromWarehouse(
                $warehouse,
                $remainingItems
            );
            
            if (!empty($warehouseAllocation)) {
                $allocation[$warehouse->id] = $warehouseAllocation;
                
                // Update remaining items
                foreach ($warehouseAllocation as $productId => $quantity) {
                    $remaining = $remainingItems[$productId]->quantity - $quantity;
                    
                    if ($remaining <= 0) {
                        $remainingItems->forget($productId);
                    } else {
                        $remainingItems[$productId]->quantity = $remaining;
                    }
                }
            }
        }
        
        // Handle any remaining items (backorder or cancel)
        if ($remainingItems->isNotEmpty()) {
            $allocation['backorder'] = $remainingItems->mapWithKeys(function($item) {
                return [$item->product_id => $item->quantity];
            })->toArray();
        }
        
        return $allocation;
    }
    
    public function syncInventoryLevels(): void
    {
        // Real-time sync with external systems
        foreach ($this->warehouses as $warehouse) {
            if ($warehouse->hasExternalSystem()) {
                $externalInventory = $warehouse->fetchExternalInventory();
                
                foreach ($externalInventory as $sku => $quantity) {
                    $this->updateWarehouseStock($warehouse->id, $sku, $quantity);
                }
            }
        }
        
        // Trigger reorder points check
        $this->checkReorderPoints();
    }
    
    protected function checkReorderPoints(): void
    {
        $lowStockProducts = DB::table('warehouse_inventory')
            ->select('product_id', 'warehouse_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_id', 'warehouse_id')
            ->having('total_quantity', '<=', DB::raw('reorder_point'))
            ->get();
        
        foreach ($lowStockProducts as $item) {
            event(new LowStockAlert($item->product_id, $item->warehouse_id, $item->total_quantity));
            
            if ($this->shouldAutoReorder($item->product_id)) {
                $this->createPurchaseOrder($item->product_id, $item->warehouse_id);
            }
        }
    }
}
```

## 5. Dynamic Category Management

### Intelligent Category Tree
```php
class DynamicCategorySystem
{
    protected array $categoryRules = [];
    protected array $attributeInheritance = [];
    
    public function buildDynamicTree(): array
    {
        $categories = Category::all();
        $tree = [];
        
        foreach ($categories as $category) {
            // Build hierarchical structure
            $node = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'attributes' => $this->getCategoryAttributes($category),
                'rules' => $this->getCategoryRules($category),
                'children' => []
            ];
            
            // Auto-categorization rules
            $node['auto_categorization'] = [
                'keywords' => $category->auto_keywords ?? [],
                'patterns' => $category->name_patterns ?? [],
                'brand_associations' => $category->brand_associations ?? []
            ];
            
            $tree = $this->insertIntoTree($tree, $node, $category->parent_id);
        }
        
        return $tree;
    }
    
    public function suggestCategory(Product $product): ?Category
    {
        $scores = [];
        
        foreach (Category::all() as $category) {
            $score = 0;
            
            // Name matching
            $nameScore = $this->calculateNameSimilarity(
                $product->name,
                $category->auto_keywords
            );
            $score += $nameScore * 0.4;
            
            // Attribute matching
            $attributeScore = $this->calculateAttributeMatch(
                $product->attributes,
                $category->required_attributes
            );
            $score += $attributeScore * 0.3;
            
            // Brand association
            if (in_array($product->brand, $category->brand_associations ?? [])) {
                $score += 0.2;
            }
            
            // Historical data
            $historicalScore = $this->getHistoricalCategoryScore(
                $product->name,
                $category->id
            );
            $score += $historicalScore * 0.1;
            
            $scores[$category->id] = $score;
        }
        
        arsort($scores);
        $topCategoryId = array_key_first($scores);
        
        return $scores[$topCategoryId] > 0.6 
            ? Category::find($topCategoryId) 
            : null;
    }
}
```

## Lessons Learned - Iteration 1

### Key Insights:
1. **Flexibility is Key**: Auto parts have vastly different attributes - a one-size-fits-all approach doesn't work
2. **Compatibility Complexity**: Vehicle compatibility requires sophisticated matching algorithms
3. **Multi-vendor Challenges**: Balancing vendor competition with platform profitability
4. **Inventory Complexity**: Multi-warehouse management requires real-time synchronization
5. **Pricing Dynamics**: Market conditions change rapidly in auto parts industry

### Best Practices Discovered:
- Use JSON columns for flexible attribute storage
- Implement caching aggressively for compatibility queries
- Build vendor scoring algorithms that adapt over time
- Create reusable validation rules for different part categories
- Design for API-first architecture to support multiple frontends

### Performance Optimizations:
- Index compatibility tables on (make, model, year) composite
- Cache category trees with Redis
- Use database views for complex pricing calculations
- Implement read replicas for search queries
- Queue inventory sync operations