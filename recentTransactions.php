<?php
session_start();
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
$db = new Database();
$conn = $db->getConnection();

// Transaction Class
class Transaction
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTransactions($limit = 50)
    {
        $sql = "SELECT 
                    o.order_id as transaction_id,
                    o.total_amount as amount,
                    o.payment_method as type,
                    GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.product_name) SEPARATOR ', ') as description,
                    o.order_date as transaction_date
                FROM orders o
                INNER JOIN order_items oi ON o.order_id = oi.order_id
                INNER JOIN products p ON oi.product_id = p.product_id
                GROUP BY o.order_id
                ORDER BY o.order_date DESC 
                LIMIT :limit";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Debug output
            if (empty($results)) {
                echo "<!-- No results found -->\n";
            } else {
                echo "<!-- Found " . count($results) . " transactions -->\n";
            }

            return $results;
        } catch (PDOException $e) {
            echo "<!-- Error: " . $e->getMessage() . " -->\n";
            return [];
        }
    }
}

// Initialize Transaction and get data
$transaction = new Transaction($conn);
$transactions = $transaction->getTransactions();

// Debug: Print any empty results
if (empty($transactions)) {
    error_log("No transactions found in the database");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lovebites | Transaction History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="POS.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .transactions-table th,
        .transactions-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .transactions-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .transactions-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .btn-print {
            display: inline-block;
            padding: 6px 12px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
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
                    <input type="text" placeholder="Search transactions...">
                </div>
                <div class="user-info">
                    <div class="user-avatar"></div>
                    <span>Cashier > Elmar</span>
                </div>
            </div>

            <div class="p-6">
                <h1 class="text-2xl font-bold mb-6">Transaction History</h1>

                <div class="bg-white rounded-lg shadow p-6">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Product</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['transaction_id']) ?></td>
                                    <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($transaction['type']) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= htmlspecialchars($transaction['transaction_date']) ?></td>
                                    <td>
                                        <a href="generate_pdf.php?id=<?= $transaction['transaction_id'] ?>"
                                            class="btn-print">
                                            <i class="fas fa-print mr-2"></i> Print
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>