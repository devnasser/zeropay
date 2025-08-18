<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'image',
        'parent_id',
        'sort_order',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $translatable = ['name', 'description'];

    /**
     * Relationships
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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

    public function scopeMain($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Methods
     */
    public function getFullPathAttribute()
    {
        $path = collect([$this->name]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }
        
        return $path->implode(' > ');
    }

    public function getAllChildrenIds()
    {
        $ids = collect([$this->id]);
        
        $this->children->each(function ($child) use (&$ids) {
            $ids = $ids->merge($child->getAllChildrenIds());
        });
        
        return $ids;
    }

    public function getProductsCount()
    {
        return $this->products()->active()->count() + 
               $this->children->sum(fn($child) => $child->getProductsCount());
    }
}
