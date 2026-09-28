<?php

$pageTitle = 'About Us - Store';

require_once __DIR__ . '/../includes/header.php';
?>

    <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About us</li>
            </ol>
        </div>
    </nav>

    <div class="container">
        <div class="page-header page-header-big text-center" style="background-image: url('assets/images/backgrounds/login-bg.jpg')">
            <h1 class="page-title text-white">About us<span class="text-white">Who we are</span></h1>
        </div>
    </div>

    <div class="page-content pb-0">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <h2 class="title">Our Vision</h2>
                    <p>We believe shopping should be simple, fast, and reliable. Our goal is to bring quality products to your doorstep with a smooth online experience — from browsing to checkout to delivery.</p>
                </div>

                <div class="col-lg-6">
                    <h2 class="title">Our Mission</h2>
                    <p>To offer a wide range of products at fair prices, backed by dependable customer service and secure payment options, so you can shop with confidence every time.</p>
                </div>
            </div>

            <div class="mb-5"></div>
        </div>

        <div class="bg-light-2 pt-6 pb-6 mb-6">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-5 mb-3 mb-lg-0">
                        <h2 class="title">Who We Are</h2>
                        <p class="lead text-primary mb-3">A team dedicated to great products and great service.</p>
                        <p class="mb-2">We started this store with a simple idea: make online shopping easy and trustworthy. Every product on our shelves is chosen with our customers in mind.</p>
                        <a href="products.php" class="btn btn-sm btn-minwidth btn-outline-primary-2">
                            <span>BROWSE PRODUCTS</span>
                            <i class="icon-long-arrow-right"></i>
                        </a>
                    </div>

                    <div class="col-lg-6 offset-lg-1">
                        <div class="text-center">
                            <i class="icon-shopping-cart" style="font-size:120px; color:#337ab7;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-6">
            <div class="row">
                <div class="col-lg-5">
                    <h2 class="title">Trusted by shoppers, built for reliability.</h2>
                    <p>Secure payments, fast processing, and a support team that's always ready to help. That's what we stand for.</p>
                </div>
                <div class="col-lg-7">
                    <div class="row justify-content-center align-items-center">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div class="col-6 col-sm-4 mb-4">
                                <img src="assets/images/brands/<?= $i ?>.png" alt="Brand" class="img-fluid">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>