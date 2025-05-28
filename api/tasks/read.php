<?php
// Disable error display to prevent HTML errors in JSON response
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../config/database.php";

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Database connection not available");
    }

    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

    if ($user_id) {
        // Query to get tasks for specific user with employee name
        $query = "SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name 
                 FROM tasks t 
                 LEFT JOIN employees e ON t.assigned_to = e.id 
                 WHERE t.assigned_to = :user_id 
                 ORDER BY t.due_date ASC";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
    } else {
        // Query to get all tasks with employee names
        $query = "SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name 
                 FROM tasks t 
                 LEFT JOIN employees e ON t.assigned_to = e.id 
                 ORDER BY t.due_date ASC";

        $stmt = $conn->prepare($query);
    }

    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tasks_arr = array();
    $tasks_arr["records"] = array();

    foreach ($result as $row) {
        $task_item = array(
            "id" => $row['id'],
            "title" => $row['title'],
            "description" => $row['description'],
            "due_date" => $row['due_date'],
            "status" => $row['status'],
            "assigned_to" => $row['assigned_to'],
            "assigned_to_name" => $row['assigned_to_name'],
            "is_weekly" => (bool)$row['is_weekly']
        );

        array_push($tasks_arr["records"], $task_item);
    }

    http_response_code(200);
    echo json_encode($tasks_arr);
} catch (PDOException $e) {
    error_log("Database Error in tasks/read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ));
} catch (Exception $e) {
    error_log("Error in tasks/read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "status" => "error",
        "message" => "Error loading tasks: " . $e->getMessage()
    ));
}
