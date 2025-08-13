<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessVendorCommission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    
    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;
    
    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        DB::transaction(function () {
            // Get vendor through shop
            $vendor = Vendor::where('shop_id', $this->order->shop_id)->first();
            
            if (!$vendor) {
                Log::error('Vendor not found for order: ' . $this->order->id);
                return;
            }
            
            // Calculate commission
            $commissionAmount = $vendor->calculateCommission($this->order->total);
            $netAmount = $this->order->total - $commissionAmount;
            
            // Create commission record
            $commission = VendorCommission::create([
                'vendor_id' => $vendor->id,
                'order_id' => $this->order->id,
                'amount' => $this->order->total,
                'commission' => $commissionAmount,
                'net_amount' => $netAmount,
                'rate' => $vendor->commission_rate,
                'status' => 'pending',
                'calculated_at' => now(),
            ]);
            
            // Update vendor balance and stats
            $vendor->increment('total_sales', $this->order->total);
            $vendor->increment('total_commission', $commissionAmount);
            $vendor->increment('balance', $netAmount);
            
            // Log the transaction
            Log::info('Commission processed', [
                'order_id' => $this->order->id,
                'vendor_id' => $vendor->id,
                'commission' => $commissionAmount,
                'net_amount' => $netAmount,
            ]);
            
            // Dispatch notification job
            ProcessVendorNotification::dispatch($vendor, $commission);
            
            // Check if vendor reached payout threshold
            if ($vendor->balance >= $vendor->minimum_payout) {
                CheckVendorPayoutThreshold::dispatch($vendor);
            }
        });
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Failed to process commission for order: ' . $this->order->id, [
            'error' => $exception->getMessage(),
        ]);
        
        // Notify admin about the failure
        // NotifyAdminAboutFailedCommission::dispatch($this->order, $exception);
    }
}