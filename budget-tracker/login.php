<?php
require_once 'includes/config.php';  // If you need configuration settings
require_once 'includes/db.php';      // For database functions
require_once 'includes/functions.php'; // For other helper functions

// Test database connection
try {
    $testConnection = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (!isset($_POST['email']) || !isset($_POST['password']) ||
        empty(trim($_POST['email'])) || empty(trim($_POST['password']))) {
        $error = 'Email and password are required';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Get user from database
        $user = fetchRow(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
        
        // Verify password and set session
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            
            // Update last login time
            execute(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Personal Budget Tracker</title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h1>Personal Budget Tracker</h1>
            <h2>Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
                
                <div class="auth-links">
                    <p>Don't have an account? <a href="register.php">Register</a></p>
                    <p><a href="forgot-password.php">Forgot Password?</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>