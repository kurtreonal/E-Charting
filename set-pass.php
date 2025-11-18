<?php
// set_admin_pass.php — run once, then delete
include 'connection.php';

$email = 'admin@gmail.com';
$new_password = '123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $con->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param('ss', $hash, $email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Password updated for {$email}. Now login with password: {$new_password}\n";
} else {
    echo "No rows updated — check that the email exists in users table.\n";
}

$stmt->close();
$con->close();
