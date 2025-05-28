<?php
// Disable error display
error_reporting(0);
ini_set('display_errors', 0);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once "../../includes/db.php";

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->id) || !isset($data->status)) {
        throw new Exception("Task ID and status are required");
    }

    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'completed'];
    if (!in_array($data->status, $valid_statuses)) {
        throw new Exception("Invalid status value");
    }

    // Update task status
    $query = "UPDATE tasks SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("si", $data->status, $data->id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to update task status: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("Task not found or no changes made");
    }

    // Set success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Task status updated successfully"
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error in tasks/update_status.php: " . $e->getMessage());

    // Set error response
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
