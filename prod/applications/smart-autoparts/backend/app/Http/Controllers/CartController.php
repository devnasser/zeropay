<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->getCartItems();
        
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        $tax = $subtotal * (config('app.vat_rate', 15) / 100);
        $total = $subtotal + $tax;
        
        return view('cart.index', compact('cartItems', 'subtotal', 'tax', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        if (!$product->canBePurchased($request->quantity)) {
            return back()->with('error', __('Insufficient stock'));
        }

        $userId = auth()->id();
        $sessionId = !$userId ? session()->getId() : null;

        // Check if item already exists in cart
        $cartItem = Cart::where(function ($query) use ($userId, $sessionId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })->where('product_id', $product->id)->first();

        if ($cartItem) {
            // Update quantity
            try {
                $cartItem->updateQuantity($cartItem->quantity + $request->quantity);
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        } else {
            // Add new item
            Cart::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price,
            ]);
        }

        // Emit event for Livewire
        $this->dispatch('cartUpdated');

        return back()->with('success', __('Product added to cart'));
    }

    public function update(Request $request, Cart $item)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        // Check ownership
        if (!$this->ownsCartItem($item)) {
            abort(403);
        }

        try {
            $item->updateQuantity($request->quantity);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->dispatch('cartUpdated');

        return back()->with('success', __('Cart updated'));
    }

    public function remove(Cart $item)
    {
        // Check ownership
        if (!$this->ownsCartItem($item)) {
            abort(403);
        }

        $item->delete();
        
        $this->dispatch('cartUpdated');

        return back()->with('success', __('Item removed from cart'));
    }

    public function clear()
    {
        $userId = auth()->id();
        $sessionId = !$userId ? session()->getId() : null;
        
        Cart::clearCart($userId, $sessionId);
        
        $this->dispatch('cartUpdated');

        return back()->with('success', __('Cart cleared'));
    }

    /**
     * Helper methods
     */
    private function getCartItems()
    {
        $userId = auth()->id();
        $sessionId = !$userId ? session()->getId() : null;
        
        return Cart::getCartItems($userId, $sessionId);
    }

    private function ownsCartItem(Cart $item)
    {
        if (auth()->check()) {
            return $item->user_id === auth()->id();
        }
        
        return $item->session_id === session()->getId();
    }

    private function dispatch($event)
    {
        if (class_exists('\Livewire\Livewire')) {
            \Livewire\Livewire::dispatch($event);
        }
    }
}
