<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "employee") {
    header("Location: ../login.php");
    exit();
}

// Fetch user's tasks
$user_id = $_SESSION["user_id"];
$stmt = $conn->prepare("SELECT * FROM tasks WHERE assigned_to = ? ORDER BY due_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tasks = $result->fetch_all(MYSQLI_ASSOC);

// Fetch user data for the sidebar
$user_stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Tasks</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .tasks-container {
            padding: 20px;
        }

        .task-filters {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .filter-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background: #f0f0f0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: #e0e0e0;
        }

        .filter-btn.active {
            background: #4A90E2;
            color: white;
        }

        .task-list {
            display: grid;
            gap: 20px;
        }

        .task-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .task-card:hover {
            transform: translateY(-2px);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .task-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
        }

        .task-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-in-progress {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .task-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .task-due-date {
            color: #666;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .task-due-date i {
            color: #4A90E2;
        }

        .task-due-date.urgent {
            background: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }

        .task-due-date.overdue {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .task-due-date.completed {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .due-date-countdown {
            font-weight: 500;
            margin-left: 4px;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .start-btn {
            background: #4A90E2;
            color: white;
        }

        .start-btn:hover {
            background: #357ABD;
        }

        .complete-btn {
            background: #28a745;
            color: white;
        }

        .complete-btn:hover {
            background: #218838;
        }

        .task-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .badge-weekly {
            background: #e3f2fd;
            color: #1976d2;
        }

        .no-tasks {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            color: #666;
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="profile">
                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../images/default-profile.png'; ?>" alt="Profile Picture" class="profile-image">
            </div>
            <nav>
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my-tasks.php" class="active"><i class="fas fa-tasks"></i> My Tasks</a>
                <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="dropdown">
                    <button class="dropbtn" style="padding: 0; border: none; background: none;">
                        <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../images/default-profile.png'; ?>"
                            alt="Profile Picture"
                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; outline: 2px solid #4A90E2; outline-offset: 2px;">
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="javascript:void(0);" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Log out</a>
                    </div>
                </div>
            </header>

            <div class="tasks-container">
                <h2>My Tasks</h2>

                <div class="task-filters">
                    <button class="filter-btn active" data-filter="all">All Tasks</button>
                    <button class="filter-btn" data-filter="pending">Pending</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                </div>

                <div class="task-list">
                    <?php if (empty($tasks)): ?>
                        <div class="no-tasks">
                            <i class="fas fa-tasks" style="font-size: 3em; color: #ccc; margin-bottom: 15px;"></i>
                            <p>No tasks assigned yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="task-card" data-status="<?php echo $task['status']; ?>">
                                <div class="task-header">
                                    <div class="task-title">
                                        <?php echo htmlspecialchars($task['title']); ?>
                                        <?php if ($task['is_weekly']): ?>
                                            <span class="task-badge badge-weekly">Weekly</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="task-status status-<?php echo str_replace('_', '-', $task['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                </div>
                                <div class="task-description">
                                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                                </div>
                                <div class="task-footer">
                                    <div class="task-due-date <?php
                                                                $due_date = strtotime($task['due_date']);
                                                                $today = strtotime('today');
                                                                $diff_days = ceil(($due_date - $today) / (60 * 60 * 24));

                                                                if ($task['status'] === 'completed') {
                                                                    echo 'completed';
                                                                } elseif ($diff_days < 0) {
                                                                    echo 'overdue';
                                                                } elseif ($diff_days <= 2) {
                                                                    echo 'urgent';
                                                                }
                                                                ?>">
                                        <i class="far fa-calendar-alt"></i>
                                        Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                        <?php if ($task['status'] !== 'completed'): ?>
                                            <span class="due-date-countdown">
                                                <?php
                                                if ($diff_days < 0) {
                                                    echo 'Overdue by ' . abs($diff_days) . ' day' . (abs($diff_days) !== 1 ? 's' : '');
                                                } elseif ($diff_days === 0) {
                                                    echo 'Due today';
                                                } elseif ($diff_days === 1) {
                                                    echo 'Due tomorrow';
                                                } else {
                                                    echo 'in ' . $diff_days . ' days';
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-actions">
                                        <?php if ($task['status'] === 'pending'): ?>
                                            <button class="action-btn start-btn" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'in_progress')">
                                                <i class="fas fa-play"></i> Start Task
                                            </button>
                                        <?php elseif ($task['status'] === 'in_progress'): ?>
                                            <button class="action-btn complete-btn" onclick="updateTaskStatus(<?php echo $task['id']; ?>, 'completed')">
                                                <i class="fas fa-check"></i> Complete Task
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Load notification count
        function loadNotificationCount() {
            fetch('../api/notifications/count.php?user_id=<?php echo $_SESSION["user_id"]; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const count = data.count;
                        const badge = document.getElementById('notificationCount');
                        badge.textContent = count;
                        badge.style.display = count > 0 ? 'inline' : 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading notification count:', error);
                });
        }

        // Load notification count on page load
        loadNotificationCount();

        // Refresh notification count every minute
        setInterval(loadNotificationCount, 60000);

        // Task status update
        document.querySelectorAll('.action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.dataset.taskId;
                const newStatus = this.dataset.status;

                fetch('update-task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update UI
                            const taskCard = this.closest('.task-card');
                            taskCard.dataset.status = newStatus;
                            taskCard.querySelector('.task-status').className = `task-status status-${newStatus}`;
                            taskCard.querySelector('.task-status').textContent = newStatus.replace('_', ' ');

                            // Update action buttons
                            const actionButtons = taskCard.querySelector('.task-actions');
                            actionButtons.innerHTML = '';
                            if (newStatus === 'pending') {
                                actionButtons.innerHTML = `
                                <button class="action-btn start-btn" data-task-id="${taskId}" data-status="in_progress">
                                    Start Task
                                </button>
                            `;
                            } else if (newStatus === 'in_progress') {
                                actionButtons.innerHTML = `
                                <button class="action-btn complete-btn" data-task-id="${taskId}" data-status="completed">
                                    Complete Task
                                </button>
                            `;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Task filtering
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                const taskCards = document.querySelectorAll('.task-card');

                taskCards.forEach(card => {
                    if (filter === 'all' || card.dataset.status === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Logout functionality
        document.getElementById("logoutBtn").addEventListener("click", function(e) {
            e.preventDefault();

            fetch("../logout.php", {
                    method: "POST",
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === "success") {
                        window.location.href = "../" + data.redirect;
                    } else {
                        throw new Error(data.message || 'Logout failed');
                    }
                })
                .catch(error => {
                    console.error("Logout error:", error);
                    alert("Failed to logout. Please try again.");
                });
        });
    </script>
</body>

</html>