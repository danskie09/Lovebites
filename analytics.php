<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Ensure only admin can access
checkAdminAccess();

$db = new Database();
$conn = $db->getConnection();

// Initialize Analytics Service
$analyticsService = new AnalyticsService($conn);

// Fetch Analytics Data
$topProducts = $analyticsService->getTopSellingProducts();
$monthlySales = $analyticsService->getMonthlySalesTrend();
$customerAcquisition = $analyticsService->getCustomerAcquisition();
$inventoryAnalysis = $analyticsService->getInventoryAnalysis();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TikaBites - Analytics Dashboard</title>
    <link rel="stylesheet" href="dashboard.css"> 
    <link rel="stylesheet" href="analytics.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <h1>Analytics Dashboard</h1>
            <div class="user-info">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </div>
        </header>

        <div class="analytics-grid">
            <div class="analytics-card">
                <h2>Top Selling Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($topProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo $product['total_sold']; ?></td>
                                <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="analytics-card">
                <h2>Monthly Sales Trend</h2>
                <canvas id="salesChart"></canvas>
            </div>

            <div class="analytics-card">
                <h2>Customer Acquisition</h2>
                <canvas id="customerChart"></canvas>
            </div>

            <div class="analytics-card">
                <h2>Inventory Analysis</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Total Products</th>
                            <th>Total Stock</th>
                            <th>Avg Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventoryAnalysis as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo $category['total_products']; ?></td>
                                <td><?php echo $category['total_stock']; ?></td>
                                <td>₱<?php echo number_format($category['avg_price'], 2); ?></td>
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

<script>
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthlySales, 'month')); ?>,
            datasets: [{
                label: 'Monthly Sales',
                data: <?php echo json_encode(array_column($monthlySales, 'total_sales')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        }
    });

    // Customer Acquisition Chart
    const customerCtx = document.getElementById('customerChart').getContext('2d');
    new Chart(customerCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($customerAcquisition, 'month')); ?>,
            datasets: [{
                label: 'New Customers',
                data: <?php echo json_encode(array_column($customerAcquisition, 'new_customers')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            }]
        }
    });
</script>
</body>
</html>