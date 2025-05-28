<?php
include_once "includes/db.php";

try {
    // Check if tasks table exists
    $result = $conn->query("SHOW TABLES LIKE 'tasks'");
    if ($result->num_rows == 0) {
        echo "Creating tasks table...\n";

        // Create tasks table
        $sql = "CREATE TABLE tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            assigned_to INT NOT NULL,
            due_date DATE NOT NULL,
            is_weekly BOOLEAN DEFAULT FALSE,
            status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (assigned_to) REFERENCES employees(id)
        )";

        if ($conn->query($sql)) {
            echo "Tasks table created successfully!\n";
        } else {
            throw new Exception("Failed to create tasks table: " . $conn->error);
        }
    } else {
        echo "Tasks table exists.\n";

        // Check and add missing columns
        $columns = [
            "is_weekly BOOLEAN DEFAULT FALSE",
            "status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending'"
        ];

        foreach ($columns as $column) {
            $column_name = explode(" ", $column)[0];
            $result = $conn->query("SHOW COLUMNS FROM tasks LIKE '$column_name'");
            if ($result->num_rows == 0) {
                echo "Adding column $column_name...\n";
                if (!$conn->query("ALTER TABLE tasks ADD COLUMN $column")) {
                    throw new Exception("Failed to add column $column_name: " . $conn->error);
                }
                echo "Column $column_name added successfully!\n";
            }
        }

        // Check table structure
        $result = $conn->query("DESCRIBE tasks");
        echo "\nTasks Table Structure:\n";
        echo "----------------------\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }

    // Check if notifications table exists
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result->num_rows == 0) {
        echo "\nCreating notifications table...\n";

        // Create notifications table
        $sql = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task_id INT,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('task', 'system', 'update') NOT NULL DEFAULT 'task',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES employees(id),
            FOREIGN KEY (task_id) REFERENCES tasks(id)
        )";

        if ($conn->query($sql)) {
            echo "Notifications table created successfully!\n";
        } else {
            throw new Exception("Failed to create notifications table: " . $conn->error);
        }
    } else {
        echo "\nNotifications table exists.\n";

        // Check table structure
        $result = $conn->query("DESCRIBE notifications");
        echo "\nNotifications Table Structure:\n";
        echo "-----------------------------\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }

    echo "\nDatabase check completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
