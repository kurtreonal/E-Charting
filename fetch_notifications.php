<?php
header('Content-Type: application/json; charset=utf-8');

include 'connection.php';
include_once 'includes/notification.php';

try {
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $is_admin_page = (
        strpos($referer, 'adm-') !== false ||
        strpos($referer, 'admin-') !== false ||
        strpos($referer, 'add-patient') !== false ||
        strpos($referer, 'create-appointment') !== false ||
        strpos($referer, 'update-patient') !== false
    );

    $is_patient_page = (
        strpos($referer, 'patient-profile') !== false ||
        strpos($referer, 'landingpage') !== false ||
        strpos($referer, 'login.php') !== false
    );

    if ($is_admin_page && !$is_patient_page) {
        session_name('nurse_session');
        session_start();

        if (empty($_SESSION['nurse_id'])) {
            echo json_encode([
                'success' => true,
                'notifications' => [],
                'unread_count' => 0,
                'message' => 'Not logged in'
            ]);
            exit;
        }

        $user_id = (int)$_SESSION['nurse_id'];
        $user_type = 'nurse';

    } elseif ($is_patient_page && !$is_admin_page) {
        session_name('patient_session');
        session_start();

        if (empty($_SESSION['patient_id'])) {
            echo json_encode([
                'success' => true,
                'notifications' => [],
                'unread_count' => 0,
                'message' => 'Not logged in'
            ]);
            exit;
        }

        $user_id = (int)$_SESSION['patient_id'];
        $user_type = 'patient';

    } else {
        $has_nurse_cookie = isset($_COOKIE['nurse_session']);
        $has_patient_cookie = isset($_COOKIE['patient_session']);
        if ($has_nurse_cookie && !$has_patient_cookie) {
            session_name('nurse_session');
            session_start();

            if (empty($_SESSION['nurse_id'])) {
                echo json_encode([
                    'success' => true,
                    'notifications' => [],
                    'unread_count' => 0,
                    'message' => 'Session expired'
                ]);
                exit;
            }

            $user_id = (int)$_SESSION['nurse_id'];
            $user_type = 'nurse';

        } elseif ($has_patient_cookie && !$has_nurse_cookie) {
            session_name('patient_session');
            session_start();

            if (empty($_SESSION['patient_id'])) {
                echo json_encode([
                    'success' => true,
                    'notifications' => [],
                    'unread_count' => 0,
                    'message' => 'Session expired'
                ]);
                exit;
            }

            $user_id = (int)$_SESSION['patient_id'];
            $user_type = 'patient';

        } else {
            echo json_encode([
                'success' => true,
                'notifications' => [],
                'unread_count' => 0,
                'message' => 'Not logged in or ambiguous session'
            ]);
            exit;
        }
    }

    $notifications = [];
    $unread_count = 0;

    if ($user_type === 'patient') {
        $patient_id = $user_id;
        generate_medication_notifications_for_patient($con, $patient_id, 5);
        $notifications = fetch_notifications_for_patient($con, $patient_id, 50);
        $unread_count = count_unread_notifications($con, $patient_id, 'patient');

    } else {
        $nurse_id = $user_id;

        $target_patient_id = null;
        if (!empty($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
            $target_patient_id = (int)$_GET['patient_id'];
            generate_medication_notifications_for_patient($con, $target_patient_id, 5);
        }
        $notifications = fetch_notifications_for_nurse($con, $nurse_id, $target_patient_id, 50);
        $unread_count = count_unread_notifications($con, $nurse_id, 'nurse', $target_patient_id);
    }
    foreach ($notifications as &$notif) {
        $notif['is_read'] = (int)$notif['is_read'];
        $notif['is_confirmed'] = (int)$notif['is_confirmed'];
        $notif['appointment_id'] = $notif['appointment_id'] ? (int)$notif['appointment_id'] : null;
        $notif['medication_id'] = $notif['medication_id'] ? (int)$notif['medication_id'] : null;
        if ($notif['appointment_id'] && !empty($notif['scheduled_date'])) {
            $datetime = date("F j, Y \a\\t g:i A", strtotime($notif['scheduled_date']));
            if ($user_type === 'patient') {
                $notif['message'] = "You have an appointment on $datetime";
            } else {
                $notif['message'] = "Patient appointment on $datetime";
            }
            $notif['title'] = 'Appointment';
        }
        if ($notif['medication_id']) {
            if (empty($notif['message']) || strpos($notif['message'], 'Time to take') === false) {
                $med_stmt = $con->prepare("SELECT medication_name, dose FROM medication WHERE medication_id = ? LIMIT 1");
                if ($med_stmt) {
                    $med_stmt->bind_param('i', $notif['medication_id']);
                    $med_stmt->execute();
                    $med_result = $med_stmt->get_result();

                    if ($med_row = $med_result->fetch_assoc()) {
                        $scheduled = !empty($notif['scheduled_date']) ? $notif['scheduled_date'] : $notif['created_date'];
                        $time_str = date("g:i A", strtotime($scheduled));
                        $notif['message'] = "Time to take {$med_row['medication_name']} ({$med_row['dose']}) at {$time_str}";
                    }

                    $med_stmt->close();
                }
            }

            if (empty($notif['title'])) {
                $notif['title'] = 'Medication Reminder';
            }
        }
    }
    unset($notif);

    //return response
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'user_type' => $user_type
    ]);

} catch (Exception $e) {
    error_log("fetch_notifications error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch notifications.',
        'message' => $e->getMessage()
    ]);
}
exit;