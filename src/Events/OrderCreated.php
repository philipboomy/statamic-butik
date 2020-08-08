<?php

namespace Jonassiewertsen\StatamicButik\Events;

use Illuminate\Queue\SerializesModels;
use Jonassiewertsen\StatamicButik\Checkout\Order;

class OrderCreated
{
    use SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
