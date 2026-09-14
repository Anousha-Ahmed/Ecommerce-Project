<?php

require_once __DIR__ . '/auth-check.php';

$auth = new Auth();

/*
 * Only authenticated administrators
 * are allowed to access this page.
 */
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {

    header('Location: login.php');
    exit;
}