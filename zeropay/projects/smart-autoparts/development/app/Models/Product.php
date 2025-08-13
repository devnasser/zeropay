<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'shop_id',
        'category_id',
        'sku',
        'barcode',
        'name',
        'description',
        'specifications',
        'brand',
        'model',
        'year_from',
        'year_to',
        'compatible_cars',
        'price',
        'compare_price',
        'cost',
        'quantity',
        'min_quantity',
        'unit',
        'weight',
        'dimensions',
        'images',
        'video_url',
        'rating',
        'reviews_count',
        'sales_count',
        'views_count',
        'is_active',
        'is_featured',
        'is_new',
        'is_used',
        'is_original',
        'condition',
        'warranty_period',
        'tags',
        'seo',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'specifications' => 'array',
        'compatible_cars' => 'array',
        'dimensions' => 'array',
        'images' => 'array',
        'tags' => 'array',
        'seo' => 'array',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:3',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_used' => 'boolean',
        'is_original' => 'boolean',
    ];

    protected $translatable = ['name', 'description'];

    /**
     * Relationships
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeCompatibleWithCar($query, $make, $model, $year = null)
    {
        return $query->whereJsonContains('compatible_cars', ['make' => $make, 'model' => $model])
            ->when($year, function ($q) use ($year) {
                $q->where('year_from', '<=', $year)
                  ->where('year_to', '>=', $year);
            });
    }

    /**
     * Accessors & Mutators
     */
    public function getDiscountPercentageAttribute()
    {
        if (!$this->compare_price || $this->compare_price <= $this->price) {
            return 0;
        }
        
        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 0);
    }

    public function getIsOnSaleAttribute()
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getMainImageAttribute()
    {
        return $this->images[0] ?? '/images/no-product.png';
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' ' . config('app.currency', 'SAR');
    }

    /**
     * Methods
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function updateRating()
    {
        $avg = $this->reviews()->avg('rating') ?? 0;
        $count = $this->reviews()->count();
        
        $this->update([
            'rating' => $avg,
            'reviews_count' => $count
        ]);
    }

    public function decrementStock($quantity = 1)
    {
        if ($this->quantity < $quantity) {
            throw new \Exception('Insufficient stock');
        }
        
        $this->decrement('quantity', $quantity);
    }

    public function incrementStock($quantity = 1)
    {
        $this->increment('quantity', $quantity);
    }

    public function isAvailable()
    {
        return $this->is_active && $this->quantity > 0;
    }

    public function canBePurchased($quantity = 1)
    {
        return $this->isAvailable() && $this->quantity >= $quantity;
    }
}
