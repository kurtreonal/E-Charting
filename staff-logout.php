<?php
session_name('nurse_session');
session_start();

if (!isset($_SESSION['is_nurse']) || $_SESSION['is_nurse'] !== true) {
    header("Location: admin-login.php");
    exit();
}
if (!empty($_SESSION['nurse_id'])) {
    error_log("Nurse logout: nurse_id=" . $_SESSION['nurse_id']);
}

$_SESSION = array();

if (isset($_COOKIE['nurse_session'])) {
    $params = session_get_cookie_params();
    setcookie('nurse_session', '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: admin-login.php");
exit();