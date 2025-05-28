<?php
session_start();
include "../includes/db.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_email"])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Check if all required fields are present
    if (!isset($_POST['id']) || !isset($_POST['first_name']) || !isset($_POST['last_name']) || !isset($_POST['email'])) {
        throw new Exception('Missing required fields');
    }

    $id = intval($_POST['id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : null;
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
    $birthday = isset($_POST['birthday']) && $_POST['birthday'] !== '' ? $_POST['birthday'] : null;
    $address = isset($_POST['address']) ? trim($_POST['address']) : null;
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'employee';

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate age if provided
    if ($age !== null && ($age < 18 || $age > 100)) {
        throw new Exception('Age must be between 18 and 100');
    }

    // Check if employee exists
    $check_query = "SELECT id FROM employees WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);

    if (!$check_stmt) {
        throw new Exception("Failed to prepare check statement: " . $conn->error);
    }

    $check_stmt->bind_param("i", $id);

    if (!$check_stmt->execute()) {
        throw new Exception("Failed to check employee existence: " . $check_stmt->error);
    }

    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Employee not found");
    }

    // Update the employee record
    $query = "UPDATE employees SET 
              first_name = ?, 
              last_name = ?, 
              email = ?, 
              gender = ?, 
              age = ?, 
              birthday = ?, 
              address = ?, 
              role = ? 
              WHERE id = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare update statement: " . $conn->error);
    }

    $stmt->bind_param(
        "ssssisssi",
        $first_name,
        $last_name,
        $email,
        $gender,
        $age,
        $birthday,
        $address,
        $role,
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to update employee: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Employee updated successfully'
    ]);
} catch (Exception $e) {
    error_log("Error in update_employee.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
