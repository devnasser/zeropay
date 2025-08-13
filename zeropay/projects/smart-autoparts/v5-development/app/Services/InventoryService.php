<?php

namespace App\Services;

use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class InventoryService
{
    protected $lowStockThreshold = 10;
    protected $criticalStockThreshold = 5;
    
    /**
     * Check product availability
     */
    public function checkAvailability(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);
        
        if (!$product || !$product->is_active) {
            return false;
        }
        
        return $product->quantity >= $quantity;
    }
    
    /**
     * Reserve stock for order
     */
    public function reserveStock(int $productId, int $quantity, string $reference): bool
    {
        return DB::transaction(function() use ($productId, $quantity, $reference) {
            $product = Product::lockForUpdate()->find($productId);
            
            if (!$product || $product->quantity < $quantity) {
                return false;
            }
            
            // Create reservation
            $reservation = DB::table('inventory_reservations')->insert([
                'product_id' => $productId,
                'quantity' => $quantity,
                'reference' => $reference,
                'expires_at' => now()->addMinutes(30),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Update available quantity (not actual quantity yet)
            $product->available_quantity -= $quantity;
            $product->save();
            
            $this->logInventoryChange($productId, 'reserved', -$quantity, $reference);
            
            return true;
        });
    }
    
    /**
     * Release reserved stock
     */
    public function releaseReservation(string $reference): void
    {
        $reservations = DB::table('inventory_reservations')
            ->where('reference', $reference)
            ->get();
        
        foreach ($reservations as $reservation) {
            $product = Product::find($reservation->product_id);
            if ($product) {
                $product->available_quantity += $reservation->quantity;
                $product->save();
                
                $this->logInventoryChange(
                    $reservation->product_id, 
                    'reservation_released', 
                    $reservation->quantity, 
                    $reference
                );
            }
        }
        
        DB::table('inventory_reservations')
            ->where('reference', $reference)
            ->delete();
    }
    
    /**
     * Decrease stock after payment confirmation
     */
    public function decreaseStock(int $productId, int $quantity, string $reference): bool
    {
        return DB::transaction(function() use ($productId, $quantity, $reference) {
            $product = Product::lockForUpdate()->find($productId);
            
            if (!$product || $product->quantity < $quantity) {
                return false;
            }
            
            $product->quantity -= $quantity;
            $product->save();
            
            // Remove reservation
            DB::table('inventory_reservations')
                ->where('product_id', $productId)
                ->where('reference', $reference)
                ->delete();
            
            $this->logInventoryChange($productId, 'sold', -$quantity, $reference);
            
            // Check if low stock alert needed
            $this->checkLowStock($product);
            
            // Clear cache
            Cache::forget("product:{$productId}");
            Cache::tags(['products'])->flush();
            
            return true;
        });
    }
    
    /**
     * Increase stock (return, restock, etc.)
     */
    public function increaseStock(int $productId, int $quantity, string $reason, string $reference = null): bool
    {
        return DB::transaction(function() use ($productId, $quantity, $reason, $reference) {
            $product = Product::lockForUpdate()->find($productId);
            
            if (!$product) {
                return false;
            }
            
            $product->quantity += $quantity;
            $product->available_quantity += $quantity;
            $product->save();
            
            $this->logInventoryChange($productId, $reason, $quantity, $reference);
            
            // Clear cache
            Cache::forget("product:{$productId}");
            Cache::tags(['products'])->flush();
            
            return true;
        });
    }
    
    /**
     * Adjust stock to specific quantity
     */
    public function adjustStock(int $productId, int $newQuantity, string $reason, int $userId): bool
    {
        return DB::transaction(function() use ($productId, $newQuantity, $reason, $userId) {
            $product = Product::lockForUpdate()->find($productId);
            
            if (!$product) {
                return false;
            }
            
            $difference = $newQuantity - $product->quantity;
            $product->quantity = $newQuantity;
            $product->available_quantity = $newQuantity - $this->getReservedQuantity($productId);
            $product->save();
            
            $this->logInventoryChange($productId, 'adjustment', $difference, $reason, $userId);
            
            // Check stock levels
            $this->checkLowStock($product);
            
            // Clear cache
            Cache::forget("product:{$productId}");
            Cache::tags(['products'])->flush();
            
            return true;
        });
    }
    
    /**
     * Get reserved quantity for product
     */
    protected function getReservedQuantity(int $productId): int
    {
        return DB::table('inventory_reservations')
            ->where('product_id', $productId)
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }
    
    /**
     * Check and alert for low stock
     */
    protected function checkLowStock(Product $product): void
    {
        if ($product->quantity <= $this->criticalStockThreshold) {
            $this->sendCriticalStockAlert($product);
        } elseif ($product->quantity <= $this->lowStockThreshold) {
            $this->sendLowStockAlert($product);
        }
    }
    
    /**
     * Send low stock alert
     */
    protected function sendLowStockAlert(Product $product): void
    {
        dispatch(function() use ($product) {
            // Notify shop owner
            app(NotificationService::class)->send(
                $product->shop->owner,
                'low_stock',
                [
                    'product_name' => $product->name['ar'] ?? $product->name['en'],
                    'quantity' => $product->quantity,
                    'sku' => $product->sku
                ]
            );
        })->afterResponse();
    }
    
    /**
     * Send critical stock alert
     */
    protected function sendCriticalStockAlert(Product $product): void
    {
        dispatch(function() use ($product) {
            // Notify shop owner with high priority
            app(NotificationService::class)->send(
                $product->shop->owner,
                'critical_stock',
                [
                    'product_name' => $product->name['ar'] ?? $product->name['en'],
                    'quantity' => $product->quantity,
                    'sku' => $product->sku
                ],
                ['email', 'sms', 'push'] // Force all channels
            );
        })->afterResponse();
    }
    
    /**
     * Log inventory changes
     */
    protected function logInventoryChange(
        int $productId, 
        string $type, 
        int $quantity, 
        string $reference = null,
        int $userId = null
    ): void {
        InventoryLog::create([
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'reference' => $reference,
            'user_id' => $userId ?? auth()->id(),
            'metadata' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ]);
    }
    
    /**
     * Get inventory report
     */
    public function getInventoryReport(int $shopId = null): array
    {
        $query = Product::query();
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }
        
        $products = $query->select([
            'id', 'name', 'sku', 'quantity', 'available_quantity', 
            'price', 'cost', 'shop_id'
        ])->get();
        
        $report = [
            'total_products' => $products->count(),
            'total_quantity' => $products->sum('quantity'),
            'total_value' => $products->sum(function($p) {
                return $p->quantity * $p->price;
            }),
            'total_cost' => $products->sum(function($p) {
                return $p->quantity * ($p->cost ?? 0);
            }),
            'low_stock_products' => $products->where('quantity', '<=', $this->lowStockThreshold)->count(),
            'out_of_stock_products' => $products->where('quantity', 0)->count(),
            'products' => []
        ];
        
        foreach ($products as $product) {
            $report['products'][] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $product->quantity,
                'available' => $product->available_quantity,
                'reserved' => $product->quantity - $product->available_quantity,
                'value' => $product->quantity * $product->price,
                'status' => $this->getStockStatus($product->quantity)
            ];
        }
        
        return $report;
    }
    
    /**
     * Get stock status
     */
    protected function getStockStatus(int $quantity): string
    {
        if ($quantity == 0) {
            return 'out_of_stock';
        } elseif ($quantity <= $this->criticalStockThreshold) {
            return 'critical';
        } elseif ($quantity <= $this->lowStockThreshold) {
            return 'low';
        }
        
        return 'in_stock';
    }
    
    /**
     * Clean expired reservations
     */
    public function cleanExpiredReservations(): int
    {
        $expired = DB::table('inventory_reservations')
            ->where('expires_at', '<', now())
            ->get();
        
        $count = 0;
        
        foreach ($expired as $reservation) {
            $this->releaseReservation($reservation->reference);
            $count++;
        }
        
        Log::info('Cleaned expired inventory reservations', ['count' => $count]);
        
        return $count;
    }
}