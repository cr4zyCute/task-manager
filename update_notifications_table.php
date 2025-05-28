<?php
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Add new columns to notifications table
    $alter_queries = [
        "ALTER TABLE notifications ADD COLUMN title VARCHAR(255) NOT NULL AFTER task_id",
        "ALTER TABLE notifications ADD COLUMN type ENUM('task', 'system', 'update') NOT NULL AFTER message",
        "ALTER TABLE notifications ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_read"
    ];

    foreach ($alter_queries as $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
    }

    echo "Notifications table updated successfully!";
} catch (PDOException $e) {
    echo "Error updating notifications table: " . $e->getMessage();
}
