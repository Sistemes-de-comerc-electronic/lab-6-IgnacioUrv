<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\PaymentTransaction;
use App\Repository\PaymentTransactionRepository;
use App\Service\PurchaseMailer;
use App\Service\StripeService;
use JsonException;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PaymentController extends AbstractController
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly PurchaseMailer $purchaseMailer,
        private readonly PaymentTransactionRepository $paymentTransactionRepository,
        #[Autowire('%env(STRIPE_PUBLIC_KEY)%')]
        private readonly string $stripePublicKey,
    ) {
    }

    #[Route('/payment/checkout', name: 'payment_checkout', methods: ['GET'])]
    public function checkout(): Response
    {
        $publicKey = htmlspecialchars($this->stripePublicKey, ENT_QUOTES, 'UTF-8');
        $html = <<<HTML
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checkout Stripe</title>
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
  <main style="max-width:460px;margin:3rem auto;font-family:Arial,sans-serif;">
    <h1>Pagament</h1>
    <p>Import fix: <strong>20,00 €</strong></p>
    <form id="payment-form">
      <label for="email">Correu electrònic</label><br>
      <input id="email" type="email" required style="width:100%;padding:8px;margin:8px 0 12px;"><br>
      <label for="card-element">Targeta</label>
      <div id="card-element" style="border:1px solid #ccc;padding:10px;border-radius:4px;margin-top:8px;"></div>
      <button type="submit" style="margin-top:14px;padding:10px 14px;">Pagar 20€</button>
      <p id="result" style="margin-top:12px;"></p>
    </form>
  </main>
  <script>
    const stripe = Stripe('{$publicKey}');
    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    const form = document.getElementById('payment-form');
    const result = document.getElementById('result');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      result.textContent = 'Processant pagament...';

      const email = document.getElementById('email').value;
      const intentResponse = await fetch('/payment/intent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email }),
      });

      const intentData = await intentResponse.json();
      if (!intentResponse.ok) {
        result.textContent = intentData.error || 'No s\\'ha pogut iniciar el pagament.';
        return;
      }

      const confirmation = await stripe.confirmCardPayment(intentData.client_secret, {
        payment_method: {
          card,
          billing_details: { email },
        },
      });

      if (confirmation.error) {
        result.textContent = confirmation.error.message || 'Pagament fallit.';
        return;
      }

      result.textContent = 'Pagament completat. Rebràs un correu d\\'agraïment en breu.';
    });
  </script>
</body>
</html>
HTML;

        return new Response($html);
    }

    #[Route('/payment/intent', name: 'payment_intent', methods: ['POST'])]
    public function createIntent(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? '';
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Valid email is required'], Response::HTTP_BAD_REQUEST);
        }

        $paymentIntent = $this->stripeService->createFixedAmountPaymentIntent($email);

        return new JsonResponse([
            'client_secret' => $paymentIntent->client_secret,
            'amount' => 20.00,
            'currency' => 'eur',
        ], Response::HTTP_OK);
    }

    #[Route('/payment/webhook', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $signature = $request->headers->get('Stripe-Signature');
        if (!$signature) {
            return new Response('Missing Stripe-Signature header', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = $this->stripeService->constructWebhookEvent($request->getContent(), $signature);
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException) {
            return new Response('Invalid webhook payload or signature', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type !== 'payment_intent.succeeded') {
            return new Response('', Response::HTTP_OK);
        }

        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;
        $paymentIntentId = (string) $paymentIntent->id;
        if ($this->paymentTransactionRepository->findOneByPaymentIntentId($paymentIntentId)) {
            return new Response('', Response::HTTP_OK);
        }

        $customerEmail = (string) ($paymentIntent->metadata['customer_email'] ?? '');
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return new Response('Missing customer email metadata', Response::HTTP_BAD_REQUEST);
        }

        $amount = (int) ($paymentIntent->amount_received ?: $paymentIntent->amount);
        $currency = strtoupper((string) $paymentIntent->currency);

        $transaction = new PaymentTransaction(
            paymentIntentId: $paymentIntentId,
            amount: $amount,
            currency: $currency,
            customerEmail: $customerEmail,
        );
        $this->paymentTransactionRepository->save($transaction, true);

        $this->purchaseMailer->sendThankYouEmail($customerEmail);

        return new Response('', Response::HTTP_OK);
    }
}
