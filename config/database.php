<?php

// MySQL database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce_db";

// Create a MySQLi connection using Object-Oriented approach
$mysqli = new mysqli($host, $username, $password, $database);

// Stop execution if database connection fails
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Use UTF-8 encoding for proper text and special characters
$mysqli->set_charset("utf8mb4");