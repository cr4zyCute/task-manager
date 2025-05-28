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

    // Get tasks for the current user
    $query = "SELECT * FROM tasks WHERE assigned_to = :user_id ORDER BY due_date ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION["user_id"]);
    $stmt->execute();

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);
} catch (Exception $e) {
    error_log("Error in get-tasks.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
