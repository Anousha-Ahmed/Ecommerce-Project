<?php

// Load the classes required for registration
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

// Create objects for authentication and validation
$auth = new Auth();
$validator = new Validator();

// Store validation errors
$errors = [];

// Store previously entered values
$name = '';
$email = '';

// Check whether the registration form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get values submitted through the form
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    /*
     * Validate the submitted data on the server.
     *
     * Client-side HTML validation is not enough because
     * users can bypass browser validation.
     */
    $validator
        ->required('name', $name, 'Name is required.')
        ->required('email', $email, 'Email is required.')
        ->email('email', $email)
        ->required('password', $password, 'Password is required.')
        ->minLength('password', $password, 8, 'Password must be at least 8 characters.')
        ->same(
            'confirm_password',
            $password,
            $confirmPassword,
            'Passwords do not match.'
        );

    // Get all validation errors
    $errors = $validator->errors();

    // Continue only if validation passed
    if ($validator->isValid()) {

        // Try to create the customer account
        if ($auth->register($name, $email, $password)) {

            // Store a temporary success message in the session
            Session::flash(
                'success',
                'Registration successful. You can now login.'
            );

            // Redirect to login page after successful registration
            header('Location: login.php');
            exit;
        }

        // Registration failed, most commonly because email already exists
        $errors['email'] = 'This email is already registered.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Make the page responsive on mobile devices -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - E-Commerce</title>

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
                        Create Account
                    </h2>

                    <!-- Display general registration error -->
                    <?php if (!empty($errors['email']) && !isset($_POST['email'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errors['email']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">

                        <!-- Name -->
                        <div class="mb-3">

                            <label for="name" class="form-label">
                                Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                value="<?= htmlspecialchars($name) ?>"
                                required
                            >

                            <?php if (isset($errors['name'])): ?>
                                <div class="text-danger small mt-1">
                                    <?= htmlspecialchars($errors['name']) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Email -->
                        <div class="mb-3">

                            <label for="email" class="form-label">
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

                            <label for="password" class="form-label">
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

                        <!-- Confirm password -->
                        <div class="mb-3">

                            <label for="confirm_password" class="form-label">
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control"
                                required
                            >

                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="text-danger small mt-1">
                                    <?= htmlspecialchars($errors['confirm_password']) ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Register
                        </button>

                    </form>

                    <p class="text-center mt-3 mb-0">

                        Already have an account?

                        <a href="login.php">
                            Login
                        </a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>