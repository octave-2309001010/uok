<?php
/**
 * Authentication API
 * Handles user registration, login, and logout
 */

// Include required files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

session_start(); // Ensure sessions work

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    // Instead of redirecting, send a JSON response for successful logout
    echo jsonResponse(true, 'Logged out successfully');
    exit; // Ensure no further processing
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'register':
            handleRegister();
            break;
        case 'login':
            handleLogin();
            break;
        default:
            echo jsonResponse(false, 'Invalid action');
            break;
    }
}

/**
 * Handle user registration
 */
function handleRegister() {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters';
    } elseif (getUserByUsername($username)) {
        $errors['username'] = 'Username already exists';
    }

    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (getUserByEmail($email)) {
        $errors['email'] = 'Email already exists';
    }

    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if (!empty($errors)) {
        echo jsonResponse(false, 'Validation failed', ['errors' => $errors]);
        return;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $userId = insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        if (!$userId) {
            echo jsonResponse(false, 'Registration failed');
            return;
        }

        generateDefaultCategories($userId);
        $_SESSION['user_id'] = $userId;

        echo jsonResponse(true, 'Registration successful');
    } catch (Exception $e) {
        error_log('Registration error: ' . $e->getMessage());
        echo jsonResponse(false, 'Server error during registration');
    }
}

/**
 * Handle user login
 */
function handleLogin() {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo jsonResponse(false, 'Email and password are required');
        return;
    }

    $user = getUserByEmail($email);

    if (!$user || !password_verify($password, $user['password'])) {
        echo jsonResponse(false, 'Invalid email or password');
        return;
    }

    $_SESSION['user_id'] = $user['id'];
    echo jsonResponse(true, 'Login successful');
}

/**
 * Return a JSON response
 */
function jsonResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;  // Ensure no further output
}
