<?php

/**
 * Shared site header.
 *
 * Expects (optional):
 * - $pageTitle : string  -> used in <title>
 *
 * Needs Session, Auth and Database to already be
 * available (they get required below if missing).
 */

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';

Session::start();

$auth = new Auth();

// ======================================================
// CART COUNT (for header cart icon)
// ======================================================

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartCount = array_sum($_SESSION['cart']);


// ======================================================
// CATEGORIES (for the shop dropdown menu)
// ======================================================

$database = new Database();
$mysqli   = $database->getConnection();

$headerCategories = $mysqli->query(
    "SELECT id, name
     FROM categories
     WHERE status = 1
     ORDER BY name ASC"
);


// Figure out current page, used to highlight active nav link.
$currentPage = basename($_SERVER['SCRIPT_NAME']);

$pageTitle = $pageTitle ?? 'Molla Store';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icons/favicon-16x16.png">
    <link rel="shortcut icon" href="assets/images/icons/favicon.ico">
    <meta name="theme-color" content="#ffffff">

    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/plugins/owl-carousel/owl.carousel.css">
    <link rel="stylesheet" href="assets/css/plugins/magnific-popup/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">
</head>

<body>
    <div class="page-wrapper">

        <header class="header">

            <div class="header-top">
                <div class="container">
                    <div class="header-left">
                        <div class="header-dropdown">
                            <a href="tel:#"><i class="icon-phone"></i> Call: +92 300 0000000</a>
                        </div>
                    </div><!-- End .header-left -->

                    <div class="header-right">
                        <ul class="top-menu">
                            <li>
                                <a href="#">Links</a>
                                <ul>
                                    <li><a href="about.php">About Us</a></li>
                                    <li><a href="contact.php">Contact Us</a></li>

                                    <?php if ($auth->isLoggedIn()): ?>

                                        <li>
                                            <a href="#">
                                                <i class="icon-user"></i>
                                                <?= htmlspecialchars(Session::get('user_name')) ?>
                                            </a>
                                        </li>
                                        <li><a href="logout.php"><i class="icon-power-off"></i> Logout</a></li>

                                    <?php else: ?>

                                        <li><a href="login.php"><i class="icon-user"></i> Login</a></li>
                                        <li><a href="register.php">Register</a></li>

                                    <?php endif; ?>
                                </ul>
                            </li>
                        </ul><!-- End .top-menu -->
                    </div><!-- End .header-right -->
                </div><!-- End .container -->
            </div><!-- End .header-top -->

            <div class="header-middle sticky-header">
                <div class="container">
                    <div class="header-left">
                        <button class="mobile-menu-toggler">
                            <span class="sr-only">Toggle mobile menu</span>
                            <i class="icon-bars"></i>
                        </button>

                        <a href="index.php" class="logo">
                            <img src="assets/images/logo.png" alt="Store Logo" width="105" height="25">
                        </a>

                        <nav class="main-nav">
                            <ul class="menu sf-arrows">
                                <li class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                                    <a href="index.php">Home</a>
                                </li>

                                <li class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                                    <a href="products.php" class="sf-with-ul">Shop</a>

                                    <ul>
                                        <li><a href="products.php">All Products</a></li>

                                        <?php while ($category = $headerCategories->fetch_assoc()): ?>

                                            <li>
                                                <a href="products.php?category=<?= (int) $category['id'] ?>">
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </a>
                                            </li>

                                        <?php endwhile; ?>
                                    </ul>
                                </li>

                                <li class="<?= $currentPage === 'cart.php' ? 'active' : '' ?>">
                                    <a href="cart.php">Cart</a>
                                </li>

                                <li class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">
                                    <a href="contact.php">Contact</a>
                                </li>
                            </ul><!-- End .menu -->
                        </nav><!-- End .main-nav -->
                    </div><!-- End .header-left -->

                    <div class="header-right">
                        <div class="header-search">
                            <a href="#" class="search-toggle" role="button" title="Search"><i class="icon-search"></i></a>
                            <form action="products.php" method="get">
                                <div class="header-search-wrapper">
                                    <label for="q" class="sr-only">Search</label>
                                    <input type="search" class="form-control" name="q" id="q" placeholder="Search products..." required>
                                </div><!-- End .header-search-wrapper -->
                            </form>
                        </div><!-- End .header-search -->

                        <div class="dropdown cart-dropdown">
                            <a href="cart.php" class="dropdown-toggle" role="button">
                                <i class="icon-shopping-cart"></i>
                                <span class="cart-count"><?= (int) $cartCount ?></span>
                            </a>
                        </div><!-- End .cart-dropdown -->
                    </div><!-- End .header-right -->
                </div><!-- End .container -->
            </div><!-- End .header-middle -->
        </header><!-- End .header -->

        <div id="main"></div>