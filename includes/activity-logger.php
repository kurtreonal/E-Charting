<?php
/**
 * Activity Logger - Helper Functions (FIXED VERSION)
 * Log all nurse actions in the system
 *
 * FIXES:
 * - Improved parameter binding
 * - Better error handling
 * - Fixed empty result handling
 */

/**
 * Log an activity/action performed by a nurse
 */
function log_activity($con, $nurse_id, $action_type, $action_description, $patient_id = null, $affected_table = null, $record_id = null, $old_values = null, $new_values = null) {

    // Get IP address
    $ip_address = get_client_ip();

    // Get user agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

    // Convert arrays to JSON
    $old_values_json = $old_values ? json_encode($old_values) : null;
    $new_values_json = $new_values ? json_encode($new_values) : null;

    // Prepare SQL statement
    $stmt = $con->prepare("
        INSERT INTO activity_log
        (nurse_id, action_type, action_description, patient_id, affected_table, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Activity log prepare failed: " . $con->error);
        return false;
    }

    $stmt->bind_param(
        "ississssss",
        $nurse_id,
        $action_type,
        $action_description,
        $patient_id,
        $affected_table,
        $record_id,
        $old_values_json,
        $new_values_json,
        $ip_address,
        $user_agent
    );

    $result = $stmt->execute();

    if (!$result) {
        error_log("Activity log execution failed: " . $stmt->error);
    }

    $stmt->close();
    return $result;
}

/**
 * Get client's IP address
 */
function get_client_ip() {
    $ip = '';

    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    return substr($ip, 0, 45); // Limit to 45 chars (for IPv6)
}

/**
 * Get recent activity logs (FIXED VERSION)
 *
 * @param mysqli $con Database connection
 * @param int|null $nurse_id Filter by specific nurse (optional)
 * @param int|null $patient_id Filter by specific patient (optional)
 * @param string|null $action_type Filter by action type (optional)
 * @param int $limit Number of records to return
 * @return array Array of activity logs
 */
function get_activity_logs($con, $nurse_id = null, $patient_id = null, $action_type = null, $limit = 50) {

    $conditions = [];
    $params = [];
    $types = '';

    if ($nurse_id) {
        $conditions[] = "al.nurse_id = ?";
        $params[] = $nurse_id;
        $types .= 'i';
    }

    if ($patient_id) {
        $conditions[] = "al.patient_id = ?";
        $params[] = $patient_id;
        $types .= 'i';
    }

    if ($action_type) {
        $conditions[] = "al.action_type = ?";
        $params[] = $action_type;
        $types .= 's';
    }

    $where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "
        SELECT
            al.log_id,
            al.nurse_id,
            CONCAT(COALESCE(nu.first_name, 'Unknown'), ' ', COALESCE(nu.last_name, 'Nurse')) AS nurse_name,
            al.action_type,
            al.action_description,
            al.patient_id,
            CASE
                WHEN al.patient_id IS NOT NULL THEN
                    CONCAT(COALESCE(pu.first_name, 'Unknown'), ' ',
                           IFNULL(CONCAT(pu.middle_name, ' '), ''),
                           COALESCE(pu.last_name, 'Patient'))
                ELSE NULL
            END AS patient_name,
            al.affected_table,
            al.record_id,
            al.old_values,
            al.new_values,
            al.ip_address,
            al.created_at
        FROM activity_log al
        LEFT JOIN nurse n ON al.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        LEFT JOIN patients p ON al.patient_id = p.patient_id
        LEFT JOIN users pu ON p.user_id = pu.user_id
        $where_clause
        ORDER BY al.created_at DESC
        LIMIT ?
    ";

    $stmt = $con->prepare($sql);

    if (!$stmt) {
        error_log("Get activity logs prepare failed: " . $con->error);
        return [];
    }

    // Add limit to parameters
    $params[] = $limit;
    $types .= 'i';

    // Bind parameters - always bind since we always have at least limit
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Get activity logs execution failed: " . $stmt->error);
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();

    $logs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    $stmt->close();
    return $logs;
}

/**
 * Get activity statistics
 *
 * @param mysqli $con Database connection
 * @param int|null $nurse_id Filter by nurse (optional)
 * @param string $period Time period ('today', 'week', 'month', 'year')
 * @return array Statistics array
 */
function get_activity_stats($con, $nurse_id = null, $period = 'today') {

    // Determine date range
    switch ($period) {
        case 'today':
            $date_condition = "DATE(al.created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'year':
            $date_condition = "al.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_condition = "1=1";
    }

    $nurse_condition = $nurse_id ? "AND al.nurse_id = " . intval($nurse_id) : "";

    $sql = "
        SELECT
            action_type,
            COUNT(*) as count
        FROM activity_log al
        WHERE $date_condition $nurse_condition
        GROUP BY action_type
        ORDER BY count DESC
    ";

    $result = $con->query($sql);

    $stats = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stats[$row['action_type']] = $row['count'];
        }
    }

    return $stats;
}

