<?php

require_once __DIR__ . '/../core/Database.php';

$database = new Database();
$mysqli   = $database->getConnection();


// ======================================================
// FEATURED CATEGORIES (for the "Shop by Category" row)
// ======================================================

$categoriesResult = $mysqli->query(
    "SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC
     LIMIT 6"
);


// ======================================================
// FEATURED PRODUCTS (latest 8 active products)
// ======================================================

$featuredResult = $mysqli->query(
    "SELECT
        p.id,
        p.name,
        p.price,
        p.stock,
        p.image,
        c.name AS category_name
     FROM products AS p
     INNER JOIN categories AS c
        ON p.category_id = c.id
     WHERE p.status = 1
       AND c.status = 1
     ORDER BY p.id DESC
     LIMIT 8"
);


$pageTitle = 'Home - Store';

require_once __DIR__ . '/../includes/header.php';
?>

    <!-- ==========================================
         HERO BANNER
         ========================================== -->

    <div class="intro-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="intro-slide" style="background-color:#f4f4f4; border-radius:4px; padding:60px 40px; text-align:center;">
                        <h4 class="intro-subtitle" style="letter-spacing:2px; color:#777;">NEW SEASON ARRIVALS</h4>
                        <h1 class="intro-title" style="font-size:42px; font-weight:700; margin:10px 0 20px;">Shop the Latest Trends</h1>
                        <a href="products.php" class="btn btn-primary btn-round">
                            <span>Shop Now</span>
                            <i class="icon-long-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- End .intro-section -->


    <!-- ==========================================
         SHOP BY CATEGORY
         ========================================== -->

    <div class="container mt-6 mb-6">

        <div class="heading heading-flex mb-3">
            <div class="heading-left">
                <h2 class="title">Shop by Category</h2>
            </div>
        </div>

        <div class="row">

            <?php if ($categoriesResult && $categoriesResult->num_rows > 0): ?>

                <?php while ($category = $categoriesResult->fetch_assoc()): ?>

                    <div class="col-6 col-md-4 col-lg-2 mb-4">
                        <a href="products.php?category=<?= (int) $category['id'] ?>" class="d-block text-center" style="text-decoration:none;">
                            <div style="background:#f4f4f4; border-radius:4px; padding:30px 10px; transition:.3s;">
                                <i class="icon-tag" style="font-size:28px; color:#337ab7;"></i>
                                <h6 class="mt-2 mb-0" style="color:#222;">
                                    <?= htmlspecialchars($category['name']) ?>
                                </h6>
                            </div>
                        </a>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12">
                    <p class="text-muted">No categories yet. Add some from the admin panel.</p>
                </div>

            <?php endif; ?>

        </div>
    </div><!-- End Shop by Category -->


    <!-- ==========================================
         FEATURED PRODUCTS
         ========================================== -->

    <div class="container mb-6">

        <div class="heading heading-flex mb-3">
            <div class="heading-left">
                <h2 class="title">Featured Products</h2>
            </div>
            <div class="heading-right">
                <a href="products.php" class="link-underline">View All<i class="icon-long-arrow-right"></i></a>
            </div>
        </div>

        <div class="products">
            <div class="row">

                <?php if ($featuredResult && $featuredResult->num_rows > 0): ?>

                    <?php while ($product = $featuredResult->fetch_assoc()): ?>

                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="product product-7 text-center">

                                <figure class="product-media">

                                    <?php if ((int) $product['stock'] <= 0): ?>
                                        <span class="product-label label-out-of-stock">Out of Stock</span>
                                    <?php endif; ?>

                                    <a href="product-detail.php?id=<?= (int) $product['id'] ?>">
                                        <?php if (!empty($product['image'])): ?>
                                            <img
                                                src="uploads/products/<?= htmlspecialchars($product['image']) ?>"
                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                                class="product-image"
                                            >
                                        <?php else: ?>
                                            <img
                                                src="assets/images/products/product-1.jpg"
                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                                class="product-image"
                                            >
                                        <?php endif; ?>
                                    </a>

                                    <div class="product-action">
                                        <?php if ((int) $product['stock'] > 0): ?>
                                            <a href="cart.php?add=<?= (int) $product['id'] ?>" class="btn-product btn-cart">
                                                <span>add to cart</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="product-detail.php?id=<?= (int) $product['id'] ?>" class="btn-product btn-cart">
                                                <span>view details</span>
                                            </a>
                                        <?php endif; ?>
                                    </div><!-- End .product-action -->

                                </figure><!-- End .product-media -->

                                <div class="product-body">
                                    <div class="product-cat">
                                        <a href="#"><?= htmlspecialchars($product['category_name']) ?></a>
                                    </div>

                                    <h3 class="product-title">
                                        <a href="product-detail.php?id=<?= (int) $product['id'] ?>">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h3>

                                    <div class="product-price">
                                        Rs. <?= number_format((float) $product['price'], 2) ?>
                                    </div>
                                </div><!-- End .product-body -->

                            </div><!-- End .product -->
                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="col-12">
                        <p class="text-muted">No products yet. Add some from the admin panel.</p>
                    </div>

                <?php endif; ?>

            </div><!-- End .row -->
        </div><!-- End .products -->
    </div><!-- End Featured Products -->


    <!-- ==========================================
         CALL TO ACTION
         ========================================== -->

    <div class="container mb-6">
        <div class="row">
            <div class="col-12">
                <div style="background:#222; border-radius:4px; padding:50px; text-align:center; color:#fff;">
                    <h3 class="mb-2" style="color:#fff;">Free Shipping on Orders Over Rs. 5000</h3>
                    <p class="mb-3" style="color:#ccc;">Quality products delivered right to your door.</p>
                    <a href="products.php" class="btn btn-outline-primary-2"><span>Shop Now</span><i class="icon-long-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>