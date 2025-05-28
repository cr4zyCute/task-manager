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
require_once "../../includes/db.php";

try {
    // Check if database connection is successful
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    // Get employee count
    $query = "SELECT COUNT(*) as count FROM employees WHERE role = 'employee'";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $count = $result->fetch_assoc()['count'];

    // Set success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "count" => (int)$count
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error in users/count.php: " . $e->getMessage());

    // Set error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error loading employee count: " . $e->getMessage()
    ]);
}
