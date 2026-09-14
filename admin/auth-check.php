<?php

/*
 * Admin Authentication Guard
 *
 * This file protects admin pages.
 *
 * It checks:
 * 1. Is the user logged in?
 * 2. Is the logged-in user's role admin?
 *
 * If either check fails, the user is sent
 * to the admin login page.
 */

// Load Auth class
require_once __DIR__ . '/../core/Auth.php';

// Create authentication object
$auth = new Auth();

/*
 * Allow access ONLY when:
 *
 * - user is logged in
 * AND
 * - user's role is admin
 */
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {

    /*
     * Find the /admin/ part of the current URL.
     *
     * This allows the same protection to work for:
     *
     * /admin/index.php
     * /admin/categories/index.php
     * /admin/products/index.php
     * etc.
     */
    $scriptName = $_SERVER['SCRIPT_NAME'];

    $adminPosition = strpos($scriptName, '/admin/');

    $projectPath = substr(
        $scriptName,
        0,
        $adminPosition
    );

    // Build admin login URL
    $loginUrl = $projectPath . '/admin/login.php';

    // Redirect unauthorized users
    header('Location: ' . $loginUrl);
    exit;
}