<?php

// Start session because cart data is stored in the session.
require_once __DIR__ . '/../core/Session.php';

Session::start();

// Database connection.
require_once __DIR__ . '/../core/Database.php';

$database = new Database();
$mysqli = $database->getConnection();

// CART SETUP

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ADD PRODUCT TO CART

if (isset($_GET['add'])) {
    $productId = (int) $_GET['add'];

    if ($productId > 0) {
        // Find active product.
        $stmt = $mysqli->prepare(
            'SELECT id, name, price, stock
             FROM products
             WHERE id = ?
               AND status = 1
             LIMIT 1'
        );

        $stmt->bind_param('i', $productId);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $product = $result->fetch_assoc();

            // Current quantity in cart.
            $currentQuantity =
                $_SESSION['cart'][$productId] ?? 0;

            // Increase quantity by 1.
            $newQuantity = $currentQuantity + 1;

            /*
             * Never allow cart quantity
             * to become greater than stock.
             */
            if ($newQuantity <= (int) $product['stock']) {
                $_SESSION['cart'][$productId] = $newQuantity;
            }
        }

        $stmt->close();
    }

    header('Location: cart.php');

    exit;
}

// REMOVE PRODUCT FROM CART

if (isset($_GET['remove'])) {
    $productId = (int) $_GET['remove'];

    unset($_SESSION['cart'][$productId]);

    header('Location: cart.php');

    exit;
}

// UPDATE CART

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $quantities = $_POST['quantity'] ?? [];

        foreach ($quantities as $productId => $quantity) {
            $productId = (int) $productId;

            $quantity = (int) $quantity;

            // Ignore invalid product IDs.
            if ($productId <= 0) {
                continue;
            }

            // CHECK PRODUCT STOCK

            $stmt = $mysqli->prepare(
                'SELECT stock
                 FROM products
                 WHERE id = ?
                   AND status = 1
                 LIMIT 1'
            );

            $stmt->bind_param('i', $productId);

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $product = $result->fetch_assoc();

                $stock = (int) $product['stock'];

                if ($quantity <= 0) {
                    unset($_SESSION['cart'][$productId]);
                } elseif ($stock > 0) {
                    $_SESSION['cart'][$productId] =
                        min($quantity, $stock);
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
            }

            $stmt->close();
        }

        // Redirect after updating.
        header('Location: cart.php');

        exit;
    }
}

// FETCH CART PRODUCTS

$cartProducts = [];

$cartTotal = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $productId = (int) $productId;

    $quantity = (int) $quantity;

    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }

    // Fetch current product information.
    $stmt = $mysqli->prepare(
        'SELECT
            id,
            name,
            price,
            stock,
            image
         FROM products
         WHERE id = ?
           AND status = 1
         LIMIT 1'
    );

    $stmt->bind_param('i', $productId);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();

        $stock = (int) $product['stock'];

        if ($quantity > $stock) {
            if ($stock > 0) {
                $quantity = $stock;

                $_SESSION['cart'][$productId] = $quantity;
            } else {
                // Product is completely out of stock.
                unset($_SESSION['cart'][$productId]);

                $stmt->close();

                continue;
            }
        }

        // Add quantity to product data.
        $product['quantity'] = $quantity;

        // Calculate product subtotal.
        $product['subtotal'] =
            (float) $product['price'] * $quantity;

        // Add to complete cart total.
        $cartTotal += $product['subtotal'];

        // Add product to cart products array.
        $cartProducts[] = $product;
    }

    $stmt->close();
}

$pageTitle = 'Shopping Cart - Store';

