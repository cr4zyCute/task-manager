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

// Handle email update
if (isset($_POST['update_email'])) {
    $new_email = $_POST['new_email'];
    $current_password = $_POST['email_password'];
    $email_error = "";

    // Verify current password
    $verify_stmt = $conn->prepare("SELECT password FROM employees WHERE id = ?");
    $verify_stmt->bind_param("i", $user_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    $user_data = $result->fetch_assoc();

    if (!password_verify($current_password, $user_data['password'])) {
        $email_error = "Current password is incorrect";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $email_error = "Invalid email format";
    } else {
        // Check if email is already taken
        $check_stmt = $conn->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $new_email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $email_error = "Email already taken";
        } else {
            // Update email
            $update_stmt = $conn->prepare("UPDATE employees SET email = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_email, $user_id);

            if ($update_stmt->execute()) {
                $email_success = "Email updated successfully";
                $user['email'] = $new_email;
            } else {
                $email_error = "Error updating email";
            }
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
    <title>Settings</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .settings-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .settings-section {
            margin-bottom: 40px;
        }

        .settings-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .settings-form {
            display: grid;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #4A90E2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8d7da;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }

        .success-message {
            color: #28a745;
            margin-bottom: 20px;
            padding: 15px;
            background: #d4edda;
            border-radius: 8px;
            border-left: 4px solid #28a745;
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
            width: fit-content;
        }

        .submit-btn:hover {
            background: #357ABD;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.2);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }

        .password-toggle i:hover {
            color: #4A90E2;
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
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="javascript:void(0);" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Log out</a>
                    </div>
                </div>
            </header>

            <div class="settings-container">
                <div class="settings-content">
                    <div class="settings-section">
                        <h3 class="section-title">Update Email</h3>
                        <?php if (isset($email_error)): ?>
                            <div class="error-message"><?php echo $email_error; ?></div>
                        <?php endif; ?>
                        <?php if (isset($email_success)): ?>
                            <div class="success-message"><?php echo $email_success; ?></div>
                        <?php endif; ?>
                        <form class="settings-form" method="POST">
                            <div class="form-group">
                                <label for="new_email">New Email</label>
                                <input type="email" id="new_email" name="new_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email_password">Current Password</label>
                                <div class="password-toggle">
                                    <input type="password" id="email_password" name="email_password" required>
                                    <i class="fas fa-eye toggle-password"></i>
                                </div>
                            </div>
                            <button type="submit" name="update_email" class="submit-btn">Update Email</button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h3 class="section-title">Update Password</h3>
                        <?php if (isset($password_error)): ?>
                            <div class="error-message"><?php echo $password_error; ?></div>
                        <?php endif; ?>
                        <?php if (isset($password_success)): ?>
                            <div class="success-message"><?php echo $password_success; ?></div>
                        <?php endif; ?>
                        <form class="settings-form" method="POST">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <div class="password-toggle">
                                    <input type="password" id="current_password" name="current_password" required>
                                    <i class="fas fa-eye toggle-password"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <div class="password-toggle">
                                    <input type="password" id="new_password" name="new_password" required>
                                    <i class="fas fa-eye toggle-password"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <div class="password-toggle">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <i class="fas fa-eye toggle-password"></i>
                                </div>
                            </div>
                            <button type="submit" name="update_password" class="submit-btn">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
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

        // Add animation to form inputs
        document.querySelectorAll('.form-group input').forEach(input => {
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