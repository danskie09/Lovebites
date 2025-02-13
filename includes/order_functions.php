
function createOrder($conn, $customer_id, $total_amount) {
    try {
        $conn->beginTransaction();

        // Insert the main order first
        $stmt = $conn->prepare("
            INSERT INTO orders (customer_id, total_amount, order_date)
            VALUES (:customer_id, :total_amount, NOW())
        ");
        
        $stmt->bindParam(':customer_id', $customer_id);
        $stmt->bindParam(':total_amount, $total_amount);
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

// Example usage:
/*
$cart_items = [
    [
        'product_id' => 1,
        'quantity' => 2,
        'price' => 100.00
    ],
    [
        'product_id' => 2,
        'quantity' => 1,
        'price' => 150.00
    ]
];

// First create the order
$order_id = createOrder($conn, $customer_id, $total_amount);

if ($order_id) {
    // Then insert the order items
    insertOrderItems($conn, $order_id, $cart_items);
}

