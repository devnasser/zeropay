<?php

namespace App\Services\PaymentGateway;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class STCPayService
{
    protected $merchantId;
    protected $secretKey;
    protected $baseUrl;
    
    public function __construct()
    {
        $this->merchantId = config('services.stcpay.merchant_id');
        $this->secretKey = config('services.stcpay.secret_key');
        $this->baseUrl = config('services.stcpay.sandbox') 
            ? 'https://api-sandbox.stcpay.com.sa/v2'
            : 'https://api.stcpay.com.sa/v2';
    }
    
    /**
     * Create payment request
     */
    public function createPayment(Order $order)
    {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'amount' => $order->total,
                'currency' => 'SAR',
                'order_id' => $order->order_number,
                'description' => 'Order #' . $order->order_number,
                'customer' => [
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone,
                ],
                'callback_url' => route('payment.callback', ['gateway' => 'stcpay']),
                'return_url' => route('orders.show', $order),
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ]
            ];
            
            $signature = $this->generateSignature($payload);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'X-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/payments', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Save payment reference
                $order->update([
                    'payment_reference' => $data['reference'],
                    'payment_gateway' => 'stcpay',
                ]);
                
                return [
                    'success' => true,
                    'payment_url' => $data['payment_url'],
                    'reference' => $data['reference'],
                ];
            }
            
            Log::error('STC Pay Error', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);
            
            return [
                'success' => false,
                'message' => $response->json('message', 'Payment initialization failed'),
            ];
            
        } catch (\Exception $e) {
            Log::error('STC Pay Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment service unavailable',
            ];
        }
    }
    
    /**
     * Verify payment callback
     */
    public function verifyCallback($data)
    {
        $signature = $data['signature'] ?? '';
        unset($data['signature']);
        
        $calculatedSignature = $this->generateSignature($data);
        
        if (!hash_equals($signature, $calculatedSignature)) {
            Log::warning('STC Pay Invalid Signature', ['data' => $data]);
            return false;
        }
        
        return $this->checkPaymentStatus($data['reference']);
    }
    
    /**
     * Check payment status
     */
    public function checkPaymentStatus($reference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->baseUrl . '/payments/' . $reference);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'status' => $data['status'],
                    'amount' => $data['amount'],
                    'reference' => $data['reference'],
                    'paid_at' => $data['paid_at'] ?? null,
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Unable to verify payment',
            ];
            
        } catch (\Exception $e) {
            Log::error('STC Pay Status Check Error', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment verification failed',
            ];
        }
    }
    
    /**
     * Refund payment
     */
    public function refund($reference, $amount, $reason = '')
    {
        try {
            $payload = [
                'amount' => $amount,
                'reason' => $reason,
            ];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->post($this->baseUrl . '/payments/' . $reference . '/refund', $payload);
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_reference' => $response->json('refund_reference'),
                ];
            }
            
            return [
                'success' => false,
                'message' => $response->json('message', 'Refund failed'),
            ];
            
        } catch (\Exception $e) {
            Log::error('STC Pay Refund Error', [
                'error' => $e->getMessage(),
                'reference' => $reference,
            ]);
            
            return [
                'success' => false,
                'message' => 'Refund service unavailable',
            ];
        }
    }
    
    /**
     * Generate signature for request
     */
    protected function generateSignature($data)
    {
        ksort($data);
        $message = http_build_query($data);
        return hash_hmac('sha256', $message, $this->secretKey);
    }
}