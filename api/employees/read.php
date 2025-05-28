<?php
// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database connection
require_once "../../includes/db.php";

try {
    // Check if database connection is successful
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    // Get all employees
    $query = "SELECT id, first_name, last_name, email, role FROM employees ORDER BY first_name, last_name";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            "id" => $row['id'],
            "first_name" => $row['first_name'],
            "last_name" => $row['last_name'],
            "email" => $row['email'],
            "role" => $row['role']
        ];
    }

    // Set success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "records" => $employees
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error in employees/read.php: " . $e->getMessage());

    // Set error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error loading employees: " . $e->getMessage()
    ]);
}
