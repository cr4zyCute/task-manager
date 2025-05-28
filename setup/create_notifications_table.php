<?php
include_once "../config/database.php";

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Read SQL file
    $sql = file_get_contents("../sql/create_notifications_table.sql");

    // Execute SQL
    if ($db->exec($sql)) {
        echo "Notifications table created successfully\n";
    } else {
        throw new Exception("Failed to create notifications table");
    }
} catch (Exception $e) {
    error_log("Error creating notifications table: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
