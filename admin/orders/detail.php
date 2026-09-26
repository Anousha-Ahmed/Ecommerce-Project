<?php

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load required classes.
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';

$database = new Database();
$mysqli = $database->getConnection();

// View button sends: detail.php?id=1
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    die('Invalid order ID.');
}

$errors = [];

// Handle order/payment status update.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $orderStatus = $_POST['order_status'] ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';

    $validOrderStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];
    $validPaymentStatuses = ['pending', 'paid', 'failed'];

    if (
        in_array($orderStatus, $validOrderStatuses, true) &&
        in_array($paymentStatus, $validPaymentStatuses, true)
    ) {

        $stmt = $mysqli->prepare(
            "UPDATE orders
             SET order_status = ?,
                 payment_status = ?
             WHERE id = ?"
        );

        $stmt->bind_param("ssi", $orderStatus, $paymentStatus, $id);

        if ($stmt->execute()) {
            Session::flash('success', 'Order updated successfully.');
        } else {
            $errors['database'] = 'Failed to update order.';
        }

        $stmt->close();

        header('Location: detail.php?id=' . $id);
        exit;

    } else {

        $errors['database'] = 'Invalid status selected.';
    }
}

// Fetch order + customer info.
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
     WHERE o.id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    die('Order not found.');
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch order items.
$itemsStmt = $mysqli->prepare(
    "SELECT
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        p.id AS product_id,
        p.name AS product_name,
        p.image AS product_image
     FROM order_items AS oi
     INNER JOIN products AS p
        ON oi.product_id = p.id
     WHERE oi.order_id = ?"
);

$itemsStmt->bind_param("i", $id);
$itemsStmt->execute();

$itemsResult = $itemsStmt->get_result();

$successMessage = Session::getFlash('success');

$basePath = '../';
$pageTitle = 'Order Detail';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success text-white">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($errors['database'])): ?>
        <div class="alert alert-danger text-white">
            <?= htmlspecialchars($errors['database']) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Order <?= htmlspecialchars($order['order_number']) ?></h5>
        <a href="index.php" class="btn btn-outline-dark btn-sm mb-0">Back</a>
    </div>

    <div class="row">

        <!-- ==========================================
             ORDER ITEMS
             ========================================== -->

        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Order Items</h6>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7 ps-4">Product</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Unit Price</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Qty</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php while ($item = $itemsResult->fetch_assoc()): ?>

                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex px-2 py-1 align-items-center">
                                                <?php if (!empty($item['product_image'])): ?>
                                                    <img src="../../public/uploads/products/<?= htmlspecialchars($item['product_image']) ?>" class="avatar avatar-sm me-3" style="object-fit:cover;" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                                <?php else: ?>
                                                    <div class="avatar avatar-sm me-3 bg-gradient-secondary d-flex align-items-center justify-content-center">
                                                        <i class="material-symbols-rounded text-white text-sm">image</i>
                                                    </div>
                                                <?php endif; ?>
                                                <h6 class="mb-0 text-sm"><?= htmlspecialchars($item['product_name']) ?></h6>
                                            </div>
                                        </td>
                                        <td><p class="text-xs font-weight-bold mb-0">Rs. <?= number_format((float) $item['unit_price'], 2) ?></p></td>
                                        <td><p class="text-xs font-weight-bold mb-0"><?= (int) $item['quantity'] ?></p></td>
                                        <td><p class="text-xs font-weight-bold mb-0">Rs. <?= number_format((float) $item['subtotal'], 2) ?></p></td>
                                    </tr>

                                <?php endwhile; ?>

                                <tr>
                                    <td colspan="3" class="ps-4 text-end"><p class="text-sm font-weight-bolder mb-0">Total</p></td>
                                    <td><p class="text-sm font-weight-bolder mb-0">Rs. <?= number_format((float) $order['total_amount'], 2) ?></p></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header pb-0">
                    <h6>Shipping Address</h6>
                </div>
                <div class="card-body">
                    <p class="text-sm mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
            </div>
        </div>

        <!-- ==========================================
             CUSTOMER + STATUS
             ========================================== -->

        <div class="col-lg-4">

            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Customer</h6>
                </div>
                <div class="card-body">
                    <p class="text-sm mb-1"><strong><?= htmlspecialchars($order['customer_name']) ?></strong></p>
                    <p class="text-sm text-secondary mb-0"><?= htmlspecialchars($order['customer_email']) ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header pb-0">
                    <h6>Update Status</h6>
                </div>
                <div class="card-body">

                    <form method="POST" action="">

                        <div class="mb-3">
                            <label class="form-label">Order Status</label>
                            <select name="order_status" class="form-control">
                                <?php foreach (['processing', 'shipped', 'delivered', 'cancelled'] as $statusOption): ?>
                                    <option value="<?= $statusOption ?>" <?= $order['order_status'] === $statusOption ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($statusOption)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-control">
                                <?php foreach (['pending', 'paid', 'failed'] as $statusOption): ?>
                                    <option value="<?= $statusOption ?>" <?= $order['payment_status'] === $statusOption ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($statusOption)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars(strtoupper($order['payment_method'])) ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Order Date</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($order['created_at']) ?>" disabled>
                        </div>

                        <button type="submit" class="btn bg-gradient-dark w-100">Update Order</button>

                    </form>

                </div>
            </div>

        </div>

    </div>

<?php

require_once __DIR__ . '/../../includes/admin-footer.php';

$itemsStmt->close();

?>