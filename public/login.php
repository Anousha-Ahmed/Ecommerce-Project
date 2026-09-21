<?php

// Load required classes
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

// Session is needed for flash messages and login state
Session::start();

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

    // Server-side validation, browser validation can be bypassed
    $validator
        ->required('email', $email, 'Email is required.')
        ->email('email', $email)
        ->required('password', $password, 'Password is required.');

    // Get validation errors
    $errors = $validator->errors();

    // Only attempt database login if validation passed
    if ($validator->isValid()) {

        // Auth::login() checks the user, password and starts the session
        if ($auth->login($email, $password)) {

            // Admin goes to admin dashboard
            if ($auth->isAdmin()) {
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

// Flash message coming from register.php after a successful signup
$successMessage = Session::getFlash('success');

$pageTitle = 'Login - Store';

require_once __DIR__ . '/../includes/header.php';
?>

    <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Login</li>
            </ol>
        </div>
    </nav><!-- End .breadcrumb-nav -->

    <div class="login-page bg-image pt-8 pb-8 pt-md-12 pb-md-12 pt-lg-17 pb-lg-17" style="background-image: url('assets/images/backgrounds/login-bg.jpg')">
        <div class="container">
            <div class="form-box" style="max-width:450px; margin:0 auto;">
                <div class="form-tab">

                    <h2 class="text-center mb-4">Sign In</h2>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($errors['login'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errors['login']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php">

                        <div class="form-group">
                            <label for="email">Email address *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                value="<?= htmlspecialchars($email) ?>"
                                required
                            >
                            <?php if (isset($errors['email'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                required
                            >
                            <?php if (isset($errors['password'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn btn-outline-primary-2">
                                <span>LOG IN</span>
                                <i class="icon-long-arrow-right"></i>
                            </button>
                        </div>

                    </form>

                    <p class="text-center mt-3 mb-0">
                        Don't have an account?
                        <a href="register.php">Create Account</a>
                    </p>

                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>