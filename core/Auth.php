<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

/**
 * Authentication class.
 *
 * Handles:
 * - Customer registration
 * - User login
 * - Password hashing and verification
 * - Login session management
 * - Admin/customer role checks
 */
class Auth
{
    // Store the database connection
    private mysqli $mysqli;

    /**
     * Create Auth object and establish database access.
     */
    public function __construct()
    {
        $database = new Database();
        $this->mysqli = $database->getConnection();

        // Make sure session is available for authentication
        Session::start();
    }

    /**
     * Register a new customer.
     *
     * Storefront registrations always create customers.
     * Users cannot register themselves as admin.
     */
    public function register(
        string $name,
        string $email,
        string $password
    ): bool {
        // Check whether the email is already registered
        $stmt = $this->mysqli->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        // Do not allow duplicate email addresses
        if ($result->num_rows > 0) {
            $stmt->close();
            return false;
        }

        $stmt->close();

        // Hash the password before storing it in the database
        $hashedPassword = password_hash(
            $password,
            PASSWORD_BCRYPT
        );

        // New storefront registrations are always customers
        $role = "customer";

        $stmt = $this->mysqli->prepare(
            "INSERT INTO users (name, email, password, role)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssss",
            $name,
            $email,
            $hashedPassword,
            $role
        );

        $success = $stmt->execute();

        $stmt->close();

        return $success;
    }

    /**
     * Authenticate an existing user.
     *
     * Returns true when login is successful.
     */
    public function login(
        string $email,
        string $password
    ): bool {
        // Find the user by email
        $stmt = $this->mysqli->prepare(
            "SELECT id, name, email, password, role, is_active
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        // User does not exist
        if ($result->num_rows !== 1) {
            $stmt->close();
            return false;
        }

        $user = $result->fetch_assoc();

        $stmt->close();

        // Prevent inactive users from logging in
        if ((int) $user['is_active'] !== 1) {
            return false;
        }

        // Verify the entered password against the stored hash
        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Regenerate session ID after successful authentication
        Session::regenerate();

        // Store authenticated user's information in the session
        Session::set('user_id', (int) $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);

        return true;
    }

    /**
     * Check whether a user is currently logged in.
     */
    public function isLoggedIn(): bool
    {
        return Session::has('user_id');
    }

    /**
     * Check whether the currently logged-in user is an admin.
     */
    public function isAdmin(): bool
    {
        return Session::get('user_role') === 'admin';
    }

    /**
     * Check whether the currently logged-in user is a customer.
     */
    public function isCustomer(): bool
    {
        return Session::get('user_role') === 'customer';
    }

    /**
     * Return the currently logged-in user's ID.
     */
    public function userId(): ?int
    {
        $userId = Session::get('user_id');

        return $userId !== null ? (int) $userId : null;
    }

    /**
     * Log the current user out.
     */
    public function logout(): void
    {
        Session::destroy();
    }
}