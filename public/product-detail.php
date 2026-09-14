<?php

// Load database connection.
require_once __DIR__ . '/../core/Database.php';


// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// ======================================================
// GET PRODUCT ID
// ======================================================

/*
 * Product ID URL se aa rahi hai:
 *
 * product-detail.php?id=3
 *
 * (int) user input ko integer mein convert karta hai.
 */
$productId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


// If no valid ID was provided, stop here.
if ($productId <= 0) {

    die('Invalid product ID.');
}


// ======================================================
// FETCH PRODUCT
// ======================================================

/*
 * Product ke saath category ka naam bhi chahiye.
 *
 * products.category_id
 *        ↓
 * categories.id
 */
$stmt = $mysqli->prepare(
    "SELECT
        p.id,
        p.name,
        p.slug,
        p.description,
        p.price,
        p.stock,
        p.image,
        p.created_at,
        c.name AS category_name
     FROM products AS p
     INNER JOIN categories AS c
        ON p.category_id = c.id
     WHERE p.id = ?
       AND p.status = 1
       AND c.status = 1
     LIMIT 1"
);


// Product ID is an integer.
$stmt->bind_param("i", $productId);


// Execute query.
$stmt->execute();


// Get result.
$result = $stmt->get_result();


// Product does not exist.
if ($result->num_rows !== 1) {

    $stmt->close();

    die('Product not found.');
}


// Get product data.
$product = $result->fetch_assoc();


// Close statement.
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
        <?= htmlspecialchars($product['name']) ?>
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body>


<div class="container py-5">


    <!-- ==========================================
         PRODUCT DETAILS
         ========================================== -->

    <div class="row g-5">


        <!-- ==========================================
             PRODUCT IMAGE
             ========================================== -->

        <div class="col-md-6">


            <?php if (!empty($product['image'])): ?>

                <img
                    src="uploads/products/<?= htmlspecialchars(
                        $product['image']
                    ) ?>"
                    alt="<?= htmlspecialchars(
                        $product['name']
                    ) ?>"
                    class="img-fluid rounded"
                    style="
                        width: 100%;
                        max-height: 500px;
                        object-fit: cover;
                    "
                >

            <?php else: ?>

                <div
                    class="d-flex
                           align-items-center
                           justify-content-center
                           bg-light
                           rounded"
                    style="height: 400px;"
                >

                    <span class="text-muted">
                        No image available
                    </span>

                </div>

            <?php endif; ?>


        </div>


        <!-- ==========================================
             PRODUCT INFORMATION
             ========================================== -->

        <div class="col-md-6">


            <!-- Category -->

            <p class="text-muted mb-2">

                Category:
                <?= htmlspecialchars(
                    $product['category_name']
                ) ?>

            </p>


            <!-- Product name -->

            <h1 class="mb-3">

                <?= htmlspecialchars(
                    $product['name']
                ) ?>

            </h1>


            <!-- Price -->

            <h2 class="mb-3">

                Rs.
                <?= number_format(
                    (float) $product['price'],
                    2
                ) ?>

            </h2>


            <!-- Stock -->

            <?php if ((int) $product['stock'] > 0): ?>

                <p class="text-success">

                    In Stock:
                    <?= (int) $product['stock'] ?>

                </p>

            <?php else: ?>

                <p class="text-danger">

                    Out of Stock

                </p>

            <?php endif; ?>


            <!-- Description -->

            <h5 class="mt-4">
                Description
            </h5>

            <p class="text-muted">

                <?= nl2br(
                    htmlspecialchars(
                        $product['description'] ?? ''
                    )
                ) ?>

            </p>


            <!-- ==========================================
                 ACTION BUTTONS
                 ========================================== -->

            <div class="mt-4">


                <?php if ((int) $product['stock'] > 0): ?>

                    <a
                        href="cart.php?add=<?= (int) $product['id'] ?>"
                        class="btn btn-success"
                    >
                        Add to Cart
                    </a>

                <?php else: ?>

                    <button
                        type="button"
                        class="btn btn-secondary"
                        disabled
                    >
                        Out of Stock
                    </button>

                <?php endif; ?>


                <a
                    href="products.php"
                    class="btn btn-outline-secondary"
                >
                    Back to Products
                </a>


            </div>


        </div>


    </div>


</div>


</body>

</html>