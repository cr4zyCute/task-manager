<?php
session_start();
include "../includes/db.php";

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_email"])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if (!isset($_POST['id'])) {
        throw new Exception("Employee ID is required");
    }

    $employee_id = intval($_POST['id']);

    // Start transaction
    $conn->begin_transaction();

    try {
        // First check if employee exists
        $check_query = "SELECT id FROM employees WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);

        if (!$check_stmt) {
            throw new Exception("Failed to prepare check statement: " . $conn->error);
        }

        $check_stmt->bind_param("i", $employee_id);

        if (!$check_stmt->execute()) {
            throw new Exception("Failed to check employee existence: " . $check_stmt->error);
        }

        $result = $check_stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Employee not found");
        }

        // Delete associated notifications
        $delete_notif_query = "DELETE FROM notifications WHERE user_id = ?";
        $delete_notif_stmt = $conn->prepare($delete_notif_query);

        if (!$delete_notif_stmt) {
            throw new Exception("Failed to prepare notifications delete statement: " . $conn->error);
        }

        $delete_notif_stmt->bind_param("i", $employee_id);

        if (!$delete_notif_stmt->execute()) {
            throw new Exception("Failed to delete employee notifications: " . $delete_notif_stmt->error);
        }

        // Delete associated tasks
        $delete_tasks_query = "DELETE FROM tasks WHERE assigned_to = ?";
        $delete_tasks_stmt = $conn->prepare($delete_tasks_query);

        if (!$delete_tasks_stmt) {
            throw new Exception("Failed to prepare tasks delete statement: " . $conn->error);
        }

        $delete_tasks_stmt->bind_param("i", $employee_id);

        if (!$delete_tasks_stmt->execute()) {
            throw new Exception("Failed to delete employee tasks: " . $delete_tasks_stmt->error);
        }

        // Now delete the employee
        $delete_query = "DELETE FROM employees WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);

        if (!$delete_stmt) {
            throw new Exception("Failed to prepare delete statement: " . $conn->error);
        }

        $delete_stmt->bind_param("i", $employee_id);

        if (!$delete_stmt->execute()) {
            throw new Exception("Failed to delete employee: " . $delete_stmt->error);
        }

        // If everything is successful, commit the transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Employee deleted successfully'
        ]);
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete_employee.php: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($delete_tasks_stmt)) $delete_tasks_stmt->close();
    if (isset($delete_notif_stmt)) $delete_notif_stmt->close();
    if (isset($delete_stmt)) $delete_stmt->close();
    if (isset($conn)) $conn->close();
}
