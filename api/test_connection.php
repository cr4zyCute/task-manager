<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include_once "../includes/db.php";

try {
    // Test database connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Test tasks table
    $tasksQuery = "SELECT COUNT(*) as count FROM tasks";
    $tasksResult = $conn->query($tasksQuery);
    $tasksCount = $tasksResult->fetch_assoc()['count'];

    // Test users table
    $usersQuery = "SELECT COUNT(*) as count FROM users WHERE role = 'employee'";
    $usersResult = $conn->query($usersQuery);
    $usersCount = $usersResult->fetch_assoc()['count'];

    // Get table structure
    $tasksStructure = $conn->query("DESCRIBE tasks");
    $usersStructure = $conn->query("DESCRIBE users");

    $tasksColumns = [];
    while ($row = $tasksStructure->fetch_assoc()) {
        $tasksColumns[] = $row;
    }

    $usersColumns = [];
    while ($row = $usersStructure->fetch_assoc()) {
        $usersColumns[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "connection" => "successful",
        "tasks_count" => $tasksCount,
        "users_count" => $usersCount,
        "tasks_structure" => $tasksColumns,
        "users_structure" => $usersColumns
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
