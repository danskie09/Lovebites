<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';


// Ensure only admin can access
checkAdminAccess();

$db = new Database();
$conn = $db->getConnection();
$staffManager = new StaffManager($conn);

// Fetch staff list
$staffList = $staffManager->getAllStaff();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TikaBites - Manage Staff</title>
    <link rel="stylesheet" href="manage-staff.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .dashboard-container {
            flex: 1;
            display: flex;
        }
        footer.elegant-footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <img src="img/tikalips.png" alt="">
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="manage-staff.php"><i class="fas fa-users"></i> Manage Staff</a></li>
                    <li><a href="manage-products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                    <li><a href="activity-logs.php"><i class="fas fa-list"></i> Activity Logs</a></li>
                    <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <h1>Manage Staff</h1>
                <div class="user-info">
                    <?php echo $_SESSION['username']; ?>
                </div>
            </header>

            <div class="staff-management-container">
                <!-- Staff Actions/Add Staff Form -->
                <div class="staff-actions">
                    <h2>Add New Staff Member</h2>
                    <form class="staff-form" method="POST" action="process-staff.php">
                        <input type="text" name="full_name" placeholder="Full Name" required>
                        <input type="email" name="email" placeholder="Email" required>
                        <input type="text" name="username" placeholder="Username" required>
                        <input type="password" name="password" placeholder="Password" required>
                        <select name="role" required>
                            <option value="">Select Role</option>
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                        <button type="submit" name="addStaff">Add  Member</button>
                    </form>
                </div>

                <!-- Staff List -->
                <div class="staff-list-container">
                    <h2>Current Staff Members</h2>
                    <table class="staff-list-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($staffList as $staff): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($staff['role'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($staff['status'])); ?></td>
                                    <td class="action-buttons">
                                        <a href="edit-staff.php?id=<?php echo $staff['user_id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="process-staff.php?delete_staff=<?php echo $staff['user_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this staff member?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <footer style="
        background: linear-gradient(to right, #f4f4f4, #e9e9e9);
        padding: 2px;
        text-align: center;
        border-top: 2px solid #3a7ca5;
        box-shadow: 0 -4px 6px rgba(0,0,0,0.1);
    ">
            <p style="
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            color: #2c3e50;
            letter-spacing: 1px;
            margin: 0;
            font-weight: 700;
        ">
            Love Bites by Tika
            </p>
            <div style="
            margin-top: 10px;
            font-family: 'Playfair Display', serif;
            font-size: 12px;
            color: #7f8c8d;
            font-style: italic;
        ">
                © 2024 . All Rights Reserved.
            </div>
        </footer>
</body>
</html>