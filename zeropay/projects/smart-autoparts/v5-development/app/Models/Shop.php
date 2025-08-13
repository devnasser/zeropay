<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'logo',
        'cover_image',
        'commercial_register',
        'tax_number',
        'address',
        'phone',
        'whatsapp',
        'email',
        'working_hours',
        'latitude',
        'longitude',
        'rating',
        'reviews_count',
        'products_count',
        'orders_count',
        'total_sales',
        'is_verified',
        'is_active',
        'is_featured',
        'verified_at',
        'settings',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'address' => 'array',
        'working_hours' => 'array',
        'settings' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $translatable = ['name', 'description', 'address'];

    /**
     * Relationships
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 50)
    {
        return $query->selectRaw("*, 
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + 
            sin(radians(?)) * sin(radians(latitude)))) AS distance", 
            [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
    }

    /**
     * Methods
     */
    public function isOpen()
    {
        $day = strtolower(date('D'));
        $currentTime = date('H:i');
        
        if (!isset($this->working_hours[$day])) {
            return false;
        }
        
        $hours = $this->working_hours[$day];
        
        if ($hours['closed'] ?? false) {
            return false;
        }
        
        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    public function updateStats()
    {
        $this->update([
            'products_count' => $this->products()->active()->count(),
            'orders_count' => $this->orders()->completed()->count(),
            'total_sales' => $this->orders()->completed()->sum('total'),
        ]);
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

    public function getFormattedAddressAttribute()
    {
        $address = $this->getTranslations('address');
        return implode(', ', array_filter([
            $address['street'] ?? '',
            $address['district'] ?? '',
            $address['city'] ?? '',
            $address['country'] ?? 'Saudi Arabia'
        ]));
    }

    public function getWorkingHoursForDayAttribute($day)
    {
        return $this->working_hours[strtolower($day)] ?? null;
    }
}
