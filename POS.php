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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="POS.css">

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

            <div class="category-menu">
                <div class="category-item active">
                    <div class="category-icon"><i class="fas fa-star"></i></div>
                    <span>Popular</span>
                </div>
                <div class="category-item">
                    <div class="category-icon"><i class="fas fa-ice-cream"></i></div>
                    <span>Ice Cream</span>
                </div>
                <div class="category-item">
                    <div class="category-icon"><i class="fas fa-martini-glass"></i></div>
                    <span>Drinks</span>
                </div>
                <div class="category-item">
                    <div class="category-icon"><i class="fas fa-cookie"></i></div>
                    <span>Snack</span>
                </div>
                <div class="category-item">
                    <div class="category-icon"><i class="fas fa-utensils"></i></div>
                    <span>Meals</span>
                </div>
            </div>
            

            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             class="product-image">
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bill-section">
            <div class="bill-header">
                <h2>Bills</h2>
                <i class="fas fa-ellipsis-v"></i>
            </div>
            
            <div class="bill-items">
                <div class="bill-item">
                    <div class="item-info">
                        <img src="/api/placeholder/50/50" alt="" class="item-image">
                        <div>
                            <div></div>
                            <div class="product-price">₱</div>
                        </div>
                    </div>
                    <div class="quantity-controls">
                        <button class="quantity-btn">-</button>
                        <span>0</span>
                        <button class="quantity-btn">+</button>
                    </div>
                </div>
               
            </div>

            <div class="bill-summary">
                <div class="summary-row total">
                    <span>Total</span>
                    <span>₱</span>
                </div>
            </div>

            <h3>Payment Method</h3>
            <div class="payment-methods">
                <div class="payment-method">
                    <i class="fas fa-money-bill"></i>
                    <div>Cash</div>
                </div>
                <div class="payment-method active">
                    <i class="fas fa-credit-card"></i>
                    <div>Debit Card</div>
                </div>
                <div class="payment-method">
                    <i class="fas fa-wallet"></i>
                    <div>G-Cash</div>
                </div>
            </div>

            <button class="checkout-btn">Add to Billing</button>
        </div>
    </div>

    <script src="pos.js"></script>
</body>
</body>
</html>