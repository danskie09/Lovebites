<?php
require_once 'config/database.php';

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Get all active products
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE status = 'active' 
    ORDER BY product_id DESC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch dashboard data
function getDashboardData($conn) {
    $data = [
        'total_revenue' => 0,
        'total_orders' => 0,
        'total_customers' => 0,
        'total_sales' => 0
    ];
    
    // Fetch today's revenue
    $stmt = $conn->prepare("
        SELECT SUM(total_amount) as total 
        FROM sales 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['total_revenue'] = $result['total'] ?? 85500;
    
    // Fetch today's orders
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM sales 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $data['total_orders'] = $result['total'] ?? 1000;
    
    return $data;
}

$dashboard_data = getDashboardData($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="POS.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <a href="POS.php"><i class="fas fa-store"></i></a>
            </div>
            <div class="nav-item"><a href="POS.php"><i class="fas fa-home"></i></a></div>
            <div class="nav-item"><a href="sales.php"><i class="fas fa-chart-bar"></i></a></div>
            <div class="nav-item"><a href="recentTransactions.php"><i class="fas fa-history"></i></a></div>
            <div class="nav-item"><i class="fas fa-cog"></i></div>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search menu...">
                </div>
                <div class="user-info">
                    <div class="user-avatar"></div>
                    <span>Cashier > Elmar</span>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content p-6">
                <!-- Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Revenue Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-gray-500 text-sm">Total Revenue</h3>
                                <p class="text-2xl font-bold">₱<?php echo number_format($dashboard_data['total_revenue']); ?></p>
                                <p class="text-green-500 text-sm">+10.5% from last day</p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-peso-sign text-yellow-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-gray-500 text-sm">Total Orders</h3>
                                <p class="text-2xl font-bold"><?php echo $dashboard_data['total_orders']; ?></p>
                                <p class="text-green-500 text-sm">+10.5% from last day</p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-full">
                                <i class="fas fa-shopping-cart text-purple-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Products Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-gray-500 text-sm">Active Products</h3>
                                <p class="text-2xl font-bold"><?php echo count($products); ?></p>
                                <p class="text-green-500 text-sm">+5.2% from last week</p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-box text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Sales Overview Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Sales Overview</h3>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

                    <!-- Top Products Chart -->
                    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Top Products</h3>
        <div class="chart-container">
            <canvas id="productsChart"></canvas>
        </div>
    </div>
</div>
                <!-- Recent Products Table -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Products</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Product Name</th>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <?php foreach($products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <div class="text-sm leading-5 font-medium text-gray-900">
                                            <?php echo htmlspecialchars($product['product_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <div class="text-sm leading-5 text-gray-900">
                                            ₱<?php echo number_format($product['price'], 2); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <?php echo htmlspecialchars($product['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Reusable chart configuration
    const chartConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                align: 'end'
            }
        }
    };

    // Initialize Sales Chart
    function initializeSalesChart() {
        const salesCtx = document.getElementById('salesChart');
        if (!salesCtx) return;

        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Sales',
                    data: [65000, 59000, 80000, 81000, 56000, 95000, 90000],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => `₱${value.toLocaleString()}`
                        }
                    }
                }
            }
        });
    }

    // Initialize Products Chart
    function initializeProductsChart() {
        const productsCtx = document.getElementById('productsChart');
        if (!productsCtx) return;

        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
                datasets: [{
                    label: 'Sales',
                    data: [120, 90, 85, 70, 65],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Initialize both charts
    initializeSalesChart();
    initializeProductsChart();
});
</script>

    <script src="pos.js"></script>
</body>
</html>