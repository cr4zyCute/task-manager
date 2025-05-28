<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Not authenticated"
    ));
    exit();
}

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get posted data
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || empty($data->notification_id)) {
        throw new Exception("Notification ID is required");
    }

    // Verify that the notification belongs to the current user
    $query = "UPDATE notifications 
              SET is_read = 1 
              WHERE id = :notification_id 
              AND user_id = :user_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":notification_id", $data->notification_id);
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);

    if ($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Notification marked as read"
        ));
    } else {
        throw new Exception("Failed to mark notification as read");
    }
} catch (Exception $e) {
    error_log("Error in mark-notification-read.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => $e->getMessage()
    ));
}
