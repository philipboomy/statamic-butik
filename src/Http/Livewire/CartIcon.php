<?php

namespace Jonassiewertsen\StatamicButik\Http\Livewire;

use Jonassiewertsen\StatamicButik\Checkout\Cart as ShoppingCart;
use Livewire\Component;
use Livewire\Attributes\On;

class CartIcon extends Component
{
    public $total = 0;

    public function mount()
    {
        $this->total = ShoppingCart::totalItems();
    }

    #[On('cartUpdated')]
    public function updateCart()
    {
        $this->total = ShoppingCart::totalItems();
    }

    public function render()
    {
        return view('butik::web.components.cart-icon');
    }
}
