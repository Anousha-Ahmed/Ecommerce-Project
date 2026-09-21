<?php

// Load database connection class.
require_once __DIR__ . '/../core/Database.php';

// Create database connection.
$database = new Database();
$mysqli = $database->getConnection();

// GET CATEGORY FILTER

$categoryId = isset($_GET['category'])
    ? (int) $_GET['category']
    : 0;

$searchTerm = isset($_GET['q'])
    ? trim($_GET['q'])
    : '';

// FETCH CATEGORIES (for sidebar filter)

$categoryStmt = $mysqli->prepare(
    'SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC'
);

$categoryStmt->execute();

$categoriesResult = $categoryStmt->get_result();

$categoryStmt2 = $mysqli->prepare(
    'SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC'
);
$categoryStmt2->execute();
$sidebarCategories = $categoryStmt2->get_result();

// FETCH PRODUCTS

$where = 'WHERE p.status = 1 AND c.status = 1';
$params = [];
$types = '';

if ($categoryId > 0) {
    $where .= ' AND p.category_id = ?';
    $params[] = $categoryId;
    $types .= 'i';
}

if ($searchTerm !== '') {
    $where .= ' AND p.name LIKE ?';
    $params[] = "%{$searchTerm}%";
    $types .= 's';
}

$sql = "SELECT
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
         {$where}
         ORDER BY p.id DESC";

$stmt = $mysqli->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$productsResult = $stmt->get_result();
$totalProducts = $productsResult->num_rows;

$pageTitle = 'Shop - Store';

require_once __DIR__ . '/../includes/header.php';
?>

   <!-- PAGE HEADER -->

    <div class="page-header text-center" style="background-color:#f4f4f4; padding:40px 0;">
        <div class="container">
            <h1 class="page-title">
                Shop
                <span>Browse all products</span>
            </h1>
        </div>
    </div>

    <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop</li>
            </ol>
        </div>
    </nav>

    <div class="page-content">
        <div class="container">
            <div class="row">

              <!-- SIDEBAR - CATEGORY FILTER -->

                <aside class="col-lg-3 order-lg-first">
                    <div class="sidebar sidebar-shop">

                        <div class="widget widget-collapsible">
                            <h3 class="widget-title">Category</h3>

                            <div class="widget-body">
                                <ul class="list-unstyled sidebar-cat-list">

                                    <?php $allUrl = 'products.php' . ($searchTerm !== '' ? '?q=' . urlencode($searchTerm) : ''); ?>
                                    <li><a href="<?= $allUrl ?>" class="<?= $categoryId === 0 ? 'active-cat' : '' ?>">All Products</a></li>

                                    <?php while ($category = $sidebarCategories->fetch_assoc()): ?>
                                        <?php $catActive = $categoryId === (int) $category['id'] ? 'active-cat' : ''; ?>
                                        <li><a href="products.php?category=<?= (int) $category['id'] ?>" class="<?= $catActive ?>"><?= htmlspecialchars($category['name']) ?></a></li>
                                    <?php endwhile; ?>

                                </ul>
                            </div>

                            <style>
                                .sidebar-cat-list { line-height: 2.2; }
                                .sidebar-cat-list a { color: #666; text-decoration: none; }
                                .sidebar-cat-list a.active-cat { color: #337ab7; font-weight: 700; }
                                .sidebar-cat-list a:hover { color: #337ab7; }
                            </style>
                        </div>

                    </div>
                </aside>


              <!-- PRODUCT GRID -->

                <div class="col-lg-9">

                    <div class="toolbox">
                        <div class="toolbox-left">
                            <div class="toolbox-info">
                                Showing <span><?= (int) $totalProducts ?></span> Products
                                <?php if ($searchTerm !== ''): ?>
                                    for "<?= htmlspecialchars($searchTerm) ?>"
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="products mb-3">
                        <div class="row">

                            <?php if ($totalProducts > 0): ?>

                                <?php while ($product = $productsResult->fetch_assoc()): ?>

                                    <div class="col-6 col-md-4">
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
                                                </div>

                                            </figure>

                                            <div class="product-body">
                                                <div class="product-cat">
                                                    <a href="products.php"><?= htmlspecialchars($product['category_name']) ?></a>
                                                </div>

                                                <h3 class="product-title">
                                                    <a href="product-detail.php?id=<?= (int) $product['id'] ?>">
                                                        <?= htmlspecialchars($product['name']) ?>
                                                    </a>
                                                </h3>

                                                <div class="product-price">
                                                    Rs. <?= number_format((float) $product['price'], 2) ?>
                                                </div>

                                                <?php if ((int) $product['stock'] > 0): ?>
                                                    <div class="text-success" style="font-size:13px;">
                                                        In Stock: <?= (int) $product['stock'] ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-danger" style="font-size:13px;">
                                                        Out of Stock
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        No products found<?= $searchTerm !== '' ? ' for "' . htmlspecialchars($searchTerm) . '"' : '' ?>.
                                    </div>
                                </div>

                            <?php endif; ?>

                        </div>
                    </div>

                </div>

            </div>=
        </div>
    </div>

<?php

require_once __DIR__ . '/../includes/footer.php';

$stmt->close();
$categoryStmt->close();
$categoryStmt2->close();

?>
