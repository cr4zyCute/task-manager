<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "employee") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

// Update all notifications status
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$result = $stmt->execute();

if ($result) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update notifications"]);
}
