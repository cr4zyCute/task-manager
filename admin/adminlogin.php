<?php
session_start();
include "../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM admins WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION["admin_email"] = $email;
            $_SESSION["admin_id"] = $admin['id'];
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid password!');</script>";
        }
    } else {
        echo "<script>alert('Email not found!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="main-container">
        <div class="left-column">
            <!-- Background image will be handled by CSS -->
            <div class="good-day-admin">Good Day ADMIN</div>
            <div class="clock-container">
                <div class="digital-clock">09:30</div>
                <div class="date">28/05/2025</div>
            </div>
            <div class="progress-task">Progress Task</div>
        </div>
        <div class="right-column">
            <div class="login-section">
                <!-- Placeholder for User Icon - replace with actual path -->
                <img src="assets/images/adminicon.png" alt="User Icon" style="width: 80px; height: 80px;">
                <h2>LOG IN</h2>
            </div>
            <form action="" method="POST" class="form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit">Login</button>
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </form>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.querySelector('.digital-clock').textContent = `${hours}:${minutes}:${seconds}`;
        }

        // Update the clock every second
        setInterval(updateClock, 1000);

        // Initial call to display the time immediately
        updateClock();
    </script>
</body>

</html>