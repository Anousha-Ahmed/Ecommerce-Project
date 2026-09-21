<?php

require_once __DIR__ . '/../core/Auth.php';

$auth = new Auth();
$auth->logout();

// Redirect back to homepage after logout.
header('Location: index.php');
exit;