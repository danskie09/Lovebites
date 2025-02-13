<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure only admin can access
checkAdminAccess();

$db = new Database();
$conn = $db->getConnection();

// Fetch dashboard statistics
$stats = [
    'total_admin_users' => getTotalUsers($conn),
    'total_customers' => getTotalCustomers($conn),
    'total_products' => getTotalProducts($conn),
    'total_orders' => getTotalOrders($conn),
    'total_sales' => getTotalSales($conn),
    'recent_activity' => getRecentActivities($conn),
    'monthly_revenue' => getMonthlyRevenue($conn),
    'weekly_revenue' => getWeeklyRevenue($conn),
    'today_revenue' => getTodayRevenue($conn),
    'order_summary' => getOrderSummary($conn)
];

function getTotalUsers($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTotalCustomers($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM customers");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTotalProducts($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTotalOrders($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getMonthlyRevenue($conn) {
    $stmt = $conn->prepare("
        SELECT 
            MONTH(transaction_date) as month, 
            SUM(total_amount) as revenue 
        FROM trabsactions 
        WHERE YEAR(transaction_date) = YEAR(CURRENT_DATE)
        GROUP BY MONTH(transaction_date)
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getWeeklyRevenue($conn) {
    $stmt = $conn->prepare("
        SELECT 
            SUM(total_amount) as revenue 
        FROM trabsactions 
        WHERE transaction_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getTodayRevenue($conn) {
    $stmt = $conn->prepare("
        SELECT 
            SUM(total_amount) as revenue 
        FROM trabsactions 
        WHERE DATE(transaction_date) = CURRENT_DATE
    ");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getOrderSummary($conn) {
    $stmt = $conn->prepare("
        SELECT 
            p.product_name, 
            COUNT(o.product_id) as total_orders,
            SUM(o.quantity) as total_quantity
        FROM orders o
        JOIN products p ON o.product_id = p.product_id
        GROUP BY p.product_id, p.product_name
        ORDER BY total_orders DESC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentActivities($conn) {
    $stmt = $conn->prepare("
        SELECT u.username, al.action, al.created_at 
        FROM activty_logs al
        JOIN users u ON al.user_id = u.user_id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TikaBites ADMINSITE</title>
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
                <h1>Dashboard Overview</h1>
                <div class="user-info">
                    <?php echo $_SESSION['username']; ?>
                </div>
            </header>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Users</h3>
                    <p><?php echo $stats['total_admin_users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Customers</h3>
                    <p><?php echo $stats['total_customers']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Products</h3>
                    <p><?php echo $stats['total_products']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Sales</h3>
                    <p>₱<?php 
                        $totalSales = array_sum(array_column($stats['total_sales'], 'revenue'));
                        echo number_format($totalSales, 2); 
                    ?></p>
                </div>
            </div>

            <div class="revenue-container">
                <div class="revenue-card">
                    <h3>Today's Revenue</h3>
                    <p>₱<?php echo number_format($stats['today_revenue'], 2); ?></p>
                </div>
                <div class="revenue-card">
                    <h3>Weekly Revenue</h3>
                    <p>₱<?php echo number_format($stats['weekly_revenue'], 2); ?></p>
                </div>
                <div class="revenue-card">
                    <h3>Monthly Revenue</h3>
                    <p>₱<?php 
                        $monthlyRevenue = array_sum(array_column($stats['monthly_revenue'], 'revenue'));
                        echo number_format($monthlyRevenue, 2); 
                    ?></p>
                </div>
            </div>

            <div class="order-summary">
                <h2>Product Order Summary</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Total Orders</th>
                            <th>Total Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats['order_summary'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo $product['total_orders']; ?></td>
                                <td><?php echo $product['total_quantity']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <br>

            <div class="recent-activity">
                <h2>Recent Activities</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats['recent_activity'] as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                <td><?php echo $activity['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
