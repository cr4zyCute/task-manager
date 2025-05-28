<?php
class Task
{
    private $conn;
    private $table_name = "tasks";

    public $id;
    public $title;
    public $description;
    public $assigned_to;
    public $due_date;
    public $is_weekly;
    public $status;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create a new task
    public function create()
    {
        try {
            // Validate required fields
            if (empty($this->title) || empty($this->assigned_to) || empty($this->due_date)) {
                throw new Exception("Missing required fields");
            }

            // Check if assigned employee exists
            $check_stmt = $this->conn->prepare("SELECT id FROM employees WHERE id = ?");
            if (!$check_stmt) {
                throw new Exception("Failed to prepare employee check statement: " . $this->conn->error);
            }

            $check_stmt->bind_param("i", $this->assigned_to);
            if (!$check_stmt->execute()) {
                throw new Exception("Failed to check employee: " . $check_stmt->error);
            }

            $result = $check_stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Assigned employee does not exist");
            }

            // Prepare insert query
            $query = "INSERT INTO " . $this->table_name . "
                    (title, description, assigned_to, due_date, is_weekly, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare task insert statement: " . $this->conn->error);
            }

            // Convert is_weekly to integer (0 or 1)
            $is_weekly = $this->is_weekly ? 1 : 0;
            $status = $this->status ?? 'pending';

            $stmt->bind_param(
                "ssisss",
                $this->title,
                $this->description,
                $this->assigned_to,
                $this->due_date,
                $is_weekly,
                $status
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create task: " . $stmt->error);
            }

            $task_id = $this->conn->insert_id;
            $this->createNotification($task_id);

            return $task_id;
        } catch (Exception $e) {
            error_log("Error in Task::create(): " . $e->getMessage());
            throw $e;
        }
    }

    // Create notification for assigned user
    public function createNotification($task_id)
    {
        try {
            // Get employee name
            $emp_query = "SELECT first_name, last_name FROM employees WHERE id = ?";
            $emp_stmt = $this->conn->prepare($emp_query);
            if (!$emp_stmt) {
                throw new Exception("Failed to prepare employee query: " . $this->conn->error);
            }

            $emp_stmt->bind_param("i", $this->assigned_to);
            if (!$emp_stmt->execute()) {
                throw new Exception("Failed to get employee name: " . $emp_stmt->error);
            }

            $result = $emp_stmt->get_result();
            if ($result->num_rows === 0) {
                throw new Exception("Employee not found");
            }

            $employee = $result->fetch_assoc();
            $notification_title = "New Task Assigned";
            $notification_message = "You have been assigned a new task: " . $this->title;

            $query = "INSERT INTO notifications (user_id, task_id, title, message, type)
                    VALUES (?, ?, ?, ?, 'task')";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare notification statement: " . $this->conn->error);
            }

            $stmt->bind_param(
                "iiss",
                $this->assigned_to,
                $task_id,
                $notification_title,
                $notification_message
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create notification: " . $stmt->error);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in Task::createNotification(): " . $e->getMessage());
            return false;
        }
    }

    // Get all tasks
    public function read()
    {
        try {
            $query = "SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name 
                    FROM " . $this->table_name . " t
                    LEFT JOIN employees e ON t.assigned_to = e.id
                    ORDER BY t.due_date ASC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare task read statement: " . $this->conn->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Failed to execute task read query: " . $stmt->error);
            }

            $result = $stmt->get_result();
            if ($result === false) {
                throw new Exception("Failed to get task read result: " . $stmt->error);
            }

            $tasks = array();
            while ($row = $result->fetch_assoc()) {
                $row['is_weekly'] = (bool)$row['is_weekly'];
                $tasks[] = $row;
            }

            return $tasks;
        } catch (Exception $e) {
            error_log("Error in Task::read(): " . $e->getMessage());
            throw $e;
        }
    }

    // Get tasks for specific user
    public function readByUser($user_id)
    {
        try {
            $query = "SELECT t.*, CONCAT(e.first_name, ' ', e.last_name) as assigned_to_name 
                    FROM " . $this->table_name . " t
                    LEFT JOIN employees e ON t.assigned_to = e.id
                    WHERE t.assigned_to = ?
                    ORDER BY t.due_date ASC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare user task read statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute user task read query: " . $stmt->error);
            }

            $result = $stmt->get_result();
            if ($result === false) {
                throw new Exception("Failed to get user task read result: " . $stmt->error);
            }

            $tasks = array();
            while ($row = $result->fetch_assoc()) {
                $row['is_weekly'] = (bool)$row['is_weekly'];
                $tasks[] = $row;
            }

            return $tasks;
        } catch (Exception $e) {
            error_log("Error in Task::readByUser(): " . $e->getMessage());
            throw $e;
        }
    }

    // Update task status
    public function updateStatus()
    {
        try {
            if (empty($this->id) || empty($this->status)) {
                throw new Exception("Missing required fields for status update");
            }

            $query = "UPDATE " . $this->table_name . "
                    SET status = ?
                    WHERE id = ?";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare status update statement: " . $this->conn->error);
            }

            $stmt->bind_param("si", $this->status, $this->id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update task status: " . $stmt->error);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in Task::updateStatus(): " . $e->getMessage());
            throw $e;
        }
    }

    // Delete task
    public function delete()
    {
        try {
            if (empty($this->id)) {
                throw new Exception("Task ID is required for deletion");
            }

            // First delete associated notifications
            $notif_query = "DELETE FROM notifications WHERE task_id = ?";
            $notif_stmt = $this->conn->prepare($notif_query);
            if (!$notif_stmt) {
                throw new Exception("Failed to prepare notification deletion statement: " . $this->conn->error);
            }

            $notif_stmt->bind_param("i", $this->id);
            if (!$notif_stmt->execute()) {
                throw new Exception("Failed to delete task notifications: " . $notif_stmt->error);
            }

            // Then delete the task
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare task deletion statement: " . $this->conn->error);
            }

            $stmt->bind_param("i", $this->id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete task: " . $stmt->error);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in Task::delete(): " . $e->getMessage());
            throw $e;
        }
    }
}
