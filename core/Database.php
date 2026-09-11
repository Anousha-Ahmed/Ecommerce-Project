<?php

/**
 * Reusable database connection class.
 * This prevents us from creating a new connection
 * manually in every PHP file.
 */
class Database
{
    // Store the active MySQLi connection
    private mysqli $connection;

    public function __construct()
    {
        // Database connection details
        $host = "localhost";
        $username = "root";
        $password = "";
        $database = "ecommerce_db";

        // Create MySQLi database connection
        $this->connection = new mysqli(
            $host,
            $username,
            $password,
            $database
        );

        // Stop execution if connection fails
        if ($this->connection->connect_error) {
            die("Database connection failed: " . $this->connection->connect_error);
        }

        // Set UTF-8 character encoding
        $this->connection->set_charset("utf8mb4");
    }

    /**
     * Return the active database connection.
     */
    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}