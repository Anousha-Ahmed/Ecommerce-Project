<?php

/**
 * Session helper class.
 * Handles starting, storing, retrieving and destroying
 * session data throughout the application.
 */
class Session
{
    /**
     * Start a PHP session if one is not already active.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Store a value in the current session.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a value from the session.
     * Returns the default value if the key does not exist.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check whether a session key exists.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a specific value from the session.
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the complete session.
     * Used during logout.
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            session_destroy();
        }
    }

    /**
     * Generate a new session ID.
     * This will be used after successful authentication
     * to protect against session fixation.
     */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Store a temporary flash message.
     * Useful for success/error messages after redirects.
     */
    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    /**
     * Retrieve a flash message and remove it immediately.
     * This means the message is normally displayed only once.
     */
    public static function getFlash(string $key): ?string
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return null;
        }

        $message = $_SESSION['_flash'][$key];

        // Remove the message after reading it
        unset($_SESSION['_flash'][$key]);

        return $message;
    }
}