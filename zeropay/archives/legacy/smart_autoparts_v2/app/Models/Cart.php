<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'quantity',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Methods
     */
    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    public function updateQuantity($quantity)
    {
        if ($quantity <= 0) {
            $this->delete();
            return;
        }

        if (!$this->product->canBePurchased($quantity)) {
            throw new \Exception('Insufficient stock');
        }

        $this->update(['quantity' => $quantity]);
    }

    public static function getCartItems($userId = null, $sessionId = null)
    {
        $query = self::with('product.shop');

        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        } else {
            return collect();
        }

        return $query->get();
    }

    public static function getCartTotal($userId = null, $sessionId = null)
    {
        $items = self::getCartItems($userId, $sessionId);
        
        return $items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }

    public static function getCartCount($userId = null, $sessionId = null)
    {
        $query = self::query();

        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        } else {
            return 0;
        }

        return $query->sum('quantity');
    }

    public static function mergeSessionCart($sessionId, $userId)
    {
        $sessionItems = self::forSession($sessionId)->get();

        foreach ($sessionItems as $item) {
            $existingItem = self::forUser($userId)
                ->where('product_id', $item->product_id)
                ->first();

            if ($existingItem) {
                $existingItem->updateQuantity($existingItem->quantity + $item->quantity);
            } else {
                $item->update(['user_id' => $userId, 'session_id' => null]);
            }
        }

        // Delete remaining session items
        self::forSession($sessionId)->delete();
    }

    public static function clearCart($userId = null, $sessionId = null)
    {
        $query = self::query();

        if ($userId) {
            $query->forUser($userId);
        } elseif ($sessionId) {
            $query->forSession($sessionId);
        }

        $query->delete();
    }
}
