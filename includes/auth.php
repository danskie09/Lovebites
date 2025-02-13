<?php
require_once 'config/database.php';

class UserManagement {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    // Add Staff
    public function addUser($username, $email, $password, $role) {
        // Password hashing
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->conn->prepare("  
                INSERT INTO users (username, email, password, role) 
                VALUES (:username, :email, :password, :role)
            ");

            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                // Log activity
                $this->logActivity($_SESSION['user_id'], "Added new {$role}: {$username}");
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("User Addition Error: " . $e->getMessage());
            return false;
        }
    }

    // Authentication
    public function login($username, $password) {
        // Ensure inputs are sanitized
        $username = htmlspecialchars(strip_tags($username));

        try {
            // Check if session is already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start(); // Only start the session if it hasn't been started yet
            }

            // Prepare the SQL statement to fetch user data
            $stmt = $this->conn->prepare("
                SELECT user_id, username, password, role 
                FROM users 
                WHERE username = :username
            ");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            // Fetch user details
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // If user exists and the password matches directly (no hash check, simple password check)
            if ($user && $password == $user['password']) {  // Direct comparison without hashing
                // Store user session data
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Log the login activity
                $this->logActivity($user['user_id'], "User logged in");

                // Return true indicating successful login
                return true;
            } else {
                // Return false if password verification fails
                return false;
            }
        } catch(PDOException $e) {
            // Log the error for debugging
            error_log("Login Error: " . $e->getMessage());
            return false;
        }
    }

    // Activity Logging
    private function logActivity($user_id, $action) {
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs (user_id, action, ip_address) 
            VALUES (:user_id, :action, :ip_address)
        ");

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->execute();
    }
}

// Access Control Function
function checkAdminAccess() {
    // Check if session is started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['role']) || 
        ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'admin')) {
        header("Location: login.php");
        exit();
    }
}


