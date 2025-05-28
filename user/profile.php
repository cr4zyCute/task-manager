<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "employee") {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch user's notifications
$notif_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch task statistics
$task_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks
    FROM tasks 
    WHERE assigned_to = ?
");
$task_stats->bind_param("i", $user_id);
$task_stats->execute();
$stats = $task_stats->get_result()->fetch_assoc();

// Calculate completion rate
$completion_rate = $stats['total_tasks'] > 0 ?
    round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0;

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $age = $_POST["age"];
    $birthday = $_POST["birthday"];
    $address = $_POST["address"];
    $error = "";

    // Handle profile image upload
    $profile_image = $user['profile_image']; // Keep existing image by default
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["profile_image"]["name"];
        $filetype = $_FILES["profile_image"]["type"];
        $filesize = $_FILES["profile_image"]["size"];

        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            $error = "Error: Please select a valid file format (JPG, JPEG, PNG).";
        } else {
            // Verify file size - 5MB maximum
            $maxsize = 5 * 1024 * 1024;
            if ($filesize > $maxsize) {
                $error = "Error: File size is larger than the allowed limit (5MB).";
            } else {
                // Verify MIME type of the file
                if (in_array($filetype, $allowed)) {
                    // Create unique filename
                    $new_filename = "uploads/profile_" . $user_id . "_" . time() . "." . $ext;

                    // Delete old profile image if exists
                    if (!empty($user['profile_image']) && file_exists("../" . $user['profile_image'])) {
                        unlink("../" . $user['profile_image']);
                    }

                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], "../" . $new_filename)) {
                        $profile_image = $new_filename;
                    } else {
                        $error = "Error: There was a problem uploading your file. Please try again.";
                    }
                } else {
                    $error = "Error: There was a problem with the file type. Please try again.";
                }
            }
        }
    }

    if (empty($error)) {
        // Update user profile
        $update_stmt = $conn->prepare("UPDATE employees SET first_name = ?, last_name = ?, age = ?, birthday = ?, address = ?, profile_image = ? WHERE id = ?");
        $update_stmt->bind_param("ssisssi", $first_name, $last_name, $age, $birthday, $address, $profile_image, $user_id);

        if ($update_stmt->execute()) {
            $_SESSION["name"] = $first_name . " " . $last_name;
            header("Location: profile.php?success=1");
            exit();
        } else {
            $error = "Error updating profile";
        }
    }
}

// Handle password update
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $password_error = "";

    // Verify current password
    $verify_stmt = $conn->prepare("SELECT password FROM employees WHERE id = ?");
    $verify_stmt->bind_param("i", $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $user_data = $result->fetch_assoc();

    if (!password_verify($current_password, $user_data['password'])) {
        $password_error = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "New passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $password_error = "Password must be at least 8 characters long";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);

        if ($update_stmt->execute()) {
            $password_success = "Password updated successfully";
        } else {
            $password_error = "Error updating password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        .profile-sidebar {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-image-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
        }

        .profile-image-large {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #4A90E2;
            transition: all 0.3s ease;
        }

        .profile-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }

        .profile-image-container:hover .profile-image-overlay {
            opacity: 1;
        }

        .profile-image-overlay i {
            color: white;
            font-size: 2rem;
        }

        .profile-info {
            text-align: center;
        }

        .profile-info h2 {
            margin: 0 0 5px;
            color: #2c3e50;
            font-size: 1.5rem;
        }

        .profile-info p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4A90E2;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .profile-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-section {
            margin-bottom: 30px;
        }

        .profile-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        .task-progress {
            margin-top: 20px;
        }

        .progress-bar {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-fill {
            height: 100%;
            background: #4A90E2;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #666;
        }

        .profile-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #4A90E2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8d7da;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            grid-column: 1 / -1;
        }

        .success-message {
            color: #28a745;
            margin-bottom: 20px;
            padding: 15px;
            background: #d4edda;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            grid-column: 1 / -1;
        }

        .submit-btn {
            background: #4A90E2;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            grid-column: 1 / -1;
            width: fit-content;
            margin: 0 auto;
        }

        .submit-btn:hover {
            background: #357ABD;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .image-upload {
            display: none;
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                position: static;
            }

            .profile-form {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
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
                        <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="javascript:void(0);" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Log out</a>
                    </div>
                </div>
            </header>

            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-header">
                        <div class="profile-image-container">
                            <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../images/default-profile.png'; ?>"
                                alt="Profile Picture"
                                class="profile-image-large">
                            <label for="profile_image" class="profile-image-overlay">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user['first_name'] . " " . $user['last_name']); ?></h2>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['completed_tasks']; ?></div>
                            <div class="stat-label">Tasks Completed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['in_progress_tasks']; ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['pending_tasks']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $completion_rate; ?>%</div>
                            <div class="stat-label">Completion Rate</div>
                        </div>
                    </div>
                </div>

                <div class="profile-content">
                    <?php if (isset($error) && !empty($error)): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="success-message">Profile updated successfully!</div>
                    <?php endif; ?>

                    <div class="profile-section">
                        <h3 class="section-title">Personal Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Employee ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['id']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Age</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['age']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Birthday</div>
                                <div class="info-value"><?php echo date('F d, Y', strtotime($user['birthday'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['address']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3 class="section-title">Task Progress</h3>
                        <div class="task-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $completion_rate; ?>%;"></div>
                            </div>
                            <div class="progress-stats">
                                <span>Overall Progress</span>
                                <span><?php echo $completion_rate; ?>%</span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-section">
                        <h3 class="section-title">Update Profile</h3>
                        <form class="profile-form" method="POST" enctype="multipart/form-data">
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" class="image-upload">

                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="birthday">Birthday</label>
                                <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($user['birthday']); ?>" required>
                            </div>

                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>

                            <button type="submit" class="submit-btn">Update Profile</button>
                        </form>
                    </div>


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
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="notif-user-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="notif-details">
                                <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="notif-timestamp"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Preview profile image before upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image-large').src = e.target.result;
                    document.querySelector('.profile-image').src = e.target.result;
                    document.querySelector('.dropbtn img').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

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

        // Notifications sidebar toggle
        const notificationToggle = document.getElementById('notificationToggle');
        const notificationsSidebar = document.getElementById('notificationsSidebar');
        const closeNotif = document.querySelector('.close-notif');

        if (notificationToggle && notificationsSidebar) {
            notificationToggle.addEventListener('click', function(e) {
                e.preventDefault();
                notificationsSidebar.classList.toggle('active');
            });
        }

        if (closeNotif && notificationsSidebar) {
            closeNotif.addEventListener('click', function() {
                notificationsSidebar.classList.remove('active');
            });
        }

        // Close notifications sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (notificationsSidebar &&
                !notificationsSidebar.contains(e.target) &&
                notificationToggle &&
                !notificationToggle.contains(e.target) &&
                notificationsSidebar.classList.contains('active')) {
                notificationsSidebar.classList.remove('active');
            }
        });

        // Logout functionality
        const logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", function(e) {
                e.preventDefault();
                fetch('../logout.php', {
                        method: 'POST',
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.href = data.redirect;
                        } else {
                            console.error('Logout failed:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error during logout:', error);
                        // Fallback to direct redirect if fetch fails
                        window.location.href = '../login.php';
                    });
            });
        }

        // Add animation to form inputs
        document.querySelectorAll('.form-group input, .form-group textarea').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>

</html>