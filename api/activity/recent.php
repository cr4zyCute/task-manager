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

    // Get recent activities from tasks and notifications
    $query = "SELECT 
        'task' as type,
        t.title as description,
        t.created_at as timestamp
    FROM tasks t
    UNION ALL
    SELECT 
        'notification' as type,
        n.message as description,
        n.created_at as timestamp
    FROM notifications n
    ORDER BY timestamp DESC
    LIMIT 10";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $activities = array();
    while ($row = $result->fetch_assoc()) {
        $activities[] = array(
            "type" => $row['type'],
            "description" => $row['description'],
            "timestamp" => $row['timestamp']
        );
    }

    // Set success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "activities" => $activities
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error in activity/recent.php: " . $e->getMessage());

    // Set error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error loading recent activity: " . $e->getMessage()
    ]);
}
