<?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/auth.php';

    function loginUser($username, $password) {
        $conn = getDatabaseConnection();
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ? AND role = 'manager'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($password === $user['password']) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Log login activity with the correct parameters
                logActivity($username, "Logged in", $user['user_id']);
                
                return true;
            }
        }
        return false;
    }

    // Login form processing
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        if (loginUser($username, $password)) {
            if ($_SESSION['role'] === 'manager') {
                header("Location: manager.php");
            } else {
                $error = "Access denied. Admin privileges required.";
            }
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Login - Love Bites</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <style>
        :root {
            --primary-color: #ff4d6d;
            --background-color: #f4f4f4;
            --text-color: #333;
        }

        * {
            margin: 3px;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 350px;
            padding: 30px;
            text-align: center;
        }

        .login-logo {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .login-title {
            font-size: 18px;
            color: var(--text-color);
            margin-bottom: 25px;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .form-group i {
            position: absolute;
            left: 10px;
            color: #888;
            font-size: 14px;
            z-index: 10;
        }

        .form-group input {
            width: 100%;
            padding: 12px 12px 12px 35px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background-color: white;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .login-button:hover {
            background: #e63e5e;
        }

        .forgot-password {
            display: block;
            margin-top: 15px;
            font-size: 12px;
            color: var(--primary-color);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">💖 Love Bites</div>
        <div class="login-title">Manager Login</div>
        <form method="POST" class="login-form">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Username" required>
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="login-button">Login</button>
            <a href="#" class="forgot-password">Forgot Password?</a>
        </form>
    </div>
</body>
</html>