<?php
// Disable error display
error_reporting(0);
ini_set('display_errors', 0);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once "../includes/db.php";

try {
    // Check if database connection is successful
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    // Get user ID from query parameter
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($user_id <= 0) {
        throw new Exception("Invalid user ID");
    }

    // Get unread notifications count
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Set success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "count" => (int)$row['count']
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error in notifications/check.php: " . $e->getMessage());

    // Set error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error checking notifications: " . $e->getMessage()
    ]);
}
