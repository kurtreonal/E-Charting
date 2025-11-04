<?php

$host = "localhost";
$user = "root";
$pass = "";
$e_charting = "e_charting";

$con = mysqli_connect($host, $user, $pass, $e_charting);

if(mysqli_connect_errno()){
        echo "Failed to connect to the server: MYSQL".mysqli_connect_error();
}