<?php
require_once 'config/database.php';

class ManagerFunctions {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function getDashboardData() {
        try {
            $data = [
                'total_revenue' => $this->getTotalRevenue(),
                'total_orders' => $this->getTotalOrders(),
                'total_products' => $this->getTotalProducts(),
                'todays_sales' => $this->getTodaysSales()
            ];
            return $data;
        } catch (PDOException $e) {
            error_log("Dashboard Data Error: " . $e->getMessage());
            return [
                'total_revenue' => 0,
                'total_orders' => 0,
                'total_products' => 0,
                'todays_sales' => 0
            ];
        }
    }

    private function getTotalRevenue() {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM orders 
            WHERE status = 'completed'
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    private function getTotalOrders() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM orders
            WHERE DATE(order_date) = CURRENT_DATE
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    }

    private function getTotalProducts() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count
            FROM products
            WHERE status = 'active'
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    }

    private function getTodaysSales() {
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM orders
            WHERE DATE(order_date) = CURRENT_DATE
            AND status = 'completed'
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    public function getRevenueData() {
        try {
            $data = [
                'today' => 0,
                'weekly' => 0,
                'monthly' => 0
            ];

            // Today's revenue
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as revenue
                FROM sales 
                WHERE DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            $data['today'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

            // Weekly revenue
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as revenue
                FROM sales 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ");
            $stmt->execute();
            $data['weekly'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

            // Monthly revenue
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as revenue
                FROM sales 
                WHERE MONTH(created_at) = MONTH(CURDATE()) 
                AND YEAR(created_at) = YEAR(CURDATE())
            ");
            $stmt->execute();
            $data['monthly'] = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];

            return $data;
        } catch (PDOException $e) {
            error_log("Revenue Data Error: " . $e->getMessage());
            return $data;
        }
    }

    public function getProductSummary() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.product_name,
                    COUNT(s.transaction_id) as total_orders,
                    SUM(si.quantity) as total_quantity,
                    SUM(si.quantity * si.price) as total_revenue
                FROM products p
                LEFT JOIN sales_items si ON p.product_id = si.product_id
                LEFT JOIN sales s ON si.sales_id = s.transaction_id
                WHERE p.status = 'active'
                GROUP BY p.product_id
                ORDER BY total_revenue DESC
                LIMIT 10
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Product Summary Error: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentActivities($limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.username,
                    al.action,
                    al.created_at as timestamp
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.user_id
                ORDER BY al.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Recent Activities Error: " . $e->getMessage());
            return [];
        }
    }

    public function getUsersList() {
        try {
            $stmt = $this->conn->prepare("
                SELECT user_id, username, role, status
                FROM users
                WHERE status = 'active'
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting users: " . $e->getMessage());
            return [];
        }
    }
}
?>