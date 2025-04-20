<?php
/**
 * Configuration file for the Budget Tracker application
 * Contains database connection settings and application constants
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('APP_NAME', 'Budget Tracker');
define('APP_URL', 'http://localhost/budget-tracker');

// Session settings
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session parameters
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
}