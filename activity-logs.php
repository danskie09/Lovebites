<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure only admin can access
checkAdminAccess();

$db = new Database();
$conn = $db->getConnection();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 15;
$offset = ($page - 1) * $recordsPerPage;

// Function to get activity logs with pagination
function getActivityLogs($conn, $offset, $recordsPerPage) {
    $stmt = $conn->prepare("
        SELECT 
            al.log_id, 
            u.username, 
            al.action, 
            al.details, 
            al.ip_address, 
            al.created_at 
        FROM activty_logs al
        JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT :offset, :records
    ");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':records', $recordsPerPage, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get total number of log entries
function getTotalLogEntries($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM activty_logs");
    $stmt->execute();
    return $stmt->fetchColumn();
}

// Fetch activity logs
$activityLogs = getActivityLogs($conn, $offset, $recordsPerPage);
$totalLogs = getTotalLogEntries($conn);
$totalPages = ceil($totalLogs / $recordsPerPage);

// Function to log user activity
function logUserActivity($conn, $userId, $action, $details, $ipAddress) {
    $stmt = $conn->prepare("
        INSERT INTO activty_logs 
        (user_id, action, details, ip_address, created_at) 
        VALUES 
        (:user_id, :action, :details, :ip_address, NOW())
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip_address', $ipAddress);
    return $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs - TikaBites Admin</title>
    <link rel="stylesheet" href="activity-logs.css">
    <link rel="stylesheet" href="dashboard.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <img src="img/tikalips.png" alt="TikaBites Logo">
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="manage-staff.php"><i class="fas fa-users"></i> Manage Staff</a></li>
                    <li><a href="manage-products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                    <li class="active"><a href="activity-logs.php"><i class="fas fa-list"></i> Activity Logs</a></li>
                    <li><a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header>
                <h1>Activity Logs</h1>
                <div class="user-info">
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </header>

            <div class="activity-logs-container">
                <table class="activity-logs-table">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Username</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($activityLogs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['log_id']); ?></td>
                                <td><?php echo htmlspecialchars($log['username']); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
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