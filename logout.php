<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Explicitly include the database configuration
require_once 'config/database.php';

class Logout {
    private $conn;

    public function __construct() {
        // Create database connection in the constructor
        try {
            $db = new Database();
            $this->conn = $db->getConnection();
        } catch (Exception $e) {
            // Log the error and handle database connection failure
            error_log("Database Connection Error in Logout: " . $e->getMessage());
            // You might want to redirect to an error page or handle this differently
        }
    }

    public function logoutUser() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Log logout activity before destroying session
        $this->logLogoutActivity();

        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        session_destroy();

        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Redirect to login page
        header("Location: login.php");
        exit();
    }

    private function logLogoutActivity() {
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $this->conn->prepare("
                    INSERT INTO activity_logs 
                    (user_id, action, description, ip_address) 
                    VALUES 
                    (:user_id, 'Logout', 'User logged out', :ip_address)
                ");

                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
                $stmt->execute();
            } catch(PDOException $e) {
                // Log error but don't interrupt logout process
                error_log("Logout Activity Log Error: " . $e->getMessage());
            }
        }
    }
}

// Instantiate and process logout
$logout = new Logout();
$logout->logoutUser();
?>