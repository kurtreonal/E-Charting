<?php
// fetch_notifications.php
header('Content-Type: application/json; charset=utf-8');
session_start();

try {
    include 'connection.php';
    if (!isset($con) || !($con instanceof mysqli)) {
        throw new Exception('Database connection ($con) not available.');
    }

    // Include notification helper functions
    include_once 'includes/notification.php';

    // Determine user context
    $context = null;
    $session_id = null; // nurse_id or patient_id depending on context
    if (!empty($_SESSION['nurse_id'])) {
        $context = 'nurse';
        $session_id = (int) $_SESSION['nurse_id'];
    } elseif (!empty($_SESSION['patient_id'])) {
        $context = 'patient';
        $session_id = (int) $_SESSION['patient_id'];
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // Optional target patient id (GET, POST, or session)
    $target_patient_id = null;
    if (!empty($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
        $target_patient_id = (int) $_GET['patient_id'];
    } elseif (!empty($_POST['patient_id']) && is_numeric($_POST['patient_id'])) {
        $target_patient_id = (int) $_POST['patient_id'];
    } elseif (!empty($_SESSION['selected_patient_id'])) {
        $target_patient_id = (int) $_SESSION['selected_patient_id'];
    }

    // Generate medication reminders if needed
    $generate_for_patient_id = null;
    if ($context === 'nurse' && $target_patient_id !== null) {
        $generate_for_patient_id = $target_patient_id;
    } elseif ($context === 'patient') {
        $generate_for_patient_id = $session_id;
    }
    $ahead_minutes = 5; // lookahead window
    if ($generate_for_patient_id !== null && function_exists('generate_medication_notifications_for_patient')) {
        generate_medication_notifications_for_patient($con, $generate_for_patient_id, $ahead_minutes);
    }

    // Build SQL query - explicitly select all important notification fields
    $sql_base = "
        SELECT
            n.notification_id,
            n.patient_id,
            n.nurse_id,
            n.appointment_id,
            n.medication_id,
            n.notification_type_id,
            n.title,
            n.message,
            n.message_status,
            n.is_confirmed,
            n.is_read,
            n.scheduled_date,
            n.created_date,
            nt.notification_type
        FROM notification n
        LEFT JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
    ";

    if ($context === 'nurse') {
        if ($target_patient_id !== null) {
            // For a specific patient: show appointments (nurse_id = ?) + all medications for that patient
            $sql = $sql_base . "
                WHERE n.patient_id = ? AND (
                    (n.appointment_id IS NOT NULL AND n.nurse_id = ?)
                    OR (n.medication_id IS NOT NULL)
                )
                ORDER BY n.is_read ASC, n.created_date DESC
                LIMIT 50
            ";
            $stmt = $con->prepare($sql);
            $stmt->bind_param('ii', $target_patient_id, $session_id);
        } else {
            // For all patients: show appointments created by this nurse + all medication notifications
            $sql = $sql_base . "
                WHERE (n.nurse_id = ? AND n.appointment_id IS NOT NULL)
                   OR (n.medication_id IS NOT NULL)
                ORDER BY n.is_read ASC, n.created_date DESC
                LIMIT 50
            ";
            $stmt = $con->prepare($sql);
            $stmt->bind_param('i', $session_id);
        }
    } else {
        // patient context - get ALL notification types (appointments + medications + others)
        $sql = $sql_base . "
            WHERE n.patient_id = ?
            ORDER BY n.is_read ASC, n.created_date DESC
            LIMIT 50
        ";
        $stmt = $con->prepare($sql);
        $stmt->bind_param('i', $session_id);
    }

    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
    $res = $stmt->get_result();
    $notifications = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    // Normalize and enhance notifications
    foreach ($notifications as &$n) {
        // Ensure numeric casting
        $n['is_confirmed'] = isset($n['is_confirmed']) ? (int)$n['is_confirmed'] : 0;
        $n['is_read'] = isset($n['is_read']) ? (int)$n['is_read'] : 0;
        $n['appointment_id'] = isset($n['appointment_id']) ? (int)$n['appointment_id'] : null;
        $n['medication_id'] = isset($n['medication_id']) ? (int)$n['medication_id'] : null;

        // Set default status if not set
        if ((!isset($n['message_status']) || $n['message_status'] === '') && isset($n['is_confirmed'])) {
            $n['message_status'] = $n['is_confirmed'] === 1 ? 'confirmed' : 'pending';
        }

        // Format appointment notifications
        if ($n['appointment_id'] && $n['appointment_id'] > 0) {
            if (!empty($n['scheduled_date'])) {
                $datetime = date("F j, Y \a\\t g:i A", strtotime($n['scheduled_date']));
                if ($context === 'patient') {
                    $n['message'] = "You have an appointment on $datetime";
                } else {
                    $n['message'] = "Patient appointment on $datetime";
                }
            }
            $n['title'] = 'Appointment';
        }
        // Format medication notifications
        elseif ($n['medication_id'] && $n['medication_id'] > 0) {
            // If message already exists and is not empty, use it; otherwise build from medication data
            if (empty($n['message'])) {
                $mid = $n['medication_id'];
                $mdStmt = $con->prepare("SELECT medication_name, dose FROM medication WHERE medication_id = ? LIMIT 1");
                if ($mdStmt) {
                    $mdStmt->bind_param('i', $mid);
                    if ($mdStmt->execute()) {
                        $mdRes = $mdStmt->get_result();
                        if ($medRow = $mdRes->fetch_assoc()) {
                            $scheduled = !empty($n['scheduled_date']) ? $n['scheduled_date'] : $n['created_date'];
                            $n['message'] = "Take {$medRow['medication_name']} ({$medRow['dose']}) scheduled at " . date("g:i A", strtotime($scheduled));
                        }
                    }
                    $mdStmt->close();
                }
            }
            // Ensure title is set
            if (empty($n['title'])) {
                $n['title'] = 'Medication Reminder';
            }
        }
    }
    unset($n);

    // Unread count - FIXED: removed n. alias from notification table
    $unread = 0;
    if ($context === 'nurse') {
        if ($target_patient_id !== null) {
            $countSql = "SELECT COUNT(*) FROM notification WHERE patient_id = ? AND ((appointment_id IS NOT NULL AND nurse_id = ?) OR (medication_id IS NOT NULL)) AND is_read = 0";
            $s2 = $con->prepare($countSql);
            $s2->bind_param('ii', $target_patient_id, $session_id);
        } else {
            $countSql = "SELECT COUNT(*) FROM notification WHERE ((nurse_id = ? AND appointment_id IS NOT NULL) OR (medication_id IS NOT NULL)) AND is_read = 0";
            $s2 = $con->prepare($countSql);
            $s2->bind_param('i', $session_id);
        }
    } else {
        $countSql = "SELECT COUNT(*) FROM notification WHERE patient_id = ? AND is_read = 0";
        $s2 = $con->prepare($countSql);
        $s2->bind_param('i', $session_id);
    }

    if ($s2->execute()) {
        $s2->bind_result($c);
        if ($s2->fetch()) $unread = (int)$c;
    }
    $s2->close();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread
    ]);
    exit;

} catch (Exception $e) {
    error_log("fetch_notifications error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to fetch notifications.']);
    exit;
}
?>
