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

    // Fetch user's notifications
    $user_id = $_SESSION["user_id"];
    $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user data for the sidebar
    $user_query = "SELECT * FROM employees WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(":user_id", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error in notifications.php: " . $e->getMessage());
    $notifications = [];
    $user = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="profile">
                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../images/default-profile.png'; ?>" alt="Profile Picture" class="profile-image">
            </div>
            <nav>
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="my-tasks.php"><i class="fas fa-tasks"></i> My Tasks</a>
                <a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a>
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

            <!-- Notifications Sidebar -->
            <div class="notifications-sidebar" id="notificationsSidebar">
                <div class="notif-header">
                    <h2>Notifications</h2>
                    <button class="close-notif">&times;</button>
                </div>
                <div class="notif-content">
                    <?php if (empty($notifications)): ?>
                        <div class="notification-item">
                            <div class="notif-details">
                                <p>No notifications yet</p>
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
                                    <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
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

        // Load notification count on page load
        loadNotificationCount();

        // Refresh notification count every minute
        setInterval(loadNotificationCount, 60000);

        // Mark notification as read when clicked
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                if (!this.classList.contains('unread')) return;

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
                            loadNotificationCount(); // Update notification count
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Close notifications sidebar
        const closeNotif = document.querySelector('.close-notif');
        closeNotif.addEventListener('click', function() {
            document.getElementById('notificationsSidebar').classList.remove('active');
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