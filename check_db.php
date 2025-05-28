<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Database configuration
$host = "localhost";
$user = "root";
$password = "";
$db = "task_manager";

try {
    // Test connection
    $conn = new mysqli($host, $user, $password);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<p style='color: green;'>✓ Connected to MySQL server successfully</p>";

    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$db'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>✗ Database '$db' does not exist</p>";

        // Create database
        if ($conn->query("CREATE DATABASE $db")) {
            echo "<p style='color: green;'>✓ Created database '$db'</p>";
        } else {
            throw new Exception("Failed to create database: " . $conn->error);
        }
    } else {
        echo "<p style='color: green;'>✓ Database '$db' exists</p>";
    }

    // Select database
    $conn->select_db($db);

    // Check if employees table exists
    $result = $conn->query("SHOW TABLES LIKE 'employees'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>✗ Table 'employees' does not exist</p>";

        // Create employees table
        $sql = "CREATE TABLE employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            gender VARCHAR(10),
            age INT,
            birthday DATE,
            address TEXT,
            email VARCHAR(150) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            profile_image VARCHAR(255),
            role VARCHAR(100) DEFAULT 'employee',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Created table 'employees'</p>";
        } else {
            throw new Exception("Failed to create employees table: " . $conn->error);
        }
    } else {
        echo "<p style='color: green;'>✓ Table 'employees' exists</p>";

        // Check table structure
        $result = $conn->query("DESCRIBE employees");
        echo "<h3>Employees Table Structure:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Check if tasks table exists
    $result = $conn->query("SHOW TABLES LIKE 'tasks'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>✗ Table 'tasks' does not exist</p>";

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
            echo "<p style='color: green;'>✓ Created table 'tasks'</p>";
        } else {
            throw new Exception("Failed to create tasks table: " . $conn->error);
        }
    } else {
        echo "<p style='color: green;'>✓ Table 'tasks' exists</p>";
    }

    // Check if notifications table exists
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>✗ Table 'notifications' does not exist</p>";

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
            echo "<p style='color: green;'>✓ Created table 'notifications'</p>";
        } else {
            throw new Exception("Failed to create notifications table: " . $conn->error);
        }
    } else {
        echo "<p style='color: green;'>✓ Table 'notifications' exists</p>";
    }

    // Check if there are any employees
    $result = $conn->query("SELECT COUNT(*) as count FROM employees");
    $row = $result->fetch_assoc();
    echo "<p>Total employees in database: " . $row['count'] . "</p>";

    if ($row['count'] == 0) {
        echo "<p style='color: orange;'>! No employees found in the database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

// Close connection
if (isset($conn)) {
    $conn->close();
}
