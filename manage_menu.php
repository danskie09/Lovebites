<?php
session_start();
require_once 'config/database.php';
require_once 'includes/manager_auth.php';
require_once 'includes/auth.php';

// Initialize ManagerAccess class
$managerAccess = new ManagerAccess();
$managerAccess->checkManagerAccess();

$db = new Database();
$conn = $db->getConnection();

$categories = getCategories($conn);

// Initialize variables
$error = '';
$success = '';
$edit_mode = false;
$current_product = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit':
                // Fetch product details for editing
                $edit_mode = true;
                $current_product = getProductById($conn, $_POST['product_id']);
                break;
            
            case 'add':
                try {
                    $image_path = '';
                    if (!empty($_FILES['image']['name'])) {
                        $image_path = uploadProductImage($_FILES['image']);
                    }
                    
                    $product_data = [
                        'name' => $_POST['name'],
                        'description' => $_POST['description'],
                        'price' => $_POST['price'],
                        'stock' => $_POST['stock'],
                        'category' => $_POST['category'],
                        'image' => $image_path
                    ];
                    
                    if (addProduct($conn, $product_data)) {
                        $success = "Product added successfully!";
                    } else {
                        $error = "Failed to add product.";
                    }
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
            
            case 'update':
                try {
                    $image_path = $_POST['existing_image'];
                    if (!empty($_FILES['image']['name'])) {
                        $image_path = uploadProductImage($_FILES['image']);
                    }
                    
                    $product_data = [
                        'id' => $_POST['product_id'],
                        'name' => $_POST['name'],
                        'description' => $_POST['description'],
                        'price' => $_POST['price'],
                        'stock' => $_POST['stock'],
                        'category' => $_POST['category'],
                        'image' => $image_path
                    ];
                    
                    if (updateProduct($conn, $product_data)) {
                        $success = "Product updated successfully!";
                    } else {
                        $error = "Failed to update product.";
                    }
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
            
            case 'delete':
                try {
                    if (deleteProduct($conn, $_POST['product_id'])) {
                        $success = "Product deleted successfully!";
                    } else {
                        $error = "Failed to delete product.";
                    }
                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all products
$products = getAllProducts($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manager Tikka</title>
    
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="manage-products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <h1>Manage Products</h1>
                <div class="user-info">
                    <?php echo $_SESSION['username']; ?>
                </div>
            </header>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="product-management">
                <div class="add-product-form">
                    <h2><?php echo $edit_mode ? 'Edit Product' : 'Add Product'; ?></h2>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo $current_product['product_id']; ?>">
                            <input type="hidden" name="existing_image" value="<?php echo $current_product['image_path'] ?? ''; ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="add">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" required 
                                   value="<?php echo $edit_mode ? htmlspecialchars($current_product['product_name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" required rows="4"><?php 
                                echo $edit_mode ? htmlspecialchars($current_product['description']) : ''; 
                            ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" name="price" step="0.01" required
                                   value="<?php echo $edit_mode ? $current_product['price'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Stock Quantity</label>
                            <input type="number" name="stock" required
                                   value="<?php echo $edit_mode ? $current_product['stock_qty'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                        <?php 
                                        if ($edit_mode && $category['category_id'] == $current_product['category_id']) {
                                            echo ' selected';
                                        }
                                        ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Product Image</label>
                            <input type="file" name="image" accept="image/*">
                            <?php if ($edit_mode && !empty($current_product['image_path'])): ?>
                                <div class="current-image">
                                    <p>Current Image:</p>
                                    <img src="<?php echo htmlspecialchars($current_product['image_path']); ?>" 
                                         alt="Current Product Image" 
                                         class="product-image-preview">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $edit_mode ? 'Update Product' : 'Save Product'; ?>
                            </button>
                            <?php if ($edit_mode): ?>
                                <a href="manage-products.php" class="btn btn-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="product-list">
                    <h2>Product List</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $product): ?>
                                <tr>
                                    <td><?php echo $product['product_id']; ?></td>
                                    <td>
                                        <?php if (!empty($product['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                                 class="product-image-preview">
                                        <?php else: ?>
                                            No Image
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $product['stock_qty']; ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td class="action-buttons">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <button type="submit" class="btn btn-edit">Edit</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <button type="submit" class="btn btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>