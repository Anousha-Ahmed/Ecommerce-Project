<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';


// ==========================================
// DATABASE CONNECTION
// ==========================================

$database = new Database();
$mysqli = $database->getConnection();


// ==========================================
// FETCH ORDERS
// ==========================================

/*
 * We need information from two tables:
 *
 * orders
 * users
 *
 * orders.user_id = users.id
 *
 * This JOIN allows us to show the customer's
 * name and email along with the order.
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
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email
     FROM orders AS o
     INNER JOIN users AS u
        ON o.user_id = u.id
     ORDER BY o.id DESC"
);


// Execute SELECT query.
$stmt->execute();


// Get result.
$result = $stmt->get_result();


// Get flash messages.
$successMessage = Session::getFlash('success');
$errorMessage = Session::getFlash('error');

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Orders - Admin</title>


    <!-- Temporary Bootstrap styling.
         Later we will integrate the admin template. -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body>


<div class="container py-5">


    <!-- ==========================================
         PAGE HEADING
         ========================================== -->

    <div class="mb-4">

        <h1 class="mb-1">
            Orders
        </h1>

        <p class="text-muted mb-0">
            Manage customer orders.
        </p>

    </div>


    <!-- ==========================================
         FLASH MESSAGES
         ========================================== -->

    <?php if ($successMessage !== null): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($successMessage) ?>

        </div>

    <?php endif; ?>


    <?php if ($errorMessage !== null): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars($errorMessage) ?>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         ORDERS TABLE
         ========================================== -->

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Order #</th>

                            <th>Customer</th>

                            <th>Total</th>

                            <th>Payment</th>

                            <th>Payment Status</th>

                            <th>Order Status</th>

                            <th>Date</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($result->num_rows > 0): ?>


                        <?php while ($order = $result->fetch_assoc()): ?>

                            <tr>


                                <!-- Order ID -->

                                <td>

                                    <?= (int) $order['id'] ?>

                                </td>


                                <!-- Order Number -->

                                <td>

                                    <?= htmlspecialchars(
                                        $order['order_number']
                                    ) ?>

                                </td>


                                <!-- Customer -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $order['customer_name']
                                        ) ?>

                                    </strong>

                                    <br>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $order['customer_email']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- Total Amount -->

                                <td>

                                    Rs.
                                    <?= number_format(
                                        (float) $order['total_amount'],
                                        2
                                    ) ?>

                                </td>


                                <!-- Payment Method -->

                                <td>

                                    <?= htmlspecialchars(
                                        strtoupper(
                                            $order['payment_method']
                                        )
                                    ) ?>

                                </td>


                                <!-- Payment Status -->

                                <td>

                                    <?php
                                    $paymentStatus =
                                        $order['payment_status'];
                                    ?>


                                    <?php if ($paymentStatus === 'completed'): ?>

                                        <span class="badge bg-success">

                                            Completed

                                        </span>

                                    <?php elseif ($paymentStatus === 'failed'): ?>

                                        <span class="badge bg-danger">

                                            Failed

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-warning text-dark">

                                            Pending

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Order Status -->

                                <td>

                                    <?php
                                    $orderStatus =
                                        $order['order_status'];
                                    ?>


                                    <?php if ($orderStatus === 'delivered'): ?>

                                        <span class="badge bg-success">

                                            Delivered

                                        </span>

                                    <?php elseif ($orderStatus === 'shipped'): ?>

                                        <span class="badge bg-info text-dark">

                                            Shipped

                                        </span>

                                    <?php elseif ($orderStatus === 'cancelled'): ?>

                                        <span class="badge bg-danger">

                                            Cancelled

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-warning text-dark">

                                            Processing

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Created Date -->

                                <td>

                                    <?= htmlspecialchars(
                                        $order['created_at']
                                    ) ?>

                                </td>


                                <!-- Actions -->

                                <td>

                                    <a
                                        href="detail.php?id=<?= (int) $order['id'] ?>"
                                        class="btn btn-sm btn-primary"
                                    >
                                        View
                                    </a>

                                </td>


                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="9"
                                class="text-center text-muted py-4"
                            >

                                No orders found.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>


</body>

</html>


<?php

// Close prepared statement.
$stmt->close();

?>