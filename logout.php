<?php
// Use separate session name for patient
session_name('patient_session');
session_start();

// Only allow patient logout (not admin)
if (isset($_SESSION['is_nurse']) && $_SESSION['is_nurse'] === true) {
    header("Location: admin-login.php");
    exit();
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
if (session_id() != '') {
    session_destroy();
}

// Delete session cookie if it exists
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

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

// Redirect patient to login
header("Location: login.php");
exit();
?>