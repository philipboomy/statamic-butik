<?php

namespace Jonassiewertsen\StatamicButik\Events;

use Carbon\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Jonassiewertsen\StatamicButik\Checkout\Customer;
use Jonassiewertsen\StatamicButik\Checkout\Order;
use Jonassiewertsen\StatamicButik\Http\Models\Order;

class PaymentSuccessful
{
    use SerializesModels;

    public $transaction;

    public function __construct($payment)
    {
        $order      = $this->fetchOrder($payment);
        $customer   = $this->createCustomer($order);
        $items      = $this->fetchItems($order);

        $this->transaction = (new Order())
            ->id($payment->id)
            ->method($payment->method)
            ->customer($customer)
            ->items($items, true)
            ->totalAmount($payment->amount->value)
            ->createdAt(Carbon::parse($payment->createdAt))
            ->paidAt(Carbon::parse($payment->paidAt));
    }

    private function fetchItems($order): Collection {
        return collect(json_decode($order->items));
    }

    private function fetchOrder($payment): Order {
        return Order::whereTransactionId($payment->id)->firstOrFail();
    }

    private function createCustomer($order): Customer {
        return (new Customer())
            ->name(json_decode($order->customer)->name)
            ->mail(json_decode($order->customer)->mail);
    }
}
