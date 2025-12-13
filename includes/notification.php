<?php
// includes/notification.php
// Safe idempotent helper for notification types and fetching notifications.
// Usage: include_once 'includes/notification.php'; // ensure $con is available in calling script

if (!function_exists('get_or_create_notification_type_id')) {
    function get_or_create_notification_type_id($con, $type_name) {
        $sql = "SELECT notification_type_id FROM notification_type WHERE notification_type = ? LIMIT 1";
        if ($stm = $con->prepare($sql)) {
            $stm->bind_param('s', $type_name);
            $stm->execute();
            $stm->bind_result($id);
            if ($stm->fetch()) {
                $stm->close();
                return (int)$id;
            }
            $stm->close();
        }

        // Insert (if unique constraint exists race will be handled by later select)
        $ins = "INSERT INTO notification_type (notification_type, created_date) VALUES (?, NOW())";
        if ($ins_stm = $con->prepare($ins)) {
            $ins_stm->bind_param('s', $type_name);
            if ($ins_stm->execute()) {
                $new_id = $ins_stm->insert_id;
                $ins_stm->close();
                return (int)$new_id;
            }
            $ins_stm->close();
        }

        // fallback select in case of race condition
        if ($stm2 = $con->prepare($sql)) {
            $stm2->bind_param('s', $type_name);
            $stm2->execute();
            $stm2->bind_result($id2);
            if ($stm2->fetch()) {
                $stm2->close();
                return (int)$id2;
            }
            $stm2->close();
        }

        return null;
    }
}

if (!function_exists('fetch_notifications_for_patient')) {
    /**
     * Fetch notifications belonging to a specific patient.
     * Returns array of rows.
     */
    function fetch_notifications_for_patient($con, $patient_id, $limit = null) {
        $notifications = [];
        $sql = "
            SELECT n.notification_id, n.title, n.message, n.created_date, n.is_read, n.is_confirmed,
                   n.appointment_id, n.notification_type_id, nt.notification_type
            FROM notification n
            LEFT JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
            WHERE n.patient_id = ?
            ORDER BY n.created_date DESC
        ";
        if (!is_null($limit) && is_numeric($limit) && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param('i', $patient_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) $notifications[] = $r;
            $stmt->close();
        }
        return $notifications;
    }
}

if (!function_exists('fetch_all_notifications')) {
    /**
     * Fetch all notifications (for landing page or admin dashboards).
     * Returns array of rows.
     */
    function fetch_all_notifications($con, $limit = 50) {
        $notifications = [];
        $sql = "
            SELECT n.notification_id, n.title, n.message, n.created_date, n.is_read, n.is_confirmed,
                   n.appointment_id, n.patient_id, nt.notification_type
            FROM notification n
            LEFT JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
            ORDER BY n.created_date DESC
            LIMIT ?
        ";
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param('i', $limit);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) $notifications[] = $r;
            $stmt->close();
        }
        return $notifications;
    }
}

if (!function_exists('mark_notification_read_for_patient')) {
    function mark_notification_read_for_patient($con, $notification_id, $patient_id) {
        if ($stmt = $con->prepare("UPDATE notification SET is_read = 1 WHERE notification_id = ? AND patient_id = ?")) {
            $stmt->bind_param('ii', $notification_id, $patient_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected === 1;
        }
        return false;
    }
}

if (!function_exists('confirm_patient_appointment_notification')) {
    function confirm_patient_appointment_notification($con, $notification_id, $patient_id, $appointment_id) {
        if ($stmt = $con->prepare("UPDATE notification SET is_confirmed = 1, message_status = 'confirmed', is_read = 1 WHERE notification_id = ? AND patient_id = ? AND appointment_id = ?")) {
            $stmt->bind_param('iii', $notification_id, $patient_id, $appointment_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return $affected === 1;
        }
        return false;
    }
}

if (!function_exists('medication_notification_exists')) {
    /**
     * Check if a notification for this medication at this scheduled date already exists
     */
    function medication_notification_exists($con, $patient_id, $medication_id, $scheduled_date) {
        $sql = "SELECT notification_id FROM notification
                WHERE patient_id = ? AND medication_id = ? AND scheduled_date = ? LIMIT 1";
        if ($stm = $con->prepare($sql)) {
            $stm->bind_param('iis', $patient_id, $medication_id, $scheduled_date);
            $stm->execute();
            $stm->store_result();
            $exists = ($stm->num_rows === 1);
            $stm->close();
            return $exists;
        }
        return false;
    }
}

if (!function_exists('generate_medication_notifications_for_patient')) {
    /**
     * Generate medication reminder notifications for a patient.
     * - $ahead_minutes: look ahead X minutes for upcoming doses (use 0 to only include <= NOW()).
     */
    function generate_medication_notifications_for_patient($con, $patient_id, $ahead_minutes = 5) {
        // ensure view medication_next_dose exists in your DB (you provided it)
        $sql = "
            SELECT medication_id, next_dose
            FROM medication_next_dose
            WHERE patient_id = ?
              AND next_dose IS NOT NULL
              AND next_dose <= DATE_ADD(NOW(), INTERVAL ? MINUTE)
              AND next_dose >= DATE_SUB(NOW(), INTERVAL 1440 DAY) -- safety: avoid super old rows
            ORDER BY next_dose ASC
        ";
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param('ii', $patient_id, $ahead_minutes);
            $stmt->execute();
            $res = $stmt->get_result();
            $notif_type_id = get_or_create_notification_type_id($con, 'medication_reminder');

            while ($row = $res->fetch_assoc()) {
                $med_id = (int)$row['medication_id'];
                $scheduled = $row['next_dose']; // DATETIME string

                // skip if a notification for this med+scheduled time already exists
                if (medication_notification_exists($con, $patient_id, $med_id, $scheduled)) {
                    continue;
                }

                // prepare title & message (adjust wording as you like)
                $title = 'Medication Reminder';
                $message = "It's time to take medication scheduled at " . date("F j, Y, g:i A", strtotime($scheduled)) . ".";

                // insert notification (is_read = 0, message_status = 'pending' by default)
                $insSql = "INSERT INTO notification
                    (patient_id, nurse_id, appointment_id, medication_id, notification_type_id, title, message, message_status, is_confirmed, is_read, scheduled_date, created_date)
                    VALUES (?, NULL, NULL, ?, ?, ?, ?, 'pending', 0, 0, ?, NOW())";
                if ($ins = $con->prepare($insSql)) {
                    $ins->bind_param('iiisss', $patient_id, $med_id, $notif_type_id, $title, $message, $scheduled);
                    $ins->execute();
                    $ins->close();
                }
            }

            $stmt->close();
        }
    }
}

?>
