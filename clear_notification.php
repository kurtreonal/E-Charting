<?php
header('Content-Type: application/json; charset=utf-8');

include 'connection.php';
include_once 'includes/notification.php';

try {
    //referer
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

    //check if call is from admin/nurse pages
    $is_admin_page = (
        strpos($referer, 'adm-') !== false ||
        strpos($referer, 'admin-') !== false ||
        strpos($referer, 'add-patient') !== false ||
        strpos($referer, 'create-appointment') !== false ||
        strpos($referer, 'update-patient') !== false
    );

    //check if call is from patient pages
    $is_patient_page = (
        strpos($referer, 'patient-profile') !== false ||
        strpos($referer, 'landingpage') !== false ||
        strpos($referer, 'login.php') !== false
    );

    if ($is_admin_page && !$is_patient_page) {
        //called from nurse/admin page
        session_name('nurse_session');
        session_start();

        if (empty($_SESSION['nurse_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized'
            ]);
            exit;
        }

        $user_id = (int)$_SESSION['nurse_id'];
        $user_type = 'nurse';

    } elseif ($is_patient_page && !$is_admin_page) {
        //called from patient page
        session_name('patient_session');
        session_start();

        if (empty($_SESSION['patient_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized'
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
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Session expired'
                ]);
                exit;
            }

            $user_id = (int)$_SESSION['nurse_id'];
            $user_type = 'nurse';

        } elseif ($has_patient_cookie && !$has_nurse_cookie) {
            session_name('patient_session');
            session_start();

            if (empty($_SESSION['patient_id'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Session expired'
                ]);
                exit;
            }

            $user_id = (int)$_SESSION['patient_id'];
            $user_type = 'patient';

        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Ambiguous or missing session'
            ]);
            exit;
        }
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!is_array($data)) {
        $data = $_POST;
    }

    if (!empty($data['notification_id']) && !empty($data['mark_read'])) {
        $notification_id = (int)$data['notification_id'];

        if (mark_notification_read($con, $notification_id, $user_id, $user_type)) {
            echo json_encode([
                'success' => true,
                'action' => 'marked_single'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to mark notification as read'
            ]);
        }
        exit;
    }

    //handle mark all as read
    if (!empty($data['mark_all_read'])) {
        $target_patient_id = null;

        //for nurses, check if filtering by patient
        if ($user_type === 'nurse' && !empty($data['patient_id'])) {
            $target_patient_id = (int)$data['patient_id'];
        }

        //build SQL based on user type
        if ($user_type === 'patient') {
            $sql = "UPDATE notification SET is_read = 1
                    WHERE patient_id = ? AND is_read = 0";
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->bind_param('i', $user_id);
        } elseif ($target_patient_id !== null) {
            $sql = "UPDATE notification SET is_read = 1
                    WHERE patient_id = ?
                    AND (nurse_id = ? OR medication_id IS NOT NULL)
                    AND is_read = 0";
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->bind_param('ii', $target_patient_id, $user_id);
        } else {
            $sql = "UPDATE notification SET is_read = 1
                    WHERE nurse_id = ? AND is_read = 0";
            $stmt = $con->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            $stmt->bind_param('i', $user_id);
        }

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();

            echo json_encode([
                'success' => true,
                'action' => 'marked_all_read',
                'affected' => $affected
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to mark notifications as read'
            ]);
        }
        exit;
    }

    //invalid request
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid action'
    ]);

} catch (Exception $e) {
    error_log("clear_notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
exit;