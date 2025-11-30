<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default local username
define('DB_PASS', '');     // Default local password (leave empty if none)
define('DB_NAME', 'phone_mart'); // Change this to your local DB name

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session
session_start();
?>
