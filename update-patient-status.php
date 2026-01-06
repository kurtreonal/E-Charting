<?php
header('Content-Type: application/json');
include_once 'includes/activity-logger.php';
include 'connection.php';
session_name('nurse_session');
session_start();

// Check authentication
$nurse_id = isset($_SESSION['nurse_id']) ? $_SESSION['nurse_id'] : null;

if (!$nurse_id) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Please log in'
    ]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$patient_id = isset($data['patient_id']) ? (int)$data['patient_id'] : 0;
$current_status = isset($data['current_status']) ? $data['current_status'] : '';

if ($patient_id <= 0 || empty($current_status)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid patient ID or status'
    ]);
    exit();
}

// Determine next status based on Option B logic
$next_status = '';
$action_message = '';

switch($current_status) {
    case 'in-patient':
        $next_status = 'out-patient';
        $action_message = 'Patient discharged';
        break;
    case 'active':
        $next_status = 'out-patient';
        $action_message = 'Patient discharged';
        break;
    case 'out-patient':
        $next_status = 'active';
        $action_message = 'Patient reactivated';
        break;
    case 'deceased':
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot change status of deceased patient'
        ]);
        exit();
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid status'
        ]);
        exit();
}

// Update patient status in database
$update_sql = "UPDATE patients SET patient_status = ? WHERE patient_id = ?";
$stmt = $con->prepare($update_sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $con->error
    ]);
    exit();
}

$stmt->bind_param('si', $next_status, $patient_id);

if ($stmt->execute()) {
    $stmt->close();

    // Get updated patient info
    $patient_sql = "SELECT u.first_name, u.last_name, p.patient_status
                    FROM patients p
                    JOIN users u ON p.user_id = u.user_id
                    WHERE p.patient_id = ?";
    $patient_stmt = $con->prepare($patient_sql);
    $patient_stmt->bind_param('i', $patient_id);
    $patient_stmt->execute();
    $result = $patient_stmt->get_result();
    $patient = $result->fetch_assoc();
    $patient_stmt->close();

    // Log activity
    $patient_full_name = trim($patient['first_name'] . ' ' . $patient['last_name']);
    $action_type = '';
    if ($next_status === 'out-patient' && $current_status !== 'out-patient') {
        $action_type = 'patient_discharged';
    } elseif ($next_status === 'active' && $current_status === 'out-patient') {
        $action_type = 'patient_reactivated';
    } else {
        $action_type = 'status_changed';
    }
    log_activity(
        $con,
        $nurse_id,
        $action_type,
        "$action_message for $patient_full_name",
        $patient_id,
        'patients',
        $patient_id,
        ['status' => $current_status],
        ['status' => $next_status]
    );

    echo json_encode([
        'success' => true,
        'new_status' => $next_status,
        'previous_status' => $current_status,
        'patient_name' => $patient['first_name'] . ' ' . $patient['last_name'],
        'message' => $action_message
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update status: ' . $stmt->error
    ]);
    $stmt->close();
}

$con->close();
?>
