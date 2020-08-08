<?php

namespace Jonassiewertsen\StatamicButik\Checkout;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mollie\Api\Resources\Order as MollieOrder;

class Order
{
    public string     $id;
    public string     $orderNumber;
    public string     $status;
    public ?string     $method;
    public string     $currencyIsoCode;
    public string     $currencySymbol;
    public string     $totalAmount;
    public Collection $items;
    public Customer   $customer;
    public Carbon     $createdAt;
    public Carbon     $paidAt;

    public function __construct(MollieOrder $payment)
    {
        $this->id          = $payment->id;
        $this->orderNumber = $payment->orderNumber;
        $this->status      = $payment->status;
        $this->method      = $payment->method ?? null;
        $this->totalAmount = $payment->amount->value;
        $this->items       = $this->items($payment->lines);
        $this->createdAt   = Carbon::parse($payment->createdAt);

        $this->currencyIsoCode = config('butik.currency_isoCode', '');
        $this->currencySymbol  = config('butik.currency_symbol', '');
    }

    public function items(array $items)
    {
        $mappedItems = array_map(function($item) {
            return [
                'id'          => $item->sku,
                'name'        => $item->name,
                'quantity'    => $item->quantity,
                'singlePrice' => $item->unitPrice->value,
                'totalPrice'  => $item->totalAmount->value,
                'taxRate'     => $item->vatRate,
            ];
        }, $items);

        return collect($mappedItems);
    }
}
