<?php
session_start();
include 'dbconnection.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
    exit();
}

$error_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Validate input
        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username and password');
        }
        
        // Get user from database
        $stmt = $conn->prepare("SELECT id, username, password, role, first_name, last_name, status FROM users WHERE username = ?");
        if (!$stmt) {
            throw new Exception('Database error');
        }
        
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            throw new Exception('Database error');
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Invalid username or password');
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new Exception('Invalid username or password');
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            throw new Exception('Your account is inactive. Please contact the administrator.');
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_role'] = $user['role']; // Added for admin dashboard check
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        // Update last login
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        
        // Log the activity
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description, ip_address) VALUES (?, 'login', ?, ?)");
        $description = "User logged in";
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("iss", $user['id'], $description, $ip_address);
        $stmt->execute();
        
        // Redirect based on role
        header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
        exit();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Plantilla Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Plantilla Management System</h1>
            <p>Please login to continue</p>
        </div>
        <div class="login-form">
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <label for="username">Username</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                </div>
                
                <button type="submit" class="btn btn-login w-100 text-white">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';
            });
        });
    </script>
</body>
</html>
