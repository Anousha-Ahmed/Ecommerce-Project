<?php

// Protect this page: only logged-in admins can access it.
require_once __DIR__ . '/../auth-check.php';

// Load database connection class.
require_once __DIR__ . '/../../core/Database.php';

// Load session class for flash messages.
require_once __DIR__ . '/../../core/Session.php';


// ==========================================
// DATABASE CONNECTION
// ==========================================

// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// ==========================================
// READ PRODUCTS
// ==========================================

/*
 * Fetch products from the database.
 *
 * We also use JOIN to get the category name.
 *
 * products.category_id
 *          ↓
 * categories.id
 */
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


// Execute SELECT query.
$stmt->execute();


// Get result.
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products - Admin</title>


    <!-- Temporary Bootstrap styling.
         Later admin template will replace this. -->
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

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="mb-1">
                Products
            </h1>

            <p class="text-muted mb-0">
                Manage your products.
            </p>

        </div>


        <!-- Add Product button -->

        <a
            href="create.php"
            class="btn btn-primary"
        >
            Add Product
        </a>

    </div>


    <!-- ==========================================
         FLASH SUCCESS MESSAGE
         ========================================== -->

    <?php $successMessage = Session::getFlash('success'); ?>

    <?php if ($successMessage): ?>

        <div class="alert alert-success">

            <?= htmlspecialchars($successMessage) ?>

        </div>

    <?php endif; ?>


    <!-- ==========================================
         PRODUCTS TABLE
         ========================================== -->

    <div class="card shadow-sm">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">


                    <!-- TABLE HEADER -->

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Image</th>

                            <th>Name</th>

                            <th>Category</th>

                            <th>Price</th>

                            <th>Stock</th>

                            <th>Status</th>

                            <th>Created</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <!-- TABLE BODY -->

                    <tbody>


                    <?php if ($result->num_rows > 0): ?>


                        <?php while ($product = $result->fetch_assoc()): ?>

                            <tr>


                                <!-- Product ID -->

                                <td>

                                    <?= (int) $product['id'] ?>

                                </td>


                                <!-- Product Image -->

                                <td>

                                    <?php if (!empty($product['image'])): ?>

                                        <img
                                            src="../../public/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>"
                                            width="70"
                                            height="50"
                                            style="object-fit: cover;"
                                        >

                                    <?php else: ?>

                                        <span class="text-muted">
                                            No image
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Product Name -->

                                <td>

                                    <?= htmlspecialchars($product['name']) ?>

                                </td>


                                <!-- Category Name -->

                                <td>

                                    <?= htmlspecialchars($product['category_name']) ?>

                                </td>


                                <!-- Price -->

                                <td>

                                    $<?= number_format(
                                        (float) $product['price'],
                                        2
                                    ) ?>

                                </td>


                                <!-- Stock -->

                                <td>

                                    <?= (int) $product['stock'] ?>

                                </td>


                                <!-- Status -->

                                <td>

                                    <?php if ((int) $product['status'] === 1): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Created date -->

                                <td>

                                    <?= htmlspecialchars(
                                        $product['created_at']
                                    ) ?>

                                </td>


                                <!-- Actions -->

                                <td>

                                    <a
                                        href="edit.php?id=<?= (int) $product['id'] ?>"
                                        class="btn btn-sm btn-warning"
                                    >
                                        Edit
                                    </a>


                                    <!-- Delete will be connected
                                         when we build delete.php -->

                                    <a
                                        href="delete.php?id=<?= (int) $product['id'] ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this product?');"
                                    >
                                        Delete
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
                                No products found.
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