<?php
session_start();
include "../includes/db.php";

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_email"])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch employee data
try {
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed");
    }

    $query = "SELECT id, first_name, last_name, email, gender, age, birthday, address, profile_image, role, created_at FROM employees ORDER BY first_name, last_name";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $employees = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error loading employees: " . $e->getMessage();
    $employees = []; // Ensure $employees is an empty array on error
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Admin</title>
    <link rel="stylesheet" href="css/dashboard.css"> <!-- Reusing existing CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Basic table styling */
        .employee-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .employee-table th,
        .employee-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .employee-table th {
            background-color: #f2f2f2;
        }

        .container {
            padding: 20px;
        }

        .error-message {
            color: red;
            margin-top: 20px;
        }

        /* Modal Styles */
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
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-buttons {
            text-align: right;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 8px 15px;
            margin-left: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background-color: #4CAF50;
            color: white;
        }

        .cancel-btn {
            background-color: #f44336;
            color: white;
        }

        .view-btn {
            background-color: #2196F3;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 5px;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .view-btn:hover {
            background-color: #0b7dda;
        }

        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <!-- Add Modal HTML -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Employee Information</h2>
            <form id="employeeForm">
                <input type="hidden" id="employeeId">
                <div class="form-group">
                    <label for="firstName">First Name:</label>
                    <input type="text" id="firstName" name="firstName" required>
                </div>
                <div class="form-group">
                    <label for="lastName">Last Name:</label>
                    <input type="text" id="lastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="18" max="100">
                </div>
                <div class="form-group">
                    <label for="birthday">Birthday:</label>
                    <input type="date" id="birthday" name="birthday">
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="employee">Employee</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="button" class="save-btn" onclick="saveEmployee()">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard">
        <!-- Sidebar (Optional: could include if you want consistent navigation) -->
        <aside class="sidebar">
            <div class="profile">
                <?php
                // You might want to fetch admin profile image here as well if needed
                $adminProfileImage = '../uploads/adminicon.png'; // Placeholder
                echo '<img src="' . htmlspecialchars($adminProfileImage) . '" alt="Profile Picture" class="profile-image">';
                ?>
            </div>
            <nav>
                <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Main Dashboard</a>
                <a href="employee_dashboard.php" class="nav-link active"><i class="fas fa-users"></i> Users</a>
                <!-- Add other navigation links as needed -->
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <h1>Employee Dashboard</h1>
                <!-- User dropdown can be added here if needed -->
            </header>

            <div class="container">
                <h2>Employee List</h2>

                <?php if (isset($error_message)): ?>
                    <p class="error-message"><?php echo $error_message; ?></p>
                <?php endif; ?>

                <?php if (empty($employees) && !isset($error_message)): ?>
                    <p>No employees found in the database.</p>
                <?php else: ?>
                    <table class="employee-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['role']); ?></td>
                                    <td>
                                        <button class="view-btn" onclick="openModal(<?php echo htmlspecialchars(json_encode($employee)); ?>)">View/Edit</button>
                                        <button class="delete-btn" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Get modal elements
        const modal = document.getElementById('employeeModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        // Close modal when clicking the X
        closeBtn.onclick = function() {
            closeModal();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        function openModal(employee) {
            document.getElementById('employeeId').value = employee.id;
            document.getElementById('firstName').value = employee.first_name;
            document.getElementById('lastName').value = employee.last_name;
            document.getElementById('email').value = employee.email;
            document.getElementById('gender').value = employee.gender || '';
            document.getElementById('age').value = employee.age || '';
            document.getElementById('birthday').value = employee.birthday || '';
            document.getElementById('address').value = employee.address || '';
            document.getElementById('role').value = employee.role || 'employee';
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        function saveEmployee() {
            const employeeId = document.getElementById('employeeId').value;
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const gender = document.getElementById('gender').value;
            const age = document.getElementById('age').value;
            const birthday = document.getElementById('birthday').value;
            const address = document.getElementById('address').value;
            const role = document.getElementById('role').value;

            $.ajax({
                url: 'update_employee.php',
                method: 'POST',
                data: {
                    id: employeeId,
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    gender: gender,
                    age: age,
                    birthday: birthday,
                    address: address,
                    role: role
                },
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success) {
                            closeModal();
                            // Show success message
                            alert('Employee updated successfully');
                            // Force refresh the page
                            setTimeout(function() {
                                window.location.href = window.location.href;
                            }, 100);
                        } else {
                            alert('Error updating employee: ' + data.message);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Error processing server response');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Update error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    alert('Error updating employee. Please try again.');
                }
            });
        }

        function deleteEmployee(employeeId) {
            if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                $.ajax({
                    url: 'delete_employee.php',
                    method: 'POST',
                    data: {
                        id: employeeId
                    },
                    success: function(response) {
                        try {
                            const data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.success) {
                                // Remove the row from the table
                                $(`tr:has(td:first-child:contains('${employeeId}'))`).fadeOut(300, function() {
                                    $(this).remove();
                                });
                                alert('Employee deleted successfully');
                            } else {
                                // Show more detailed error message
                                let errorMessage = data.message || 'Unknown error';
                                if (data.debug_info) {
                                    console.error('Debug info:', data.debug_info);
                                }
                                alert('Error deleting employee: ' + errorMessage);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e, 'Raw response:', response);
                            alert('Error processing server response. Check console for details.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        let errorMessage = 'Error deleting employee. ';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage += response.message || error;
                        } catch (e) {
                            errorMessage += error;
                        }
                        alert(errorMessage);
                    }
                });
            }
        }
    </script>
</body>

</html>