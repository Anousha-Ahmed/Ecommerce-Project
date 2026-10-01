<?php

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../includes/order-functions.php';


// Start session.
Session::start();


// ======================================================
// AUTHENTICATION
// ======================================================

$auth = new Auth();


// Customer must be logged in.
if (!$auth->isLoggedIn()) {

    Session::flash(
        'error',
        'Please login before checkout.'
    );

    header('Location: login.php');
    exit;
}


// ======================================================
// DATABASE
// ======================================================

$database = new Database();
$mysqli = $database->getConnection();


// ======================================================
// CHECK CART
// ======================================================

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {

    Session::flash(
        'error',
        'Your cart is empty.'
    );

    header('Location: cart.php');
    exit;
}


// Show an error coming back from stripe-return.php (if any).
$error = Session::getFlash('error') ?? '';


// ======================================================
// CHECKOUT FORM
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get shipping address.
    $shippingAddress =
        trim($_POST['shipping_address'] ?? '');

    // Get payment method.
    $paymentMethod =
        $_POST['payment_method'] ?? '';


    // ----------------------------------------------
    // VALIDATION
    // ----------------------------------------------

    if ($shippingAddress === '') {

        $error = 'Shipping address is required.';

    } elseif (
        !in_array(
            $paymentMethod,
            ['cod', 'stripe'],
            true
        )
    ) {

        $error = 'Please choose a payment method.';
    }


    if ($error === '') {

        try {

            // ==========================================
            // VALIDATE CART + STOCK
            //
            // Same check is needed for both COD and
            // Stripe, so it happens before we branch.
            // ==========================================

            $cartItems = [];
            $totalAmount = 0;

            foreach ($_SESSION['cart'] as $productId => $quantity) {

                $productId = (int) $productId;
                $quantity = (int) $quantity;

                if ($productId <= 0 || $quantity <= 0) {
                    throw new Exception('Invalid cart item.');
                }

                $stmt = $mysqli->prepare(
                    "SELECT id, name, price, stock
                     FROM products
                     WHERE id = ? AND status = 1"
                );

                $stmt->bind_param("i", $productId);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows !== 1) {
                    $stmt->close();
                    throw new Exception('One of the products is no longer available.');
                }

                $product = $result->fetch_assoc();
                $stmt->close();

                if ((int) $product['stock'] < $quantity) {
                    throw new Exception('Not enough stock for: ' . $product['name']);
                }

                $subtotal = (float) $product['price'] * $quantity;
                $totalAmount += $subtotal;

                $cartItems[] = [
                    'product_id' => $productId,
                    'name'       => $product['name'],
                    'quantity'   => $quantity,
                    'unit_price' => (float) $product['price'],
                    'subtotal'   => $subtotal,
                ];
            }


            // ==========================================
            // COD -> place the order immediately
            // ==========================================

            if ($paymentMethod === 'cod') {

                $orderId = placeOrder(
                    $mysqli,
                    $auth->userId(),
                    $cartItems,
                    $totalAmount,
                    $shippingAddress,
                    'cod',
                    'pending'
                );

                $_SESSION['cart'] = [];

                Session::flash('success', 'Order placed successfully.');

                header('Location: order-confirmation.php?id=' . $orderId);
                exit;
            }


            // ==========================================
            // STRIPE -> send customer to Stripe Checkout
            // ==========================================

            if ($paymentMethod === 'stripe') {

                require_once __DIR__ . '/../core/Stripe.php';

                // Save everything needed to finish the
                // order once Stripe confirms the payment.
                Session::set('stripe_pending_order', [
                    'user_id'          => $auth->userId(),
                    'cart_items'       => $cartItems,
                    'total_amount'     => $totalAmount,
                    'shipping_address' => $shippingAddress,
                ]);

                $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
                $baseUrl  = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

                $stripe = new Stripe();

                $checkoutSession = $stripe->createCheckoutSession(
                    $cartItems,
                    $baseUrl . '/stripe-return.php',
                    $baseUrl . '/stripe-cancel.php'
                );

                header('Location: ' . $checkoutSession['url']);
                exit;
            }

        } catch (Throwable $e) {

            $error = $e->getMessage();
        }
    }
}