function getTotalSales($conn) {
    $stmt = $conn->prepare("
        SELECT 
            MONTH(order_date) as month, 
            SUM(total_amount) as revenue 
        FROM orders 
        WHERE YEAR(order_date) = YEAR(CURRENT_DATE)
        GROUP BY MONTH(order_date)
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>


<?php
function getTotalUsersAsCustomers($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $stmt->execute();
    return $stmt->fetchColumn();
}

?>


<?php
// File: includes/dashboard_functions.php



function getRevenue($conn, $period = 'today') {
    $query = match($period) {
        'today' => "SELECT SUM(total_amount) as revenue FROM trabsactions WHERE DATE(transaction_date) = CURRENT_DATE",
        'weekly' => "SELECT SUM(total_amount) as revenue FROM trabsactions WHERE YEARWEEK(transaction_date) = YEARWEEK(CURRENT_DATE)",
        'monthly' => "SELECT SUM(total_amount) as revenue FROM trabsactions WHERE YEAR(transaction_date) = YEAR(CURRENT_DATE) AND MONTH(transaction_date) = MONTH(CURRENT_DATE)"
    };
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;
}



function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}
?>
    <?php
class StaffManager {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    // Add new staff or manager
    public function addStaff($username, $email, $password, $full_name, $role) {
        // Validate input
        if (!$this->validateInput($username, $email, $password, $role)) {
            return false;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO users (username, email, password, full_name, role) 
                VALUES (:username, :email, :password, :full_name, :role)
            ");

            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);

            return $stmt->execute();
        } catch (PDOException $e) {
            // Log error or handle duplicate entry
            return false;
        }
    }

    // Update staff information
    public function updateStaff($user_id, $username = null, $email = null, $full_name = null, $role = null, $status = null) {
        $updates = [];
        $params = [':user_id' => $user_id];

        if ($username !== null) {
            $updates[] = "username = :username";
            $params[':username'] = $username;
        }
        if ($email !== null) {
            $updates[] = "email = :email";
            $params[':email'] = $email;
        }
        if ($full_name !== null) {
            $updates[] = "full_name = :full_name";
            $params[':full_name'] = $full_name;
        }
        if ($role !== null) {
            $updates[] = "role = :role";
            $params[':role'] = $role;
        }
        if ($status !== null) {
            $updates[] = "status = :status";
            $params[':status'] = $status;
        }

        if (empty($updates)) {
            return false;
        }

        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = :user_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Delete staff
    public function deleteStaff($user_id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Get all staff members
    public function getAllStaff($role = null) {
        $query = "SELECT user_id, username, email, full_name, role, status FROM users";
        
        if ($role !== null) {
            $query .= " WHERE role = :role";
        }

        try {
            $stmt = $this->conn->prepare($query);
            
            if ($role !== null) {
                $stmt->bindParam(':role', $role);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Validate input for adding/updating staff
    private function validateInput($username, $email, $password, $role) {
        // Basic validation
        if (
            empty($username) || 
            empty($email) || 
            empty($password) || 
            empty($role)
        ) {
            return false;
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Role validation
        $valid_roles = ['admin', 'manager', 'staff'];
        if (!in_array($role, $valid_roles)) {
            return false;
        }

        // Password strength (optional, can be customized)
        if (strlen($password) < 8) {
            return false;
        }

        return true;    
    }
}
function logActivity($username, $action, $user_id = null) {
    try {
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("INSERT INTO activity_logs (username, action, user_id, ip_address, timestamp) VALUES (?, ?, ?, ?, NOW())");
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("ssis", $username, $action, $user_id, $ip);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}
?>
<?php
// product_functions.php
function getAllProducts($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, c.category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            ORDER BY p.product_id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // This will help you see the exact error
        echo "Error: " . $e->getMessage();
        die();
    }
}
// In your database/product functions file
function getProductById($conn, $product_id) {
    $stmt = $conn->prepare("SELECT p.*, c.category_name 
                            FROM products p 
                            JOIN categories c ON p.category_id = c.category_id 
                            WHERE p.product_id = :product_id");
    
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function addProduct($conn, $data) {
    $stmt = $conn->prepare("
        INSERT INTO products 
        (product_name, description, price, stock_qty, category_id, image_path) 
        VALUES 
        (:name, :description, :price, :stock, :category, :image)
    ");
    
    $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
    $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
    $stmt->bindValue(':price', $data['price'], PDO::PARAM_STR);
    $stmt->bindValue(':stock', $data['stock'], PDO::PARAM_INT);
    $stmt->bindValue(':category', $data['category'], PDO::PARAM_INT);
    $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
    
    return $stmt->execute();
}

function updateProduct($conn, $data) {
    $stmt = $conn->prepare("
        UPDATE products 
        SET 
            product_name = :name, 
            description = :description, 
            price = :price, 
            stock_qty = :stock, 
            category_id = :category, 
            image_path = :image
        WHERE product_id = :id
    ");
    
    $stmt->bindValue(':id', $data['id'], PDO::PARAM_INT);
    $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
    $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
    $stmt->bindValue(':price', $data['price'], PDO::PARAM_STR);
    $stmt->bindValue(':stock', $data['stock'], PDO::PARAM_INT);
    $stmt->bindValue(':category', $data['category'], PDO::PARAM_INT);
    $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
    
    return $stmt->execute();
}

function deleteProduct($conn, $product_id) {
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :id");
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    return $stmt->execute();
}

function getCategories($conn) {
    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY category_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uploadProductImage($file) {
    $target_dir = "uploads/products/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;
    
    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    // Check file size (limit to 5MB)
    if ($file['size'] > 5000000) {
        return false;
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    
    return false;
}


?>
<?php
class ActivityLogger {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    /**
     * Log user login activity
     * @param int $user_id User's unique identifier
     * @param string $ip_address User's IP address
     * @return bool Success of log insertion
     */
    public function logUserLogin($user_id, $ip_address) {
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs 
            (user_id, action, details, ip_address) 
            VALUES 
            (:user_id, 'Login', 'Successful user login', :ip_address)
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Log user logout activity
     * @param int $user_id User's unique identifier
     * @param string $ip_address User's IP address
     * @return bool Success of log insertion
     */
    public function logUserLogout($user_id, $ip_address) {
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs 
            (user_id, action, details, ip_address) 
            VALUES 
            (:user_id, 'Logout', 'User logged out', :ip_address)
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Log product-related activities
     * @param int $user_id User performing the action
     * @param string $action Type of product action
     * @param string $product_name Name of the product
     * @param string $ip_address User's IP address
     * @return bool Success of log insertion
     */
    public function logProductActivity($user_id, $action, $product_name, $ip_address) {
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs 
            (user_id, action, details, ip_address) 
            VALUES 
            (:user_id, :action, :details, :ip_address)
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action, PDO::PARAM_STR);
        $details = ucfirst($action) . " product: " . $product_name;
        $stmt->bindParam(':details', $details, PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Log user account activities
     * @param int $user_id User performing the action
     * @param string $action Type of account action
     * @param string $target_username Target user's username
     * @param string $ip_address User's IP address
     * @return bool Success of log insertion
     */
    public function logAccountActivity($user_id, $action, $target_username, $ip_address) {
        $stmt = $this->conn->prepare("
            INSERT INTO activty_logs 
            (user_id, action, details, ip_address) 
            VALUES 
            (:user_id, :action, :details, :ip_address)
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':action', $action, PDO::PARAM_STR);
        $details = ucfirst($action) . " performed on user: " . $target_username;
        $stmt->bindParam(':details', $details, PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        return $stmt->execute();
    }

    /**
     * Log system error or critical events
     * @param int|null $user_id User associated with the error (optional)
     * @param string $error_type Type of error
     * @param string $error_message Detailed error message
     * @param string $ip_address User's IP address
     * @return bool Success of log insertion
     */
    public function logSystemError($user_id = null, $error_type, $error_message, $ip_address) {
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs 
            (user_id, action, details, ip_address) 
            VALUES 
            (:user_id, :error_type, :error_message, :ip_address)
        ");
        
        // Properly handle null user_id
        if ($user_id === null) {
            $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        }
        
        $stmt->bindParam(':error_type', $error_type, PDO::PARAM_STR);
        $stmt->bindParam(':error_message', $error_message, PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Retrieve recent activity logs
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Recent activity logs
     */
    public function getRecentActivityLogs($limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT 
                al.log_id, 
                u.username, 
                al.action, 
                al.details, 
                al.ip_address, 
                al.created_at 
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of activity log entries
     * @return int Total number of log entries
     */
    public function getTotalLogEntries() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM activity_logs");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Filter activity logs based on various criteria
     * @param array $filters Associative array of filter conditions
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Filtered activity logs
     */
    public function filterActivityLogs($filters = [], $limit = 10, $offset = 0) {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['username'])) {
            $whereConditions[] = "u.username LIKE :username";
            $params[':username'] = '%' . $filters['username'] . '%';
        }

        if (!empty($filters['action'])) {
            $whereConditions[] = "al.action = :action";
            $params[':action'] = $filters['action'];
        }

        if (!empty($filters['start_date'])) {
            $whereConditions[] = "al.created_at >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $whereConditions[] = "al.created_at <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";

        $stmt = $this->conn->prepare("
            SELECT 
                al.log_id, 
                u.username, 
                al.action, 
                al.details, 
                al.ip_address, 
                al.created_at 
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            $whereClause
            ORDER BY al.created_at DESC
            LIMIT :limit OFFSET :offset
        ");

        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Example usage
try {
    // Assuming you have a Database class that establishes connection
    $db = new Database();
    $conn = $db->getConnection();
    $logger = new ActivityLogger($conn);

    // Example of logging different types of activities
    
    // Log a login
    $logger->logUserLogin(1, $_SERVER['REMOTE_ADDR']);

    // Log a product addition
    $logger->logProductActivity(1, 'create', 'New Chocolate Product', $_SERVER['REMOTE_ADDR']);

    // Log a system error without a specific user
    try {
        // Some database operation that might fail
        $someOperation = $conn->prepare("SELECT * FROM non_existent_table");
        $someOperation->execute();
    } catch (PDOException $e) {
        // Log system error with null user_id
        $logger->logSystemError(null, 'Database Error', $e->getMessage(), $_SERVER['REMOTE_ADDR']);
    }

    // Retrieve recent logs
    $recentLogs = $logger->getRecentActivityLogs();
} catch (PDOException $e) {
    // Fallback error logging
    error_log("Critical Error: " . $e->getMessage());
}
?>

<?php
class AnalyticsService {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    // Top-selling products
    public function getTopSellingProducts($limit = 5) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.product_name, 
                SUM(od.quantity) as total_sold, 
                SUM(od.quantity * od.price) as total_revenue
            FROM order_details od
            JOIN products p ON od.product_id = p.product_id
            GROUP BY p.product_id
            ORDER BY total_sold DESC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Monthly sales trend
    public function getMonthlySalesTrend($year = null) {
        $year = $year ?? date('Y');
        $stmt = $this->conn->prepare("
            SELECT 
                MONTH(order_date) as month, 
                SUM(total_amount) as total_sales
            FROM orders
            WHERE YEAR(order_date) = :year
            GROUP BY MONTH(order_date)
            ORDER BY month
        ");
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Customer Acquisition
    public function getCustomerAcquisition() {
        $stmt = $this->conn->prepare("
            SELECT 
                MONTH(created_at) as month, 
                COUNT(*) as new_customers
            FROM customers
            WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
            GROUP BY MONTH(created_at)
            ORDER BY month
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Inventory Analysis
    public function getInventoryAnalysis() {
        $stmt = $this->conn->prepare("
            SELECT 
                c.category_name, 
                COUNT(p.product_id) as total_products,
                SUM(p.stock_qty) as total_stock,
                AVG(p.price) as avg_price
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            GROUP BY c.category_id
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

function createOrder($conn, $customer_id, $total_amount) {
    try {
        $conn->beginTransaction();

        // Insert the main order first
        $stmt = $conn->prepare("
            INSERT INTO orders (customer_id, total_amount, order_date)
            VALUES (:customer_id, :total_amount, NOW())
        ");
        
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':total_amount', $total_amount); // Fixed missing quote
        $stmt->execute();
        
        // Get the newly created order ID
        $order_id = $conn->lastInsertId();
        
        $conn->commit();
        return $order_id;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Order creation error: " . $e->getMessage());
        return false;
    }
}

function insertOrderItems($conn, $order_id, $cart_items) {
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, total_price)
            VALUES (:order_id, :product_id, :quantity, :price, :total_price)
        ");

        foreach ($cart_items as $item) {
            $total_price = $item['quantity'] * $item['price'];
            
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':price', $item['price']);
            $stmt->bindParam(':total_price', $total_price);
            
            $stmt->execute();
        }

        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Order items insertion error: " . $e->getMessage());
        return false;
    }
}
function submitOrder($orderData) {
    global $conn;
    try {
        // Start transaction
        $conn->beginTransaction();

        // Insert into orders table
        $stmt = $conn->prepare("
            INSERT INTO orders 
            (total_amount, discount, net_amount, payment_method) 
            VALUES 
            (:total, :discount, :net, :payment)
        ");
        
        $totalAmount = array_reduce($orderData['items'], function($carry, $item) {
            return $carry + ($item['price'] * $item['quantity']);
        }, 0);
        
        $stmt->execute([
            ':total' => $totalAmount,
            ':discount' => $orderData['discount'] ?? 0,
            ':net' => $totalAmount - ($orderData['discount'] ?? 0),
            ':payment' => $orderData['payment_method']
        ]);
        
        $orderId = $conn->lastInsertId();

        // Insert order items
        $itemStmt = $conn->prepare("
            INSERT INTO order_items 
            (order_id, product_id, quantity, unit_price, total_price) 
            VALUES 
            (:order_id, :product_id, :quantity, :unit_price, :total_price)
        ");

        foreach ($orderData['items'] as $item) {
            $itemStmt->execute([
                ':order_id' => $orderId,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['price'],
                ':total_price' => $item['price'] * $item['quantity']
            ]);
        }

        // Commit transaction
        $conn->commit();

        return [
            'success' => true, 
            'order_id' => $orderId, 
            'message' => 'Order submitted successfully'
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        return [
            'success' => false, 
            'message' => 'Order submission failed: ' . $e->getMessage()
        ];
    }
}



 ?>


