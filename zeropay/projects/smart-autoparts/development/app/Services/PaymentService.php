<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $config;

    public function __construct()
    {
        $this->config = [
            'stc_pay' => [
                'name' => 'STC Pay',
                'icon' => 'stc-pay.png',
                'enabled' => true,
                'test_mode' => true,
                'api_url' => env('STC_PAY_API_URL'),
                'merchant_id' => env('STC_PAY_MERCHANT_ID'),
                'secret_key' => env('STC_PAY_SECRET_KEY'),
            ],
            'tamara' => [
                'name' => 'Tamara - تقسيط',
                'icon' => 'tamara.png',
                'enabled' => true,
                'test_mode' => true,
                'api_url' => env('TAMARA_API_URL'),
                'merchant_token' => env('TAMARA_MERCHANT_TOKEN'),
            ],
            'tabby' => [
                'name' => 'Tabby - تقسيط',
                'icon' => 'tabby.png',
                'enabled' => true,
                'test_mode' => true,
                'api_url' => env('TABBY_API_URL'),
                'api_key' => env('TABBY_API_KEY'),
                'secret_key' => env('TABBY_SECRET_KEY'),
            ],
            'apple_pay' => [
                'name' => 'Apple Pay',
                'icon' => 'apple-pay.png',
                'enabled' => true,
            ],
            'mada' => [
                'name' => 'مدى',
                'icon' => 'mada.png',
                'enabled' => true,
            ],
            'cod' => [
                'name' => 'الدفع عند الاستلام',
                'icon' => 'cod.png',
                'enabled' => true,
                'max_amount' => 5000,
            ],
        ];
    }

    public function getAvailableMethods()
    {
        return collect($this->config)
            ->filter(fn($method) => $method['enabled'])
            ->map(fn($method, $key) => [
                'id' => $key,
                'name' => $method['name'],
                'icon' => $method['icon'],
                'installments' => in_array($key, ['tamara', 'tabby']),
            ]);
    }

    public function processPayment(array $orders, string $method)
    {
        $totalAmount = collect($orders)->sum('total');

        switch ($method) {
            case 'stc_pay':
                return $this->processSTCPay($orders, $totalAmount);
            
            case 'tamara':
                return $this->processTamara($orders, $totalAmount);
            
            case 'tabby':
                return $this->processTabby($orders, $totalAmount);
            
            case 'apple_pay':
            case 'mada':
                return $this->processCardPayment($orders, $totalAmount, $method);
            
            case 'cod':
                return ['success' => true, 'message' => 'Cash on delivery selected'];
            
            default:
                return ['success' => false, 'message' => 'Invalid payment method'];
        }
    }

    protected function processSTCPay($orders, $amount)
    {
        try {
            $config = $this->config['stc_pay'];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['secret_key'],
                'Content-Type' => 'application/json',
            ])->post($config['api_url'] . '/payments', [
                'merchant_id' => $config['merchant_id'],
                'amount' => $amount,
                'currency' => 'SAR',
                'order_id' => collect($orders)->pluck('order_number')->implode('-'),
                'callback_url' => route('payment.callback', ['method' => 'stc_pay']),
                'customer' => [
                    'mobile' => auth()->user()->phone,
                    'email' => auth()->user()->email,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'redirect_url' => $data['payment_url'],
                    'transaction_id' => $data['transaction_id'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to initialize STC Pay payment',
            ];

        } catch (\Exception $e) {
            Log::error('STC Pay Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment processing error',
            ];
        }
    }

    protected function processTamara($orders, $amount)
    {
        try {
            $config = $this->config['tamara'];
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['merchant_token'],
                'Content-Type' => 'application/json',
            ])->post($config['api_url'] . '/checkout', [
                'order_reference_id' => collect($orders)->pluck('order_number')->implode('-'),
                'order_number' => collect($orders)->first()->order_number,
                'total_amount' => [
                    'amount' => $amount,
                    'currency' => 'SAR',
                ],
                'description' => 'Smart AutoParts Order',
                'country_code' => 'SA',
                'payment_type' => 'PAY_BY_INSTALMENTS',
                'instalments' => 3,
                'consumer' => [
                    'first_name' => auth()->user()->name,
                    'last_name' => '',
                    'phone_number' => '+966' . auth()->user()->phone,
                    'email' => auth()->user()->email,
                ],
                'billing_address' => $this->formatAddressForTamara($orders[0]->billing_address),
                'shipping_address' => $this->formatAddressForTamara($orders[0]->shipping_address),
                'items' => $this->formatItemsForTamara($orders),
                'merchant_url' => [
                    'success' => route('payment.success'),
                    'failure' => route('payment.failure'),
                    'cancel' => route('payment.cancel'),
                    'notification' => route('payment.webhook', ['method' => 'tamara']),
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'redirect_url' => $data['checkout_url'],
                    'checkout_id' => $data['checkout_id'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to initialize Tamara payment',
            ];

        } catch (\Exception $e) {
            Log::error('Tamara Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment processing error',
            ];
        }
    }

    protected function processTabby($orders, $amount)
    {
        // Similar implementation for Tabby
        return [
            'success' => true,
            'message' => 'Tabby integration pending',
        ];
    }

    protected function processCardPayment($orders, $amount, $method)
    {
        // Implementation for card payments (Apple Pay, Mada)
        return [
            'success' => true,
            'message' => 'Card payment integration pending',
        ];
    }

    protected function formatAddressForTamara($address)
    {
        return [
            'first_name' => auth()->user()->name,
            'last_name' => '',
            'line1' => $address['street'] ?? '',
            'city' => $address['city'] ?? '',
            'country_code' => 'SA',
        ];
    }

    protected function formatItemsForTamara($orders)
    {
        $items = [];
        
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $items[] = [
                    'reference_id' => $item->product->sku,
                    'type' => 'physical',
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => [
                        'amount' => $item->price,
                        'currency' => 'SAR',
                    ],
                    'total_amount' => [
                        'amount' => $item->total,
                        'currency' => 'SAR',
                    ],
                ];
            }
        }
        
        return $items;
    }
}