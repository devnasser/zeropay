<?php

namespace App\Http\Livewire;

use App\Models\Cart;
use Livewire\Component;

class CartCounter extends Component
{
    public $count = 0;

    protected $listeners = ['cartUpdated' => 'updateCount'];

    public function mount()
    {
        $this->updateCount();
    }

    public function updateCount()
    {
        if (auth()->check()) {
            $this->count = Cart::getCartCount(auth()->id());
        } else {
            $this->count = Cart::getCartCount(null, session()->getId());
        }
    }

    public function render()
    {
        return view('livewire.cart-counter');
    }
}