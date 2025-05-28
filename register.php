<?php
session_start();
include "includes/db.php";

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_email"])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit'])) {
    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $birthday = $_POST['birthday'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Handle file upload
    $imageName = $_FILES['profile_image']['name'];
    $imageTmp = $_FILES['profile_image']['tmp_name'];
    $imagePath = 'uploads/' . time() . '_' . basename($imageName);
    move_uploaded_file($imageTmp, $imagePath);

    // Check if email already exists in either table
    $check_query = "SELECT email FROM admins WHERE email = ? UNION SELECT email FROM employees WHERE email = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ss", $email, $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already exists!'); window.location.href='register.php';</script>";
    } else {
        // Insert into appropriate table based on role
        if ($role === 'admin') {
            $stmt = $conn->prepare("INSERT INTO admins (first_name, last_name, gender, age, birthday, address, email, password, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        } else {
            $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, gender, age, birthday, address, email, password, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        }

        $stmt->bind_param("sssisssss", $first, $last, $gender, $age, $birthday, $address, $email, $password, $imagePath);

        if ($stmt->execute()) {
            echo "<script>alert('User added successfully!'); window.location.href='admin/dashboard.php';</script>";
        } else {
            echo "<script>alert('Failed to add user!'); window.location.href='register.php';</script>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add New User</title>
    <link rel="stylesheet" href="css/registration.css">
</head>

<body>
    <h1>Add New User</h1>
    <form action="register.php" method="POST" enctype="multipart/form-data">
        <div class="container">
            <div class="left">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" required>
                </div>
                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" name="birthday" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" required>
                </div>
            </div>

            <div class="right">
                <div class="profile-image-container">
                    <div class="profile-image">
                        <img src="assets/images/profile-preview.png" alt="Profile Picture Preview" id="preview">
                        <div class="edit-icon"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Upload Picture</label>
                    <input type="file" name="profile_image" accept="image/*" onchange="previewImage(event)">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>User Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
                <button type="submit" name="submit">Add User</button>
                <p><a href="admin/dashboard.php">Back to Dashboard</a></p>
            </div>
        </div>
    </form>

    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('preview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>

</html>