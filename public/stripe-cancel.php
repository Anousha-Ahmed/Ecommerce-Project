<?php

/**
 * Customer clicked "Back" on Stripe's hosted page, or
 * closed the tab before paying. Nothing was charged and
 * no order was created, so we just clean up and send
 * them back to checkout.
 */

require_once __DIR__ . '/../core/Session.php';

Session::start();

Session::remove('stripe_pending_order');

Session::flash('error', 'Stripe payment was cancelled. Your cart is still saved.');

header('Location: checkout.php');
exit;