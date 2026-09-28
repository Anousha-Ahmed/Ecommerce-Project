<?php

require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../core/Database.php';

$database = new Database();
$mysqli = $database->getConnection();

// Total products
$totalProducts = $mysqli->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];

// Total categories
$totalCategories = $mysqli->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc()['total'];

// Total orders
$totalOrders = $mysqli->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'];

// Total customers (role = customer)
$totalCustomers = $mysqli->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'")->fetch_assoc()['total'];

// Total revenue from paid orders (cancelled orders are not counted)
$revenueRow = $mysqli->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM orders
     WHERE payment_status = 'paid'
       AND order_status != 'cancelled'"
)->fetch_assoc();

$totalRevenue = (float) $revenueRow['total'];

// Latest 5 orders for the recent orders table
$recentOrders = $mysqli->query(
    "SELECT o.id, o.order_number, o.total_amount, o.payment_status, o.order_status, o.created_at, u.name AS customer_name
     FROM orders AS o
     INNER JOIN users AS u ON o.user_id = u.id
     ORDER BY o.id DESC
     LIMIT 5"
);

$basePath = '';
$pageTitle = 'Dashboard';

require_once __DIR__ . '/../includes/admin-header.php';
?>

    <!-- ==========================================
         STAT CARDS
         ========================================== -->

    <div class="row">

        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Products</p>
                                <h5 class="font-weight-bolder"><?= (int) $totalProducts ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                <i class="material-symbols-rounded opacity-10">inventory_2</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Categories</p>
                                <h5 class="font-weight-bolder"><?= (int) $totalCategories ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-dark shadow-dark text-center rounded-circle">
                                <i class="material-symbols-rounded opacity-10">category</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Orders</p>
                                <h5 class="font-weight-bolder"><?= (int) $totalOrders ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                <i class="material-symbols-rounded opacity-10">shopping_cart</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-capitalize font-weight-bold">Revenue</p>
                                <h5 class="font-weight-bolder">Rs. <?= number_format($totalRevenue, 2) ?></h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                <i class="material-symbols-rounded opacity-10">payments</i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- End .row -->


    <!-- ==========================================
         RECENT ORDERS
         ========================================== -->

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Recent Orders</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7 ps-4">Order #</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Customer</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Amount</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Payment</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if ($recentOrders->num_rows > 0): ?>

                                    <?php while ($order = $recentOrders->fetch_assoc()): ?>

                                        <tr>
                                            <td class="ps-4"><?= htmlspecialchars($order['order_number']) ?></td>
                                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                            <td>Rs. <?= number_format((float) $order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge badge-sm bg-gradient-<?= $order['payment_status'] === 'paid' ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars(ucfirst($order['payment_status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-sm bg-gradient-info">
                                                    <?= htmlspecialchars(ucfirst($order['order_status'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-sm"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                            <td class="text-end pe-4">
                                                <a href="orders/detail.php?id=<?= (int) $order['id'] ?>" class="text-secondary font-weight-bold text-xs">View</a>
                                            </td>
                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No orders yet.</td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>