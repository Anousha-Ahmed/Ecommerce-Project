<?php

// Load database connection class.
require_once __DIR__ . '/../core/Database.php';


// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();


// ======================================================
// GET CATEGORY FILTER
// ======================================================

/*
 * Category filter URL se aa sakta hai:
 *
 * products.php?category=3
 *
 * Agar category nahi di gayi to
 * saare active products show honge.
 */
$categoryId = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;


// ======================================================
// FETCH CATEGORIES
// ======================================================

/*
 * Categories ko bhi fetch kar rahe hain
 * taake customer products ko category ke
 * through filter kar sake.
 */
$categoryStmt = $mysqli->prepare(
    "SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC"
);

$categoryStmt->execute();

$categoriesResult = $categoryStmt->get_result();


// ======================================================
// FETCH PRODUCTS
// ======================================================

/*
 * products.category_id ko categories.id ke saath
 * JOIN kiya gaya hai.
 *
 * Is se product ke saath category ka naam bhi
 * mil jata hai.
 */
if ($categoryId > 0) {

    // If a category was selected.
    $stmt = $mysqli->prepare(
        "SELECT
            p.id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.stock,
            p.image,
            c.name AS category_name
         FROM products AS p
         INNER JOIN categories AS c
            ON p.category_id = c.id
         WHERE p.status = 1
           AND c.status = 1
           AND p.category_id = ?
         ORDER BY p.id DESC"
    );

    // category_id is an integer.
    $stmt->bind_param("i", $categoryId);

} else {

    // No category filter: show all active products.
    $stmt = $mysqli->prepare(
        "SELECT
            p.id,
            p.name,
            p.slug,
            p.description,
            p.price,
            p.stock,
            p.image,
            c.name AS category_name
         FROM products AS p
         INNER JOIN categories AS c
            ON p.category_id = c.id
         WHERE p.status = 1
           AND c.status = 1
         ORDER BY p.id DESC"
    );
}


// Execute product query.
$stmt->execute();


// Get product result.
$productsResult = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Products</title>


    <!-- Temporary Bootstrap styling.
         Later we can integrate the provided template. -->
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

        <h1>
            Products
        </h1>

        <p class="text-muted">
            Browse our available products.
        </p>

    </div>


    <!-- ==========================================
         CATEGORY FILTER
         ========================================== -->

    <div class="mb-4">

        <h5 class="mb-3">
            Categories
        </h5>


        <!-- All products -->

        <a
            href="products.php"
            class="btn
            <?= $categoryId === 0
                ? 'btn-primary'
                : 'btn-outline-primary' ?>"
        >
            All
        </a>


        <?php while ($category = $categoriesResult->fetch_assoc()): ?>

            <a
                href="products.php?category=<?= (int) $category['id'] ?>"
                class="btn
                <?= $categoryId === (int) $category['id']
                    ? 'btn-primary'
                    : 'btn-outline-primary' ?>"
            >

                <?= htmlspecialchars(
                    $category['name']
                ) ?>

            </a>

        <?php endwhile; ?>

    </div>


    <!-- ==========================================
         PRODUCTS
         ========================================== -->

    <div class="row g-4">


        <?php if ($productsResult->num_rows > 0): ?>


            <?php while ($product = $productsResult->fetch_assoc()): ?>

                <div class="col-md-4 col-lg-3">


                    <div class="card h-100 shadow-sm">


                        <!-- Product image -->

                        <?php if (!empty($product['image'])): ?>

                            <img
                                src="uploads/products/<?= htmlspecialchars(
                                    $product['image']
                                ) ?>"
                                class="card-img-top"
                                alt="<?= htmlspecialchars(
                                    $product['name']
                                ) ?>"
                                style="
                                    height: 220px;
                                    object-fit: cover;
                                "
                            >

                        <?php else: ?>

                            <div
                                class="d-flex
                                       align-items-center
                                       justify-content-center
                                       bg-light"
                                style="height: 220px;"
                            >

                                <span class="text-muted">
                                    No image
                                </span>

                            </div>

                        <?php endif; ?>


                        <!-- Product information -->

                        <div class="card-body d-flex flex-column">


                            <!-- Category -->

                            <small class="text-muted mb-2">

                                <?= htmlspecialchars(
                                    $product['category_name']
                                ) ?>

                            </small>


                            <!-- Product name -->

                            <h5 class="card-title">

                                <?= htmlspecialchars(
                                    $product['name']
                                ) ?>

                            </h5>


                            <!-- Description -->

                            <p class="card-text text-muted">

                                <?php
                                $description =
                                    $product['description'] ?? '';

                                if (
                                    strlen($description) > 100
                                ) {

                                    echo htmlspecialchars(
                                        substr($description, 0, 100)
                                    ) . '...';

                                } else {

                                    echo htmlspecialchars(
                                        $description
                                    );
                                }
                                ?>

                            </p>


                            <!-- Price -->

                            <h5 class="mb-2">

                                Rs.
                                <?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>

                            </h5>


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


                            <!-- Buttons -->

                            <div class="mt-auto">


                                <!-- View product -->

                                <a
                                    href="product-detail.php?id=<?= (int) $product['id'] ?>"
                                    class="btn btn-outline-primary"
                                >
                                    View Details
                                </a>


                                <!-- Add to cart -->

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


                            </div>


                        </div>

                    </div>


                </div>

            <?php endwhile; ?>


        <?php else: ?>


            <div class="col-12">

                <div class="alert alert-info">

                    No products found.

                </div>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>


<?php

// Close prepared statement.
$stmt->close();

$categoryStmt->close();

?>