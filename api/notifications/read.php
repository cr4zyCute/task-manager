<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get user ID from query parameter
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : die();

// Query to get notifications
$query = "SELECT n.*, t.title as task_title, t.due_date 
          FROM notifications n 
          LEFT JOIN tasks t ON n.task_id = t.id 
          WHERE n.user_id = :user_id 
          ORDER BY n.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$notifications = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    array_push($notifications, array(
        "id" => $row['id'],
        "message" => $row['message'],
        "task_title" => $row['task_title'],
        "due_date" => $row['due_date'],
        "is_read" => $row['is_read'],
        "created_at" => $row['created_at']
    ));
}

http_response_code(200);
echo json_encode(array(
    "status" => "success",
    "records" => $notifications
));
