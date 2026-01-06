<?php
/**
 * Nurse Authentication Check
 * Include this at the top of every nurse-only page
 * Redirects to login if not authenticated
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name('nurse_session');
    session_start();
}

// Check if nurse is logged in
if (!isset($_SESSION['nurse_id']) || !isset($_SESSION['is_nurse']) || $_SESSION['is_nurse'] !== true) {
    // Not logged in - redirect to admin login
    header("Location: admin-login.php");
    exit();
}

// Session timeout: 30 minutes
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: admin-login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
?>
