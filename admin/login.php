<?php

// Load authentication and validation classes
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

$auth = new Auth();
$validator = new Validator();

$errors = [];
$email = '';

// Check whether the login form was submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $validator
        ->required('email', $email, 'Email is required.')
        ->email('email', $email)
        ->required('password', $password, 'Password is required.');

    $errors = $validator->errors();

    if ($validator->isValid()) {

        if ($auth->login($email, $password)) {

            if ($auth->isAdmin()) {
                header('Location: index.php');
                exit;
            }

            // A normal customer successfully logged in,
            // but does NOT have permission to access admin.
            $auth->logout();

            $errors['login'] = 'You do not have permission to access the admin panel.';

        } else {

            $errors['login'] = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">

    <title>Admin Login</title>

    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
    <link href="assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="assets/css/nucleo-svg.css" rel="stylesheet" />
    <link id="pagestyle" href="assets/css/material-dashboard.css" rel="stylesheet" />

    <style>
        body {
            background: linear-gradient(87deg, #344767 0, #191934 100%);
            min-height: 100vh;
        }
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
    </style>
</head>

<body>

    <div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
        <div class="col-11 col-sm-8 col-md-6 col-lg-4">

            <div class="card mb-0">
                <div class="card-header text-center pt-4">
                    <img src="assets/img/logo-ct-dark.png" width="40" height="40" alt="logo" class="mb-2">
                    <h5 class="mb-0">Store Admin</h5>
                    <p class="text-sm text-secondary mb-0">Sign in to manage your store</p>
                </div>

                <div class="card-body">

                    <?php if (isset($errors['login'])): ?>
                        <div class="alert alert-danger text-white">
                            <?= htmlspecialchars($errors['login']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn bg-gradient-dark w-100">Login as Admin</button>

                    </form>

                </div>
            </div>

            <p class="text-center text-white text-sm mt-3">
                <a href="../public/index.php" class="text-white text-sm">&larr; Back to Store</a>
            </p>

        </div>
    </div>

    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/material-dashboard.min.js"></script>

</body>

</html>