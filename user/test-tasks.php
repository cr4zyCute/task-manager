<?php
session_start();
include_once "../config/database.php";

// Set a test user ID if not logged in
if (!isset($_SESSION["user_id"])) {
    $_SESSION["user_id"] = 1; // Replace with a valid user ID from your database
}

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

    echo "<h2>Database Connection Test</h2>";
    echo "<p>Database connection successful!</p>";

    echo "<h2>Tasks for User ID: " . $_SESSION["user_id"] . "</h2>";
    if (count($tasks) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Due Date</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>" . $task['id'] . "</td>";
            echo "<td>" . $task['title'] . "</td>";
            echo "<td>" . $task['status'] . "</td>";
            echo "<td>" . $task['due_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No tasks found for this user.</p>";
    }
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
