<?php

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';


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


$error = '';


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
            ['cod', 'paypal'],
            true
        )
    ) {

        $error = 'Invalid payment method.';
    }


    // ==================================================
    // CREATE ORDER
    // ==================================================

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


            // ==========================================
            // CHECK PRODUCTS + STOCK
            // ==========================================

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
                    "SELECT id, name, price, stock
                     FROM products
                     WHERE id = ? AND status = 1
                     FOR UPDATE"
                );

                $stmt->bind_param(
                    "i",
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
                    (int) $product['stock']
                    < $quantity
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
                    'quantity' => $quantity,
                    'unit_price' => (float) $product['price'],
                    'subtotal' => $subtotal
                ];
            }


            // ==========================================
            // GENERATE ORDER NUMBER
            // ==========================================

            $orderNumber =
                'ORD-'
                . date('YmdHis')
                . '-'
                . random_int(100, 999);


            // Current logged-in user's ID.
            $userId = $auth->userId();


            // ==========================================
            // INSERT ORDER
            // ==========================================

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
                "isdss",
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


            // ==========================================
            // INSERT ORDER ITEMS
            // ==========================================

            foreach ($cartItems as $item) {

                $stmt = $mysqli->prepare(
                    "INSERT INTO order_items
                    (
                        order_id,
                        product_id,
                        quantity,
                        unit_price,
                        subtotal
                    )
                    VALUES (?, ?, ?, ?, ?)"
                );


                $stmt->bind_param(
                    "iiidd",
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


                // ======================================
                // REDUCE PRODUCT STOCK
                // ======================================

                $stmt = $mysqli->prepare(
                    "UPDATE products
                     SET stock = stock - ?
                     WHERE id = ?"
                );


                $stmt->bind_param(
                    "ii",
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


            // ==========================================
            // COMMIT TRANSACTION
            // ==========================================

            /*
             * All database operations succeeded.
             * Permanently save them.
             */
            $mysqli->commit();


            // ==========================================
            // CLEAR CART
            // ==========================================

            /*
             * Order successfully created,
             * so customer's cart is now empty.
             */
            $_SESSION['cart'] = [];


            // ==========================================
            // SUCCESS
            // ==========================================

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


// ======================================================
// GET FLASH MESSAGE
// ======================================================

$successMessage =
    Session::getFlash('success');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body>

<div class="container py-5">

    <h1 class="mb-4">
        Checkout
    </h1>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <!-- ==========================================
             SHIPPING ADDRESS
             ========================================== -->

        <div class="mb-3">

            <label
                for="shipping_address"
                class="form-label"
            >
                Shipping Address
            </label>

            <textarea
                name="shipping_address"
                id="shipping_address"
                class="form-control"
                rows="4"
                required
            ><?= htmlspecialchars(
                $_POST['shipping_address'] ?? ''
            ) ?></textarea>

        </div>


        <!-- ==========================================
             PAYMENT METHOD
             ========================================== -->

        <div class="mb-3">

            <label
                for="payment_method"
                class="form-label"
            >
                Payment Method
            </label>


            <select
                name="payment_method"
                id="payment_method"
                class="form-select"
                required
            >

                <option value="">
                    Select payment method
                </option>

                <option
                    value="cod"
                    <?= (
                        ($_POST['payment_method'] ?? '')
                        === 'cod'
                    ) ? 'selected' : '' ?>
                >
                    Cash on Delivery
                </option>

                <option
                    value="paypal"
                    <?= (
                        ($_POST['payment_method'] ?? '')
                        === 'paypal'
                    ) ? 'selected' : '' ?>
                >
                    PayPal
                </option>

            </select>

        </div>


        <button
            type="submit"
            class="btn btn-success"
        >
            Place Order
        </button>


        <a
            href="cart.php"
            class="btn btn-secondary"
        >
            Back to Cart
        </a>


    </form>

</div>

</body>

</html> 