<?php

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

// Start session.
Session::start();

// AUTHENTICATION

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

// DATABASE

$database = new Database();
$mysqli = $database->getConnection();

// CHECK CART

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

$error = '';

// CHECKOUT FORM

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get shipping address.
    $shippingAddress =
        trim($_POST['shipping_address'] ?? '');

    // Get payment method.
    $paymentMethod =
        $_POST['payment_method'] ?? '';

    // VALIDATION

    if ($shippingAddress === '') {
        $error = 'Shipping address is required.';
    } elseif (
        !in_array(
            $paymentMethod,
            ['cod', 'paypal'],
            true
        )
    ) {
        $error = 'Please choose a payment method.';
    }

    // CREATE ORDER

    if ($error === '') {
        try {
            /*
             * START TRANSACTION
             *
             * Order creation involves multiple queries.
             * We want all of them to succeed together.
             */
            $mysqli->begin_transaction();

            $cartItems = [];

            $totalAmount = 0;

            // CHECK PRODUCTS + STOCK

            foreach ($_SESSION['cart'] as $productId => $quantity) {
                $productId = (int) $productId;
                $quantity = (int) $quantity;

                if ($productId <= 0 || $quantity <= 0) {
                    throw new Exception(
                        'Invalid cart item.'
                    );
                }

                /*
                 * FOR UPDATE locks the selected product row
                 * during this transaction.
                 *
                 * This helps prevent two checkout processes
                 * from incorrectly using the same stock.
                 */
                $stmt = $mysqli->prepare(
                    'SELECT id, name, price, stock
                     FROM products
                     WHERE id = ? AND status = 1
                     FOR UPDATE'
                );

                $stmt->bind_param(
                    'i',
                    $productId
                );

                $stmt->execute();

                $result = $stmt->get_result();

                if ($result->num_rows !== 1) {
                    $stmt->close();

                    throw new Exception(
                        'One of the products is no longer available.'
                    );
                }

                $product = $result->fetch_assoc();

                $stmt->close();

                // Check available stock.
                if (
                    (int) $product['stock'] <
                    $quantity
                ) {
                    throw new Exception(
                        'Not enough stock for: '
                        . $product['name']
                    );
                }

                // Calculate item subtotal.
                $subtotal =
                    (float) $product['price']
                    * $quantity;

                $totalAmount += $subtotal;

                // Store validated item for later insertion.
                $cartItems[] = [
                    'product_id' => $productId,
                    'name' => $product['name'],
                    'quantity' => $quantity,
                    'unit_price' => (float) $product['price'],
                    'subtotal' => $subtotal
                ];
            }

            // GENERATE ORDER NUMBER

            $orderNumber =
                'ORD-'
                . date('YmdHis')
                . '-'
                . random_int(100, 999);

            // Current logged-in user's ID.
            $userId = $auth->userId();

            // INSERT ORDER

            $stmt = $mysqli->prepare(
                "INSERT INTO orders
                (
                    user_id,
                    order_number,
                    total_amount,
                    payment_method,
                    payment_status,
                    order_status,
                    shipping_address
                )
                VALUES (?, ?, ?, ?, 'pending', 'processing', ?)"
            );

            $stmt->bind_param(
                'isdss',
                $userId,
                $orderNumber,
                $totalAmount,
                $paymentMethod,
                $shippingAddress
            );

            if (!$stmt->execute()) {
                $stmt->close();

                throw new Exception(
                    'Failed to create order.'
                );
            }

            // Get newly created order ID.
            $orderId = $stmt->insert_id;

            $stmt->close();

            // INSERT ORDER ITEMS

            foreach ($cartItems as $item) {
                $stmt = $mysqli->prepare(
                    'INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        quantity,
                        unit_price,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->bind_param(
                    'iiidd',
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['subtotal']
                );

                if (!$stmt->execute()) {
                    $stmt->close();

                    throw new Exception(
                        'Failed to create order items.'
                    );
                }

                $stmt->close();

                // REDUCE PRODUCT STOCK

                $stmt = $mysqli->prepare(
                    'UPDATE products
                     SET stock = stock - ?
                     WHERE id = ?'
                );

                $stmt->bind_param(
                    'ii',
                    $item['quantity'],
                    $item['product_id']
                );

                if (!$stmt->execute()) {
                    $stmt->close();

                    throw new Exception(
                        'Failed to update product stock.'
                    );
                }

                $stmt->close();
            }

            // COMMIT TRANSACTION

            $mysqli->commit();

            // CLEAR CART

            /*
             * Order successfully created,
             * so customer's cart is now empty.
             */
            $_SESSION['cart'] = [];

            Session::flash(
                'success',
                'Order placed successfully.'
            );

            header(
                'Location: order-confirmation.php?id='
                . $orderId
            );

            exit;
        } catch (Throwable $e) {
            /*
             * Something went wrong.
             *
             * Rollback cancels all database changes
             * made during this transaction.
             */
            $mysqli->rollback();

            $error =
                $e->getMessage();
        }
    }
}

// FETCH CART PRODUCTS FOR ORDER SUMMARY

$cartProducts = [];
$cartTotal = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $productId = (int) $productId;
    $quantity = (int) $quantity;

    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }

    $stmt = $mysqli->prepare(
        'SELECT id, name, price
         FROM products
         WHERE id = ?
           AND status = 1
         LIMIT 1'
    );

    $stmt->bind_param('i', $productId);
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

   <!-- Page Header -->

    <div class="page-header text-center" style="background-color:#f4f4f4; padding:40px 0;">
        <div class="container">
            <h1 class="page-title">
                Checkout
                <span>Shop</span>
            </h1>
        </div>
    </div>

    <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </div>
    </nav>

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

                        
                             SHIPPING DETAILS
                            

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
                                <input type="radio" id="pay-paypal" name="payment_method" value="paypal" class="custom-control-input" <?= $selectedPayment === 'paypal' ? 'checked' : '' ?> required>
                                <label class="custom-control-label" for="pay-paypal">PayPal</label>
                            </div>
                        </div>


                             <!-- ORDER SUMMARY -->
                        

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
                            </div>\

                            <a href="cart.php" class="btn btn-outline-dark-2 btn-block mt-3">
                                <span>BACK TO CART</span>
                            </a>
                        </aside>

                    </div>
                </form>

            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>