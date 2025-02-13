<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}

// Initialize error message
$error_message = '';

// Verify database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    $error_message = "Database connection failed. Please try again later.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manager Tikka</title>
    
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="manage-products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        <main class="main-content">
            <header>
                <h1>Sales Report</h1>
                <div class="user-info">
                    <?php echo $_SESSION['username']; ?>
                </div>
            </header>


            <div class="dashboard-overview">
                
            <div class="container">
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="report-header">
            <h2>Date Range: <span id="date-range"></span></h2>
            <div class="date-picker">
                <input type="date" id="start_date" name="start_date">
                <input type="date" id="end_date" name="end_date">
                <button onclick="loadSales()">Filter</button>
            </div>
        </div>

        <div class="sales-table">
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Quantity Sold</th>
                        <th>Price per Item (₱)</th>
                        <th>Total Sales (₱)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sales-data">
                    <!-- Sales data will be loaded here -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total Sales</strong></td>
                        <td colspan="2" id="total-sales"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="functions">
            <h3>Functions:</h3>
            <div class="function-buttons">
                <button onclick="generateReport()" class="generate">📊 [Generate Report]</button>
                <button onclick="editReport()" class="edit">✏️ [Edit Report]</button>
                <button onclick="printReport()" class="print">🖨️ [Print Report]</button>
            </div>
        </div>
    </div>

    <script src="assets/js/sales.js"></script>
    <script>
        // Initialize sales manager
        document.addEventListener('DOMContentLoaded', () => {
            if (!document.querySelector('.error-message')) {
                // Initialize only if no errors
                try {
                    const today = new Date();
                    const defaultStart = new Date(today);
                    defaultStart.setDate(today.getDate() - 3);
                    
                    document.getElementById('start_date').value = defaultStart.toISOString().split('T')[0];
                    document.getElementById('end_date').value = today.toISOString().split('T')[0];
                    
                    SalesManager.loadSales(
                        document.getElementById('start_date').value,
                        document.getElementById('end_date').value
                    );

                    // Add event listeners
                    document.querySelector('.date-picker button').addEventListener('click', () => {
                        SalesManager.loadSales(
                            document.getElementById('start_date').value,
                            document.getElementById('end_date').value
                        );
                    });
                } catch (error) {
                    console.error('Initialization error:', error);
                    alert('Error initializing sales report: ' + error.message);
                }
            }
        });
    </script>
            </div>

           

        </div>
    </div>
</body>
</html>