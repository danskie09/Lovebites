<?php
session_start();
require_once 'config/database.php';
require_once 'includes/managerB.php';

// Stricter session checking
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager') {
    header('Location: manager_login.php');
    exit();
}

// Initialize manager functions
$manager = new ManagerFunctions();

// Get dashboard data
$dashboard_data = $manager->getDashboardData();
$revenue_data = $manager->getRevenueData();
$product_summary = $manager->getProductSummary();
$recent_activities = $manager->getRecentActivities();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manager Tikka</title>
    <link rel="stylesheet" href="manager.css">
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
                    <li><a href="manager.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="manage_menu.php"><i class="fas fa-users"></i>Menu's</a></li>
                    <li><a href="sales_report.php"><i class="fas fa-box"></i>Sales Report</a></li>
                    <li><a href="sales_monitoring.php"><i class="fas fa-list"></i>Sales Monitoring</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        <div class="main-content">
        <header>
                <h1>Dashboard Overview</h1>
                <div class="user-info">
                    <?php echo $_SESSION['username']; ?>
                </div>
            </header>
            <div class="dashboard-overview">
                <div class="overview-card">
                    <h3>Total Revenue</h3>
                    <p>₱<?php echo number_format($dashboard_data['total_revenue'], 2); ?></p>
                </div>
                <div class="overview-card">
                    <h3>Today's Orders</h3>
                    <p><?php echo number_format($dashboard_data['total_orders']); ?></p>
                </div>
                <div class="overview-card">
                    <h3>Active Products</h3>
                    <p><?php echo number_format($dashboard_data['total_products']); ?></p>
                </div>
                <div class="overview-card">
                    <h3>Today's Sales</h3>
                    <p>₱<?php echo number_format($dashboard_data['todays_sales'], 2); ?></p>
                </div>
            </div>

            <div class="revenue-cards">
                <div class="revenue-card">
                    <h3>Today's Revenue</h3>
                    <p>₱<?php echo number_format($revenue_data['today'], 2); ?></p>
                </div>
                <div class="revenue-card">
                    <h3>Weekly Revenue</h3>
                    <p>₱<?php echo number_format($revenue_data['weekly'], 2); ?></p>
                </div>
                <div class="revenue-card">
                    <h3>Monthly Revenue</h3>
                    <p>₱<?php echo number_format($revenue_data['monthly'], 2); ?></p>
                </div>
            </div>

            <div class="product-order-summary">
                <h3>Product Order Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Total Orders</th>
                            <th>Total Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($product_summary as $summary): ?>
                        <tr>
                            <td><?php echo $summary['product_name']; ?></td>
                            <td><?php echo $summary['total_orders']; ?></td>
                            <td><?php echo $summary['total_quantity']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>