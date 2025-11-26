<?php
session_start();

// Get user type before destroying session
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;

// Destroy the session
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

// Redirect based on user type
if ($user_type === 'nurse') {
    header("Location: admin-login.php");
} else {
    header("Location: login.php");
}
exit();
?>