<?php
// clear_notification.php
session_start();
include 'connection.php'; // ensure $con is mysqli instance

header('Content-Type: application/json; charset=utf-8');

// Read raw JSON body (the frontend sends JSON)
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

// if JSON decode failed, fall back to $_POST for compatibility
if ($body === null && !empty($_POST)) {
    $body = $_POST;
}
if (!is_array($body)) $body = [];

// Who is logged in?
$is_nurse = isset($_SESSION['nurse_id']) && !empty($_SESSION['nurse_id']);
$is_patient = isset($_SESSION['patient_id']) && !empty($_SESSION['patient_id']);

if (!$is_nurse && !$is_patient) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // normalize inputs
    $mark_all_read = isset($body['mark_all_read']) ? (int)$body['mark_all_read'] : 0;
    $notification_id = isset($body['notification_id']) ? (int)$body['notification_id'] : null;
    $mark_read_single = isset($body['mark_read']) ? (int)$body['mark_read'] : 0;
    $target_patient_id = isset($body['patient_id']) ? (int)$body['patient_id'] : null;

    // If patient is logged in, override any provided patient_id to enforce scope
    if ($is_patient) {
        $session_patient = (int) $_SESSION['patient_id'];
        $target_patient_id = $session_patient;
    }

    // 1) Mark a single notification read (if notification_id provided)
    if ($notification_id && $mark_read_single === 1) {
        // Build SQL so that nurse/patient session can only modify notifications they should own
        if ($is_nurse) {
            $nurse_id = (int) $_SESSION['nurse_id'];
            // If a target_patient_id is provided, include it to restrict
            if ($target_patient_id !== null) {
                $sql = "UPDATE notification SET is_read = 1 WHERE notification_id = ? AND nurse_id = ? AND patient_id = ?";
                $stmt = $con->prepare($sql);
                if (!$stmt) throw new Exception('Prepare failed: ' . $con->error);
                $stmt->bind_param('iii', $notification_id, $nurse_id, $target_patient_id);
            } else {
                $sql = "UPDATE notification SET is_read = 1 WHERE notification_id = ? AND nurse_id = ?";
                $stmt = $con->prepare($sql);
                if (!$stmt) throw new Exception('Prepare failed: ' . $con->error);
                $stmt->bind_param('ii', $notification_id, $nurse_id);
            }
        } else {
            // patient
            $session_patient = (int) $_SESSION['patient_id'];
            $sql = "UPDATE notification SET is_read = 1 WHERE notification_id = ? AND patient_id = ?";
            $stmt = $con->prepare($sql);
            if (!$stmt) throw new Exception('Prepare failed: ' . $con->error);
            $stmt->bind_param('ii', $notification_id, $session_patient);
        }

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        echo json_encode(['success' => true, 'action' => 'marked_single', 'affected' => $affected]);
        exit();
    }

    // 2) Mark all unread read for scope
    if ($mark_all_read === 1) {
        if ($is_nurse) {
            $nurse_id = (int) $_SESSION['nurse_id'];
            if ($target_patient_id !== null) {
                // Only mark notifications for this nurse + patient
                $sql = "UPDATE notification SET is_read = 1 WHERE nurse_id = ? AND patient_id = ? AND is_read = 0";
                $stmt = $con->prepare($sql);
                if (!$stmt) throw new Exception('Prepare failed: ' . $con->error);
                $stmt->bind_param('ii', $nurse_id, $target_patient_id);
            } else {
                // Mark all nurse notifications
                $sql = "UPDATE notification SET is_read = 1 WHERE nurse_id = ? AND is_read = 0";
                $stmt = $con->prepare($sql);
                if (!$stmt) throw new Exception('Prepare failed: ' . $con->error);
                $stmt->bind_param('i', $nurse_id);
            }
        } else {
            // patient - mark only this patient's unread notifications
            $session_patient = (int) $_SESSION['patient_id'];
            $sql = "UPDATE notification SET is_read = 1 WHERE patient_id = ? AND is_read = 0";
            $stmt = $con->prepare($sql);
            if (!$stmt) throw new Exception('Prepare failed: ' . $con->error);
            $stmt->bind_param('i', $session_patient);
        }

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        echo json_encode(['success' => true, 'action' => 'marked_all_read', 'affected' => $affected]);
        exit();
    }

    // 3) If client wants to clear appointment_notice in session (compatibility)
    if (isset($body['clear']) && (int)$body['clear'] === 1) {
        if (isset($_SESSION['appointment_notice'])) {
            unset($_SESSION['appointment_notice']);
        }
        echo json_encode(['success' => true, 'action' => 'session_cleared']);
        exit();
    }

    // If none matched, invalid request
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action or missing parameters.']);
    exit();

} catch (Exception $e) {
    error_log('clear_notification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
    exit();
}
?>
