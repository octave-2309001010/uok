<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Authenticate the current user
 * 
 * @param bool $redirect Whether to redirect to login if not authenticated
 * @return int|false The user ID if authenticated, false otherwise
 */
function authenticateUser($redirect = false) {
    if (isset($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    
    // Check for API authentication via token (for API requests)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        
        // Basic authentication
        if (stripos($authHeader, 'Basic ') === 0) {
            $credentials = explode(':', base64_decode(substr($authHeader, 6)));
            if (count($credentials) === 2) {
                list($email, $password) = $credentials;
                
                // Look up user by email
                require_once 'db.php';
                $user = fetchRow("SELECT * FROM users WHERE email = ?", [$email]);
                
                // Verify password and return user ID if valid
                if ($user && password_verify($password, $user['password'])) {
                    return (int)$user['id'];
                }
            }
        }
        
        // Token authentication could be added here in the future
    }
    
    // Not authenticated
    if ($redirect) {
        // Store the original requested URL for redirection after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: login.php');
        exit;
    }
    
    return false;
}

/**
 * Check if the current user has permission to access a specific resource
 * 
 * @param string $resourceType The type of resource (e.g., 'category', 'transaction')
 * @param int $resourceId The ID of the resource
 * @return bool True if the user has permission, false otherwise
 */
function hasPermission($resourceType, $resourceId) {
    $user_id = authenticateUser();
    if (!$user_id) {
        return false;
    }
    
    require_once 'db.php';
    
    switch ($resourceType) {
        case 'category':
            $resource = fetchRow(
                "SELECT * FROM categories WHERE id = ? AND user_id = ?",
                [$resourceId, $user_id]
            );
            break;
            
        case 'transaction':
            $resource = fetchRow(
                "SELECT * FROM transactions WHERE id = ? AND user_id = ?",
                [$resourceId, $user_id]
            );
            break;
            
        case 'budget':
            $resource = fetchRow(
                "SELECT * FROM budgets WHERE id = ? AND user_id = ?",
                [$resourceId, $user_id]
            );
            break;
            
        default:
            // Unknown resource type
            return false;
    }
    
    return $resource !== false;
}

/**
 * Generate a secure random token
 * 
 * @param int $length The length of the token
 * @return string The generated token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Log out the current user
 */
function logoutUser() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}
?>