<?php

namespace Jonassiewertsen\StatamicButik\Tests\Checkout;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Jonassiewertsen\StatamicButik\Events\PaymentSuccessful;
use Jonassiewertsen\StatamicButik\Http\Models\Order;
use Jonassiewertsen\StatamicButik\Tests\TestCase;
use Jonassiewertsen\StatamicButik\Tests\Utilities\MolliePaymentCanceled;
use Jonassiewertsen\StatamicButik\Tests\Utilities\MolliePaymentExpired;
use Jonassiewertsen\StatamicButik\Tests\Utilities\MolliePaymentFailed;
use Jonassiewertsen\StatamicButik\Tests\Utilities\MolliePaymentSuccessful;
use Mollie\Laravel\Facades\Mollie;

class MolliePaymentTest extends TestCase
{
    protected $payload;

    public function setUp(): void
    {
        parent::setUp();
        Event::fake();
    }

    /** @test */
    public function a_successful_payment_will_fire_the_an_event()
    {
        $this->withoutExceptionHandling();
        $order = create(Order::class, ['transaction_id' => 'tr_fake_id'])->first();
        $this->mockMollie(new MolliePaymentSuccessful());

        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);
        Event::assertDispatched(PaymentSuccessful::class);
    }

    /** @test */
    public function a_successful_payment_without_payment_id_wont_fire_the_event()
    {
        create(Order::class, ['transaction_id' => 'tr_fake_id']);
        $this->mockMollie(new MolliePaymentSuccessful());

        $this->post(route('butik.payment.webhook.mollie'));
        Event::assertNotDispatched(PaymentSuccessful::class);
    }

    /** @test */
    public function a_successful_payment_will_update_the_order_status()
    {
        $order = create(Order::class)->first();

        $paymentResponse     = new MolliePaymentSuccessful();
        $paymentResponse->id = $order->transaction_id;

        $this->mockMollie($paymentResponse);

        $this->assertDatabaseHas('butik_orders', ['transaction_id' => $order->transaction_id, 'status' => 'open']);

        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->transaction_id]);
        $this->assertDatabaseHas('butik_orders', [
            'id'      => $order->id,
            'paid_at' => Carbon::parse($paymentResponse->paidAt),
            'status'  => 'paid',
        ]);
    }

    /** @test */
    public function a_failed_payment_wont_fire_the_event()
    {
        $this->mockMollie(new MolliePaymentFailed());

        $this->post(route('butik.payment.webhook.mollie'));
        Event::assertNotDispatched(PaymentSuccessful::class);
    }

    /** @test */
    public function an_expired_payment_wont_fire_the_event()
    {
        $this->mockMollie(new MolliePaymentFailed());

        $this->post(route('butik.payment.webhook.mollie'));
        Event::assertNotDispatched(PaymentSuccessful::class);
    }

    /** @test */
    public function an_expired_payment_will_update_the_order_status()
    {
        $order = create(Order::class)->first();

        $paymentResponse     = new MolliePaymentExpired();
        $paymentResponse->id = $order->transaction_id;

        $this->mockMollie($paymentResponse);

        $this->assertDatabaseHas('butik_orders', ['id' => $order->id, 'status' => 'open']);

        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);
        $this->assertDatabaseHas('butik_orders', [
            'id'             => $order->id,
            'transaction_id' => $paymentResponse->id,
            'status'         => 'expired',
        ]);
    }

    /** @test */
    public function an_canceled_payment_wont_fire_the_event()
    {
        $this->mockMollie(new MolliePaymentCanceled());

        $this->post(route('butik.payment.webhook.mollie'));
        Event::assertNotDispatched(PaymentSuccessful::class);
    }

    /** @test */
    public function an_canceled_payment_will_update_the_order_status()
    {
        $order = create(Order::class)->first();

        $paymentResponse     = new MolliePaymentCanceled();
        $paymentResponse->id = $order->transaction_id;

        $this->mockMollie($paymentResponse);

        $this->assertDatabaseHas('butik_orders', ['id' => $order->id, 'status' => 'open']);

        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);
        $this->assertDatabaseHas('butik_orders', [
            'id'             => $order->id,
            'transaction_id' => $paymentResponse->id,
            'status'         => 'canceled',
        ]);
    }

    public function mockMollie($mock)
    {
        Mollie::shouldReceive('api->orders->get')
            ->andReturn($mock);
    }
}
