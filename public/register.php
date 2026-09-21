<?php

// Load the classes required for registration
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

Session::start();

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

    // Validate on the server, browser validation can be bypassed
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

$pageTitle = 'Create Account - Store';

require_once __DIR__ . '/../includes/header.php';
?>

    <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Register</li>
            </ol>
        </div>
    </nav>

    <div class="login-page bg-image pt-8 pb-8 pt-md-12 pb-md-12 pt-lg-17 pb-lg-17" style="background-image: url('assets/images/backgrounds/login-bg.jpg')">
        <div class="container">
            <div class="form-box" style="max-width:450px; margin:0 auto;">
                <div class="form-tab">

                    <h2 class="text-center mb-4">Create Account</h2>

                    <?php if (!empty($errors['email']) && !isset($_POST['email'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($errors['email']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php">

                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                value="<?= htmlspecialchars($name) ?>"
                                required
                            >
                            <?php if (isset($errors['name'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

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

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control"
                                required
                            >
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn btn-outline-primary-2">
                                <span>SIGN UP</span>
                                <i class="icon-long-arrow-right"></i>
                            </button>
                        </div>

                    </form>

                    <p class="text-center mt-3 mb-0">
                        Already have an account?
                        <a href="login.php">Login</a>
                    </p>

                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>