<?php
$host = 'localhost';
$dbname = 'plantilla';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

// Test connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Log successful connection instead of echoing HTML
error_log("Database connection successful");

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>