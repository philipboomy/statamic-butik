<?php

namespace Jonassiewertsen\StatamicButik\Http\Livewire;

use Jonassiewertsen\StatamicButik\Checkout\Cart;
use Livewire\Component;
use Jonassiewertsen\StatamicButik\Http\Livewire\CartIcon;

class AddToCart extends Component
{
    public $slug;
    public $redirect;
    public $locale;

    public function mount($slug, $redirect = null)
    {
        $this->slug = $slug;
        $this->locale = locale();
        $this->redirect = $redirect;
    }

    public function add()
    {
        Cart::add($this->slug, $this->locale);

        // Dispatch to JavaScript listeners
        $this->dispatch('cartUpdated');

        if ($this->redirect) {
            return redirect(route('butik.cart'));
        }
    }

    public function render()
    {
        return view('butik::web.components.add-to-cart');
    }
}
