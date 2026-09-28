<?php

// Protect this page: only logged-in admins can access it.
require_once __DIR__ . '/../auth-check.php';

// Load database connection class.
require_once __DIR__ . '/../../core/Database.php';

// Load session class for flash messages.
require_once __DIR__ . '/../../core/Session.php';


// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// Fetch products, joined with category name.
$stmt = $mysqli->prepare(
    "SELECT
        products.id,
        products.name,
        products.slug,
        products.description,
        products.price,
        products.stock,
        products.image,
        products.status,
        products.created_at,
        categories.name AS category_name
     FROM products
     INNER JOIN categories
        ON products.category_id = categories.id
     ORDER BY products.id DESC"
);

$stmt->execute();

$result = $stmt->get_result();

$successMessage = Session::getFlash('success');
$errorMessage = Session::getFlash('error');

$basePath = '../';
$pageTitle = 'Products';

require_once __DIR__ . '/../../includes/admin-header.php';
?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success text-white">
            <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger text-white">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">

                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Products</h6>
                    <a href="create.php" class="btn bg-gradient-dark btn-sm mb-0">
                        <i class="material-symbols-rounded text-sm align-middle">add</i>
                        Add Product
                    </a>
                </div>

                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7 ps-4">Product</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Category</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Price</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Stock</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-uppercase text-xs font-weight-bolder opacity-7">Created</th>
                                    <th></th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if ($result->num_rows > 0): ?>

                                    <?php while ($product = $result->fetch_assoc()): ?>

                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex px-2 py-1 align-items-center">
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="../../public/uploads/products/<?= htmlspecialchars($product['image']) ?>" class="avatar avatar-sm me-3" style="object-fit:cover;" alt="<?= htmlspecialchars($product['name']) ?>">
                                                    <?php else: ?>
                                                        <div class="avatar avatar-sm me-3 bg-gradient-secondary d-flex align-items-center justify-content-center">
                                                            <i class="material-symbols-rounded text-white text-sm">image</i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($product['name']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0">#<?= (int) $product['id'] ?></p>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($product['category_name']) ?></p>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">Rs. <?= number_format((float) $product['price'], 2) ?></p>
                                            </td>

                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?= (int) $product['stock'] ?></p>
                                            </td>

                                            <td>
                                                <?php if ((int) $product['status'] === 1): ?>
                                                    <span class="badge badge-sm bg-gradient-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-sm bg-gradient-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($product['created_at']) ?></p>
                                            </td>

                                            <td class="text-end pe-4">
                                                <a href="edit.php?id=<?= (int) $product['id'] ?>" class="text-secondary font-weight-bold text-xs" title="Edit"><i class="material-symbols-rounded text-sm align-middle">edit</i></a>
                                            </td>
                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No products found.</td>
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