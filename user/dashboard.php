<?php
session_start();
include_once "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "employee") {
    header("Location: ../login.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Fetch user data for the sidebar
    $user_id = $_SESSION["user_id"];
    $query = "SELECT * FROM employees WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch user's notifications
    $notif_query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bindParam(":user_id", $user_id);
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error in dashboard.php: " . $e->getMessage());
    $user = [];
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="profile">
                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../images/default-profile.png'; ?>" alt="Profile Picture" class="profile-image">
            </div>
            <nav>
                <a href="#" class="nav-link active" data-tab="dashboard"><i class="fas fa-home"></i> Dashboard</a>
                <a href="#" class="nav-link" data-tab="tasks"><i class="fas fa-tasks"></i> My Tasks</a>
                <a href="#" class="nav-link notification-toggle" id="notificationToggle">
                    <i class="fas fa-bell"></i> Notifications
                    <span class="notification-badge" id="notificationCount">0</span>
                </a>
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

            <div id="dashboard" class="tab-content active">
                <section class="content">
                    <div class="progress-task">
                        <h2>Progress Task</h2>
                        <div class="progress-container">
                            <div class="progress-stats">
                                <div class="stat-card">
                                    <div class="stat-icon pending">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3 id="pendingCount">0</h3>
                                        <p>Pending Tasks</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon in-progress">
                                        <i class="fas fa-spinner"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3 id="inProgressCount">0</h3>
                                        <p>In Progress</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon completed">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3 id="completedCount">0</h3>
                                        <p>Completed</p>
                                    </div>
                                </div>
                            </div>
                            <div class="progress-chart">
                                <canvas id="progressChart"></canvas>
                            </div>
                            <div class="deadline-tasks">
                                <h3>Nearest Deadlines</h3>
                                <div id="deadlineTaskList" class="deadline-list-container">
                                    <!-- Deadline tasks will be loaded here -->
                                </div>
                            </div>
                            <div class="progress-list">
                                <h3>Progress Task List</h3>
                                <div class="progress-filters">
                                    <select id="progressStatusFilter" class="filter-select">
                                        <option value="all">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div id="progressTaskList" class="task-list-container">
                                    <!-- Tasks will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="calendar">
                        <h2>Calendar</h2>
                        <div id="taskCalendar"></div>
                    </div>
                </section>
            </div>

            <div id="tasks" class="tab-content">
                <div class="tasks-header">
                    <h2>My Tasks</h2>
                    <div class="tasks-controls">
                        <div class="search-container">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="taskSearch" placeholder="Search tasks...">
                            </div>
                        </div>
                        <div class="task-filters">
                            <select id="statusFilter" class="filter-select">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="task-list" id="taskList">
                    <!-- Tasks will be loaded here dynamically -->
                </div>
            </div>
        </main>

        <!-- Notifications Sidebar -->
        <div class="notifications-sidebar" id="notificationsSidebar">
            <div class="notif-header">
                <h2>Notifications</h2>
                <button class="close-notif">&times;</button>
            </div>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="notificationSearch" placeholder="Search notifications...">
                </div>
            </div>
            <div class="notif-content">
                <?php if (empty($notifications)): ?>
                    <div class="notification-item empty-state">
                        <div class="notif-details">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications yet</p>
                            <small>You'll see your notifications here</small>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                            data-id="<?php echo $notification['id']; ?>">
                            <div class="notif-user-icon">
                                <i class="fas <?php echo $notification['type'] === 'task' ? 'fa-tasks' : 'fa-info-circle'; ?>"></i>
                            </div>
                            <div class="notif-details">
                                <div class="notif-header">
                                    <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                    <span class="notif-time"><?php echo date('M d, H:i', strtotime($notification['created_at'])); ?></span>
                                </div>
                                <div class="notif-message">
                                    <?php
                                    $message = htmlspecialchars($notification['message']);

                                    if ($notification['type'] === 'task') {
                                        // Format task details
                                        $message = preg_replace(
                                            '/Task Details:\nTitle: (.*?)\nDescription: (.*?)\nStatus Updated: (.*?)$/s',
                                            '<div class="task-details">
                                                <span class="task-title">$1</span>
                                                <div class="task-description">$2</div>
                                                <div><span class="status-label">Status Updated:</span> <span class="status-update">$3</span></div>
                                            </div>',
                                            $message
                                        );
                                    } else {
                                        // Format other messages with line breaks
                                        $message = nl2br($message);
                                    }

                                    echo $message;
                                    ?>
                                </div>
                                <?php if (!$notification['is_read']): ?>
                                    <div class="notif-actions">
                                        <span class="unread-indicator"></span>
                                        <small>Click to mark as read</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update the message preview modal HTML structure -->
        <div class="message-preview-modal" id="messagePreviewModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"></h3>
                    <div class="modal-actions">
                        <button class="delete-notification" title="Delete notification">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="close-modal">&times;</button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="message-header">
                        <div class="message-meta">
                            <div class="message-time">
                                <i class="fas fa-clock"></i>
                                <span></span>
                            </div>
                        </div>
                    </div>
                    <div class="message-content"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .notifications-sidebar {
            width: 350px;
            background: #fff;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
            border-left: 1px solid #e0e0e0;
        }

        .notif-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }

        .close-notif {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0;
        }

        .notif-content {
            max-height: calc(100vh - 60px);
            overflow-y: auto;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: background-color 0.2s;
            cursor: pointer;
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.unread {
            background-color: #f0f7ff;
        }

        .notification-item.empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .notification-item.empty-state i {
            font-size: 2rem;
            color: #ccc;
            margin-bottom: 10px;
        }

        .notif-user-icon {
            width: 40px;
            height: 40px;
            background: #e3f2fd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1976d2;
        }

        .notif-details {
            flex: 1;
        }

        .notif-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
        }

        .notif-header strong {
            color: #333;
            font-size: 0.95rem;
        }

        .notif-time {
            color: #666;
            font-size: 0.8rem;
        }

        .notif-message .due-date {
            color: #e53935;
            font-weight: 500;
            background: #ffebee;
            padding: 4px 8px;
            border-radius: 4px;
            border-left: 3px solid #e53935;
            display: inline-block;
            margin: 4px 0;
            font-size: 0.95rem;
        }

        .notif-message .description {
            color: #666;
            font-style: italic;
            margin: 8px 0;
            padding-left: 8px;
            border-left: 2px solid #e0e0e0;
        }

        .notif-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: #666;
        }

        .unread-indicator {
            width: 8px;
            height: 8px;
            background: #1976d2;
            border-radius: 50%;
        }

        .notification-badge {
            background: #e53935;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .message-preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .message-preview-modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalSlideIn 0.3s ease-out;
            display: flex;
            flex-direction: column;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
            position: relative;
        }

        .modal-title {
            margin: 0;
            font-size: 1.4rem;
            color: #1a73e8;
            font-weight: 600;
            padding-right: 40px;
            line-height: 1.4;
        }

        .modal-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .delete-notification {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 8px;
            font-size: 1.1rem;
            transition: all 0.2s;
            border-radius: 50%;
        }

        .delete-notification:hover {
            background: #ffebee;
            color: #c82333;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
        }

        .close-modal:hover {
            background: #f0f0f0;
            color: #333;
        }

        .modal-body {
            padding: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .message-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #fff;
        }

        .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .message-time {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-time i {
            color: #1a73e8;
        }

        .message-content {
            padding: 20px;
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
            flex: 1;
        }

        .message-content .due-date {
            color: #e53935;
            font-weight: 600;
            background: #ffebee;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #e53935;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 16px 0;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(229, 57, 53, 0.15);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 2px 8px rgba(229, 57, 53, 0.15);
            }

            50% {
                box-shadow: 0 2px 12px rgba(229, 57, 53, 0.25);
            }

            100% {
                box-shadow: 0 2px 8px rgba(229, 57, 53, 0.15);
            }
        }

        .message-content .description {
            color: #555;
            font-style: italic;
            margin: 16px 0;
            padding: 12px 16px;
            border-left: 3px solid #e0e0e0;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .message-content .description i {
            color: #1a73e8;
            margin-top: 3px;
        }

        .tasks-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 0 20px;
        }

        .task-filters {
            display: flex;
            gap: 10px;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            color: #333;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-select:hover {
            border-color: #1a73e8;
        }

        .task-list {
            padding: 20px;
            display: grid;
            gap: 20px;
        }

        .task-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .task-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .task-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .task-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-in-progress {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .task-details {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .task-meta {
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 0.9rem;
        }

        .task-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .task-meta i {
            color: #1a73e8;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .update-status {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            background: #1a73e8;
            color: white;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .update-status:hover {
            background-color: #1557b0;
        }

        .status-dropdown {
            position: relative;
            display: inline-block;
        }

        .status-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            border-radius: 6px;
            z-index: 1;
        }

        .status-dropdown-content.show {
            display: block;
        }

        .status-option {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .status-option:hover {
            background-color: #f5f5f5;
        }

        .status-option i {
            font-size: 0.9rem;
        }

        .search-container {
            padding: 10px 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .search-box {
            position: relative;
            width: 100%;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .search-box input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #1a73e8;
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
        }

        .tasks-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-container {
            min-width: 250px;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        .no-results i {
            font-size: 2rem;
            color: #ccc;
            margin-bottom: 10px;
        }

        .notif-message {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 8px;
        }

        .notif-message .task-details {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 12px;
            margin: 8px 0;
        }

        .notif-message .task-title {
            color: #1a73e8;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 8px;
            display: block;
        }

        .notif-message .task-description {
            color: #666;
            margin: 8px 0;
            padding-left: 8px;
            border-left: 3px solid #e0e0e0;
        }

        .notif-message .status-update {
            color: #2e7d32;
            font-weight: 500;
            background: #e8f5e9;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 8px;
        }

        .notif-message .status-label {
            color: #666;
            font-weight: 500;
        }

        /* Calendar Styles */
        .calendar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
            flex: 1;
        }

        .calendar h2 {
            margin-bottom: 20px;
            color: #333;
        }

        #taskCalendar {
            height: 800px;
        }

        .fc-event {
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .fc-event-title {
            font-weight: 500;
        }

        .fc-toolbar-title {
            font-size: 1.2rem !important;
            color: #333;
        }

        .fc-button-primary {
            background-color: #1a73e8 !important;
            border-color: #1a73e8 !important;
        }

        .fc-button-primary:hover {
            background-color: #1557b0 !important;
            border-color: #1557b0 !important;
        }

        .fc-button-primary:disabled {
            background-color: #e0e0e0 !important;
            border-color: #e0e0e0 !important;
        }

        .fc-daygrid-day-number {
            color: #666;
            text-decoration: none;
        }

        .fc-day-today {
            background-color: #f8f9fa !important;
        }

        /* Progress Task Styles */
        .progress-task {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 20px;
            max-width: 500px;
            width: 100%;
        }

        .progress-task h2 {
            margin-bottom: 10px;
            color: #333;
            font-size: 1.1rem;
        }

        .progress-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .progress-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 1.3rem;
            color: #333;
        }

        .stat-info p {
            margin: 2px 0 0;
            color: #666;
            font-size: 0.8rem;
        }

        .progress-chart {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            height: 180px;
        }

        /* Update the content section to use flexbox */
        .content {
            display: flex;
            gap: 20px;
            padding: 0 20px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .progress-task {
            flex: 0 0 500px;
            max-width: 500px;
        }

        .calendar {
            flex: 1;
            min-width: 800px;
        }

        #taskCalendar {
            height: 800px;
            width: 100%;
        }

        .fc-view-harness {
            min-height: 700px !important;
        }

        .fc-toolbar {
            margin-bottom: 1.5em !important;
        }

        .fc-toolbar-title {
            font-size: 1.4em !important;
        }

        .fc-button {
            padding: 0.5em 1em !important;
        }

        .fc-daygrid-day {
            min-height: 100px !important;
        }

        /* Progress List Styles */
        .progress-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .progress-list h3 {
            font-size: 1rem;
            color: #333;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        .progress-filters {
            margin-bottom: 10px;
        }

        .filter-select {
            width: 100%;
            padding: 6px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #333;
            background: white;
        }

        .task-list-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .progress-task-item {
            background: white;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 8px;
            font-size: 0.8rem;
            border-left: 3px solid #1976d2;
            transition: transform 0.2s;
        }

        .progress-task-item:hover {
            transform: translateX(5px);
        }

        .progress-task-item .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .progress-task-item .task-title {
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
            margin: 0;
        }

        .progress-task-item .task-description {
            color: #666;
            font-size: 0.8rem;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .progress-task-item .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 0.75rem;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
        }

        .progress-task-item .task-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .progress-task-item .task-meta i {
            color: #1976d2;
        }

        .no-tasks {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .no-tasks i {
            font-size: 1.5rem;
            color: #1976d2;
            margin-bottom: 8px;
        }

        .no-tasks p {
            margin: 0;
            font-size: 0.8rem;
        }

        /* Deadline Tasks Styles */
        .deadline-tasks {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .deadline-tasks h3 {
            font-size: 1rem;
            color: #333;
            margin: 0 0 12px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }

        .deadline-list-container {
            max-height: 200px;
            overflow-y: auto;
        }

        .deadline-task-item {
            background: white;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 8px;
            font-size: 0.8rem;
            border-left: 3px solid #e53935;
            transition: transform 0.2s;
        }

        .deadline-task-item:hover {
            transform: translateX(5px);
        }

        .deadline-task-item .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .deadline-task-item .task-title {
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
            margin: 0;
        }

        .deadline-task-item .days-left {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 500;
            background: #ffebee;
            color: #c62828;
        }

        .deadline-task-item .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 0.75rem;
            padding-top: 8px;
            border-top: 1px solid #f0f0f0;
        }

        .deadline-task-item .task-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .deadline-task-item .task-meta i {
            color: #e53935;
        }

        .deadline-task-item .urgent {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(229, 57, 53, 0.2);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(229, 57, 53, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(229, 57, 53, 0);
            }
        }

        /* Add these styles to your existing CSS */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .btn-primary {
            background-color: #4A90E2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #357ABD;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit,
        .btn-delete {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
        }

        .btn-edit i {
            color: #4A90E2;
        }

        .btn-delete i {
            color: #E24A4A;
        }

        .btn-edit:hover i {
            color: #357ABD;
        }

        .btn-delete:hover i {
            color: #BD3535;
        }
    </style>

    <script>
        // Initialize notification system
        let notificationQueue = [];
        let isShowingPreview = false;
        let lastNotificationId = 0;

        // Function to show notification preview
        function showNotificationPreview(notification) {
            const preview = document.getElementById('notificationPreview');
            if (!preview) return;

            const title = preview.querySelector('.preview-message-title');
            const text = preview.querySelector('.preview-message-text');
            const time = preview.querySelector('.preview-time');

            if (title) title.textContent = notification.title;
            if (text) text.textContent = notification.message;
            if (time) time.textContent = new Date(notification.created_at).toLocaleString();

            preview.style.display = 'block';
            setTimeout(() => {
                preview.classList.add('show');
            }, 100);

            isShowingPreview = true;

            // Auto-hide after 5 seconds
            setTimeout(() => {
                hideNotificationPreview();
            }, 5000);
        }

        // Function to hide notification preview
        function hideNotificationPreview() {
            const preview = document.getElementById('notificationPreview');
            if (!preview) return;

            preview.classList.remove('show');
            setTimeout(() => {
                preview.style.display = 'none';
                isShowingPreview = false;

                // Show next notification in queue if any
                if (notificationQueue.length > 0) {
                    setTimeout(() => {
                        showNotificationPreview(notificationQueue.shift());
                    }, 500);
                }
            }, 300);
        }

        // Function to queue notification
        function queueNotification(notification) {
            console.log('Queueing notification:', notification);
            if (isShowingPreview) {
                notificationQueue.push(notification);
            } else {
                showNotificationPreview(notification);
            }
        }

        // Function to check for new notifications
        function checkNewNotifications() {
            console.log('Checking for new notifications...');
            fetch('../api/notifications/check.php?user_id=<?php echo $_SESSION["user_id"]; ?>&last_id=' + lastNotificationId)
                .then(response => response.json())
                .then(data => {
                    console.log('Notification check response:', data);
                    if (data.status === 'success' && data.notifications.length > 0) {
                        data.notifications.forEach(notification => {
                            queueNotification(notification);
                            lastNotificationId = Math.max(lastNotificationId, notification.id);
                        });
                        loadNotificationCount();
                    }
                })
                .catch(error => {
                    console.error('Error checking notifications:', error);
                });
        }

        // Function to load notification count
        function loadNotificationCount() {
            fetch('../api/notifications/count.php?user_id=<?php echo $_SESSION["user_id"]; ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const count = data.count;
                        const badge = document.getElementById('notificationCount');
                        if (badge) {
                            badge.textContent = count;
                            badge.style.display = count > 0 ? 'inline' : 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading notification count:', error);
                });
        }

        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            // Close preview when clicking the close button
            const closeButton = document.querySelector('.close-preview');
            if (closeButton) {
                closeButton.addEventListener('click', hideNotificationPreview);
            }

            // Load initial notification count
            loadNotificationCount();

            // Check for new notifications every 30 seconds
            setInterval(checkNewNotifications, 30000);

            // Check for new notifications immediately
            checkNewNotifications();
        });

        // Update notification items click handlers
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                const notification = {
                    id: notificationId,
                    title: this.querySelector('strong').textContent,
                    message: this.querySelector('.notif-message').textContent.trim(),
                    created_at: this.querySelector('.notif-time').getAttribute('data-time')
                };

                showMessagePreview(notification);

                // Mark as read if unread
                if (this.classList.contains('unread')) {
                    fetch('mark-notification-read.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                notification_id: notificationId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                this.classList.remove('unread');
                                // Update the notification count immediately
                                loadNotificationCount();
                                // Remove the unread indicator and "Click to mark as read" text
                                const unreadIndicator = this.querySelector('.unread-indicator');
                                const clickToRead = this.querySelector('.notif-actions small');
                                if (unreadIndicator) unreadIndicator.remove();
                                if (clickToRead) clickToRead.remove();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });
        });

        // Tab switching functionality
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.classList.contains('notification-toggle')) {
                    e.preventDefault();

                    // Remove active class from all links and tabs
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));

                    // Add active class to clicked link
                    this.classList.add('active');

                    // Show corresponding tab
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                }
            });
        });

        // Notifications sidebar toggle
        const notificationToggle = document.getElementById('notificationToggle');
        const notificationsSidebar = document.getElementById('notificationsSidebar');
        const closeNotif = document.querySelector('.close-notif');

        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            notificationsSidebar.classList.toggle('active');
        });

        closeNotif.addEventListener('click', function() {
            notificationsSidebar.classList.remove('active');
        });

        // Close notifications sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationsSidebar.contains(e.target) &&
                !notificationToggle.contains(e.target) &&
                notificationsSidebar.classList.contains('active')) {
                notificationsSidebar.classList.remove('active');
            }
        });

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

        // Add these new functions for message preview
        function showMessagePreview(notification) {
            const modal = document.getElementById('messagePreviewModal');
            const title = modal.querySelector('.modal-title');
            const content = modal.querySelector('.message-content');
            const time = modal.querySelector('.message-time span');
            const deleteBtn = modal.querySelector('.delete-notification');

            title.textContent = notification.title;

            // Format the message content with enhanced styling
            let formattedMessage = notification.message
                .replace(/Due date: (.*?)(\.|$)/g, '<div class="due-date"><i class="fas fa-calendar-alt"></i> Due date: $1</div>')
                .replace(/Description: (.*?)(\.|$)/g, '<div class="description"><i class="fas fa-info-circle"></i> Description: $1</div>');

            content.innerHTML = formattedMessage;
            time.textContent = new Date(notification.created_at).toLocaleString();

            // Set up delete button
            deleteBtn.onclick = () => deleteNotification(notification.id);

            modal.classList.add('show');
        }

        function hideMessagePreview() {
            const modal = document.getElementById('messagePreviewModal');
            modal.classList.remove('show');
        }

        function deleteNotification(notificationId) {
            if (!confirm('Are you sure you want to delete this notification?')) {
                return;
            }

            fetch('delete-notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        notification_id: notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove notification from the list
                        const notificationElement = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                        if (notificationElement) {
                            notificationElement.remove();
                        }
                        // Hide the modal
                        hideMessagePreview();
                        // Update notification count
                        loadNotificationCount();
                    } else {
                        alert('Failed to delete notification');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the notification');
                });
        }

        // Close modal when clicking outside
        document.getElementById('messagePreviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideMessagePreview();
            }
        });

        // Close modal when clicking close button
        document.querySelector('.close-modal').addEventListener('click', hideMessagePreview);

        // Add these new functions for search functionality
        function setupSearch() {
            const notificationSearch = document.getElementById('notificationSearch');
            const taskSearch = document.getElementById('taskSearch');

            if (notificationSearch) {
                notificationSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const notifications = document.querySelectorAll('.notification-item');
                    let hasResults = false;

                    notifications.forEach(notification => {
                        const title = notification.querySelector('strong').textContent.toLowerCase();
                        const message = notification.querySelector('.notif-message').textContent.toLowerCase();

                        if (title.includes(searchTerm) || message.includes(searchTerm)) {
                            notification.style.display = 'flex';
                            hasResults = true;
                        } else {
                            notification.style.display = 'none';
                        }
                    });

                    // Show/hide no results message
                    let noResults = document.querySelector('.notif-content .no-results');
                    if (!hasResults) {
                        if (!noResults) {
                            noResults = document.createElement('div');
                            noResults.className = 'no-results';
                            noResults.innerHTML = `
                                <i class="fas fa-search"></i>
                                <p>No notifications found</p>
                            `;
                            document.querySelector('.notif-content').appendChild(noResults);
                        }
                    } else if (noResults) {
                        noResults.remove();
                    }
                });
            }

            if (taskSearch) {
                taskSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const tasks = document.querySelectorAll('.task-card');
                    let hasResults = false;

                    tasks.forEach(task => {
                        const title = task.querySelector('.task-title').textContent.toLowerCase();
                        const description = task.querySelector('.task-details').textContent.toLowerCase();

                        if (title.includes(searchTerm) || description.includes(searchTerm)) {
                            task.style.display = 'block';
                            hasResults = true;
                        } else {
                            task.style.display = 'none';
                        }
                    });

                    // Show/hide no results message
                    let noResults = document.querySelector('#taskList .no-results');
                    if (!hasResults) {
                        if (!noResults) {
                            noResults = document.createElement('div');
                            noResults.className = 'no-results';
                            noResults.innerHTML = `
                                <i class="fas fa-search"></i>
                                <p>No tasks found</p>
                            `;
                            document.getElementById('taskList').appendChild(noResults);
                        }
                    } else if (noResults) {
                        noResults.remove();
                    }
                });
            }
        }

        // Update the existing loadTasks function to include search setup
        function loadTasks() {
            fetch('get-tasks.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Tasks data:', data); // Debug log
                    if (data.success) {
                        const taskList = document.getElementById('taskList');
                        taskList.innerHTML = ''; // Clear existing tasks

                        if (data.tasks && data.tasks.length > 0) {
                            data.tasks.forEach(task => {
                                const taskCard = document.createElement('div');
                                taskCard.className = 'task-card';
                                taskCard.innerHTML = `
                                    <div class="task-header">
                                        <h3 class="task-title">${task.title}</h3>
                                        <div class="status-dropdown">
                                            <button class="update-status" onclick="toggleStatusDropdown(this)">
                                                <span class="task-status status-${task.status}">${formatStatus(task.status)}</span>
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                            <div class="status-dropdown-content">
                                                <div class="status-option" onclick="updateTaskStatus(${task.id}, 'pending')">
                                                    <i class="fas fa-clock"></i> Pending
                                                </div>
                                                <div class="status-option" onclick="updateTaskStatus(${task.id}, 'in_progress')">
                                                    <i class="fas fa-spinner"></i> In Progress
                                                </div>
                                                <div class="status-option" onclick="updateTaskStatus(${task.id}, 'completed')">
                                                    <i class="fas fa-check-circle"></i> Completed
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="task-details">${task.description}</div>
                                    <div class="task-footer">
                                        <div class="task-meta">
                                            <span><i class="fas fa-calendar"></i> Due: ${formatDate(task.due_date)}</span>
                                            <span><i class="fas fa-clock"></i> ${task.is_weekly ? 'Weekly' : 'Regular'}</span>
                                        </div>
                                    </div>
                                `;
                                taskList.appendChild(taskCard);
                            });
                        } else {
                            taskList.innerHTML = `
                                <div class="no-tasks">
                                    <i class="fas fa-tasks"></i>
                                    <p>No tasks found</p>
                                </div>
                            `;
                        }
                        setupSearch(); // Setup search after tasks are loaded
                    } else {
                        console.error('Failed to load tasks:', data.message);
                        const taskList = document.getElementById('taskList');
                        taskList.innerHTML = `
                            <div class="no-tasks">
                                <i class="fas fa-exclamation-circle"></i>
                                <p>Error loading tasks: ${data.message}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading tasks:', error);
                    const taskList = document.getElementById('taskList');
                    taskList.innerHTML = `
                        <div class="no-tasks">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Error loading tasks. Please try again later.</p>
                        </div>
                    `;
                });
        }

        function toggleStatusDropdown(button) {
            const dropdown = button.nextElementSibling;
            dropdown.classList.toggle('show');
        }

        function updateTaskStatus(taskId, newStatus) {
            fetch('update-task-status.php', {
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
                        loadTasks(); // Reload tasks to show updated status
                    } else {
                        alert('Failed to update task status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the task');
                });
        }

        function formatDate(dateString) {
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }

        function formatStatus(status) {
            return status.split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.matches('.update-status')) {
                document.querySelectorAll('.status-dropdown-content.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });

        // Filter tasks
        document.getElementById('statusFilter').addEventListener('change', filterTasks);

        function filterTasks() {
            const statusFilter = document.getElementById('statusFilter').value;

            document.querySelectorAll('.task-card').forEach(card => {
                const status = card.querySelector('.task-status').classList[1].replace('status-', '');
                card.style.display = statusFilter === 'all' || status === statusFilter ? 'block' : 'none';
            });
        }

        // Load tasks when the tasks tab is clicked
        document.querySelector('.nav-link[data-tab="tasks"]').addEventListener('click', function() {
            loadTasks();
        });

        // Setup search when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupSearch();
        });

        // Calendar Implementation
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('taskCalendar');
            if (calendarEl) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: function(info, successCallback, failureCallback) {
                        fetch('get-tasks.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const events = data.tasks.map(task => ({
                                        title: task.title,
                                        start: task.due_date,
                                        backgroundColor: getStatusColor(task.status),
                                        borderColor: getStatusColor(task.status),
                                        textColor: '#333333',
                                        extendedProps: {
                                            description: task.description,
                                            status: task.status
                                        }
                                    }));
                                    successCallback(events);
                                } else {
                                    failureCallback(new Error('Failed to load tasks'));
                                }
                            })
                            .catch(error => {
                                console.error('Error loading tasks:', error);
                                failureCallback(error);
                            });
                    },
                    eventClick: function(info) {
                        const task = info.event;
                        const modal = document.createElement('div');
                        modal.className = 'task-modal';
                        modal.innerHTML = `
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h3>${task.title}</h3>
                                    <button class="close-modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Due Date:</strong> ${task.start.toLocaleDateString()}</p>
                                    <p><strong>Status:</strong> ${task.extendedProps.status}</p>
                                    <p><strong>Description:</strong></p>
                                    <p>${task.extendedProps.description}</p>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(modal);

                        // Add styles for the modal
                        const style = document.createElement('style');
                        style.textContent = `
                            .task-modal {
                                position: fixed;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                background: rgba(0, 0, 0, 0.5);
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                z-index: 1000;
                            }
                            .task-modal .modal-content {
                                background: white;
                                padding: 20px;
                                border-radius: 10px;
                                width: 90%;
                                max-width: 500px;
                                max-height: 80vh;
                                overflow-y: auto;
                            }
                            .task-modal .modal-header {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-bottom: 15px;
                            }
                            .task-modal .close-modal {
                                background: none;
                                border: none;
                                font-size: 1.5rem;
                                cursor: pointer;
                                color: #666;
                            }
                            .task-modal .modal-body {
                                color: #333;
                            }
                            .task-modal .modal-body p {
                                margin: 10px 0;
                            }
                        `;
                        document.head.appendChild(style);

                        // Close modal functionality
                        modal.querySelector('.close-modal').addEventListener('click', () => {
                            modal.remove();
                            style.remove();
                        });

                        modal.addEventListener('click', (e) => {
                            if (e.target === modal) {
                                modal.remove();
                                style.remove();
                            }
                        });
                    }
                });
                calendar.render();
            }
        });

        // Progress Task Implementation
        function updateProgressStats() {
            console.log('Fetching tasks for progress...');
            fetch('get-tasks.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Received tasks data:', data);
                    if (data.success) {
                        const tasks = data.tasks;
                        console.log('Processing tasks:', tasks);

                        const stats = {
                            pending: tasks.filter(task => task.status === 'pending').length,
                            inProgress: tasks.filter(task => task.status === 'in_progress').length,
                            completed: tasks.filter(task => task.status === 'completed').length
                        };
                        console.log('Calculated stats:', stats);

                        // Update counters
                        document.getElementById('pendingCount').textContent = stats.pending;
                        document.getElementById('inProgressCount').textContent = stats.inProgress;
                        document.getElementById('completedCount').textContent = stats.completed;

                        // Update chart
                        updateProgressChart(stats);

                        // Update progress task list
                        updateProgressTaskList(tasks);

                        // Update deadline tasks
                        updateDeadlineTasks(tasks);
                    } else {
                        console.error('Failed to load tasks:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading task progress:', error);
                });
        }

        function updateProgressChart(stats) {
            console.log('Updating progress chart with stats:', stats);
            const ctx = document.getElementById('progressChart').getContext('2d');

            // Destroy existing chart if it exists and is a Chart instance
            if (window.progressChart && window.progressChart instanceof Chart) {
                window.progressChart.destroy();
            }

            // Create new chart
            window.progressChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'In Progress', 'Completed'],
                    datasets: [{
                        data: [stats.pending, stats.inProgress, stats.completed],
                        backgroundColor: [
                            '#fff3e0',
                            '#e3f2fd',
                            '#e8f5e9'
                        ],
                        borderColor: [
                            '#f57c00',
                            '#1976d2',
                            '#2e7d32'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        function updateProgressTaskList(tasks) {
            console.log('Updating progress task list with tasks:', tasks);
            const taskListContainer = document.getElementById('progressTaskList');
            const statusFilter = document.getElementById('progressStatusFilter').value;
            taskListContainer.innerHTML = '';

            // Filter in-progress tasks
            let inProgressTasks = tasks.filter(task => task.status === 'in_progress');
            console.log('Filtered in-progress tasks:', inProgressTasks);

            // Apply status filter
            if (statusFilter !== 'all') {
                inProgressTasks = inProgressTasks.filter(task => task.status === statusFilter);
            }

            if (inProgressTasks.length === 0) {
                taskListContainer.innerHTML = `
                    <div class="no-tasks">
                        <i class="fas fa-spinner"></i>
                        <p>No tasks in progress</p>
                    </div>
                `;
                return;
            }

            // Sort tasks by due date
            inProgressTasks.sort((a, b) => new Date(a.due_date) - new Date(b.due_date));

            inProgressTasks.forEach(task => {
                const taskElement = document.createElement('div');
                taskElement.className = 'progress-task-item';
                taskElement.innerHTML = `
                    <div class="task-header">
                        <h4 class="task-title">${task.title}</h4>
                    </div>
                    <div class="task-description">${task.description}</div>
                    <div class="task-meta">
                        <span><i class="fas fa-calendar"></i> Due: ${formatDate(task.due_date)}</span>
                        <span><i class="fas fa-clock"></i> In Progress</span>
                    </div>
                `;
                taskListContainer.appendChild(taskElement);
            });
        }

        // Add event listener for status filter
        document.getElementById('progressStatusFilter').addEventListener('change', function() {
            updateProgressStats();
        });

        // Make sure to call updateProgressStats when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, initializing progress stats...');
            updateProgressStats();

            // Update progress every 30 seconds
            setInterval(updateProgressStats, 30000);
        });

        function updateDeadlineTasks(tasks) {
            const deadlineListContainer = document.getElementById('deadlineTaskList');
            deadlineListContainer.innerHTML = '';

            // Get current date
            const now = new Date();

            // Filter tasks that are not completed and have due dates
            let deadlineTasks = tasks.filter(task =>
                task.status !== 'completed' &&
                task.due_date
            );

            // Calculate days left and add to tasks
            deadlineTasks = deadlineTasks.map(task => {
                const dueDate = new Date(task.due_date);
                const daysLeft = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));
                return {
                    ...task,
                    daysLeft
                };
            });

            // Sort by days left
            deadlineTasks.sort((a, b) => a.daysLeft - b.daysLeft);

            // Take only the 3 nearest deadlines
            deadlineTasks = deadlineTasks.slice(0, 3);

            if (deadlineTasks.length === 0) {
                deadlineListContainer.innerHTML = `
                    <div class="no-tasks">
                        <i class="fas fa-calendar-check"></i>
                        <p>No upcoming deadlines</p>
                    </div>
                `;
                return;
            }

            deadlineTasks.forEach(task => {
                const taskElement = document.createElement('div');
                taskElement.className = `deadline-task-item ${task.daysLeft <= 2 ? 'urgent' : ''}`;

                const daysLeftText = task.daysLeft === 0 ? 'Due Today' :
                    task.daysLeft === 1 ? 'Due Tomorrow' :
                    `${task.daysLeft} days left`;

                taskElement.innerHTML = `
                    <div class="task-header">
                        <h4 class="task-title">${task.title}</h4>
                        <span class="days-left">${daysLeftText}</span>
                    </div>
                    <div class="task-meta">
                        <span><i class="fas fa-calendar"></i> Due: ${formatDate(task.due_date)}</span>
                    </div>
                `;
                deadlineListContainer.appendChild(taskElement);
            });
        }

        // Add this function to get color based on status
        function getStatusColor(status) {
            switch (status) {
                case 'pending':
                    return '#fff3e0'; // Light orange
                case 'in_progress':
                    return '#e3f2fd'; // Light blue
                case 'completed':
                    return '#e8f5e9'; // Light green
                default:
                    return '#f5f5f5'; // Light gray
            }
        }
    </script>
</body>

</html>