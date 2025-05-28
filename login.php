<?php
session_start();
include "includes/db.php";

$error = ""; // Initialize the error variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Check admin table first
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if (password_verify($password, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = "admin";
            $_SESSION["email"] = $user["email"];
            $_SESSION["name"] = $user["first_name"] . ' ' . $user["last_name"];
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $error = "wrong password";
        }
    } else {
        // Check employees table if not found in admins
        $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["role"] = "employee";
                $_SESSION["email"] = $user["email"];
                $_SESSION["name"] = $user["first_name"] . ' ' . $user["last_name"];
                header("Location: user/dashboard.php");
                exit();
            } else {
                $error = "wrong password";
            }
        } else {
            $error = "wrong email";
        }
    }
}
?>

<style>
    body {
        margin: 0;
        font-family: Arial, sans-serif;
    }

    .container {
        display: flex;
        height: 100vh;
    }

    .left {
        flex: 2;
        background-color: #000;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        padding: 30px;
    }

    .left img {
        max-width: 90%;
        height: auto;
    }

    .left h1 {
        font-size: 28px;
        font-weight: bold;
        margin-top: 20px;
    }

    .right {
        flex: 1;
        background-color: #fff;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .right img {
        width: 80px;
        margin: 0 auto;
    }

    .right h2 {
        text-align: center;
        margin-top: 20px;
        font-size: 24px;
    }

    form {
        margin-top: 30px;
    }

    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 1px solid #999;
        box-sizing: borderQ-box;
        font-size: 16px;
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #ccc;
        color: #000;
        font-weight: bold;
        border: none;
        cursor: pointer;
    }

    button:hover {
        background-color: #aaa;
    }

    .error {
        color: red;
        margin-top: 10px;
        text-align: center;
    }
</style>

<div class="container">
    <div class="left">
        <img src="./assets/images/illustration.png" alt="Task Illustration">

    </div>
    <div class="right">
        <img src="./assets/images/icon.png" alt="Login Icon">
        <h2>LOG IN</h2>
        <form method="POST">
            <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>
            <label>Email</label>
            <input name="email" type="email" placeholder="Email" required>
            <label>Password</label>
            <input name="password" type="password" placeholder="Password" required>
            <button type="submit">LOG IN</button>
        </form>
        <p style="text-align: center; margin-top: 20px;">Haven't registered yet? Click <a href="userRegistration.php">here</a> to register.</p>
    </div>
</div>