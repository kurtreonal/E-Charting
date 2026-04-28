<?php
/**
 * Database Connection Handler
 * Loads credentials from .env configuration
 */

// Load configuration from .env file
require_once dirname(__FILE__) . '/includes/config.php';

// Get database credentials from .env
$host = Config::get('DB_HOST', 'localhost');
$user = Config::get('DB_USER', 'root');
$pass = Config::get('DB_PASS', '');
$e_charting = Config::get('DB_NAME', 'e_charting');
$charset = Config::get('DB_CHARSET', 'utf8mb4');

// Set timezone from configuration
date_default_timezone_set(Config::get('APP_TIMEZONE', 'Asia/Manila'));

try {
    $con = new mysqli($host, $user, $pass, $e_charting);
    $con->set_charset($charset);

    // Check for connection errors
    if ($con->connect_error) {
        throw new Exception("Database connection failed: " . $con->connect_error);
    }
} catch (Exception $e) {
    // Log the error securely
    error_log("Database connection error: " . $e->getMessage());
    
    // Display user-friendly error
    http_response_code(500);
    echo "<center><h1>SERVER ERROR</h1></center>";
    echo "<p>Failed to connect to the database. Please contact the system administrator.</p>";
    exit();
}
