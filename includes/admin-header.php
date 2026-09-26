<?php

// Every admin page must set $basePath before requiring this file.
// admin/index.php            -> $basePath = ''
// admin/products/index.php   -> $basePath = '../'
$basePath = $basePath ?? '';

require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

Session::start();

$auth = new Auth();

// Current admin's name, used in the top navbar
$adminName = Session::get('user_name', 'Admin');

// Used to highlight the active sidebar link
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$currentFolder = basename(dirname($_SERVER['SCRIPT_NAME']));

$pageTitle = $pageTitle ?? 'Admin Panel';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="<?= $basePath ?>assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="<?= $basePath ?>assets/img/favicon.png">

    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="<?= $basePath ?>assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="<?= $basePath ?>assets/css/nucleo-svg.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link id="pagestyle" href="<?= $basePath ?>assets/css/material-dashboard.css" rel="stylesheet" />

    <!--
        Material Dashboard removes the border from .form-control by
        default (it expects the .input-group-outline wrapper for
        styling). We use plain .form-control everywhere for
        simplicity, so this restores a normal visible input box.
    -->
        <style>
        .form-control {
            border: 1px solid #d2d6da !important;
            border-radius: 0.5rem !important;
            padding: 0.625rem 0.75rem !important;
            line-height: 1.3 !important;
        }
        .form-control:focus {
            border-color: #344767 !important;
            box-shadow: 0 0 0 2px rgba(52, 71, 103, 0.2) !important;
        }
                .table th,
        .table td {
            padding-left: 1rem !important;
            vertical-align: middle;
        }
        .table th.ps-4,
        .table td.ps-4 {
            padding-left: 1.5rem !important;
        }
    </style>
</head>

<body class="g-sidenav-show bg-gray-100">

    <!-- ==========================================
         SIDEBAR
         ========================================== -->

    <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 bg-white my-2" id="sidenav-main">

        <div class="sidenav-header">
            <a class="navbar-brand px-4 py-3 m-0" href="<?= $basePath ?>index.php">
                <img src="<?= $basePath ?>assets/img/logo-ct-dark.png" class="navbar-brand-img" width="26" height="26" alt="logo">
                <span class="ms-1 text-sm text-dark">Store Admin</span>
            </a>
        </div>

        <hr class="horizontal dark mt-0 mb-2">

        <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
            <ul class="navbar-nav">

                <li class="nav-item">
                    <a class="nav-link <?= $currentFolder === 'admin' && $currentPage === 'index.php' ? 'active bg-gradient-dark text-white' : 'text-dark' ?>" href="<?= $basePath ?>index.php">
                        <i class="material-symbols-rounded opacity-5">dashboard</i>
                        <span class="nav-link-text ms-1">Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentFolder === 'products' ? 'active bg-gradient-dark text-white' : 'text-dark' ?>" href="<?= $basePath ?>products/index.php">
                        <i class="material-symbols-rounded opacity-5">inventory_2</i>
                        <span class="nav-link-text ms-1">Products</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentFolder === 'categories' ? 'active bg-gradient-dark text-white' : 'text-dark' ?>" href="<?= $basePath ?>categories/index.php">
                        <i class="material-symbols-rounded opacity-5">category</i>
                        <span class="nav-link-text ms-1">Categories</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentFolder === 'orders' ? 'active bg-gradient-dark text-white' : 'text-dark' ?>" href="<?= $basePath ?>orders/index.php">
                        <i class="material-symbols-rounded opacity-5">shopping_cart</i>
                        <span class="nav-link-text ms-1">Orders</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentFolder === 'users' ? 'active bg-gradient-dark text-white' : 'text-dark' ?>" href="<?= $basePath ?>users/index.php">
                        <i class="material-symbols-rounded opacity-5">group</i>
                        <span class="nav-link-text ms-1">Customers</span>
                    </a>
                </li>

                <li class="nav-item mt-3">
                    <hr class="horizontal dark">
                </li>

                <li class="nav-item">
                    <a class="nav-link text-dark" href="<?= $basePath ?>../public/index.php" target="_blank">
                        <i class="material-symbols-rounded opacity-5">storefront</i>
                        <span class="nav-link-text ms-1">View Store</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?= $basePath ?>logout.php">
                        <i class="material-symbols-rounded opacity-5">logout</i>
                        <span class="nav-link-text ms-1">Logout</span>
                    </a>
                </li>

            </ul>
        </div>
    </aside>

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">

        <!-- ==========================================
             TOP NAVBAR
             ========================================== -->

        <nav class="navbar navbar-main navbar-expand-lg px-0 mx-3 shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
            <div class="container-fluid py-1 px-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
                        <li class="breadcrumb-item text-sm text-dark active" aria-current="page">
                            <?= htmlspecialchars($pageTitle) ?>
                        </li>
                    </ol>
                </nav>

                <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
                    <ul class="navbar-nav d-flex align-items-center justify-content-end ms-auto">
                        <li class="nav-item px-3 d-flex align-items-center">
                            <span class="text-sm text-dark font-weight-bold">
                                <i class="material-symbols-rounded align-middle">account_circle</i>
                                <?= htmlspecialchars($adminName) ?>
                            </span>
                        </li>
                        <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                            <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                                <div class="sidenav-toggler-inner">
                                    <i class="sidenav-toggler-line"></i>
                                    <i class="sidenav-toggler-line"></i>
                                    <i class="sidenav-toggler-line"></i>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid py-4">