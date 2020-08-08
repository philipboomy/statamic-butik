<?php

namespace Jonassiewertsen\StatamicButik\Tests\Checkout;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Jonassiewertsen\StatamicButik\Checkout\Customer;
use Jonassiewertsen\StatamicButik\Checkout\Item;
use Jonassiewertsen\StatamicButik\Checkout\Order;
use Jonassiewertsen\StatamicButik\Events\OrderCreated;
use Jonassiewertsen\StatamicButik\Http\Controllers\PaymentGateways\MolliePaymentGateway;
use Illuminate\Support\Facades\Session;
use Jonassiewertsen\StatamicButik\Http\Models\Product;
use Jonassiewertsen\StatamicButik\Tests\TestCase;
use Jonassiewertsen\StatamicButik\Tests\Utilities\MolliePaymentOpen;
use Mollie\Laravel\Facades\Mollie;

class CreateOpenOrderTest extends TestCase
{
    protected Customer $customer;
    protected $items;

    public function setUp(): void
    {
        parent::setUp();

        $this->customer = (new Customer($this->createUserData()));
        $this->items    = collect();
        $this->items->push(new Item(factory(Product::class)->create()->slug));

        Session::put('butik.customer', $this->customer);

        Mail::fake();
    }

    /** @test */
    public function the_payment_open_event_will_be_fired_when_checking_out()
    {
        Event::fake();
        $this->checkout();
        Event::assertDispatched(OrderCreated::class);
    }

    private function checkout()
    {
        $openPayment = new MolliePaymentOpen();

        Mollie::shouldReceive('api->orders->create')->andReturn($openPayment);
        Mollie::shouldReceive('api->orders->get')->with($openPayment->id)->andReturn($openPayment);

        (new MolliePaymentGateway())->handle($this->customer, $this->items, $openPayment->amount->value);
    }

    private function createUserData($key = null, $value = null)
    {
        $customer = [
            'country'      => 'Germany',
            'name'         => 'John Doe',
            'mail'         => 'johndoe@mail.de',
            'address1'     => 'Main Street 2',
            'address2'     => '',
            'city'         => 'Flensburg',
            'state_region' => '',
            'zip'          => '24579',
            'phone'        => '013643-23837',
        ];

        if ($key !== null || $value !== null) {
            $customer->$key = $value;
        }

        return $customer;
    }
}
