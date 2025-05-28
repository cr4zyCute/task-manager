<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Get user ID from query parameters
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if (!$user_id) {
        throw new Exception("User ID is required");
    }

    // Get unread notification count
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];

    echo json_encode(array(
        "status" => "success",
        "count" => (int)$count
    ));
} catch (Exception $e) {
    error_log("Error in notifications/count.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => $e->getMessage()
    ));
}
