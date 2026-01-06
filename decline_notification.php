<?php
header('Content-Type: application/json; charset=utf-8');
session_name('patient_session');
session_start();

include 'connection.php';
include_once 'includes/notification.php';

try {
    //check if user is logged in as patient
    if (empty($_SESSION['patient_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized - Patient login required'
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        exit;
    }

    $patient_id = (int)$_SESSION['patient_id'];

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $notification_id = isset($data['notification_id']) ? (int)$data['notification_id'] : 0;

    if ($notification_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid notification ID'
        ]);
        exit;
    }

    $check_sql = "SELECT notification_id, appointment_id
                  FROM notification
                  WHERE notification_id = ? AND patient_id = ?
                  LIMIT 1";

    $check_stmt = $con->prepare($check_sql);
    if (!$check_stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database prepare failed'
        ]);
        exit;
    }

    $check_stmt->bind_param('ii', $notification_id, $patient_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Notification not found or access denied'
        ]);
        exit;
    }

    $notif_data = $check_result->fetch_assoc();
    $check_stmt->close();

    //update notification to declined
    $update_sql = "UPDATE notification
                   SET is_confirmed = 0,
                       message_status = 'declined',
                       is_read = 1
                   WHERE notification_id = ?";

    $update_stmt = $con->prepare($update_sql);
    if (!$update_stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database prepare failed for update'
        ]);
        exit;
    }

    $update_stmt->bind_param('i', $notification_id);

    if ($update_stmt->execute()) {
        $update_stmt->close();

        if (!empty($notif_data['appointment_id'])) {
            $appt_sql = "UPDATE appointment
                        SET appointment_status = 'declined'
                        WHERE appointment_id = ?";
            $appt_stmt = $con->prepare($appt_sql);
            if ($appt_stmt) {
                $appt_stmt->bind_param('i', $notif_data['appointment_id']);
                $appt_stmt->execute();
                $appt_stmt->close();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Appointment declined successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to decline notification'
        ]);
    }

} catch (Exception $e) {
    error_log("decline_notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
exit;
