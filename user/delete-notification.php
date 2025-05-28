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

    // Get the notification ID from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $notification_id = isset($data['notification_id']) ? $data['notification_id'] : null;

    if (!$notification_id) {
        throw new Exception("Notification ID is required");
    }

    // Verify the notification belongs to the current user
    $query = "SELECT id FROM notifications WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $notification_id);
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception("Notification not found or unauthorized");
    }

    // Delete the notification
    $query = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $notification_id);
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to delete notification");
    }
} catch (Exception $e) {
    error_log("Error in delete-notification.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
