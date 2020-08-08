<?php

namespace Jonassiewertsen\StatamicButik\Http\Controllers\PaymentGateways;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Jonassiewertsen\StatamicButik\Checkout\Customer;
use Jonassiewertsen\StatamicButik\Checkout\Transaction;
use Jonassiewertsen\StatamicButik\Events\PaymentSubmitted;
use Jonassiewertsen\StatamicButik\Events\PaymentSuccessful;
use Jonassiewertsen\StatamicButik\Http\Traits\MollyLocale;
use Jonassiewertsen\StatamicButik\Http\Traits\MoneyTrait;
use Mollie\Laravel\Facades\Mollie;

class MolliePaymentGateway extends PaymentGateway implements PaymentGatewayInterface
{
    use MollyLocale;
    use MoneyTrait;

    /**
     * All data from this transaction
     */
    protected Transaction $transaction;

    public function handle(Customer $customer, Collection $items, string $totalPrice)
    {
        $orderId          = str_random(20);

//        $payment = Mollie::api()->payments()->get($payment->id);

        $payment = Mollie::api()->orders()->create([
            'amount' => [
                'currency' => config('butik.currency_isoCode'),
                'value'    => $totalPrice,
            ],
            'billingAddress' => [
                'givenName'       => $customer->name,
                'familyName'      => $customer->name,
                'streetAndNumber' => $customer->address1 . ', ' . $customer->address2,
                'city'            => $customer->city,
                'postalCode'      => $customer->zip,
                'country'         => $customer->country,
                'email'           => $customer->mail,
            ],
            'orderNumber' => $orderId,
            'locale'      => $this->getLocale(),
            'webhookUrl' => env('MOLLIE_NGROK_REDIRECT') . route('butik.payment.webhook.mollie', [], false),
            'redirectUrl' => URL::temporarySignedRoute('butik.payment.receipt', now()->addMinutes(5), ['order' => $orderId]),
            'lines'       => $this->mapItems($items),
        ]);

        $this->transaction = (new Transaction())
            ->id($orderId)
            ->transactionId($payment->id)
            ->method($payment->method ?? '')
            ->totalAmount($totalPrice)
            ->createdAt(Carbon::parse($payment->createdAt))
            ->items($items)
            ->customer($customer);

        event(new PaymentSubmitted($this->transaction));

        // redirect customer to Mollie checkout page
        return redirect($payment->getCheckoutUrl(), 303);
    }

    public function webhook(Request $request)
    {
        if (!$request->has('id')) {
            return;
        }

        $payment = Mollie::api()->orders()->get($request->id);

        switch ($payment->status) {
            case 'paid':
                $this->setOrderStatusToPaid($payment);
                event(new PaymentSuccessful($payment));
                break;
            case 'authorized':
                // TODO: Add authorized action
                break;
            case 'completed':
                // TODO: Add completion action
                break;
            case 'expired':
                $this->setOrderStatusToExpired($payment);
                break;
            case 'canceled':
                $this->setOrderStatusToCanceled($payment);
                break;
        }
    }

    private function mapItems($items)
    {
        return $items->map(function($item) {
            return [
                'type'           => 'physical',
                'sku'            => $item->slug,
                'name'           => $item->name,
                'imageUrl'       => $this->images[0] ?? null,
                'quantity'       => $item->getQuantity(),
                'vatRate'        => (string) number_format($item->taxRate, 2),
                'unitPrice'      => [
                    'currency' => config('butik.currency_isoCode'),
                    'value'    => $this->humanPriceWithDot($item->singlePrice()),
                ],
                'totalAmount'    => [
                    'currency' => config('butik.currency_isoCode'),
                    'value'    => $this->humanPriceWithDot($item->totalPrice()),
                ],
                'vatAmount'      => [
                    'currency' => config('butik.currency_isoCode'),
                    'value'    => $this->humanPriceWithDot($item->taxAmount),
                ]
            ];
        })->toArray();
    }

    private function paymentInformation($items, $mollieCustomer, $orderId)
    {
        $payment = [
            'description' => 'ORDER ' . $orderId,
            'customerId'  => $mollieCustomer->id,
            'metadata'    => $this->generateMetaData($items, $orderId),
            'locale'      => $this->getLocale(),
            'redirectUrl' => URL::temporarySignedRoute('butik.payment.receipt', now()->addMinutes(5), ['order' => $orderId]),
            'amount'      => [
                'currency' => config('butik.currency_isoCode'),
                'value'    => $this->totalPrice,
            ],
        ];

        if (!App::environment(['local'])) {
            // Only adding the mollie webhook, when not in local environment
            $payment = array_merge($payment, [
                'webhookUrl' => route('butik.payment.webhook.mollie'),
            ]);
        } elseif (App::environment(['local']) && $this->ngrokSet()) {
            // When local env and the the NGROK has been set, it will add the ngrok url
            $route = env('MOLLIE_NGROK_REDIRECT') . route('butik.payment.webhook.mollie', [], false);

            $payment = array_merge($payment, [
                'webhookUrl' => $route,
            ]);
        }

        return $payment;
    }

    private function ngrokSet(): bool
    {
        return env('MOLLIE_NGROK_REDIRECT', false) == true;
    }
}
