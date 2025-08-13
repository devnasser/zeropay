<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ShippingService
{
    protected $providers;

    public function __construct()
    {
        $this->providers = [
            'smsa' => [
                'name' => 'SMSA Express',
                'icon' => 'smsa.png',
                'enabled' => true,
                'api_url' => env('SMSA_API_URL'),
                'api_key' => env('SMSA_API_KEY'),
                'options' => [
                    'standard' => [
                        'name' => 'Standard Delivery',
                        'days' => '3-5',
                        'base_cost' => 25,
                    ],
                    'express' => [
                        'name' => 'Express Delivery',
                        'days' => '1-2',
                        'base_cost' => 45,
                    ],
                    'same_day' => [
                        'name' => 'Same Day Delivery',
                        'days' => '0',
                        'base_cost' => 75,
                        'cutoff_time' => '14:00',
                    ],
                ],
            ],
            'aramex' => [
                'name' => 'Aramex',
                'icon' => 'aramex.png',
                'enabled' => true,
                'api_url' => env('ARAMEX_API_URL'),
                'account_number' => env('ARAMEX_ACCOUNT'),
                'options' => [
                    'standard' => [
                        'name' => 'Standard Delivery',
                        'days' => '2-4',
                        'base_cost' => 30,
                    ],
                    'express' => [
                        'name' => 'Priority Express',
                        'days' => '1',
                        'base_cost' => 55,
                    ],
                ],
            ],
            'saudi_post' => [
                'name' => 'Saudi Post - البريد السعودي',
                'icon' => 'saudi-post.png',
                'enabled' => true,
                'options' => [
                    'standard' => [
                        'name' => 'Wasel',
                        'days' => '5-7',
                        'base_cost' => 15,
                    ],
                ],
            ],
        ];
    }

    public function getOptions()
    {
        $options = [];
        
        foreach ($this->providers as $provider => $config) {
            if (!$config['enabled']) continue;
            
            foreach ($config['options'] as $type => $option) {
                $key = "{$provider}_{$type}";
                $options[$key] = [
                    'id' => $key,
                    'provider' => $config['name'],
                    'type' => $type,
                    'name' => $option['name'],
                    'days' => $option['days'],
                    'cost' => $option['base_cost'],
                    'icon' => $config['icon'],
                ];
            }
        }
        
        return collect($options)->sortBy('cost')->values();
    }

    public function calculateCost(string $option, Collection $items)
    {
        [$provider, $type] = explode('_', $option);
        
        if (!isset($this->providers[$provider]['options'][$type])) {
            throw new \Exception('Invalid shipping option');
        }
        
        $config = $this->providers[$provider]['options'][$type];
        $baseCost = $config['base_cost'];
        
        // Calculate weight-based cost
        $totalWeight = $items->sum(function ($item) {
            return $item->product->weight * $item->quantity;
        });
        
        $weightCost = 0;
        if ($totalWeight > 5) { // Over 5kg
            $weightCost = ceil(($totalWeight - 5) / 5) * 10; // 10 SAR per additional 5kg
        }
        
        // Calculate distance-based cost (simplified)
        $distanceCost = 0; // Would calculate based on actual addresses
        
        return $baseCost + $weightCost + $distanceCost;
    }

    public function createShipment($order, $shippingOption)
    {
        [$provider, $type] = explode('_', $shippingOption);
        
        switch ($provider) {
            case 'smsa':
                return $this->createSMSAShipment($order, $type);
            case 'aramex':
                return $this->createAramexShipment($order, $type);
            case 'saudi_post':
                return $this->createSaudiPostShipment($order, $type);
            default:
                throw new \Exception('Invalid shipping provider');
        }
    }

    protected function createSMSAShipment($order, $type)
    {
        $config = $this->providers['smsa'];
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type' => 'application/json',
            ])->post($config['api_url'] . '/shipments', [
                'order_id' => $order->order_number,
                'service_type' => $type,
                'cod_amount' => $order->payment_method === 'cod' ? $order->total : 0,
                'customer' => [
                    'name' => $order->user->name,
                    'mobile' => '+966' . $order->user->phone,
                    'email' => $order->user->email,
                ],
                'address' => [
                    'city' => $order->shipping_address['city'],
                    'district' => $order->shipping_address['district'] ?? '',
                    'street' => $order->shipping_address['street'],
                    'building' => $order->shipping_address['building'] ?? '',
                ],
                'items' => $order->items->map(function ($item) {
                    return [
                        'description' => $item->product->name,
                        'quantity' => $item->quantity,
                        'weight' => $item->product->weight,
                        'value' => $item->price,
                    ];
                })->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'tracking_number' => $data['awb_number'],
                    'label_url' => $data['label_url'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create SMSA shipment',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function createAramexShipment($order, $type)
    {
        // Similar implementation for Aramex
        return [
            'success' => true,
            'tracking_number' => 'ARX' . rand(100000000, 999999999),
            'message' => 'Aramex integration pending',
        ];
    }

    protected function createSaudiPostShipment($order, $type)
    {
        // Similar implementation for Saudi Post
        return [
            'success' => true,
            'tracking_number' => 'SP' . rand(100000000, 999999999),
            'message' => 'Saudi Post integration pending',
        ];
    }

    public function trackShipment($trackingNumber, $provider)
    {
        switch ($provider) {
            case 'smsa':
                return $this->trackSMSAShipment($trackingNumber);
            case 'aramex':
                return $this->trackAramexShipment($trackingNumber);
            case 'saudi_post':
                return $this->trackSaudiPostShipment($trackingNumber);
            default:
                throw new \Exception('Invalid shipping provider');
        }
    }

    protected function trackSMSAShipment($trackingNumber)
    {
        $config = $this->providers['smsa'];
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
            ])->get($config['api_url'] . '/track/' . $trackingNumber);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $data['status'],
                    'location' => $data['current_location'],
                    'history' => $data['tracking_history'],
                ];
            }

            return [
                'success' => false,
                'message' => 'Tracking information not available',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function trackAramexShipment($trackingNumber)
    {
        // Implementation for Aramex tracking
        return [
            'success' => true,
            'status' => 'In Transit',
            'message' => 'Aramex tracking pending',
        ];
    }

    protected function trackSaudiPostShipment($trackingNumber)
    {
        // Implementation for Saudi Post tracking
        return [
            'success' => true,
            'status' => 'Processing',
            'message' => 'Saudi Post tracking pending',
        ];
    }
}