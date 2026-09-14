<?php

// Load required classes.
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';


// Start session.
Session::start();


// Create Auth object.
$auth = new Auth();


// ======================================================
// CHECK LOGIN
// ======================================================

/*
 * Only a logged-in customer can view
 * their order confirmation.
 */
if (!$auth->isLoggedIn()) {

    header('Location: login.php');
    exit;
}


// ======================================================
// DATABASE CONNECTION
// ======================================================

$database = new Database();
$mysqli = $database->getConnection();


// ======================================================
// GET ORDER ID
// ======================================================

/*
 * Checkout redirects here like:
 *
 * order-confirmation.php?id=5
 *
 * So we read the order ID from the URL.
 */
$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


// Make sure a valid order ID exists.
if ($orderId <= 0) {

    die('Invalid order ID.');
}


// ======================================================
// GET CURRENT USER ID
// ======================================================

$userId = $auth->userId();


// ======================================================
// FETCH ORDER
// ======================================================

/*
 * IMPORTANT:
 *
 * We use BOTH:
 *
 *     o.id = ?
 *     o.user_id = ?
 *
 * This means a customer can only view
 * their own order.
 */
$stmt = $mysqli->prepare(
    "SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.shipping_address,
        o.created_at
     FROM orders AS o
     WHERE o.id = ?
       AND o.user_id = ?
     LIMIT 1"
);


// Both values are integers.
$stmt->bind_param(
    "ii",
    $orderId,
    $userId
);


// Execute query.
$stmt->execute();


// Get result.
$result = $stmt->get_result();


// Order not found.
if ($result->num_rows !== 1) {

    $stmt->close();

    die('Order not found.');
}


// Get order data.
$order = $result->fetch_assoc();

$stmt->close();


// ======================================================
// FETCH ORDER ITEMS
// ======================================================

/*
 * order_items contains the products
 * that belong to this order.
 *
 * We JOIN products to get the product name.
 */
$stmt = $mysqli->prepare(
    "SELECT
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        p.name AS product_name
     FROM order_items AS oi
     INNER JOIN products AS p
        ON oi.product_id = p.id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC"
);


$stmt->bind_param(
    "i",
    $orderId
);


$stmt->execute();


$itemsResult = $stmt->get_result();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Order Confirmation
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body>


<div class="container py-5">


    <!-- ==========================================
         SUCCESS MESSAGE
         ========================================== -->

    <div class="alert alert-success">

        <h4 class="alert-heading">
            Order Placed Successfully! 🎉
        </h4>

        <p class="mb-0">

            Thank you for your order.

        </p>

    </div>


    <!-- ==========================================
         ORDER INFORMATION
         ========================================== -->

    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h4 class="mb-3">
                Order Information
            </h4>


            <p>

                <strong>
                    Order Number:
                </strong>

                <?= htmlspecialchars(
                    $order['order_number']
                ) ?>

            </p>


            <p>

                <strong>
                    Payment Method:
                </strong>

                <?= htmlspecialchars(
                    strtoupper(
                        $order['payment_method']
                    )
                ) ?>

            </p>


            <p>

                <strong>
                    Payment Status:
                </strong>

                <?= htmlspecialchars(
                    ucfirst(
                        $order['payment_status']
                    )
                ) ?>

            </p>


            <p>

                <strong>
                    Order Status:
                </strong>

                <?= htmlspecialchars(
                    ucfirst(
                        $order['order_status']
                    )
                ) ?>

            </p>


            <p>

                <strong>
                    Shipping Address:
                </strong>

                <br>

                <?= nl2br(
                    htmlspecialchars(
                        $order['shipping_address']
                    )
                ) ?>

            </p>


            <p class="mb-0">

                <strong>
                    Order Date:
                </strong>

                <?= htmlspecialchars(
                    $order['created_at']
                ) ?>

            </p>

        </div>

    </div>


    <!-- ==========================================
         ORDER ITEMS
         ========================================== -->

    <div class="card shadow-sm">

        <div class="card-body">

            <h4 class="mb-3">
                Order Items
            </h4>


            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>
                                Product
                            </th>

                            <th>
                                Unit Price
                            </th>

                            <th>
                                Quantity
                            </th>

                            <th>
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $item = $itemsResult->fetch_assoc()
                    ): ?>

                        <tr>

                            <td>

                                <?= htmlspecialchars(
                                    $item['product_name']
                                ) ?>

                            </td>


                            <td>

                                Rs.
                                <?= number_format(
                                    (float) $item['unit_price'],
                                    2
                                ) ?>

                            </td>


                            <td>

                                <?= (int) $item['quantity'] ?>

                            </td>


                            <td>

                                Rs.
                                <?= number_format(
                                    (float) $item['subtotal'],
                                    2
                                ) ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>


                    </tbody>


                    <tfoot>

                        <tr>

                            <th
                                colspan="3"
                                class="text-end"
                            >

                                Total:

                            </th>

                            <th>

                                Rs.
                                <?= number_format(
                                    (float) $order['total_amount'],
                                    2
                                ) ?>

                            </th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </div>


    <!-- ==========================================
         NAVIGATION
         ========================================== -->

    <div class="mt-4">

        <a
            href="products.php"
            class="btn btn-primary"
        >
            Continue Shopping
        </a>

    </div>


</div>


</body>

</html>