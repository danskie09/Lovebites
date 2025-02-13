<?php
header('Content-Type: application/json');
require_once 'config/database.php';

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    // Create database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();

    // Insert order into orders table
    $stmt = $conn->prepare("
        INSERT INTO orders (total_amount, payment_method, order_date)
        VALUES (:total, :payment, NOW())
    ");

    $stmt->execute([
        ':total' => $data['total_amount'],
        ':payment' => $data['payment_method']
    ]);

    $order_id = $conn->lastInsertId();

    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (:order_id, :product_id, :quantity, :price)
    ");

    foreach ($data['items'] as $item) {
        $stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $item['product_id'],
            ':quantity' => $item['quantity'],
            ':price' => $item['price']
        ]);
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }

    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error creating order: ' . $e->getMessage()
    ]);
}
