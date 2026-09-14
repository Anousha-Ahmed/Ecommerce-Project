<?php

// Load authentication and validation classes
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

$auth = new Auth();
$validator = new Validator();

$errors = [];
$email = '';

/*
 * Check whether the login form was submitted.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get submitted values
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input on the server
    $validator
        ->required('email', $email, 'Email is required.')
        ->email('email', $email)
        ->required('password', $password, 'Password is required.');

    $errors = $validator->errors();

    /*
     * Only try authentication when validation passes.
     */
    if ($validator->isValid()) {

        if ($auth->login($email, $password)) {

            /*
             * Authentication succeeded.
             *
             * Now we check authorization:
             * Is this user actually an admin?
             */
            if ($auth->isAdmin()) {

                // Admin is allowed to enter the dashboard
                header('Location: index.php');
                exit;
            }

            /*
             * A normal customer successfully logged in,
             * but does NOT have permission to access admin.
             */
            $auth->logout();

            $errors['login'] =
                'You do not have permission to access the admin panel.';

        } else {

            // Wrong email/password or inactive account
            $errors['login'] = 'Invalid email or password.';
        }
    }
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

    <title>Admin Login - E-Commerce</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow-sm">

                <div class="card-body p-4">

                    <h2 class="text-center mb-4">
                        Admin Login
                    </h2>

                    <!-- Login error -->
                    <?php if (isset($errors['login'])): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errors['login']) ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST" action="">

                        <!-- Email -->

                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($email) ?>"
                                required
                            >

                            <?php if (isset($errors['email'])): ?>

                                <div class="text-danger small mt-1">
                                    <?= htmlspecialchars($errors['email']) ?>
                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- Password -->

                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label"
                            >
                                Password
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                required
                            >

                            <?php if (isset($errors['password'])): ?>

                                <div class="text-danger small mt-1">
                                    <?= htmlspecialchars($errors['password']) ?>
                                </div>

                            <?php endif; ?>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-dark w-100"
                        >
                            Login as Admin
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>