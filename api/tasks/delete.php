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

    if (!isset($data->id)) {
        throw new Exception("Task ID is required");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // First, check if the task exists
        $check_query = "SELECT id FROM tasks WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);

        if (!$check_stmt) {
            throw new Exception("Failed to prepare check statement: " . $conn->error);
        }

        $check_stmt->bind_param("i", $data->id);

        if (!$check_stmt->execute()) {
            throw new Exception("Failed to check task existence: " . $check_stmt->error);
        }

        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Task not found");
        }

        // Delete associated notifications first
        $delete_notif_query = "DELETE FROM notifications WHERE task_id = ?";
        $delete_notif_stmt = $conn->prepare($delete_notif_query);

        if (!$delete_notif_stmt) {
            throw new Exception("Failed to prepare notification delete statement: " . $conn->error);
        }

        $delete_notif_stmt->bind_param("i", $data->id);

        if (!$delete_notif_stmt->execute()) {
            throw new Exception("Failed to delete task notifications: " . $delete_notif_stmt->error);
        }

        // Now delete the task
        $delete_query = "DELETE FROM tasks WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);

        if (!$delete_stmt) {
            throw new Exception("Failed to prepare delete statement: " . $conn->error);
        }

        $delete_stmt->bind_param("i", $data->id);

        if (!$delete_stmt->execute()) {
            throw new Exception("Failed to delete task: " . $delete_stmt->error);
        }

        // If everything is successful, commit the transaction
        $conn->commit();

        // Set success response
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Task deleted successfully"
        ]);
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Log the error
    error_log("Error in tasks/delete.php: " . $e->getMessage());

    // Set error response
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($delete_notif_stmt)) $delete_notif_stmt->close();
    if (isset($delete_stmt)) $delete_stmt->close();
    if (isset($conn)) $conn->close();
}
