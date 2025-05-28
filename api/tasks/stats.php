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
require_once "../../includes/db.php"; // Adjust path if necessary

$response = array('status' => 'error', 'message' => 'An error occurred');

// Check if database connection is successful
if (!isset($conn) || !$conn) {
    $response['message'] = "Database connection failed.";
    http_response_code(500);
    echo json_encode($response);
    exit();
}

try {
    // Query to get total tasks
    $sql_total = "SELECT COUNT(*) AS total_tasks FROM tasks";
    $result_total = $conn->query($sql_total);
    $total_tasks = $result_total ? $result_total->fetch_assoc()['total_tasks'] : 0;
    if ($result_total) $result_total->free();

    // Query to get completed tasks
    $sql_completed = "SELECT COUNT(*) AS completed_tasks FROM tasks WHERE status = 'completed'";
    $result_completed = $conn->query($sql_completed);
    $completed_tasks = $result_completed ? $result_completed->fetch_assoc()['completed_tasks'] : 0;
    if ($result_completed) $result_completed->free();

    // Query to get pending tasks
    $sql_pending = "SELECT COUNT(*) AS pending_tasks FROM tasks WHERE status = 'pending' OR status = 'in_progress'";
    $result_pending = $conn->query($sql_pending);
    $pending_tasks = $result_pending ? $result_pending->fetch_assoc()['pending_tasks'] : 0; // Assuming 'in_progress' tasks also count as pending for the dashboard stat
    if ($result_pending) $result_pending->free();

    // Set success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "total" => (int)$total_tasks,
        "completed" => (int)$completed_tasks,
        "pending" => (int)$pending_tasks
        // Add data for charts if needed in the future
        // "completion_labels" => [],
        // "completion_data" => []
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error in tasks/stats.php: " . $e->getMessage());

    // Set error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error loading task statistics: " . $e->getMessage()
    ]);
} finally {
    // Close the database connection if it exists and is open
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
