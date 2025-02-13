<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'super_admin') {
        header("Location: dashboard.php");
        exit();
    }
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $userManagement = new UserManagement();
    
    if ($userManagement->login($username, $password)) {
        // Successful login
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'super_admin') {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Access denied. Admin rights required.";
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tika Admin Loging-in</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <h1>Admin</h1>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
            <div class="form-group">
    <label for="username">Username</label>
    <div class="input-wrapper username-wrapper">
        <i class="fas fa-user"></i>
        <input 
            type="text" 
            id="username" 
            name="username" 
            required 
            placeholder="Enter your username"
        >
    </div>
</div>

<div class="form-group">
    <label for="password">Password</label>
    <div class="input-wrapper password-wrapper">
        <i class="fas fa-lock"></i>
        <input 
            type="password" 
            id="password" 
            name="password" 
            required 
            placeholder="Enter your password"
        >
        <span class="toggle-password" onclick="togglePasswordVisibility()">
            <i class="fas fa-eye"></i>
        </span>
    </div>
</div>

                <div class="form-group">
                    <button type="submit" class="login-button">
                        Login
                    </button>
                </div>
            </form>

            <div class="forgot-password">
                <a href="forgot-password.php">Forgot Password?</a>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const icon = document.querySelector('.toggle-password i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
