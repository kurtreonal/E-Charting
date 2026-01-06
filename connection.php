<?php
date_default_timezone_set('Asia/Manila');

$host = "localhost";
$user = "root";
$pass = "";
$e_charting = "e_charting";
try {
    $con = new mysqli($host, $user, $pass, $e_charting);
    $con->set_charset("utf8mb4");
} catch (Exception $e) {
    echo "<center><h1>SERVER ERROR!</center></h1> Failed to connect to the server: MYSQL. Start MYSQL connection first! - " . $e->getMessage();
    exit();
}


if(mysqli_connect_errno()){
        echo "Failed to connect to the server: MYSQL".mysqli_connect_error();
}
