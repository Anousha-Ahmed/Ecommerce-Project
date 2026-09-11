<?php

// Load required classes
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

// Create Auth and Validator objects
$auth = new Auth();
$validator = new Validator();

// Store validation errors
$errors = [];

// Keep email so it remains in the form if validation fails
$email = '';

// Check whether the login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get submitted form values
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
     * Server-side validation.
     * We validate again on the server because browser
     * validation can be bypassed.
     */
    $validator
        ->required('email', $email, 'Email is required.')
        ->email('email', $email)
        ->required('password', $password, 'Password is required.');

    // Get validation errors
    $errors = $validator->errors();

    // Only attempt database login if validation passed
    if ($validator->isValid()) {

        /*
         * Auth::login() handles:
         * - Finding the user
         * - Checking active status
         * - password_verify()
         * - Session regeneration
         * - Saving user information in session
         */
        if ($auth->login($email, $password)) {

            // Check the logged-in user's role
            if ($auth->isAdmin()) {

                // Admin goes to admin dashboard
                header('Location: ../admin/index.php');
                exit;
            }

            // Customer goes to storefront
            header('Location: index.php');
            exit;
        }

        // Login failed
        $errors['login'] = 'Invalid email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <!-- Responsive layout -->
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - E-Commerce</title>

    <!-- Bootstrap CSS -->
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
                        Login
                    </h2>

                    <!--
                        Display flash message from registration.
                        Example:
                        "Registration successful. You can now login."
                    -->
                    <?php
                    $successMessage = Session::getFlash('success');

                    if ($successMessage):
                    ?>

                        <div class="alert alert-success">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>

                    <?php endif; ?>


                    <!-- General login error -->

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


                        <!-- Login button -->

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Login
                        </button>

                    </form>


                    <p class="text-center mt-3 mb-0">

                        Don't have an account?

                        <a href="register.php">
                            Create Account
                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>