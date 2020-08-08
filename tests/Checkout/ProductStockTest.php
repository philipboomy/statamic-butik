<?php

namespace Jonassiewertsen\StatamicButik\Tests\Checkout;

use Illuminate\Support\Facades\Mail;
use Jonassiewertsen\StatamicButik\Checkout\Item;
use Jonassiewertsen\StatamicButik\Checkout\Transaction;
use Jonassiewertsen\StatamicButik\Http\Models\Order;
use Jonassiewertsen\StatamicButik\Http\Models\Product;
use Jonassiewertsen\StatamicButik\Http\Models\Variant;
use Jonassiewertsen\StatamicButik\Tests\TestCase;
use Jonassiewertsen\StatamicButik\Tests\Utilities\MolliePaymentSuccessful;
use Mollie\Laravel\Facades\Mollie;

class ProductStockTest extends TestCase
{
    protected $cart;

    public function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /** @test */
    public function the_prodcut_stock_will_be_reduced_by_one_for_a_single_product_after_checkout()
    {
        $order = create(Order::class, ['transaction_id' => 'tr_fake_id'])->first();
        $stock = Product::first()->stock;

        $this->assertEquals($stock, Product::first()->stock);

        $this->mockMollie(new MolliePaymentSuccessful());
        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);

        $this->assertEquals($stock - 1, Product::first()->stock);
    }

    /** @test */
    public function the_prodcut_stock_will_be_reduced_by_the_items_quantity_after_checkout()
    {
        $product = create(Product::class)->first();
        $stock   = $product->stock;

        $item = (new Item($product->slug));
        $item->setQuantity(2);

        $transaction = (new Transaction())->items(collect()->push($item));

        $order = create(Order::class, [
            'transaction_id' => 'tr_fake_id',
            'items'          => json_encode($transaction->items),
        ])->first();

        $this->assertEquals($stock, Product::first()->stock);

        $this->mockMollie(new MolliePaymentSuccessful());
        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);

        $this->assertEquals($stock - 2, $product->fresh()->stock);
    }

    /** @test */
    public function the_variant_stock_will_be_reduced_by_the_items_quantity_after_checkout()
    {
        $variant = create(Variant::class, ['inherit_stock' => false])->first();
        $stock   = $variant->stock;

        $item = (new Item($variant->slug));
        $item->setQuantity(2);

        $transaction = (new Transaction())->items(collect()->push($item));

        $order = create(Order::class, [
            'transaction_id' => 'tr_fake_id',
            'items'          => json_encode($transaction->items),
        ])->first();

        $this->assertEquals($stock, Variant::first()->stock);

        $this->mockMollie(new MolliePaymentSuccessful());
        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);

        $this->assertEquals($stock - 2, $variant->fresh()->stock);
    }

    /** @test */
    public function the_parent_stock_will_be_reduced_if_the_stock_is_inherited()
    {
        $product = create(Product::class)->first();
        $variant = create(Variant::class, [
            'inherit_stock' => true,
            'product_slug'   => $product->slug,
        ])->first();

        $productStock = $product->stock;
        $variantStock = $variant->original_stock;

        $item = (new Item($variant->slug));
        $item->setQuantity(2);

        $transaction = (new Transaction())->items(collect()->push($item));

        $order = create(Order::class, [
            'transaction_id' => 'tr_fake_id',
            'items'          => json_encode($transaction->items),
        ])->first();

        $this->assertEquals($productStock, Product::first()->stock);
        $this->assertEquals($variantStock, Variant::first()->original_stock);

        $this->mockMollie(new MolliePaymentSuccessful());
        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);

        $this->assertEquals($variantStock, Variant::first()->original_stock);
        $this->assertEquals($productStock - 2, Product::first()->stock);
    }

    /** @test */
    public function the_prodcut_stock_wont_be_reduced_on_unlimited_products()
    {
        $order = create(Order::class, ['transaction_id' => 'tr_fake_id'])->first();

        $product                  = Product::first();
        $product->stock_unlimited = true;
        $product->save();

        $stock = Product::first()->stock;

        $this->assertEquals($stock, Product::first()->stock);

        $this->mockMollie(new MolliePaymentSuccessful());
        $this->post(route('butik.payment.webhook.mollie'), ['id' => $order->id]);

        $this->assertEquals($stock, Product::first()->stock);
    }

    public function mockMollie($mock)
    {
        Mollie::shouldReceive('api->orders->get')
            ->andReturn($mock);
    }
}
