<?php
require_once 'db.php';
require_once 'auth.php';

// Get authenticated user ID or redirect to login
$user_id = authenticateUser(true);

// Get user data
$user = fetchRow("SELECT id, name, email, created_at, last_login, currency FROM users WHERE id = ?", [$user_id]);

// Process form submission for profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['update_profile'])) {
        // Handle profile update
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $currency = trim($_POST['currency'] ?? 'USD');
        
        // Validate inputs
        if (empty($name)) {
            $error_message = 'Name is required';
        } elseif (empty($email)) {
            $error_message = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format';
        } else {
            // Check if email is already taken by another user
            $existing_user = fetchRow(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$email, $user_id]
            );
            
            if ($existing_user) {
                $error_message = 'Email is already in use by another account';
            } else {
                // Update profile
                $result = execute(
                    "UPDATE users SET name = ?, email = ?, currency = ? WHERE id = ?",
                    [$name, $email, $currency, $user_id]
                );
                
                if ($result) {
                    // Update session data
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    
                    // Refresh user data
                    $user = fetchRow("SELECT id, name, email, created_at, last_login, currency FROM users WHERE id = ?", [$user_id]);
                    $success_message = 'Profile updated successfully';
                } else {
                    $error_message = 'Failed to update profile';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Handle password change
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($current_password)) {
            $error_message = 'Current password is required';
        } elseif (empty($new_password)) {
            $error_message = 'New password is required';
        } elseif (strlen($new_password) < 8) {
            $error_message = 'New password must be at least 8 characters long';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match';
        } else {
            // Verify current password
            $user_with_password = fetchRow(
                "SELECT password FROM users WHERE id = ?",
                [$user_id]
            );
            
            if (!password_verify($current_password, $user_with_password['password'])) {
                $error_message = 'Current password is incorrect';
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $result = execute(
                    "UPDATE users SET password = ? WHERE id = ?",
                    [$hashed_password, $user_id]
                );
                
                if ($result) {
                    $success_message = 'Password changed successfully';
                } else {
                    $error_message = 'Failed to change password';
                }
            }
        }
    }
}

// Get page title
$page_title = 'Profile Settings';

// Include header
include 'header.php';
?>

<div class="container mt-4">
    <h1><?php echo $page_title; ?></h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Profile Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="currency" class="form-label">Preferred Currency</label>
                            <select class="form-select" id="currency" name="currency">
                                <option value="USD" <?php echo $user['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                <option value="EUR" <?php echo $user['currency'] === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                <option value="GBP" <?php echo $user['currency'] === 'GBP' ? 'selected' : ''; ?>>British Pound (GBP)</option>
                                <option value="JPY" <?php echo $user['currency'] === 'JPY' ? 'selected' : ''; ?>>Japanese Yen (JPY)</option>
                                <option value="CAD" <?php echo $user['currency'] === 'CAD' ? 'selected' : ''; ?>>Canadian Dollar (CAD)</option>
                                <option value="AUD" <?php echo $user['currency'] === 'AUD' ? 'selected' : ''; ?>>Australian Dollar (AUD)</option>
                                <option value="CNY" <?php echo $user['currency'] === 'CNY' ? 'selected' : ''; ?>>Chinese Yuan (CNY)</option>
                                <option value="INR" <?php echo $user['currency'] === 'INR' ? 'selected' : ''; ?>>Indian Rupee (INR)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Account Created</label>
                            <p class<p class="form-control-plaintext"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Login</label>
                            <p class="form-control-plaintext"><?php echo date('F j, Y \a\t g:i a', strtotime($user['last_login'])); ?></p>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Change Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Account Actions</h3>
                </div>
                <div class="card-body">
                    <p>Need to export your data or manage your account?</p>
                    
                    <div class="d-grid gap-2">
                        <a href="export-data.php" class="btn btn-outline-primary">Export All Data</a>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger">Warning: This action cannot be undone. All your data will be permanently deleted.</p>
                <p>Please enter your password to confirm account deletion:</p>
                <form id="deleteAccountForm" action="delete-account.php" method="POST">
                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="delete_password" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger">Delete My Account</button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>