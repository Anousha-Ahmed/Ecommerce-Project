<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only logged-in admins can access this page.
require_once __DIR__ . '/../auth-check.php';

// Load database connection class.
require_once __DIR__ . '/../../core/Database.php';

$database = new Database();
$mysqli = $database->getConnection();

// Fetch customers, with their total order count.
$stmt = $mysqli->prepare(
    "SELECT
        u.id,
        u.name,
        u.email,
        u.is_active,
        u.created_at,
        COUNT(o.id) AS order_count
     FROM users AS u
     LEFT JOIN orders AS o
        ON o.user_id = u.id
     WHERE u.role = 'customer'
     GROUP BY u.id
     ORDER BY u.id DESC"
);

$stmt->execute();

$result = $stmt->get_result();

$basePath = '../';
$pageTitle = 'Customers';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">

                <div class="card-header pb-0">
                    <h6>Customers</h6>
                </div>

                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7 ps-4">Customer</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Email</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Orders</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Joined</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($result->num_rows > 0): ?>

                                    <?php while ($customer = $result->fetch_assoc()): ?>

                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex px-2 py-1 align-items-center">
                                                    <div class="avatar avatar-sm me-3 bg-gradient-dark d-flex align-items-center justify-content-center">
                                                        <i class="material-symbols-rounded text-white text-sm">person</i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($customer['name']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0">#<?= (int) $customer['id'] ?></p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($customer['email']) ?></p>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?= (int) $customer['order_count'] ?></p>
                                            </td>

                                            <td>
                                                <?php if ((int) $customer['is_active'] === 1): ?>
                                                    <span class="badge badge-sm bg-gradient-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-sm bg-gradient-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($customer['created_at']) ?></p>
                                            </td>
                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No customers found.</td>
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