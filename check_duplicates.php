<?php
include_once "includes/db.php";

try {
    // Check for duplicate emails
    $query = "SELECT email, COUNT(*) as count 
              FROM employees 
              GROUP BY email 
              HAVING count > 1";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo "Found duplicate emails:\n";
        while ($row = $result->fetch_assoc()) {
            echo "Email: " . $row['email'] . " appears " . $row['count'] . " times\n";
        }
    } else {
        echo "No duplicate emails found.\n";
    }

    // Check for duplicate names
    $query = "SELECT first_name, last_name, COUNT(*) as count 
              FROM employees 
              GROUP BY first_name, last_name 
              HAVING count > 1";

    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        echo "\nFound duplicate names:\n";
        while ($row = $result->fetch_assoc()) {
            echo "Name: " . $row['first_name'] . " " . $row['last_name'] . " appears " . $row['count'] . " times\n";
        }
    } else {
        echo "No duplicate names found.\n";
    }

    // Show all employees
    echo "\nAll employees in database:\n";
    $query = "SELECT id, first_name, last_name, email, role FROM employees ORDER BY first_name, last_name";
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] .
            ", Name: " . $row['first_name'] . " " . $row['last_name'] .
            ", Email: " . $row['email'] .
            ", Role: " . $row['role'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
