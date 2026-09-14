<?php

/**
 * PayPal Sandbox Configuration
 *
 * IMPORTANT:
 * Never put real/live PayPal credentials in your
 * GitHub repository.
 */

// PayPal Sandbox API URL.
define(
    'PAYPAL_API_BASE_URL',
    'https://api-m.sandbox.paypal.com'
);


// PayPal Sandbox Client ID.
//
// Is value ko baad mein apne PayPal Developer
// Sandbox App ke Client ID se replace karna hai.
define(
    'PAYPAL_CLIENT_ID',
    'YOUR_SANDBOX_CLIENT_ID'
);


// PayPal Sandbox Client Secret.
//
// Is value ko apne Sandbox App ke Client Secret
// se replace karna hai.
define(
    'PAYPAL_CLIENT_SECRET',
    'YOUR_SANDBOX_CLIENT_SECRET'
);


// Currency used by PayPal.
//
// IMPORTANT:
// PayPal payments ke liye hum USD use karenge.
define(
    'PAYPAL_CURRENCY',
    'USD'
);