// ======================================================
// FETCH CART PRODUCTS FOR ORDER SUMMARY
// ======================================================

$cartProducts = [];
$cartTotal = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {

    $productId = (int) $productId;
    $quantity = (int) $quantity;

    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }

    $stmt = $mysqli->prepare(
        "SELECT id, name, price
         FROM products
         WHERE id = ?
           AND status = 1
         LIMIT 1"
    );

    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $product = $result->fetch_assoc();

        $subtotal = (float) $product['price'] * $quantity;

        $product['quantity'] = $quantity;
        $product['subtotal'] = $subtotal;

        $cartTotal += $subtotal;

        $cartProducts[] = $product;
    }

    $stmt->close();
}


$pageTitle = 'Checkout - Store';

require_once __DIR__ . '/../includes/header.php';
?>

    <!-- ==========================================
         PAGE HEADER
         ========================================== -->

    <div class="page-header text-center" style="background-color:#f4f4f4; padding:40px 0;">
        <div class="container">
            <h1 class="page-title">
                Checkout
                <span>Shop</span>
            </h1>
        </div>
    </div><!-- End .page-header -->

    <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </div>
    </nav><!-- End .breadcrumb-nav -->

    <div class="page-content">
        <div class="checkout">
            <div class="container">

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="checkout.php">
                    <div class="row">

                        <!-- ==========================================
                             SHIPPING DETAILS
                             ========================================== -->

                        <div class="col-lg-9">
                            <h2 class="checkout-title">Shipping Details</h2>

                            <label>Shipping Address *</label>
                            <textarea
                                name="shipping_address"
                                class="form-control"
                                rows="4"
                                placeholder="House number, street, city, postal code..."
                                required
                            ><?= htmlspecialchars($_POST['shipping_address'] ?? '') ?></textarea>

                            <label class="mt-3">Payment Method *</label>

                            <?php $selectedPayment = $_POST['payment_method'] ?? ''; ?>

                            <div class="custom-control custom-radio mt-2">
                                <input type="radio" id="pay-cod" name="payment_method" value="cod" class="custom-control-input" <?= $selectedPayment === 'cod' ? 'checked' : '' ?> required>
                                <label class="custom-control-label" for="pay-cod">Cash on Delivery</label>
                            </div>

                            <div class="custom-control custom-radio mt-2">
                                <input type="radio" id="pay-stripe" name="payment_method" value="stripe" class="custom-control-input" <?= $selectedPayment === 'stripe' ? 'checked' : '' ?> required>
                                <label class="custom-control-label" for="pay-stripe">
                                    Credit / Debit Card (Stripe)
                                </label>
                            </div>
                        </div><!-- End .col-lg-9 -->


                        <!-- ==========================================
                             ORDER SUMMARY
                             ========================================== -->

                        <aside class="col-lg-3">
                            <div class="summary">
                                <h3 class="summary-title">Your Order</h3>

                                <table class="table table-summary">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($cartProducts as $product): ?>
                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($product['name']) ?>
                                                    &times; <?= (int) $product['quantity'] ?>
                                                </td>
                                                <td>Rs. <?= number_format($product['subtotal'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <tr class="summary-subtotal">
                                            <td>Subtotal:</td>
                                            <td>Rs. <?= number_format($cartTotal, 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Shipping:</td>
                                            <td>Free shipping</td>
                                        </tr>
                                        <tr class="summary-total">
                                            <td>Total:</td>
                                            <td>Rs. <?= number_format($cartTotal, 2) ?></td>
                                        </tr>
                                    </tbody>
                                </table>

                                <button type="submit" class="btn btn-outline-primary-2 btn-order btn-block">
                                    <span class="btn-text">Place Order</span>
                                </button>
                            </div><!-- End .summary -->

                            <a href="cart.php" class="btn btn-outline-dark-2 btn-block mt-3">
                                <span>BACK TO CART</span>
                            </a>
                        </aside><!-- End .col-lg-3 -->

                    </div><!-- End .row -->
                </form>

            </div><!-- End .container -->
        </div><!-- End .checkout -->
    </div><!-- End .page-content -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>