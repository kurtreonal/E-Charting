<?php
session_name('patient_session');
session_start();

if (!empty($_SESSION['patient_id'])) {
    error_log("Patient logout: patient_id=" . $_SESSION['patient_id']);
}

$_SESSION = array();

if (isset($_COOKIE['patient_session'])) {
    $params = session_get_cookie_params();
    setcookie('patient_session', '', time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: login.php");
exit();