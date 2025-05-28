<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$db = "task_manager";

try {
    // Log connection attempt
    error_log("Attempting to connect to database: $db");

    $conn = new mysqli($host, $user, $password, $db);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Log successful connection
    error_log("Successfully connected to database: $db");

    // Set charset to ensure proper encoding
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

    // Log charset setting
    error_log("Successfully set charset to utf8mb4");
} catch (Exception $e) {
    // Log the error
    error_log("Database connection error: " . $e->getMessage());

    // Return JSON error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}
