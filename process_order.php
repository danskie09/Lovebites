<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Assuming the cart data is sent via POST request
    $cart_items = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
    $customer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // Default to 1 if no user is logged in

    if (empty($cart_items)) {
        throw new Exception("Cart is empty");
    }

    // Create the order first
    $order_id = createOrder($conn, $customer_id, $total_amount);

    if (!$order_id) {
        throw new Exception("Failed to create order");
    }

    // Insert order items
    $success = insertOrderItems($conn, $order_id, $cart_items);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Order processed successfully',
            'order_id' => $order_id
        ]);
    } else {
        throw new Exception("Failed to insert order items");
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
