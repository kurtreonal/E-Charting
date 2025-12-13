<?php
// confirm_notification.php
session_start();
include 'connection.php'; // Ensure your database connection file is correctly included

header('Content-Type: application/json');

// require nurse login and POST method
if (!isset($_SESSION['nurse_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized or invalid request method']);
    exit();
}

$nurse_id = (int) $_SESSION['nurse_id'];

// read JSON body
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;

if ($notification_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit();
}

// Update the notification: set is_confirmed = 1, message_status = 'confirmed', and is_read = 1
$sql = "UPDATE notification SET is_confirmed = 1, message_status = 'confirmed', is_read = 1
        WHERE notification_id = ? AND nurse_id = ?"; // Restrict to current nurse for security
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('ii', $notification_id, $nurse_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Notification not found or already confirmed.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database update failed.']);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement.']);
}
