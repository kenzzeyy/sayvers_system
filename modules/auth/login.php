<?php
// Login page for SAYVERS Management System
require_once '../../config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error_message = '';

// Handle login form submission
if ($_POST) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // Changed here: plain text password comparison instead of password_verify
            if ($user && $password === $user['password']) {
                // Successful login
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['login_time'] = time();
                
                // Update last login
                $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Log successful login
                logAuditTrail('admin_users', $user['id'], 'LOGIN');
                
                // Redirect to dashboard
                header('Location: ' . BASE_URL . 'index.php');
                exit;
            } else {
                $error_message = 'Invalid username or password.';
                
                // Log failed login attempt
                logAuditTrail('admin_users', 0, 'LOGIN', null, ['username' => $username, 'status' => 'failed']);
            }
        } catch(PDOException $e) {
            $error_message = 'Login system temporarily unavailable.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="../../assets/images/logo.png" alt="SAYVERS Logo" style="height: 80px;">
                <h1 class="login-title"><?php echo ORGANIZATION_SHORT; ?></h1>
                <p style="color: #666; margin-bottom: 2rem;">Management System</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" data-validate="true">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           required 
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <small style="color: #666;">
                    <i class="fas fa-shield-alt"></i>
                    Authorized Personnel Only
                </small>
                <br>
                <small style="color: #999; font-size: 0.8rem;">
                    Version <?php echo SYSTEM_VERSION; ?>
                </small>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script>
        // Auto-focus on username field
        document.getElementById('username').focus();
        
        // Handle form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                showAlert('Please enter both username and password.', 'danger');
            }
        });
    </script>
</body>
</html>
