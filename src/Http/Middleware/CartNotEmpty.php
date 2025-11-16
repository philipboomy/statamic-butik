<?php

namespace Jonassiewertsen\StatamicButik\Http\Middleware;

use Closure;
use Jonassiewertsen\StatamicButik\Checkout\Cart;

class CartNotEmpty
{
    public function handle($request, Closure $next)
    {
        $totalItems = Cart::totalItems();
        \Log::info('CartNotEmpty middleware', ['total_items' => $totalItems]);

        if ($totalItems === 0) {
            \Log::warning('Cart is empty, redirecting to cart page');
            return redirect()->route('butik.cart');
        }

        return $next($request);
    }
}
