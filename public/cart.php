<?php

// Start session because cart data is stored in the session.
require_once __DIR__ . '/../core/Session.php';

Session::start();


// Database connection.
require_once __DIR__ . '/../core/Database.php';

$database = new Database();
$mysqli = $database->getConnection();


// ======================================================
// CART SETUP
// ======================================================

/*
 * Cart session format:
 *
 * $_SESSION['cart'] = [
 *     3 => 2,
 *     5 => 1
 * ];
 *
 * 3 = product ID
 * 2 = quantity
 */

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// ======================================================
// ADD PRODUCT TO CART
// ======================================================

if (isset($_GET['add'])) {

    $productId = (int) $_GET['add'];

    if ($productId > 0) {

        // Find active product.
        $stmt = $mysqli->prepare(
            "SELECT id, name, price, stock
             FROM products
             WHERE id = ?
               AND status = 1
             LIMIT 1"
        );

        $stmt->bind_param("i", $productId);

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


    /*
     * Redirect after adding.
     *
     * This removes ?add=3 from the URL
     * and prevents accidental duplicate
     * additions when refreshing the page.
     */
    header('Location: cart.php');

    exit;
}


// ======================================================
// UPDATE CART
// ======================================================

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


            // --------------------------------------------------
            // CHECK PRODUCT STOCK
            // --------------------------------------------------

            $stmt = $mysqli->prepare(
                "SELECT stock
                 FROM products
                 WHERE id = ?
                   AND status = 1
                 LIMIT 1"
            );

            $stmt->bind_param("i", $productId);

            $stmt->execute();

            $result = $stmt->get_result();


            if ($result->num_rows === 1) {

                $product = $result->fetch_assoc();

                $stock = (int) $product['stock'];


                /*
                 * Quantity 0 means remove the product
                 * from the cart.
                 */
                if ($quantity <= 0) {

                    unset($_SESSION['cart'][$productId]);

                } elseif ($stock > 0) {

                    /*
                     * min() ensures that customer
                     * cannot select more than
                     * available stock.
                     *
                     * Example:
                     *
                     * Requested = 10
                     * Stock     = 5
                     *
                     * Saved quantity = 5
                     */
                    $_SESSION['cart'][$productId] =
                        min($quantity, $stock);

                } else {

                    /*
                     * Product is now out of stock.
                     * Remove it from the cart.
                     */
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


// ======================================================
// FETCH CART PRODUCTS
// ======================================================

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
        "SELECT
            id,
            name,
            price,
            stock,
            image
         FROM products
         WHERE id = ?
           AND status = 1
         LIMIT 1"
    );


    $stmt->bind_param("i", $productId);

    $stmt->execute();

    $result = $stmt->get_result();


    if ($result->num_rows === 1) {

        $product = $result->fetch_assoc();

        $stock = (int) $product['stock'];


        /*
         * Stock may have changed after the
         * product was added to cart.
         */
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
        Shopping Cart
    </title>


    <!-- Temporary Bootstrap styling. -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body>


<div class="container py-5">


    <h1 class="mb-4">
        Shopping Cart
    </h1>


    <!-- ==================================================
         EMPTY CART
         ================================================== -->

    <?php if (empty($cartProducts)): ?>


        <div class="alert alert-info">

            Your cart is empty.

        </div>


        <a
            href="products.php"
            class="btn btn-primary"
        >
            Continue Shopping
        </a>


    <?php else: ?>


        <!-- ==================================================
             CART FORM
             ================================================== -->

        <form method="POST">


            <div class="table-responsive">

                <table class="table table-bordered align-middle">


                    <thead>

                        <tr>

                            <th>
                                Product
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Available Stock
                            </th>

                            <th>
                                Quantity
                            </th>

                            <th>
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($cartProducts as $product): ?>


                        <tr>


                            <!-- Product name -->

                            <td>

                                <?= htmlspecialchars(
                                    $product['name']
                                ) ?>

                            </td>


                            <!-- Price -->

                            <td>

                                Rs.
                                <?= number_format(
                                    (float) $product['price'],
                                    2
                                ) ?>

                            </td>


                            <!-- Current stock -->

                            <td>

                                <?= (int) $product['stock'] ?>

                            </td>


                            <!-- Quantity -->

                            <td>

                                <input
                                    type="number"
                                    name="quantity[<?= (int) $product['id'] ?>]"
                                    value="<?= (int) $product['quantity'] ?>"
                                    min="0"
                                    max="<?= (int) $product['stock'] ?>"
                                    class="form-control"
                                    style="width: 100px;"
                                >


                                <small class="text-muted">

                                    Enter 0 to remove

                                </small>

                            </td>


                            <!-- Subtotal -->

                            <td>

                                Rs.
                                <?= number_format(
                                    $product['subtotal'],
                                    2
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                    <!-- ==================================================
                         CART TOTAL
                         ================================================== -->

                    <tfoot>

                        <tr>

                            <th
                                colspan="4"
                                class="text-end"
                            >

                                Total:

                            </th>


                            <th>

                                Rs.
                                <?= number_format(
                                    $cartTotal,
                                    2
                                ) ?>

                            </th>

                        </tr>

                    </tfoot>


                </table>

            </div>


            <!-- ==================================================
                 ACTION BUTTONS
                 ================================================== -->

            <button
                type="submit"
                name="update_cart"
                class="btn btn-secondary"
            >
                Update Cart
            </button>


            <a
                href="products.php"
                class="btn btn-outline-primary"
            >
                Continue Shopping
            </a>


            <a
                href="checkout.php"
                class="btn btn-success"
            >
                Proceed to Checkout
            </a>


        </form>


    <?php endif; ?>


</div>


</body>

</html>