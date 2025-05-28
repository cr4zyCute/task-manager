<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once "../../config/database.php";

// Check if the request method is PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['id']) || !isset($data['title']) || !isset($data['description']) || !isset($data['due_date'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Update the task
    $stmt = $conn->prepare("
        UPDATE tasks 
        SET title = :title,
            description = :description,
            due_date = :due_date,
            status = :status,
            is_weekly = :is_weekly
        WHERE id = :id
    ");

    $status = $data['status'] ?? 'pending';
    $is_weekly = $data['is_weekly'] ?? false;

    $stmt->bindParam(":id", $data['id']);
    $stmt->bindParam(":title", $data['title']);
    $stmt->bindParam(":description", $data['description']);
    $stmt->bindParam(":due_date", $data['due_date']);
    $stmt->bindParam(":status", $status);
    $stmt->bindParam(":is_weekly", $is_weekly);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Task updated successfully'
        ]);
    } else {
        throw new Exception("Error updating task");
    }
} catch (PDOException $e) {
    error_log("Database Error in tasks/update.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error in tasks/update.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