require_once __DIR__ . '/../includes/header.php';
?>
<!-- PAGE HEADER -->
    <div class="page-header text-center" style="background-image:url('assets/images/backgrounds/bg-4.jpg'); background-size:cover; background-position:center; padding:150px 0; position:relative;">
    <div style="background:rgba(0,0,0,0.45); position:absolute; inset:0;"></div>
       <div class="container" style="position:relative; z-index:1;">
            <h1 class="page-title text-white">
                Shopping Cart
                <!-- <span>Shop</span> -->
            </h1>
        </div>
    </div>

    <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Shop</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
            </ol>
        </div>
    </nav>

    <div class="page-content">
        <div class="cart">
            <div class="container">

                <?php if (empty($cartProducts)): ?>

                    <!-- EMPTY CART -->

                    <div class="text-center py-5">
                        <p class="mb-4" style="font-size:18px; color:#777;">Your cart is currently empty.</p>
                        <a href="products.php" class="btn btn-outline-primary-2">
                            <span>CONTINUE SHOPPING</span>
                            <i class="icon-long-arrow-right"></i>
                        </a>
                    </div>

                <?php else: ?>

                    <form method="POST" action="cart.php">
                        <div class="row">

                          <!-- CART TABLE -->

                            <div class="col-lg-9">
                                <table class="table table-cart table-mobile">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($cartProducts as $product): ?>

                                            <tr>
                                                <td class="product-col">
                                                    <div class="product">
                                                        <figure class="product-media">
                                                            <a href="product-detail.php?id=<?= (int) $product['id'] ?>">
                                                                <?php if (!empty($product['image'])): ?>
                                                                    <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                                                <?php else: ?>
                                                                    <img src="assets/images/products/product-1.jpg" alt="<?= htmlspecialchars($product['name']) ?>">
                                                                <?php endif; ?>
                                                            </a>
                                                        </figure>

                                                        <h3 class="product-title">
                                                            <a href="product-detail.php?id=<?= (int) $product['id'] ?>">
                                                                <?= htmlspecialchars($product['name']) ?>
                                                            </a>
                                                        </h3>
                                                    </div>
                                                </td>

                                                <td class="price-col">
                                                    Rs. <?= number_format((float) $product['price'], 2) ?>
                                                </td>

                                                <td class="quantity-col">
                                                    <div class="cart-product-quantity">
                                                        <input
                                                            type="number"
                                                            class="form-control"
                                                            name="quantity[<?= (int) $product['id'] ?>]"
                                                            value="<?= (int) $product['quantity'] ?>"
                                                            min="0"
                                                            max="<?= (int) $product['stock'] ?>"
                                                            step="1"
                                                        >
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Enter 0 to remove</small>
                                                </td>

                                                <td class="total-col">
                                                    Rs. <?= number_format($product['subtotal'], 2) ?>
                                                </td>

                                                <td class="remove-col">
                                                    <a href="cart.php?remove=<?= (int) $product['id'] ?>" class="btn-remove" title="Remove Product">
                                                        <i class="icon-close"></i>
                                                    </a>
                                                </td>
                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>
                                </table>

                                <div class="cart-bottom">
                                    <button type="submit" name="update_cart" class="btn btn-outline-dark-2">
                                        <span>UPDATE CART</span>
                                        <i class="icon-refresh"></i>
                                    </button>
                                </div>
                            </div>


                           <!-- CART SUMMARY -->

                            <aside class="col-lg-3">
                                <div class="summary summary-cart">
                                    <h3 class="summary-title">Cart Total</h3>

                                    <table class="table table-summary">
                                        <tbody>
                                            <tr class="summary-subtotal">
                                                <td>Subtotal:</td>
                                                <td>Rs. <?= number_format($cartTotal, 2) ?></td>
                                            </tr>
                                            <tr class="summary-total">
                                                <td>Total:</td>
                                                <td>Rs. <?= number_format($cartTotal, 2) ?></td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <a href="checkout.php" class="btn btn-outline-primary-2 btn-order btn-block">
                                        PROCEED TO CHECKOUT
                                    </a>
                                </div>

                                <a href="products.php" class="btn btn-outline-dark-2 btn-block mb-3">
                                    <span>CONTINUE SHOPPING</span>
                                    <i class="icon-refresh"></i>
                                </a>
                            </aside>

                        </div>
                    </form>

                <?php endif; ?>

            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>