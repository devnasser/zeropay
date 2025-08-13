<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\NotificationService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\PerformanceMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $order;
    public $tries = 3;
    public $timeout = 120;
    public $failOnTimeout = true;
    
    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->queue = 'orders';
    }
    
    /**
     * Execute the job.
     */
    public function handle(
        NotificationService $notification,
        InventoryService $inventory,
        PaymentService $payment,
        PerformanceMonitorService $monitor
    ): void {
        $timerId = $monitor->startTimer('process_order');
        
        try {
            DB::beginTransaction();
            
            // Step 1: Verify inventory availability
            $this->verifyInventory($inventory);
            
            // Step 2: Process payment
            $this->processPayment($payment);
            
            // Step 3: Update inventory
            $this->updateInventory($inventory);
            
            // Step 4: Generate invoice
            $this->generateInvoice();
            
            // Step 5: Update order status
            $this->order->update([
                'status' => 'processing',
                'processed_at' => now()
            ]);
            
            // Step 6: Send notifications
            $this->sendNotifications($notification);
            
            // Step 7: Trigger related jobs
            $this->triggerRelatedJobs();
            
            DB::commit();
            
            $monitor->endTimer($timerId, ['order_id' => $this->order->id]);
            
            Log::info('Order processed successfully', [
                'order_id' => $this->order->id,
                'total' => $this->order->total
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Order processing failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update order status to failed
            $this->order->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage()
            ]);
            
            // Notify customer about failure
            $notification->send($this->order->user, 'order_failed', [
                'order_number' => $this->order->order_number,
                'reason' => 'Processing error. Our team has been notified.'
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Verify inventory availability
     */
    protected function verifyInventory(InventoryService $inventory): void
    {
        foreach ($this->order->items as $item) {
            if (!$inventory->checkAvailability($item->product_id, $item->quantity)) {
                throw new \Exception("Product {$item->product->name} is out of stock");
            }
        }
    }
    
    /**
     * Process payment
     */
    protected function processPayment(PaymentService $payment): void
    {
        if ($this->order->payment_method === 'online') {
            $result = $payment->charge([
                'amount' => $this->order->total,
                'currency' => $this->order->currency,
                'reference' => $this->order->order_number,
                'customer_id' => $this->order->user_id,
                'metadata' => [
                    'order_id' => $this->order->id,
                    'shop_id' => $this->order->shop_id
                ]
            ]);
            
            if (!$result['success']) {
                throw new \Exception('Payment failed: ' . $result['message']);
            }
            
            $this->order->update([
                'payment_status' => 'paid',
                'payment_reference' => $result['transaction_id'],
                'paid_at' => now()
            ]);
        }
    }
    
    /**
     * Update inventory
     */
    protected function updateInventory(InventoryService $inventory): void
    {
        foreach ($this->order->items as $item) {
            $inventory->decreaseStock(
                $item->product_id,
                $item->quantity,
                "Order #{$this->order->order_number}"
            );
        }
    }
    
    /**
     * Generate invoice
     */
    protected function generateInvoice(): void
    {
        dispatch(new GenerateInvoiceJob($this->order))
            ->onQueue('documents')
            ->delay(now()->addSeconds(5));
    }
    
    /**
     * Send notifications
     */
    protected function sendNotifications(NotificationService $notification): void
    {
        // Notify customer
        $notification->send($this->order->user, 'order_confirmed', [
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
            'delivery_date' => $this->order->estimated_delivery_date
        ]);
        
        // Notify shop
        $notification->send($this->order->shop->owner, 'new_order', [
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->user->name,
            'total' => $this->order->total
        ]);
        
        // Send SMS if enabled
        if ($this->order->user->sms_notifications) {
            dispatch(new SendSmsJob(
                $this->order->user->phone,
                "Your order #{$this->order->order_number} has been confirmed. Track it at: " . 
                route('orders.track', $this->order->tracking_code)
            ))->onQueue('notifications');
        }
    }
    
    /**
     * Trigger related jobs
     */
    protected function triggerRelatedJobs(): void
    {
        // Update shop statistics
        dispatch(new UpdateShopStatisticsJob($this->order->shop_id))
            ->onQueue('analytics')
            ->delay(now()->addMinutes(5));
        
        // Update product popularity
        dispatch(new UpdateProductPopularityJob($this->order->items->pluck('product_id')->toArray()))
            ->onQueue('analytics')
            ->delay(now()->addMinutes(10));
        
        // Check for loyalty rewards
        dispatch(new ProcessLoyaltyRewardsJob($this->order->user_id, $this->order->total))
            ->onQueue('rewards')
            ->delay(now()->addMinutes(15));
    }
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessOrderJob failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage()
        ]);
        
        // Send critical alert
        dispatch(new SendAlertJob(
            'critical',
            'Order Processing Failed',
            [
                'order_id' => $this->order->id,
                'error' => $exception->getMessage()
            ]
        ))->onQueue('alerts');
    }
    
    /**
     * Get retry delay
     */
    public function retryAfter(): int
    {
        return 60 * $this->attempts(); // Exponential backoff
    }
}