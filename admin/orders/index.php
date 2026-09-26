<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';

$database = new Database();
$mysqli = $database->getConnection();

// Fetch orders, joined with customer info.
$stmt = $mysqli->prepare(
    "SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email
     FROM orders AS o
     INNER JOIN users AS u
        ON o.user_id = u.id
     ORDER BY o.id DESC"
);

$stmt->execute();

$result = $stmt->get_result();

$successMessage = Session::getFlash('success');
$errorMessage = Session::getFlash('error');

$basePath = '../';
$pageTitle = 'Orders';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success text-white">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage !== null): ?>
        <div class="alert alert-danger text-white">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">

                <div class="card-header pb-0">
                    <h6>Orders</h6>
                </div>

                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7 ps-4">Order #</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Customer</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Total</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Payment</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Payment Status</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Order Status</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Date</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($result->num_rows > 0): ?>

                                    <?php while ($order = $result->fetch_assoc()): ?>

                                        <?php
                                        $paymentStatus = $order['payment_status'];
                                        $paymentBadge = 'secondary';
                                        if ($paymentStatus === 'paid') { $paymentBadge = 'success'; }
                                        elseif ($paymentStatus === 'failed') { $paymentBadge = 'danger'; }

                                        $orderStatus = $order['order_status'];
                                        $orderBadge = 'secondary';
                                        if ($orderStatus === 'delivered') { $orderBadge = 'success'; }
                                        elseif ($orderStatus === 'shipped') { $orderBadge = 'info'; }
                                        elseif ($orderStatus === 'cancelled') { $orderBadge = 'danger'; }
                                        elseif ($orderStatus === 'processing') { $orderBadge = 'warning'; }
                                        ?>

                                        <tr>
                                            <td class="ps-4">
                                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($order['order_number']) ?></p>
                                                <p class="text-xs text-secondary mb-0">#<?= (int) $order['id'] ?></p>
                                            </td>

                                            <td>
                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($order['customer_name']) ?></h6>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($order['customer_email']) ?></p>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">Rs. <?= number_format((float) $order['total_amount'], 2) ?></p>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars(strtoupper($order['payment_method'])) ?></p>
                                            </td>

                                            <td>
                                                <span class="badge badge-sm bg-gradient-<?= $paymentBadge ?>"><?= htmlspecialchars(ucfirst($paymentStatus)) ?></span>
                                            </td>

                                            <td>
                                                <span class="badge badge-sm bg-gradient-<?= $orderBadge ?>"><?= htmlspecialchars(ucfirst($orderStatus)) ?></span>
                                            </td>

                                            <td>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($order['created_at']) ?></p>
                                            </td>

                                            <td class="text-end pe-4">
                                                <a href="detail.php?id=<?= (int) $order['id'] ?>" class="text-secondary font-weight-bold text-xs">View</a>
                                            </td>
                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No orders found.</td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

require_once __DIR__ . '/../../includes/admin-footer.php';

$stmt->close();

?>