<?php

declare(strict_types=1);

namespace App\Service;

use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeService
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret,
    ) {
        Stripe::setApiKey($this->secretKey);
    }

    /**
     * @throws ApiErrorException
     */
    public function createFixedAmountPaymentIntent(string $customerEmail): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => 2000,
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'customer_email' => $customerEmail,
            ],
        ]);
    }

    /**
     * @throws SignatureVerificationException
     * @throws UnexpectedValueException
     */
    public function constructWebhookEvent(string $payload, string $signatureHeader): Event
    {
        return Webhook::constructEvent($payload, $signatureHeader, $this->webhookSecret);
    }
}
