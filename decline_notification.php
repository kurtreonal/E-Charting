<?php
session_start();
include 'connection.php'; // $con

header('Content-Type: application/json');

// require nurse login + POST
if (!isset($_SESSION['nurse_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized or invalid request method']);
    exit();
}

$nurse_id = (int) $_SESSION['nurse_id'];

// read JSON
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;

if ($notification_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
    exit();
}

// 1. Fetch notification and get appointment_id
$sql = "SELECT notification_id, appointment_id FROM notification WHERE notification_id = ? AND nurse_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param('ii', $notification_id, $nurse_id);
$stmt->execute();
$res = $stmt->get_result();
$notif = $res->fetch_assoc();
$stmt->close();

if (!$notif) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Notification not found or does not belong to you']);
    exit();
}

// 2. Update notification table
$updSql = "UPDATE notification SET is_confirmed = 0, message_status = 'declined', is_read = 1 WHERE notification_id = ? AND nurse_id = ?";
$stmt = $con->prepare($updSql);
$stmt->bind_param('ii', $notification_id, $nurse_id);
$notifUpdated = $stmt->execute();
$stmt->close();

// 3. Update appointment table if appointment_id exists
$appointmentUpdated = false;
if (!empty($notif['appointment_id'])) {
    $apptSql = "UPDATE appointment SET appointment_status = 'declined' WHERE appointment_id = ? AND nurse_id = ?";
    $stmt = $con->prepare($apptSql);
    $stmt->bind_param('ii', $notif['appointment_id'], $nurse_id);
    $appointmentUpdated = $stmt->execute();
    $stmt->close();
}

if ($notifUpdated && $appointmentUpdated) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update notification or appointment'
    ]);
}
exit();
?>
