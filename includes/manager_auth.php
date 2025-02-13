<?php
require_once 'config/database.php';

class ManagerAccess {
    private $conn;

    public function __construct() {
        try {
            $db = new Database();
            $this->conn = $db->getConnection();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }

    public function checkManagerAccess() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            $this->redirectToLogin();
        }

        if ($_SESSION['role'] !== 'manager') {
            $this->logUnauthorizedAccess();
            $this->redirectToLogin();
        }
    }

    private function redirectToLogin() {
        header("Location: login.php");
        exit();
    }

    private function logUnauthorizedAccess() {
        $user_id = $_SESSION['user_id'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'];
        error_log("Unauthorized access attempt by user_id: $user_id from IP: $ip");
    }

    public function getManagerDashboardStats() {
        try {
            // Today's sales with error handling
            $daily_sales = $this->executeQuery("
                SELECT COALESCE(SUM(total_amount), 0) as daily_sales 
                FROM orders 
                WHERE DATE(order_date) = CURRENT_DATE
            ");

            // Weekly sales with error handling
            $weekly_sales = $this->executeQuery("
                SELECT COALESCE(SUM(total_amount), 0) as weekly_sales 
                FROM orders 
                WHERE YEARWEEK(order_date) = YEARWEEK(CURRENT_DATE)
            ");

            // Monthly sales with error handling
            $monthly_sales = $this->executeQuery("
                SELECT COALESCE(SUM(total_amount), 0) as monthly_sales 
                FROM orders 
                WHERE MONTH(order_date) = MONTH(CURRENT_DATE) 
                AND YEAR(order_date) = YEAR(CURRENT_DATE)
            ");

            return [
                'daily_sales' => $daily_sales,
                'weekly_sales' => $weekly_sales,
                'monthly_sales' => $monthly_sales
            ];
        } catch (PDOException $e) {
            error_log("Error fetching manager stats: " . $e->getMessage());
            return [
                'daily_sales' => 0,
                'weekly_sales' => 0,
                'monthly_sales' => 0
            ];
        }
    }

    private function executeQuery($query) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? array_values($result)[0] : 0;
        } catch (PDOException $e) {
            error_log("Query execution error: " . $e->getMessage());
            return 0;
        }
    }

    public function getInventoryAlerts() {
        try {
            $stmt = $this->conn->prepare("
                SELECT product_id, product_name, stock_qty 
                FROM products 
                WHERE stock_qty <= 10 
                ORDER BY stock_qty ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching inventory alerts: " . $e->getMessage());
            return [];
        }
    }

    public function getStaffPerformance() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.username,
                    COUNT(o.order_id) as total_orders,
                    SUM(o.total_amount) as total_sales
                FROM users u
                LEFT JOIN orders o ON u.user_id = o.staff_id
                WHERE u.role = 'staff'
                AND DATE(o.order_date) = CURRENT_DATE
                GROUP BY u.user_id
                ORDER BY total_sales DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching staff performance: " . $e->getMessage());
            return [];
        }
    }

    public function getPopularItems() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.product_name,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.quantity * oi.price) as revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                JOIN orders o ON oi.order_id = o.order_id
                WHERE DATE(o.order_date) = CURRENT_DATE
                GROUP BY p.product_id
                ORDER BY total_sold DESC
                LIMIT 5
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching popular items: " . $e->getMessage());
            return [];
        }
    }

    public function createDailyReport() {
        try {
            $stats = $this->getManagerDashboardStats();
            $popular_items = $this->getPopularItems();
            $staff_performance = $this->getStaffPerformance();
            $inventory_alerts = $this->getInventoryAlerts();

            $report_data = [
                'date' => date('Y-m-d'),
                'sales_summary' => $stats,
                'popular_items' => $popular_items,
                'staff_performance' => $staff_performance,
                'inventory_alerts' => $inventory_alerts
            ];

            $stmt = $this->conn->prepare("
                INSERT INTO daily_reports 
                (report_date, report_data) 
                VALUES 
                (CURRENT_DATE, :report_data)
            ");

            $stmt->bindValue(':report_data', json_encode($report_data));
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating daily report: " . $e->getMessage());
            return false;
        }
    }

    public function getDailyReport($date = null) {
        try {
            $date = $date ?? date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT * FROM daily_reports 
                WHERE report_date = :date
            ");
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            return $report ? json_decode($report['report_data'], true) : null;
        } catch (PDOException $e) {
            error_log("Error fetching daily report: " . $e->getMessage());
            return null;
        }
    }
}
?>
