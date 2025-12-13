<?php
// Use separate session name for admin
session_name('admin_session');
session_start();

// Only allow nurse/admin logout
if (!isset($_SESSION['is_nurse']) || $_SESSION['is_nurse'] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Destroy only the admin session
session_destroy();

// Clear session data
$_SESSION = array();

// Delete session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect admin to admin login
header("Location: admin-login.php");
exit();
?>