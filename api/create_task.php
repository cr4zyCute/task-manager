<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['title', 'description', 'assigned_to', 'due_date'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Validate if the assigned user exists
    $stmt = $conn->prepare("SELECT id FROM employees WHERE id = :assigned_to");
    $stmt->bindParam(":assigned_to", $data['assigned_to']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Assigned user not found']);
        exit;
    }

    // Insert the task
    $stmt = $conn->prepare("
        INSERT INTO tasks (title, description, assigned_to, due_date, status)
        VALUES (:title, :description, :assigned_to, :due_date, 'pending')
    ");

    $stmt->bindParam(":title", $data['title']);
    $stmt->bindParam(":description", $data['description']);
    $stmt->bindParam(":assigned_to", $data['assigned_to']);
    $stmt->bindParam(":due_date", $data['due_date']);

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

        echo json_encode([
            'success' => true,
            'message' => 'Task created and assigned successfully',
            'task_id' => $task_id
        ]);
    } else {
        $error = $stmt->errorInfo();
        throw new Exception("Error creating task: " . $error[2]);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
