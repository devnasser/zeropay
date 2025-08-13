<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Address;
use App\Services\PaymentService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    protected $paymentService;
    protected $shippingService;

    public function __construct(PaymentService $paymentService, ShippingService $shippingService)
    {
        $this->middleware('auth');
        $this->paymentService = $paymentService;
        $this->shippingService = $shippingService;
    }

    public function index()
    {
        $cartItems = Cart::getCartItems(auth()->id());
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('Your cart is empty'));
        }

        // Group items by shop
        $itemsByShop = $cartItems->groupBy('product.shop_id');
        
        // Calculate totals
        $subtotal = $cartItems->sum(fn($item) => $item->quantity * $item->price);
        $tax = $subtotal * (config('app.vat_rate', 15) / 100);
        
        // Get user addresses
        $addresses = auth()->user()->addresses;
        
        // Get available payment methods
        $paymentMethods = $this->paymentService->getAvailableMethods();
        
        // Get shipping options
        $shippingOptions = $this->shippingService->getOptions();

        return view('checkout.index', compact(
            'cartItems',
            'itemsByShop',
            'subtotal',
            'tax',
            'addresses',
            'paymentMethods',
            'shippingOptions'
        ));
    }

    public function process(Request $request)
    {
        $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:stc_pay,tamara,tabby,apple_pay,mada,cod',
            'shipping_option' => 'required|in:standard,express,same_day',
            'notes' => 'nullable|string|max:500'
        ]);

        $cartItems = Cart::getCartItems(auth()->id());
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', __('Your cart is empty'));
        }

        DB::beginTransaction();
        
        try {
            // Group items by shop
            $itemsByShop = $cartItems->groupBy('product.shop_id');
            $orders = [];

            foreach ($itemsByShop as $shopId => $items) {
                // Calculate shop totals
                $subtotal = $items->sum(fn($item) => $item->quantity * $item->price);
                $tax = $subtotal * (config('app.vat_rate', 15) / 100);
                $shipping = $this->shippingService->calculateCost($request->shipping_option, $items);
                $total = $subtotal + $tax + $shipping;

                // Create order
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'shop_id' => $shopId,
                    'status' => Order::STATUS_PENDING,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'shipping' => $shipping,
                    'total' => $total,
                    'payment_method' => $request->payment_method,
                    'payment_status' => Order::PAYMENT_PENDING,
                    'shipping_address' => Address::find($request->shipping_address_id)->toArray(),
                    'billing_address' => Address::find($request->billing_address_id ?? $request->shipping_address_id)->toArray(),
                    'notes' => $request->notes,
                ]);

                // Create order items
                foreach ($items as $cartItem) {
                    $order->items()->create([
                        'product_id' => $cartItem->product_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'total' => $cartItem->quantity * $cartItem->price,
                    ]);

                    // Decrease product stock
                    $cartItem->product->decrementStock($cartItem->quantity);
                }

                $orders[] = $order;
            }

            // Clear cart
            Cart::clearCart(auth()->id());

            // Process payment
            if ($request->payment_method !== 'cod') {
                $paymentResult = $this->paymentService->processPayment($orders, $request->payment_method);
                
                if (!$paymentResult['success']) {
                    throw new \Exception($paymentResult['message']);
                }

                // Update payment status
                foreach ($orders as $order) {
                    $order->update([
                        'payment_status' => Order::PAYMENT_PAID,
                        'status' => Order::STATUS_CONFIRMED,
                    ]);
                }
            }

            DB::commit();

            // Send notifications
            foreach ($orders as $order) {
                $order->user->notify(new \App\Notifications\OrderPlaced($order));
                $order->shop->owner->notify(new \App\Notifications\NewOrder($order));
            }

            return redirect()->route('customer.orders.index')->with('success', __('Order placed successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', __('Failed to process order: ') . $e->getMessage());
        }
    }
}
