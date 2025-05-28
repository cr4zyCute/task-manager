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

$response = array();

// Check if database connection is successful
if (!isset($conn) || !$conn) {
    // Return an empty array or error if connection fails, FullCalendar expects an array
    echo json_encode([]);
    exit();
}

try {
    // Query to get tasks with assigned employee info
    $sql = "
        SELECT 
            t.id,
            t.title,
            t.description,
            t.due_date AS start,
            DATE_ADD(t.due_date, INTERVAL 1 DAY) AS end, // FullCalendar 'end' is exclusive, so add 1 day for whole-day events
            t.status,
            e.first_name,
            e.last_name,
            e.profile_image
        FROM 
            tasks t
        LEFT JOIN 
            employees e ON t.assigned_to = e.id
        -- WHERE t.status != 'completed' -- Optional: uncomment to only show non-completed tasks
        ORDER BY
            t.due_date
    ";

    $result = $conn->query($sql);

    $events = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Determine event color based on status
            $color = '';
            switch ($row['status']) {
                case 'completed':
                    $color = '#28a745'; // Green
                    break;
                case 'in_progress':
                    $color = '#17a2b8'; // Blue/Cyan
                    break;
                case 'pending':
                    $color = '#ffc107'; // Yellow
                    break;
                default:
                    $color = '#6c757d'; // Grey
            }

            // Prepend the uploads path to the profile_image filename
            $profile_image_path = !empty($row['profile_image']) ? '../uploads/' . basename($row['profile_image']) : '../uploads/default-avatar.png';

            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'], // Task title only
                'start' => $row['start'],
                'end' => $row['end'], // Use calculated end date
                'description' => $row['description'],
                'status' => $row['status'], // Add status as extended property
                'color' => $color, // Add color
                'profile_image' => $profile_image_path, // Add profile image path
                'assigned_to_name' => ($row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'Unassigned') // Add employee name as extended property
            ];
        }
    }

    if ($result) $result->free();

    // Return events as JSON
    echo json_encode($events);
} catch (Exception $e) {
    // Log the error
    error_log("Error in tasks/calendar.php: " . $e->getMessage());

    // Return an empty array or error if connection fails, FullCalendar expects an array
    echo json_encode([]); // Return empty array on error for calendar compatibility
} finally {
    // Close the database connection if it exists and is open
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
