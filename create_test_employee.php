<?php
include_once "includes/db.php";

try {
    // Check if any employees exist
    $result = $conn->query("SELECT COUNT(*) as count FROM employees");
    $row = $result->fetch_assoc();

    if ($row['count'] == 0) {
        // Create a test employee
        $first_name = "Test";
        $last_name = "Employee";
        $email = "test@example.com";
        $password = password_hash("test123", PASSWORD_DEFAULT);
        $role = "employee";

        $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $password, $role);

        if ($stmt->execute()) {
            echo "Test employee created successfully!\n";
            echo "Email: test@example.com\n";
            echo "Password: test123\n";
        } else {
            throw new Exception("Failed to create test employee: " . $stmt->error);
        }
    } else {
        echo "Employees already exist in the database.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
