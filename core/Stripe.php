<?php

/**
 * Small helper class around Stripe's REST API (Checkout Sessions).
 *
 * Flow used by this project:
 *
 * 1. checkout.php       -> createCheckoutSession() -> redirect customer to Stripe
 * 2. Stripe hosted page -> customer pays
 * 3. stripe-return.php  -> getSession() -> save the real order in DB
 * 4. stripe-cancel.php  -> customer cancelled, go back to checkout
 */

class Stripe
{
    private string $secretKey;
    private string $apiBase = 'https://api.stripe.com/v1';

    public function __construct()
    {
        require_once __DIR__ . '/../config/stripe.php';

        $this->secretKey = STRIPE_SECRET_KEY;
    }


    // ======================================================
    // CREATE CHECKOUT SESSION
    // ======================================================

    /**
     * Creates a Stripe Checkout Session from the cart items and
     * returns Stripe's response (contains "id" and the "url"
     * the customer must be redirected to).
     */
    public function createCheckoutSession(array $cartItems, string $successUrl, string $cancelUrl): array
    {
        $body = [
            'mode'        => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancelUrl,
        ];

        foreach ($cartItems as $index => $item) {

            $body['line_items'][$index]['quantity'] = $item['quantity'];

            $body['line_items'][$index]['price_data']['currency'] = STRIPE_CURRENCY;

            $body['line_items'][$index]['price_data']['product_data']['name'] = $item['name'];

            // Stripe wants the amount in the smallest currency unit
            // (cents), not rupees/dollars.
            $body['line_items'][$index]['price_data']['unit_amount'] =
                (int) round($item['unit_price'] * 100);
        }

        $ch = curl_init($this->apiBase . '/checkout/sessions');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            // Stripe uses HTTP Basic Auth: secret key as username, no password.
            CURLOPT_USERPWD        => $this->secretKey . ':',
            CURLOPT_POSTFIELDS     => http_build_query($body),
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            throw new Exception('Stripe connection error: ' . $curlError);
        }

        $data = json_decode($response, true);

        if (!isset($data['id'])) {
            $message = $data['error']['message'] ?? 'Failed to create Stripe checkout session.';
            throw new Exception($message);
        }

        return $data;
    }


    // ======================================================
    // RETRIEVE A SESSION (used after redirect back from Stripe)
    // ======================================================

    public function getSession(string $sessionId): array
    {
        $ch = curl_init($this->apiBase . '/checkout/sessions/' . $sessionId);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->secretKey . ':',
        ]);

        $response = curl_exec($ch);

        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data['id'])) {
            throw new Exception('Could not retrieve Stripe session.');
        }

        return $data;
    }
}