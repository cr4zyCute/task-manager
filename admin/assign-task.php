<?php
session_start();
if ($_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}
include "../includes/db.php";

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $assigned_to = $_POST["assigned_to"];
    $due_date = $_POST["due_date"];
    $is_weekly = isset($_POST["is_weekly"]) ? 1 : 0;

    if (empty($title) || empty($assigned_to) || empty($due_date)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert task
        $sql = "INSERT INTO tasks (title, description, assigned_to, due_date, is_weekly, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisi", $title, $description, $assigned_to, $due_date, $is_weekly);

        if ($stmt->execute()) {
            $task_id = $conn->insert_id;

            // Get employee details for notification
            $emp_sql = "SELECT first_name, last_name, email FROM employees WHERE id = ?";
            $emp_stmt = $conn->prepare($emp_sql);
            $emp_stmt->bind_param("i", $assigned_to);
            $emp_stmt->execute();
            $employee = $emp_stmt->get_result()->fetch_assoc();

            // Create notification
            $notification_title = "New Task Assigned";
            $notification_message = sprintf(
                "You have been assigned a new %s task: '%s'. Due date: %s",
                $is_weekly ? "weekly" : "",
                $title,
                date('M d, Y', strtotime($due_date))
            );

            // Insert notification
            $notify_sql = "INSERT INTO notifications (user_id, task_id, title, message, type, created_at) 
                          VALUES (?, ?, ?, ?, 'task', NOW())";
            $notify = $conn->prepare($notify_sql);
            $notify->bind_param("iiss", $assigned_to, $task_id, $notification_title, $notification_message);
            $notify->execute();

            // Send email notification
            $to = $employee['email'];
            $subject = "New Task Assignment: " . $title;
            $message = "Dear " . $employee['first_name'] . ",\n\n";
            $message .= "You have been assigned a new task:\n\n";
            $message .= "Title: " . $title . "\n";
            $message .= "Description: " . $description . "\n";
            $message .= "Due Date: " . date('M d, Y', strtotime($due_date)) . "\n";
            $message .= "Type: " . ($is_weekly ? "Weekly Task" : "Regular Task") . "\n\n";
            $message .= "Please log in to your account to view more details.\n\n";
            $message .= "Best regards,\nTask Management System";

            $headers = "From: noreply@taskmanager.com\r\n";
            $headers .= "Reply-To: admin@taskmanager.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            mail($to, $subject, $message, $headers);

            $success_message = "Task assigned successfully!";

            // Clear form data
            $title = $description = $due_date = "";
            $is_weekly = 0;
        } else {
            $error_message = "Error assigning task. Please try again.";
        }
    }
}

// Fetch employees for dropdown
$employees = $conn->query("SELECT id, first_name, last_name FROM employees ORDER BY first_name, last_name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Assign Task</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .assign-task-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .assign-task-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .form-group .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .submit-btn {
            background: #4A90E2;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #357ABD;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="profile">
                <img src="../images/adminicon.png" alt="Admin Profile" class="profile-image">
            </div>
            <nav>
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="tasks.php"><i class="fas fa-tasks"></i> Tasks</a>
                <a href="assign-task.php" class="active"><i class="fas fa-plus"></i> Assign Task</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="dropdown">
                    <button class="dropbtn" style="padding: 0; border: none; background: none;">
                        <img src="../images/adminicon.png"
                            alt="Admin Profile"
                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; outline: 2px solid #4A90E2; outline-offset: 2px;">
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>

                        <a href="javascript:void(0);" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Log out</a>
                    </div>
                </div>
            </header>

            <div class="assign-task-container">
                <h2>Assign New Task</h2>

                <?php if ($success_message): ?>
                    <div class="message success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="message error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form class="assign-task-form" method="POST">
                    <div class="form-group">
                        <label for="title">Task Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Task Description</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="assigned_to">Assign To *</label>
                        <select id="assigned_to" name="assigned_to" required>
                            <option value="">Select Employee</option>
                            <?php while ($employee = $employees->fetch_assoc()): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="date" id="due_date" name="due_date" value="<?php echo $due_date ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_weekly" name="is_weekly" <?php echo ($is_weekly ?? 0) ? 'checked' : ''; ?>>
                            <label for="is_weekly">This is a weekly task</label>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Assign Task</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('due_date').min = today;

        // Load employees when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadEmployees();
        });

        // Function to load employees
        function loadEmployees() {
            fetch('../api/users/read.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success' && Array.isArray(data.records)) {
                        const select = document.getElementById('assigned_to');
                        select.innerHTML = '<option value="">Select Employee</option>';
                        data.records.forEach(employee => {
                            select.innerHTML += `<option value="${employee.id}">${employee.first_name} ${employee.last_name}</option>`;
                        });
                    } else {
                        throw new Error('Invalid data format received');
                    }
                })
                .catch(error => {
                    console.error('Error loading employees:', error);
                    const select = document.getElementById('assigned_to');
                    select.innerHTML = '<option value="">Error loading employees</option>';
                });
        }

        // Logout functionality
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();

            fetch("../logout.php", {
                    method: "POST"
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        window.location.href = "../" + data.redirect;
                    }
                })
                .catch(error => {
                    console.error("Logout error:", error);
                });
        });
    </script>
</body>

</html>