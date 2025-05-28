<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include_once "../config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || empty($data->task_id) || empty($data->status)) {
        throw new Exception("Task ID and status are required");
    }

    // First, get the task details
    $task_query = "SELECT title, description FROM tasks WHERE id = :task_id AND assigned_to = :user_id";
    $task_stmt = $db->prepare($task_query);
    $task_stmt->bindParam(":task_id", $data->task_id);
    $task_stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $task_stmt->execute();
    $task = $task_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new Exception("Task not found or unauthorized");
    }

    // Update the task status
    $query = "UPDATE tasks 
              SET status = :status 
              WHERE id = :task_id 
              AND assigned_to = :user_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":task_id", $data->task_id);
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);

    if ($stmt->execute()) {
        // Create a notification for the task update with detailed information
        $notification_query = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                             VALUES (:user_id, :title, :message, 'task', NOW())";

        $notification_stmt = $db->prepare($notification_query);
        $title = "Task Update: " . htmlspecialchars($task['title']);

        // Create a more detailed message
        $message = "Task Details:\n";
        $message .= "Title: " . htmlspecialchars($task['title']) . "\n";
        $message .= "Description: " . htmlspecialchars($task['description']) . "\n";
        $message .= "Status Updated: " . ucwords(str_replace('_', ' ', $data->status));

        $notification_stmt->bindParam(":user_id", $_SESSION["user_id"]);
        $notification_stmt->bindParam(":title", $title);
        $notification_stmt->bindParam(":message", $message);
        $notification_stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Task status updated successfully'
        ]);
    } else {
        throw new Exception("Failed to update task status");
    }
} catch (Exception $e) {
    error_log("Error in update-task-status.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
