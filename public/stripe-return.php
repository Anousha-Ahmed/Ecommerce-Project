<?php

/**
 * Customer lands here after paying on Stripe's hosted
 * Checkout page (Stripe redirects here automatically
 * with ?session_id=cs_test_...).
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Stripe.php';
require_once __DIR__ . '/../includes/order-functions.php';

Session::start();

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}


$sessionId = $_GET['session_id'] ?? '';

$pendingOrder = Session::get('stripe_pending_order');


if ($sessionId === '' || $pendingOrder === null) {

    Session::flash('error', 'We could not find your Stripe payment. Please try again.');

    header('Location: checkout.php');
    exit;
}


try {

    $stripe = new Stripe();

    $checkoutSession = $stripe->getSession($sessionId);

    $paymentStatus = $checkoutSession['payment_status'] ?? '';


    if ($paymentStatus !== 'paid') {

        throw new Exception('Payment was not completed. Please try again.');
    }


    // ==========================================
    // PAYMENT CONFIRMED -> SAVE THE REAL ORDER
    // ==========================================

    $database = new Database();
    $mysqli = $database->getConnection();

    $orderId = placeOrder(
        $mysqli,
        $pendingOrder['user_id'],
        $pendingOrder['cart_items'],
        $pendingOrder['total_amount'],
        $pendingOrder['shipping_address'],
        'stripe',
        'paid'
    );


    // Payment succeeded and order is saved -> clean up.
    $_SESSION['cart'] = [];
    Session::remove('stripe_pending_order');

    Session::flash('success', 'Payment successful. Order placed!');

    header('Location: order-confirmation.php?id=' . $orderId);
    exit;

} catch (Throwable $e) {

    Session::flash('error', $e->getMessage());

    header('Location: checkout.php');
    exit;
}