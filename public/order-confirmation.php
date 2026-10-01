<?php

// Load required classes.
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';

// Start session.
Session::start();

// Create Auth object.
$auth = new Auth();

// CHECK LOGIN

/*
 * Only a logged-in customer can view
 * their order confirmation.
 */
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// DATABASE CONNECTION

$database = new Database();
$mysqli = $database->getConnection();

// GET ORDER ID

$orderId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

// Make sure a valid order ID exists.
if ($orderId <= 0) {
    die('Invalid order ID.');
}

// GET CURRENT USER ID

$userId = $auth->userId();

// FETCH ORDER

$stmt = $mysqli->prepare(
    'SELECT
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
     LIMIT 1'
);

// Both values are integers.
$stmt->bind_param(
    'ii',
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

// FETCH ORDER ITEMS

$stmt = $mysqli->prepare(
    'SELECT
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        p.name AS product_name,
        p.image AS product_image
     FROM order_items AS oi
     INNER JOIN products AS p
        ON oi.product_id = p.id
     WHERE oi.order_id = ?
     ORDER BY oi.id ASC'
);

$stmt->bind_param(
    'i',
    $orderId
);

$stmt->execute();

$itemsResult = $stmt->get_result();

$stmt->close();


// Pick a badge color depending on the status.
$paymentBadge = 'secondary';
if ($order['payment_status'] === 'paid') { $paymentBadge = 'success'; }
elseif ($order['payment_status'] === 'failed') { $paymentBadge = 'danger'; }

$orderBadge = 'secondary';
if ($order['order_status'] === 'delivered') { $orderBadge = 'success'; }
elseif ($order['order_status'] === 'shipped') { $orderBadge = 'info'; }
elseif ($order['order_status'] === 'cancelled') { $orderBadge = 'danger'; }
elseif ($order['order_status'] === 'processing') { $orderBadge = 'warning'; }


$pageTitle = 'Order Confirmation - Store';

require_once __DIR__ . '/../includes/header.php';
?>

    <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order Confirmation</li>
            </ol>
        </div>
    </nav>

    <div class="page-content pb-6">
        <div class="container">

            <!-- SUCCESS MESSAGE -->

            <div class="text-center py-4">
                <i class="icon-check-circle" style="font-size:60px; color:#28a745;"></i>
                <h2 class="mt-3 mb-1">Order Placed Successfully!</h2>
                <p class="text-muted">Thank you for your order. A confirmation has been saved to your account.</p>
            </div>

            <div class="row">

                <!-- ORDER ITEMS -->

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Order Items</h5>

                            <table class="table table-cart table-mobile">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php while ($item = $itemsResult->fetch_assoc()): ?>

                                        <tr>
                                            <td class="product-col">
                                                <div class="product">
                                                    <figure class="product-media" style="width:60px; height:60px;">
                                                        <?php if (!empty($item['product_image'])): ?>
                                                            <img src="uploads/products/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                                        <?php else: ?>
                                                            <img src="assets/images/products/product-1.jpg" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                                        <?php endif; ?>
                                                    </figure>
                                                    <h3 class="product-title" style="font-size:14px;"><?= htmlspecialchars($item['product_name']) ?></h3>
                                                </div>
                                            </td>
                                            <td>Rs. <?= number_format((float) $item['unit_price'], 2) ?></td>
                                            <td><?= (int) $item['quantity'] ?></td>
                                            <td>Rs. <?= number_format((float) $item['subtotal'], 2) ?></td>
                                        </tr>

                                    <?php endwhile; ?>

                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total</strong></td>
                                        <td><strong>Rs. <?= number_format((float) $order['total_amount'], 2) ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="mb-2">Shipping Address</h5>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                        </div>
                    </div>
                </div>


                <!-- ORDER INFO -->

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Order Information</h5>

                            <p class="mb-2"><strong>Order Number:</strong><br><?= htmlspecialchars($order['order_number']) ?></p>

                            <p class="mb-2"><strong>Payment Method:</strong><br><?= htmlspecialchars(strtoupper($order['payment_method'])) ?></p>

                            <p class="mb-2">
                                <strong>Payment Status:</strong><br>
                                <span class="badge badge-<?= $paymentBadge ?>" style="padding:5px 10px;"><?= htmlspecialchars(ucfirst($order['payment_status'])) ?></span>
                            </p>

                            <p class="mb-2">
                                <strong>Order Status:</strong><br>
                                <span class="badge badge-<?= $orderBadge ?>" style="padding:5px 10px;"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span>
                            </p>

                            <p class="mb-0"><strong>Order Date:</strong><br><?= htmlspecialchars($order['created_at']) ?></p>
                        </div>
                    </div>

                    <a href="products.php" class="btn btn-outline-primary-2 btn-block mt-3">
                        <span>CONTINUE SHOPPING</span>
                        <i class="icon-long-arrow-right"></i>
                    </a>
                </div>

            </div>

        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>