<?php
session_start();

//only allow nurse/admin logout
if (!isset($_SESSION['is_nurse']) || $_SESSION['is_nurse'] !== true) {
    header("Location: login.php");
    exit();
}

//destroy the session
session_destroy();

//clear session data
$_SESSION = array();

//delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

//redirect admin to admin login
header("Location: admin-login.php");
exit();
?>
