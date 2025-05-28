<?php
session_start();
include "../includes/db.php";

if (!isset($_SESSION["admin_email"])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch admin data
$email = $_SESSION["admin_email"];
$stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Debug information
error_reporting(0);
ini_set('display_errors', 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="profile">
                <?php
                $profileImage = !empty($admin['profile_image']) ? '../uploads/' . $admin['profile_image'] : '../uploads/adminicon.png';
                echo '<img src="' . htmlspecialchars($profileImage) . '" alt="Profile Picture" class="profile-image" onerror="this.src=\'../uploads/adminicon.png\'">';
                ?>
            </div>
            <nav>
                <a href="#" class="nav-link active" data-tab="dashboard"><i class="fas fa-home"></i> Dashboard</a>
                <a href="employee_dashboard.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                <a href="#" class="nav-link" data-tab="tasks"><i class="fas fa-tasks"></i> Tasks</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <h1>WELCOME ADMIN!</h1>
                <div class="dropdown">
                    <button class="dropbtn" style="padding: 0; border: none; background: none;">
                        <?php
                        $headerImage = !empty($admin['profile_image']) ? '../uploads/' . $admin['profile_image'] : '../uploads/adminicon.png';
                        echo '<img src="' . htmlspecialchars($headerImage) . '" alt="Profile Picture" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; outline: 2px solid #4A90E2; outline-offset: 2px;" onerror="this.src=\'../uploads/adminicon.png\'">';
                        ?>
                    </button>
                    <div class="dropdown-content">
                        <a href="#"><i class="fas fa-user"></i> Profile</a>
                        <a href="#"><i class="fas fa-cog"></i> Settings</a>
                        <a href="adminlogout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
                    </div>
                </div>
            </header>

            <div id="dashboard" class="tab-content active">
                <section class="content">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Total Employees</h3>
                                <p class="stat-number" id="totalEmployees">
                                    <span class="loading-spinner"></span>
                                </p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Total Tasks</h3>
                                <p class="stat-number" id="totalTasks">
                                    <span class="loading-spinner"></span>
                                </p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Completed Tasks</h3>
                                <p class="stat-number" id="completedTasks">
                                    <span class="loading-spinner"></span>
                                </p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Pending Tasks</h3>
                                <p class="stat-number" id="pendingTasks">
                                    <span class="loading-spinner"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-charts">
                        <div class="chart-container">
                            <h3>Task Completion Rate</h3>
                            <div class="chart-wrapper">
                                <canvas id="taskCompletionChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-container">
                            <h3>Tasks by Status</h3>
                            <div class="chart-wrapper">
                                <canvas id="taskStatusChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="employee-performance">
                        <h3>Employee Performance Overview</h3>
                        <div class="table-responsive">
                            <table class="performance-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Total Tasks</th>
                                        <th>Completed</th>
                                        <th>In Progress</th>
                                        <th>Pending</th>
                                        <th>Completion Rate</th>
                                    </tr>
                                </thead>
                                <tbody id="employeePerformance">
                                    <!-- Employee performance data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="calendar-section">
                        <h3>Employee Work Calendar</h3>
                        <div id="calendar"></div>
                    </div>

                </section>
            </div>

            <div id="users" class="tab-content">
                <h2>Employee List</h2>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody id="employeeList">
                            <!-- Employees will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="tasks" class="tab-content">
                <h2>Task Management</h2>
                <div class="task-management">
                    <!-- Create Task Form -->
                    <div class="task-form">
                        <h3>Create New Task</h3>
                        <form id="createTaskForm">
                            <div class="form-group">
                                <label for="title">Task Title</label>
                                <input type="text" id="title" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="assigned_to">Assign To</label>
                                <select id="assigned_to" name="assigned_to" required>
                                    <option value="">Select Employee</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="due_date">Due Date</label>
                                <input type="date" id="due_date" name="due_date" required>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="is_weekly" name="is_weekly">
                                    This is a weekly task
                                </label>
                            </div>
                            <button type="submit" class="btn-primary">Create Task</button>
                        </form>
                    </div>

                    <!-- Task List -->
                    <div class="task-list">
                        <h3>Task List</h3>
                        <div class="search-container">
                            <input type="text" id="taskSearch" placeholder="Search by employee name..." class="search-input">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Assigned To</th>
                                        <th>Due Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="taskList">
                                    <!-- Tasks will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab switching functionality
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Skip event handling for links that have an href to a different page
                if (this.getAttribute('href') && this.getAttribute('href') !== '#') {
                    return true; // Allow normal link behavior
                }

                e.preventDefault();

                const tabId = this.getAttribute('data-tab');
                const tabContent = document.getElementById(tabId);

                // Only proceed if the clicked tab is not already active
                if (!this.classList.contains('active')) {
                    // Remove active class from all links and tabs
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));

                    // Add active class to clicked link and show corresponding tab
                    this.classList.add('active');
                    if (tabContent) {
                        tabContent.classList.add('active');
                    }

                    // If tasks tab is clicked, load employees and tasks
                    if (tabId === 'tasks') {
                        loadEmployees();
                        loadTasks();
                    }
                    // If dashboard tab is clicked, load dashboard stats
                    if (tabId === 'dashboard') {
                        loadDashboardStats();
                    }
                }
            });
        });

        // Logout functionality
        document.querySelector('.dropdown-content a[href="adminlogout.php"]').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'adminlogout.php';
        });

        // Add this to your existing JavaScript
        function loadEmployees() {
            console.log('Loading employees...');
            const employeeList = document.getElementById('employeeList');
            const assignedToSelect = document.getElementById('assigned_to');

            if (employeeList) {
                employeeList.innerHTML = '<tr><td colspan="5" class="text-center">Loading employees...</td></tr>';
            }
            if (assignedToSelect) {
                assignedToSelect.innerHTML = '';
                assignedToSelect.innerHTML += '<option value="">Select Employee</option>';
            }

            fetch('../api/employees/read.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received employee data:', data);
                    if (data.status === 'success' && Array.isArray(data.records)) {
                        // Update employee list table
                        if (employeeList) {
                            employeeList.innerHTML = '';
                            const uniqueEmployeesList = new Map();
                            data.records.forEach(employee => {
                                console.log('Processing employee for list:', employee.id, employee.first_name, employee.last_name);
                                if (!uniqueEmployeesList.has(employee.id)) {
                                    uniqueEmployeesList.set(employee.id, employee);
                                    console.log('Adding employee to list:', employee.id, employee.first_name, employee.last_name);
                                    employeeList.innerHTML += `
                                        <tr>
                                            <td>${employee.id}</td>
                                            <td>${employee.first_name}</td>
                                            <td>${employee.last_name}</td>
                                            <td>${employee.email || 'N/A'}</td>
                                            <td>${employee.role || 'N/A'}</td>
                                        </tr>
                                    `;
                                }
                            });
                        }

                        // Update task assignment dropdown
                        if (assignedToSelect) {
                            const uniqueEmployeesSelect = new Map();
                            data.records.forEach(employee => {
                                console.log('Processing employee for select:', employee.id, employee.first_name, employee.last_name);
                                if (!uniqueEmployeesSelect.has(employee.id)) {
                                    uniqueEmployeesSelect.set(employee.id, employee);
                                    console.log('Adding employee to select:', employee.id, employee.first_name, employee.last_name);
                                    assignedToSelect.innerHTML += `
                                        <option value="${employee.id}">${employee.first_name} ${employee.last_name}</option>
                                    `;
                                }
                            });
                        }
                    } else {
                        throw new Error(data.message || 'Invalid data format received');
                    }
                })
                .catch(error => {
                    console.error('Error loading employees:', error);
                    if (employeeList) {
                        employeeList.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading employees</td></tr>';
                    }
                    if (assignedToSelect) {
                        assignedToSelect.innerHTML = '<option value="">Error loading employees</option>';
                    }
                });
        }

        function loadTasks() {
            fetch('../api/tasks/read.php')
                .then(response => response.json())
                .then(data => {
                    const taskList = document.getElementById('taskList');
                    taskList.innerHTML = '';
                    data.records.forEach(task => {
                        taskList.innerHTML += `
                            <tr>
                                <td>${task.title}</td>
                                <td>${task.description || ''}</td>
                                <td>${task.assigned_to_name || 'Unassigned'}</td>
                                <td>
                                    <div class="due-date-cell ${getDueDateClass(task.due_date, task.status)}">
                                        <i class="far fa-calendar-alt"></i>
                                        ${formatDate(task.due_date)}
                                        ${task.status !== 'completed' ? getDueDateCountdown(task.due_date) : ''}
                                    </div>
                                </td>
                                <td>
                                    <span class="task-type ${task.is_weekly ? 'weekly' : 'regular'}">
                                        ${task.is_weekly ? 'Weekly' : 'Regular'}
                                    </span>
                                </td>
                                <td>
                                    <select class="status-select" data-task-id="${task.id}" onchange="updateTaskStatus(this)">
                                        <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>Pending</option>
                                        <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                        <option value="completed" ${task.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn-danger delete-task" data-task-id="${task.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                    });

                    // Store tasks data for filtering
                    window.tasksData = data.records;

                    // Add event listeners for delete buttons
                    document.querySelectorAll('.delete-task').forEach(button => {
                        button.addEventListener('click', function() {
                            const taskId = this.getAttribute('data-task-id');
                            deleteTask(taskId);
                        });
                    });
                })
                .catch(error => {
                    console.error('Error loading tasks:', error);
                    alert('Error loading tasks. Please try again.');
                });
        }

        function updateTaskStatus(selectElement) {
            const taskId = selectElement.getAttribute('data-task-id');
            const newStatus = selectElement.value;

            // Show loading state
            selectElement.disabled = true;
            const originalValue = selectElement.value;

            fetch('../api/tasks/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: taskId,
                        status: newStatus
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        // Update the task in the stored data
                        if (window.tasksData) {
                            const taskIndex = window.tasksData.findIndex(task => task.id === taskId);
                            if (taskIndex !== -1) {
                                window.tasksData[taskIndex].status = newStatus;
                            }
                        }

                        // Show success message
                        const successMessage = document.createElement('div');
                        successMessage.className = 'success-message';
                        successMessage.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        <span>Task status updated successfully</span>
                    `;
                        document.querySelector('.task-list').insertBefore(successMessage, document.querySelector('.search-container'));

                        // Remove success message after 3 seconds
                        setTimeout(() => {
                            successMessage.style.opacity = '0';
                            setTimeout(() => successMessage.remove(), 300);
                        }, 3000);

                        // Refresh dashboard stats
                        loadDashboardStats();
                    } else {
                        throw new Error(data.message || 'Failed to update task status');
                    }
                })
                .catch(error => {
                    console.error('Error updating task status:', error);
                    // Revert the select element to its original value
                    selectElement.value = originalValue;
                    alert('Error updating task status: ' + error.message);
                })
                .finally(() => {
                    // Re-enable the select element
                    selectElement.disabled = false;
                });
        }

        function deleteTask(taskId) {
            if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
                fetch('../api/tasks/delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: taskId
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.message || 'Failed to delete task');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            // Remove the task from the stored data
                            if (window.tasksData) {
                                window.tasksData = window.tasksData.filter(task => task.id !== taskId);
                            }

                            // Remove the task row with animation
                            const taskRow = document.querySelector(`tr:has(button[data-task-id="${taskId}"])`);
                            if (taskRow) {
                                taskRow.style.transition = 'opacity 0.3s ease';
                                taskRow.style.opacity = '0';
                                setTimeout(() => {
                                    taskRow.remove();

                                    // If no tasks left, show no results message
                                    const taskList = document.getElementById('taskList');
                                    if (taskList.children.length === 0) {
                                        taskList.innerHTML = `
                                        <tr>
                                            <td colspan="7" class="no-results">
                                                <div class="no-results-message">
                                                    <i class="fas fa-search"></i>
                                                    <p>No tasks found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                                    }
                                }, 300);
                            }

                            // Show success message
                            const successMessage = document.createElement('div');
                            successMessage.className = 'success-message';
                            successMessage.innerHTML = `
                            <i class="fas fa-check-circle"></i>
                            <span>${data.message}</span>
                        `;
                            document.querySelector('.task-list').insertBefore(successMessage, document.querySelector('.search-container'));

                            // Remove success message after 3 seconds
                            setTimeout(() => {
                                successMessage.style.opacity = '0';
                                setTimeout(() => successMessage.remove(), 300);
                            }, 3000);

                            // Refresh dashboard stats
                            loadDashboardStats();
                        } else {
                            throw new Error(data.message || 'Failed to delete task');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting task:', error);
                        alert('Error deleting task: ' + error.message);
                    });
            }
        }

        // Initialize task creation form
        document.addEventListener('DOMContentLoaded', function() {
            const createTaskForm = document.getElementById('createTaskForm');

            // Handle form submission
            createTaskForm.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Submitting task form...');

                const assignedTo = document.getElementById('assigned_to').value;
                if (!assignedTo) {
                    alert('Please select an employee to assign the task to');
                    return;
                }

                const taskData = {
                    title: document.getElementById('title').value.trim(),
                    description: document.getElementById('description').value.trim(),
                    assigned_to: assignedTo,
                    due_date: document.getElementById('due_date').value,
                    is_weekly: document.getElementById('is_weekly').checked,
                    status: 'pending'
                };

                console.log('Task data:', taskData);

                fetch('../api/tasks/create.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(taskData)
                    })
                    .then(response => {
                        console.log('Create task response:', response);
                        if (!response.ok) {
                            return response.text().then(text => {
                                try {
                                    // Try to parse as JSON first
                                    const jsonError = JSON.parse(text);
                                    throw new Error(jsonError.message || 'Failed to create task');
                                } catch (e) {
                                    // If not JSON, use the raw text
                                    throw new Error(`Server error: ${text}`);
                                }
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Create task data:', data);
                        if (data.status === 'success') {
                            alert('Task created successfully!');
                            createTaskForm.reset();
                            loadTasks(); // Refresh task list
                            loadDashboardStats(); // Refresh dashboard stats
                        } else {
                            throw new Error(data.message || 'Failed to create task');
                        }
                    })
                    .catch(error => {
                        console.error('Error creating task:', error);
                        alert('Error creating task: ' + error.message);
                    });
            });
        });

        // Add these helper functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function getDueDateClass(dueDate, status) {
            if (status === 'completed') return 'completed';

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const due = new Date(dueDate);
            due.setHours(0, 0, 0, 0);
            const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));

            if (diffDays < 0) return 'overdue';
            if (diffDays <= 2) return 'urgent';
            return '';
        }

        function getDueDateCountdown(dueDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const due = new Date(dueDate);
            due.setHours(0, 0, 0, 0);
            const diffDays = Math.ceil((due - today) / (1000 * 60 * 60 * 24));

            if (diffDays < 0) {
                return `<span class="due-date-countdown">Overdue by ${Math.abs(diffDays)} day${Math.abs(diffDays) !== 1 ? 's' : ''}</span>`;
            } else if (diffDays === 0) {
                return '<span class="due-date-countdown">Due today</span>';
            } else if (diffDays === 1) {
                return '<span class="due-date-countdown">Due tomorrow</span>';
            } else {
                return `<span class="due-date-countdown">in ${diffDays} days</span>`;
            }
        }

        // Add this after your existing JavaScript code
        let taskCompletionChart = null;
        let taskStatusChart = null;

        function loadDashboardStats() {
            console.log('Loading dashboard stats...');

            // Load employee count
            fetch('../api/users/count.php')
                .then(response => {
                    console.log('Employee count response:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Employee count data:', data);
                    if (data.status === 'success') {
                        document.getElementById('totalEmployees').textContent = data.count;
                    } else {
                        throw new Error(data.message || 'Failed to load employee count');
                    }
                })
                .catch(error => {
                    console.error('Error loading employee count:', error);
                    document.getElementById('totalEmployees').innerHTML = '<span class="error-text">Error loading data</span>';
                });

            // Load task statistics
            fetch('../api/tasks/stats.php')
                .then(response => {
                    console.log('Task stats response:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Task stats data:', data);
                    if (data.status === 'success') {
                        document.getElementById('totalTasks').textContent = data.total;
                        document.getElementById('completedTasks').textContent = data.completed;
                        document.getElementById('pendingTasks').textContent = data.pending;

                        // Update charts
                        updateTaskCompletionChart(data);
                        updateTaskStatusChart(data);
                    } else {
                        throw new Error(data.message || 'Failed to load task statistics');
                    }
                })
                .catch(error => {
                    console.error('Error loading task stats:', error);
                    document.getElementById('totalTasks').innerHTML = '<span class="error-text">Error loading data</span>';
                    document.getElementById('completedTasks').innerHTML = '<span class="error-text">Error loading data</span>';
                    document.getElementById('pendingTasks').innerHTML = '<span class="error-text">Error loading data</span>';
                });

            // Load employee performance data
            loadEmployeePerformance();

            // Load recent activity
            loadRecentActivity();
        }

        function updateTaskCompletionChart(data) {
            const ctx = document.getElementById('taskCompletionChart').getContext('2d');

            if (taskCompletionChart) {
                taskCompletionChart.destroy();
            }

            taskCompletionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.completion_labels || [],
                    datasets: [{
                        label: 'Task Completion Rate',
                        data: data.completion_data || [],
                        borderColor: '#4A90E2',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        function updateTaskStatusChart(data) {
            const ctx = document.getElementById('taskStatusChart').getContext('2d');

            if (taskStatusChart) {
                taskStatusChart.destroy();
            }

            taskStatusChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending', 'In Progress'],
                    datasets: [{
                        data: [data.completed, data.pending, data.in_progress],
                        backgroundColor: ['#28a745', '#ffc107', '#17a2b8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        function loadEmployeePerformance() {
            fetch('../api/employees/performance.php')
                .then(response => response.json())
                .then(data => {
                    const performanceTable = document.getElementById('employeePerformance');
                    performanceTable.innerHTML = '';

                    if (data.status === 'success' && data.employees) {
                        data.employees.forEach(employee => {
                            const completionRate = employee.total_tasks > 0 ?
                                Math.round((employee.completed_tasks / employee.total_tasks) * 100) :
                                0;

                            performanceTable.innerHTML += `
                                <tr>
                                    <td>
                                        <div class="employee-info">
                                            <img src="${employee.profile_image || '../uploads/default-avatar.png'}" 
                                                 alt="${employee.first_name}" 
                                                 class="employee-avatar">
                                            <span>${employee.first_name} ${employee.last_name}</span>
                                        </div>
                                    </td>
                                    <td>${employee.total_tasks}</td>
                                    <td>${employee.completed_tasks}</td>
                                    <td>${employee.in_progress_tasks}</td>
                                    <td>${employee.pending_tasks}</td>
                                    <td>
                                        <div class="completion-rate">
                                            <div class="progress-bar">
                                                <div class="progress" style="width: ${completionRate}%"></div>
                                            </div>
                                            <span>${completionRate}%</span>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        performanceTable.innerHTML = `
                            <tr>
                                <td colspan="6" class="no-data">No employee performance data available</td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading employee performance:', error);
                    document.getElementById('employeePerformance').innerHTML = `
                        <tr>
                            <td colspan="6" class="error-text">Error loading employee performance data</td>
                        </tr>
                    `;
                });
        }

        function loadRecentActivity() {
            console.log('Loading recent activity...');
            fetch('../api/activity/recent.php')
                .then(response => {
                    console.log('Activity response:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Activity data:', data);
                    if (data.status === 'success') {
                        const activityList = document.getElementById('recentActivity');
                        activityList.innerHTML = '';

                        if (data.activities && data.activities.length > 0) {
                            data.activities.forEach(activity => {
                                activityList.innerHTML += `
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas ${getActivityIcon(activity.type)}"></i>
                                        </div>
                                        <div class="activity-details">
                                            <p>${activity.description}</p>
                                            <small>${formatTimeAgo(activity.timestamp)}</small>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            activityList.innerHTML = '<p class="no-data">No recent activity</p>';
                        }
                    } else {
                        throw new Error(data.message || 'Failed to load recent activity');
                    }
                })
                .catch(error => {
                    console.error('Error loading recent activity:', error);
                    document.getElementById('recentActivity').innerHTML = '<p class="error-text">Error loading activity data</p>';
                });
        }

        function getActivityIcon(type) {
            const icons = {
                'task_created': 'fa-plus-circle',
                'task_completed': 'fa-check-circle',
                'task_updated': 'fa-edit',
                'user_registered': 'fa-user-plus',
                'default': 'fa-info-circle'
            };
            return icons[type] || icons.default;
        }

        function formatTimeAgo(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            if (seconds < 60) return 'just now';
            if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
            if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
            return date.toLocaleDateString();
        }

        // Initialize FullCalendar
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: '../api/tasks/calendar.php',
                    eventClick: function(info) {
                        alert('Task: ' + info.event.title + '\nAssigned to: ' + info.event.extendedProps.assigned_to_name + '\nStatus: ' + info.event.extendedProps.status);
                    },
                    eventDidMount: function(info) {
                        info.el.classList.add(info.event.extendedProps.status);

                        const eventContent = info.el.querySelector('.fc-event-title');
                        if (eventContent) {
                            const profileImage = info.event.extendedProps.profile_image || '../uploads/default-avatar.png';
                            const assignedEmployee = info.event.extendedProps.assigned_to_name || 'Unassigned';

                            // Create a container for the employee info
                            const employeeContainer = document.createElement('div');
                            employeeContainer.className = 'employee-task-container';

                            // Add profile image
                            const imgElement = document.createElement('img');
                            imgElement.src = profileImage;
                            imgElement.classList.add('event-profile-image');
                            imgElement.alt = assignedEmployee;

                            // Add employee name
                            const nameSpan = document.createElement('span');
                            nameSpan.classList.add('event-employee-name');
                            nameSpan.textContent = assignedEmployee;

                            // Add task title
                            const taskTitle = document.createElement('div');
                            taskTitle.classList.add('event-task-title');
                            taskTitle.textContent = info.event.title;

                            // Add task status
                            const taskStatus = document.createElement('div');
                            taskStatus.classList.add('event-task-status');
                            taskStatus.textContent = info.event.extendedProps.status.charAt(0).toUpperCase() +
                                info.event.extendedProps.status.slice(1);

                            // Assemble the event content
                            employeeContainer.appendChild(imgElement);
                            employeeContainer.appendChild(nameSpan);
                            employeeContainer.appendChild(taskTitle);
                            employeeContainer.appendChild(taskStatus);

                            // Clear and add the new content
                            eventContent.innerHTML = '';
                            eventContent.appendChild(employeeContainer);
                        }
                    }
                });
                calendar.render();
            }

            // Initialize dashboard
            loadDashboardStats();
            // Refresh stats every 5 minutes
            setInterval(loadDashboardStats, 300000);
        });

        // Add search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('taskSearch');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                // Clear any existing timeout
                clearTimeout(searchTimeout);

                // Set a new timeout to execute the search after 300ms of no typing
                searchTimeout = setTimeout(() => {
                    const searchTerm = this.value.toLowerCase().trim();
                    const taskList = document.getElementById('taskList');
                    const tasks = window.tasksData || [];

                    if (!tasks.length) return;

                    const filteredTasks = tasks.filter(task => {
                        const employeeName = (task.assigned_to_name || 'Unassigned').toLowerCase();
                        return employeeName.includes(searchTerm);
                    });

                    // Update the table with filtered results
                    updateTaskTable(filteredTasks);
                }, 300); // 300ms delay
            });

            // Function to update the task table
            function updateTaskTable(tasks) {
                const taskList = document.getElementById('taskList');
                taskList.innerHTML = '';

                if (tasks.length === 0) {
                    taskList.innerHTML = `
                        <tr>
                            <td colspan="7" class="no-results">
                                <div class="no-results-message">
                                    <i class="fas fa-search"></i>
                                    <p>No tasks found for this employee</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    return;
                }

                tasks.forEach(task => {
                    taskList.innerHTML += `
                        <tr>
                            <td>${task.title}</td>
                            <td>${task.description || ''}</td>
                            <td>${task.assigned_to_name || 'Unassigned'}</td>
                            <td>
                                <div class="due-date-cell ${getDueDateClass(task.due_date, task.status)}">
                                    <i class="far fa-calendar-alt"></i>
                                    ${formatDate(task.due_date)}
                                    ${task.status !== 'completed' ? getDueDateCountdown(task.due_date) : ''}
                                </div>
                            </td>
                            <td>
                                <span class="task-type ${task.is_weekly ? 'weekly' : 'regular'}">
                                    ${task.is_weekly ? 'Weekly' : 'Regular'}
                                </span>
                            </td>
                            <td>
                                <select class="status-select" data-task-id="${task.id}">
                                    <option value="pending" ${task.status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="in_progress" ${task.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="completed" ${task.status === 'completed' ? 'selected' : ''}>Completed</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn-danger delete-task" data-task-id="${task.id}">Delete</button>
                            </td>
                        </tr>
                    `;
                });

                // Reattach event listeners for delete buttons
                document.querySelectorAll('.delete-task').forEach(button => {
                    button.addEventListener('click', function() {
                        const taskId = this.getAttribute('data-task-id');
                        deleteTask(taskId);
                    });
                });
            }
        });
    </script>

    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f6fa;
        }

        /* Dashboard layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
        }

        .profile {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
        }

        .nav-link {
            display: flex;
            align-items: center;
            color: #ecf0f1;
            text-decoration: none;
            padding: 12px 15px;
            margin: 5px 0;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover,
        .nav-link.active {
            background-color: #34495e;
        }

        /* Main content styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
        }

        .header h1 {
            margin: 0;
        }

        .dropdown {
            position: relative;
        }

        .dropbtn {
            padding: 0;
            border: none;
            background: none;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .tab-content {
            display: none;
            padding: 20px;
        }

        .tab-content.active {
            display: block;
        }

        .task-management {
            padding: 20px;
        }

        .task-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .btn-primary {
            background: #4A90E2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #357ABD;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .status-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .status-select:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-danger:active {
            background-color: #bd2130;
        }

        .task-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .task-type.weekly {
            background: #e3f2fd;
            color: #1976d2;
        }

        .task-type.regular {
            background: #f5f5f5;
            color: #616161;
        }

        .due-date-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .due-date-cell i {
            color: #4A90E2;
        }

        .due-date-cell.urgent {
            background: #fff3cd;
            color: #856404;
        }

        .due-date-cell.overdue {
            background: #f8d7da;
            color: #721c24;
        }

        .due-date-cell.completed {
            background: #d4edda;
            color: #155724;
        }

        .due-date-countdown {
            font-size: 0.9em;
            margin-left: 4px;
        }

        .nav-link {
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .nav-link.active {
            background-color: #4A90E2;
            color: white;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #4A90E2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        .stat-number {
            margin: 5px 0 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .dashboard-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chart-container h3 {
            margin: 0 0 20px;
            color: #333;
        }

        .activity-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4A90E2;
        }

        .activity-details p {
            margin: 0;
            color: #333;
        }

        .activity-details small {
            color: #666;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4A90E2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .calendar-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        #calendar {
            margin-top: 20px;
        }

        .fc-event {
            cursor: pointer;
        }

        .fc-event-title {
            font-weight: 500;
        }

        .fc-event.pending {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .fc-event.completed {
            background-color: #28a745;
            border-color: #28a745;
        }

        .fc-event.in-progress {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .error-text {
            color: #dc3545;
            font-size: 0.9em;
        }

        .no-data {
            color: #6c757d;
            text-align: center;
            padding: 20px;
        }

        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
            cursor: pointer;
        }

        .form-group select:focus {
            border-color: #4A90E2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .form-group select option {
            padding: 8px;
        }

        /* Employee Performance Styles */
        .employee-performance {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .employee-performance h3 {
            margin: 0 0 20px;
            color: #333;
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .performance-table th,
        .performance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .performance-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .employee-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .employee-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .completion-rate {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: #4A90E2;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .completion-rate span {
            min-width: 45px;
            text-align: right;
            font-weight: 500;
            color: #333;
        }

        .performance-table tr:hover {
            background-color: #f8f9fa;
        }

        .performance-table .no-data,
        .performance-table .error-text {
            text-align: center;
            padding: 20px;
        }

        /* Calendar Event Styling for Employee Profile */
        .fc-event-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 4px;
        }

        .employee-task-container {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .event-profile-image {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
        }

        .event-employee-name {
            font-size: 0.85em;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .event-task-title {
            font-size: 0.8em;
            color: #fff;
            opacity: 0.9;
            margin-top: 2px;
        }

        .event-task-status {
            font-size: 0.75em;
            color: #fff;
            opacity: 0.8;
            padding: 2px 4px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.2);
            display: inline-block;
        }

        .fc-event {
            padding: 4px;
            border-radius: 4px;
            margin-bottom: 2px;
        }

        .fc-event.pending {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .fc-event.completed {
            background-color: #28a745;
            border-color: #28a745;
        }

        .fc-event.in-progress {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .fc-daygrid-event {
            margin: 1px 0;
        }

        .fc-daygrid-day-events {
            padding: 2px;
        }

        .search-container {
            position: relative;
            margin-bottom: 20px;
            max-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #4A90E2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            pointer-events: none;
        }

        .no-results {
            text-align: center;
            padding: 30px !important;
        }

        .no-results-message {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #666;
        }

        .no-results-message i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #999;
        }

        .no-results-message p {
            margin: 0;
            font-size: 14px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 1;
            transition: opacity 0.3s ease;
        }

        .success-message i {
            font-size: 18px;
        }

        .success-message span {
            font-size: 14px;
        }

        tr {
            transition: opacity 0.3s ease;
        }
    </style>
</body>

</html>