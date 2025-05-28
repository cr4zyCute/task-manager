<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "employee") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);
$task_id = $data["task_id"] ?? null;
$status = $data["status"] ?? null;

if (!$task_id || !$status) {
    echo json_encode(["success" => false, "message" => "Task ID and status are required"]);
    exit();
}

// Validate status
$valid_statuses = ["pending", "in_progress", "completed"];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(["success" => false, "message" => "Invalid status"]);
    exit();
}

// Update task status
$stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND assigned_to = ?");
$stmt->bind_param("sii", $status, $task_id, $_SESSION["user_id"]);
$result = $stmt->execute();

if ($result) {
    // Create notification for task status change
    $notification_title = "Task Status Updated";
    $notification_message = "Your task has been marked as " . str_replace("_", " ", $status);

    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'task')");
    $notif_stmt->bind_param("iss", $_SESSION["user_id"], $notification_title, $notification_message);
    $notif_stmt->execute();

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update task status"]);
}