/**
 * Format action type for display
 *
 * @param string $action_type Action type from database
 * @return string Formatted action type
 */
function format_action_type($action_type) {
    $formatted = [
        'patient_created' => 'Patient Created',
        'patient_updated' => 'Patient Updated',
        'patient_admitted' => 'Patient Admitted',
        'patient_discharged' => 'Patient Discharged',
        'patient_reactivated' => 'Patient Reactivated',
        'appointment_created' => 'Appointment Created',
        'appointment_updated' => 'Appointment Updated',
        'appointment_deleted' => 'Appointment Deleted',
        'medication_added' => 'Medication Added',
        'medication_updated' => 'Medication Updated',
        'medication_deleted' => 'Medication Deleted',
        'status_changed' => 'Status Changed',
        'login' => 'Login',
        'logout' => 'Logout',
        'password_reset' => 'Password Reset'
    ];

    return isset($formatted[$action_type]) ? $formatted[$action_type] : ucwords(str_replace('_', ' ', $action_type));
}

/**
 * Get action icon for display
 *
 * @param string $action_type Action type from database
 * @return string Font Awesome icon class
 */
function get_action_icon($action_type) {
    $icons = [
        'patient_created' => 'fa-user-plus',
        'patient_updated' => 'fa-user-edit',
        'patient_admitted' => 'fa-hospital',
        'patient_discharged' => 'fa-door-open',
        'patient_reactivated' => 'fa-check-circle',
        'appointment_created' => 'fa-calendar-plus',
        'appointment_updated' => 'fa-calendar-edit',
        'appointment_deleted' => 'fa-calendar-times',
        'medication_added' => 'fa-pills',
        'medication_updated' => 'fa-prescription',
        'medication_deleted' => 'fa-trash-alt',
        'status_changed' => 'fa-exchange-alt',
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'password_reset' => 'fa-key'
    ];

    return isset($icons[$action_type]) ? $icons[$action_type] : 'fa-clipboard';
}

/**
 * Get action color for display
 *
 * @param string $action_type Action type from database
 * @return string CSS color class or hex color
 */
function get_action_color($action_type) {
    $colors = [
        'patient_created' => '#28a745',    // Green
        'patient_updated' => '#007bff',    // Blue
        'patient_admitted' => '#17a2b8',   // Cyan
        'patient_discharged' => '#cd9a00', // Yellow
        'patient_reactivated' => '#28a745',// Green
        'appointment_created' => '#6f42c1',// Purple
        'appointment_updated' => '#6c757d',// Gray
        'appointment_deleted' => '#dc3545', // Red
        'medication_added' => '#20c997',   // Teal
        'medication_updated' => '#fd7e14',  // Orange
        'medication_deleted' => '#dc3545', // Red
        'status_changed' => '#007bff',     // Blue
        'login' => '#28a745',              // Green
        'logout' => '#6c757d',             // Gray
        'password_reset' => '#cd9a00'      // Yellow
    ];

    return isset($colors[$action_type]) ? $colors[$action_type] : '#6c757d';
}

?>
