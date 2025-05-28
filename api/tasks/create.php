<?php
// Disable error display
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "../../config/database.php";

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['title', 'description', 'assigned_to', 'due_date'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Validate if the assigned user exists
    $stmt = $conn->prepare("SELECT id FROM employees WHERE id = :assigned_to");
    $stmt->bindParam(":assigned_to", $data['assigned_to']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Assigned user not found']);
        exit;
    }

    // Insert the task
    $stmt = $conn->prepare("
        INSERT INTO tasks (title, description, assigned_to, due_date, status, is_weekly)
        VALUES (:title, :description, :assigned_to, :due_date, :status, :is_weekly)
    ");

    $status = $data['status'] ?? 'pending';
    $is_weekly = $data['is_weekly'] ?? false;

    $stmt->bindParam(":title", $data['title']);
    $stmt->bindParam(":description", $data['description']);
    $stmt->bindParam(":assigned_to", $data['assigned_to']);
    $stmt->bindParam(":due_date", $data['due_date']);
    $stmt->bindParam(":status", $status);
    $stmt->bindParam(":is_weekly", $is_weekly);

    if ($stmt->execute()) {
        $task_id = $conn->lastInsertId();

        // Create notification for the assigned user
        $notification_title = "New Task Assigned";
        $notification_message = "You have been assigned a new task: " . $data['title'];

        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, task_id, title, message, type)
            VALUES (:user_id, :task_id, :title, :message, 'task')
        ");

        $stmt->bindParam(":user_id", $data['assigned_to']);
        $stmt->bindParam(":task_id", $task_id);
        $stmt->bindParam(":title", $notification_title);
        $stmt->bindParam(":message", $notification_message);

        $stmt->execute();

        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'message' => 'Task was created successfully.',
            'task_id' => $task_id
        ]);
    } else {
        throw new Exception("Error creating task");
    }
} catch (PDOException $e) {
    error_log("Database Error in tasks/create.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in tasks/create.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
