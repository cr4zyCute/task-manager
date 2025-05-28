<?php
// Disable error display
error_reporting(0);
ini_set('display_errors', 0);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once "../../includes/db.php"; // Adjust path if necessary

$response = array('status' => 'error', 'message' => 'An error occurred');

// Check if database connection is successful
if (!isset($conn) || !$conn) {
    $response['message'] = "Database connection failed.";
    http_response_code(500);
    echo json_encode($response);
    exit();
}

try {
    // Query to get employee performance data
    // This query joins the employees and tasks tables to count tasks by status for each employee
    $sql = "
        SELECT 
            e.id,
            e.first_name,
            e.last_name,
            e.profile_image,
            COUNT(t.id) AS total_tasks,
            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
            SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_tasks,
            SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) AS pending_tasks
        FROM 
            employees e
        LEFT JOIN 
            tasks t ON e.id = t.assigned_to
        WHERE e.role = 'employee'
        GROUP BY 
            e.id, e.first_name, e.last_name, e.profile_image
        ORDER BY
            e.first_name, e.last_name
    ";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $employees_performance = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Prepend the uploads path to the profile_image filename
            $row['profile_image'] = !empty($row['profile_image']) ? '../uploads/' . basename($row['profile_image']) : '../uploads/default-avatar.png';
            $employees_performance[] = $row;
        }
    }

    if ($result) $result->free();

    // Set success response
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "employees" => $employees_performance
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Error in employees/performance.php: " . $e->getMessage());

    // Set error response
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error loading employee performance data: " . $e->getMessage()
    ]);
} finally {
    // Close the database connection if it exists and is open
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
