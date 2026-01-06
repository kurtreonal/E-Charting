<?php
/**
 * Notification Helper Functions
 * Handles creation and fetching of notifications with proper security
 */

if (!function_exists('get_or_create_notification_type')) {
    /**
     * Get or create a notification type ID
     * @param mysqli $con Database connection
     * @param string $type_name Type name (e.g., 'appointment', 'medication')
     * @return int|null Notification type ID or null on failure
     */
    function get_or_create_notification_type($con, $type_name) {
        // Try to get existing type
        $stmt = $con->prepare("SELECT notification_type_id FROM notification_type WHERE notification_type = ? LIMIT 1");
        if (!$stmt) return null;

        $stmt->bind_param('s', $type_name);
        $stmt->execute();
        $stmt->bind_result($type_id);

        if ($stmt->fetch()) {
            $stmt->close();
            return (int)$type_id;
        }
        $stmt->close();

        // Create if not exists
        $stmt = $con->prepare("INSERT INTO notification_type (notification_type, created_date) VALUES (?, NOW())");
        if (!$stmt) return null;

        $stmt->bind_param('s', $type_name);
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            return (int)$new_id;
        }

        $stmt->close();
        return null;
    }
}

if (!function_exists('create_notification')) {
    /**
     * Create a new notification
     * @param mysqli $con Database connection
     * @param int $patient_id Patient ID
     * @param int|null $nurse_id Nurse ID (optional)
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int|null $appointment_id Related appointment ID (optional)
     * @param int|null $medication_id Related medication ID (optional)
     * @param string|null $scheduled_date Scheduled date/time (optional)
     * @return bool Success status
     */
    function create_notification($con, $patient_id, $nurse_id, $type, $title, $message, $appointment_id = null, $medication_id = null, $scheduled_date = null) {
        // Get notification type ID
        $type_id = get_or_create_notification_type($con, $type);
        if (!$type_id) return false;

        // Prepare SQL
        $sql = "INSERT INTO notification
                (patient_id, nurse_id, notification_type_id, title, message,
                 appointment_id, medication_id, scheduled_date, message_status,
                 is_confirmed, is_read, created_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, 0, NOW())";

        $stmt = $con->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('iiississ',
            $patient_id, $nurse_id, $type_id, $title, $message,
            $appointment_id, $medication_id, $scheduled_date
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}

if (!function_exists('generate_medication_notifications_for_patient')) {
    /**
     * Generate medication reminder notifications for a specific patient
     * @param mysqli $con Database connection
     * @param int $patient_id Patient ID
     * @param int $lookahead_minutes How many minutes ahead to check for doses
     * @return int Number of notifications created
     */
    function generate_medication_notifications_for_patient($con, $patient_id, $lookahead_minutes = 5) {
        $notifications_created = 0;
        $now = new DateTime();
        $lookahead = (clone $now)->modify("+{$lookahead_minutes} minutes");

        // Fetch active medications for this patient
        $sql = "SELECT medication_id, nurse_id, medication_name, dose,
                       times_per_day, interval_minutes,
                       start_datetime, date_prescribed
                FROM medication
                WHERE patient_id = ?
                AND (times_per_day > 0 OR interval_minutes > 0)";

        $stmt = $con->prepare($sql);
        if (!$stmt) return 0;

        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($med = $result->fetch_assoc()) {
            // Determine base time
            $base_time = !empty($med['start_datetime'])
                ? new DateTime($med['start_datetime'])
                : new DateTime($med['date_prescribed']);

            // Calculate interval in seconds
            $interval_seconds = 0;
            if (!empty($med['interval_minutes']) && $med['interval_minutes'] > 0) {
                $interval_seconds = $med['interval_minutes'] * 60;
            } elseif (!empty($med['times_per_day']) && $med['times_per_day'] > 0) {
                $interval_seconds = (24 * 3600) / $med['times_per_day'];
            }

            if ($interval_seconds <= 0) continue;

            // Calculate next dose time
            $next_dose = clone $base_time;
            while ($next_dose <= $now) {
                $next_dose->modify("+{$interval_seconds} seconds");
            }

            // Check if next dose is within lookahead window
            if ($next_dose >= $now && $next_dose <= $lookahead) {
                // Check if notification already exists for this dose time
                $check_sql = "SELECT notification_id
                             FROM notification
                             WHERE patient_id = ?
                             AND medication_id = ?
                             AND scheduled_date = ?
                             LIMIT 1";

                $check_stmt = $con->prepare($check_sql);
                $dose_time_str = $next_dose->format('Y-m-d H:i:s');
                $check_stmt->bind_param('iis', $patient_id, $med['medication_id'], $dose_time_str);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows === 0) {
                    // Create notification
                    $title = "Medication Reminder";
                    $message = "Time to take {$med['medication_name']} ({$med['dose']})";

                    if (create_notification(
                        $con,
                        $patient_id,
                        $med['nurse_id'],
                        'medication',
                        $title,
                        $message,
                        null,
                        $med['medication_id'],
                        $dose_time_str
                    )) {
                        $notifications_created++;
                    }
                }

                $check_stmt->close();
            }
        }

        $stmt->close();
        return $notifications_created;
    }
}

if (!function_exists('fetch_notifications_for_patient')) {
    /**
     * Fetch notifications for a specific patient (SECURE)
     * @param mysqli $con Database connection
     * @param int $patient_id Patient ID
     * @param int $limit Maximum number of notifications to fetch
     * @return array Array of notifications
     */
    function fetch_notifications_for_patient($con, $patient_id, $limit = 50) {
        $sql = "SELECT n.notification_id, n.title, n.message, n.created_date,
                       n.is_read, n.is_confirmed, n.message_status,
                       n.appointment_id, n.medication_id, n.scheduled_date,
                       nt.notification_type
                FROM notification n
                LEFT JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
                WHERE n.patient_id = ?
                ORDER BY n.is_read ASC, n.created_date DESC
                LIMIT ?";

        $stmt = $con->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param('ii', $patient_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $stmt->close();
        return $notifications;
    }
}

if (!function_exists('fetch_notifications_for_nurse')) {
    /**
     * Fetch notifications for a specific nurse (SECURE)
     * @param mysqli $con Database connection
     * @param int $nurse_id Nurse ID
     * @param int|null $patient_id Optional patient ID filter
     * @param int $limit Maximum number of notifications
     * @return array Array of notifications
     */
    function fetch_notifications_for_nurse($con, $nurse_id, $patient_id = null, $limit = 50) {
        if ($patient_id !== null) {
            // Fetch for specific patient under this nurse's care
            $sql = "SELECT n.notification_id, n.title, n.message, n.created_date,
                           n.is_read, n.is_confirmed, n.message_status,
                           n.appointment_id, n.medication_id, n.scheduled_date,
                           nt.notification_type
                    FROM notification n
                    LEFT JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
                    WHERE n.patient_id = ?
                    AND (n.nurse_id = ? OR n.medication_id IS NOT NULL)
                    ORDER BY n.is_read ASC, n.created_date DESC
                    LIMIT ?";

            $stmt = $con->prepare($sql);
            $stmt->bind_param('iii', $patient_id, $nurse_id, $limit);
        } else {
            // Fetch all notifications for this nurse
            $sql = "SELECT n.notification_id, n.title, n.message, n.created_date,
                           n.is_read, n.is_confirmed, n.message_status,
                           n.appointment_id, n.medication_id, n.scheduled_date,
                           nt.notification_type
                    FROM notification n
                    LEFT JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
                    WHERE n.nurse_id = ?
                    ORDER BY n.is_read ASC, n.created_date DESC
                    LIMIT ?";

            $stmt = $con->prepare($sql);
            $stmt->bind_param('ii', $nurse_id, $limit);
        }

        if (!$stmt) return [];

        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $stmt->close();
        return $notifications;
    }
}

if (!function_exists('count_unread_notifications')) {
    /**
     * Count unread notifications for a user
     * @param mysqli $con Database connection
     * @param int $user_id User ID (patient or nurse)
     * @param string $user_type 'patient' or 'nurse'
     * @param int|null $patient_id Optional patient filter (for nurses)
     * @return int Unread count
     */
    function count_unread_notifications($con, $user_id, $user_type, $patient_id = null) {
        if ($user_type === 'patient') {
            $sql = "SELECT COUNT(*) FROM notification WHERE patient_id = ? AND is_read = 0";
            $stmt = $con->prepare($sql);
            $stmt->bind_param('i', $user_id);
        } elseif ($user_type === 'nurse' && $patient_id !== null) {
            $sql = "SELECT COUNT(*) FROM notification
                    WHERE patient_id = ?
                    AND (nurse_id = ? OR medication_id IS NOT NULL)
                    AND is_read = 0";
            $stmt = $con->prepare($sql);
            $stmt->bind_param('ii', $patient_id, $user_id);
        } else {
            $sql = "SELECT COUNT(*) FROM notification WHERE nurse_id = ? AND is_read = 0";
            $stmt = $con->prepare($sql);
            $stmt->bind_param('i', $user_id);
        }

        if (!$stmt) return 0;

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count;
    }
}

if (!function_exists('mark_notification_read')) {
    /**
     * Mark a notification as read (with security check)
     * @param mysqli $con Database connection
     * @param int $notification_id Notification ID
     * @param int $user_id User ID (patient or nurse)
     * @param string $user_type 'patient' or 'nurse'
     * @return bool Success status
     */
    function mark_notification_read($con, $notification_id, $user_id, $user_type) {
        if ($user_type === 'patient') {
            $sql = "UPDATE notification SET is_read = 1
                    WHERE notification_id = ? AND patient_id = ?";
        } else {
            $sql = "UPDATE notification SET is_read = 1
                    WHERE notification_id = ? AND nurse_id = ?";
        }

        $stmt = $con->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param('ii', $notification_id, $user_id);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}
