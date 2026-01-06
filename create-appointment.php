<?php
include 'authcheck.php';
include_once 'includes/activity-logger.php';
include 'connection.php';
include_once 'includes/notification.php';

//require nurse login
$nurse_id = (int) $_SESSION['nurse_id'];

//check patient_id
$patient_id = null;
if (!empty($_GET['patient_id'])) {
    $patient_id = (int) $_GET['patient_id'];
} elseif (!empty($_POST['patient_id'])) {
    $patient_id = (int) $_POST['patient_id'];
} else {
    if (!empty($_SESSION['selected_patient_id'])) {
        $patient_id = (int) $_SESSION['selected_patient_id'];
    } else {
        die('Patient not specified.');
    }
}

//helper to fetch all assoc rows from prepared stmt
function fetch_all_assoc($stmt) {
    $res = $stmt->get_result();
    if (!$res) return [];
    return $res->fetch_all(MYSQLI_ASSOC);
}

function get_or_create_notification_type_id($con, $type_name) {
    //attempt select
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

    //if not found, insert
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

//create appointment POST
$createAppointmentError = '';
$createAppointmentSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_appointment') {
    $appt_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : '';
    $appt_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
    $posted_patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $posted_nurse_id = isset($_POST['nurse_id']) ? (int)$_POST['nurse_id'] : 0;

    if ($posted_patient_id !== $patient_id) {
        $createAppointmentError = 'Patient mismatch.';
    } elseif ($posted_nurse_id !== $nurse_id) {
        $createAppointmentError = 'Nurse mismatch.';
    } elseif (empty($appt_date) || empty($appt_time)) {
        $createAppointmentError = 'Please provide date and time for appointment.';
    } else {
        $sql = "INSERT INTO appointment (nurse_id, patient_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)";
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param('iiss', $nurse_id, $patient_id, $appt_date, $appt_time);
            if ($stmt->execute()) {
                $appointment_id = $stmt->insert_id;
                $createAppointmentSuccess = 'Appointment created successfully.';
                $nt_id = get_or_create_notification_type_id($con, 'appointment');
                if ($nt_id !== null) {
                    $pstmt = $con->prepare("SELECT u.first_name, u.last_name FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ? LIMIT 1");
                    $patientNameText = '';
                    if ($pstmt) {
                        $pstmt->bind_param('i', $patient_id);
                        $pstmt->execute();
                        $pstmt->bind_result($pfname, $plname);
                        if ($pstmt->fetch()) {
                            $patientNameText = trim($pfname . ' ' . $plname);
                        }
                        $pstmt->close();
                    }

                    $title = "You have an appointment";
                    $message = sprintf(
                        "in this day %s on %s at %s",
                        $patientNameText ?: 'patient',
                        date("F j, Y", strtotime($appt_date)),
                        date("g:i A", strtotime($appt_time))
                    );

                    $insNotif = "INSERT INTO notification
                                 (patient_id, nurse_id, appointment_id, notification_type_id, title, message, message_status, is_confirmed, is_read, created_date)
                                 VALUES (?, ?, ?, ?, ?, ?, 'pending', 0, 0, NOW())";
                    if ($nstmt = $con->prepare($insNotif)) {
                        $nstmt->bind_param('iiiiss', $patient_id, $nurse_id, $appointment_id, $nt_id, $title, $message);
                        $nstmt->execute();
                        $nstmt->close();
                    }
                }

                $_SESSION['appointment_notice'] = sprintf(
                    'New appointment on %s at %s',
                    date("F j, Y", strtotime($appt_date)),
                    date("g:i A", strtotime($appt_time))
                );

                // Log activity
                $appt_date_display = date("F j, Y", strtotime($appt_date));
                $appt_time_display = date("g:i A", strtotime($appt_time));
                log_activity(
                    $con,
                    $nurse_id,
                    'appointment_created',
                    "Created appointment for $patientNameText on $appt_date_display at $appt_time_display",
                    $patient_id,
                    'appointment',
                    $appointment_id,
                    [],
                    ['appointment_date' => $appt_date, 'appointment_time' => $appt_time]
                );

                header("Location: adm-patient-list.php?patient_id=" . $patient_id);
                exit();

            } else {
                $createAppointmentError = 'Failed to create appointment: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $createAppointmentError = 'Failed to prepare statement.';
        }
    }
}

$patient = null;
$sql = "SELECT p.*, u.first_name, u.last_name, u.middle_name, u.email
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = ? LIMIT 1";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc() ?: null;
    $stmt->close();
}
if (!$patient) {
    die('Patient not found.');
}

//compute age
$age = 'N/A';
if (!empty($patient['date_of_birth']) && $patient['date_of_birth'] !== '0000-00-00') {
    $dob = new DateTime($patient['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}

$admissionRows = [];
$sql = "SELECT a.*,
               n.nurse_id AS admitted_nurse_id,
               un.first_name AS admitted_fn, un.last_name AS admitted_ln,
               uu.first_name AS updated_fn, uu.last_name AS updated_ln
        FROM admission_data a
        LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
        LEFT JOIN users un ON n.user_id = un.user_id
        LEFT JOIN users uu ON a.updated_by = uu.user_id
        WHERE a.patient_id = ?
        ORDER BY a.created_date DESC";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $admissionRows = fetch_all_assoc($stmt);
    $stmt->close();
}
$admittedByName = '';
if (!empty($admissionRows) && !empty($admissionRows[0]['admitted_fn'])) {
    $admittedByName = trim($admissionRows[0]['admitted_fn'] . ' ' . $admissionRows[0]['admitted_ln']);
}

$historyRows = [];
$sql = "SELECT h.*, n.nurse_id, un.first_name AS nurse_fn, un.last_name AS nurse_ln
        FROM history h
        LEFT JOIN nurse n ON h.nurse_id = n.nurse_id
        LEFT JOIN users un ON n.user_id = un.user_id
        WHERE h.patient_id = ?
        ORDER BY h.history_date DESC";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $historyRows = fetch_all_assoc($stmt);
    $stmt->close();
}

$physicalRows = [];
$sql = "SELECT p.*, n.nurse_id, un.first_name AS nurse_fn, un.last_name AS nurse_ln
        FROM physical_assessment p
        LEFT JOIN nurse n ON p.nurse_id = n.nurse_id
        LEFT JOIN users un ON n.user_id = un.user_id
        WHERE p.patient_id = ?
        ORDER BY p.created_date DESC";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $physicalRows = fetch_all_assoc($stmt);
    $stmt->close();
}

$medicationRows = [];
$sql = "SELECT m.*, n.nurse_id, un.first_name AS nurse_fn, un.last_name AS nurse_ln
        FROM medication m
        LEFT JOIN nurse n ON m.nurse_id = n.nurse_id
        LEFT JOIN users un ON n.user_id = un.user_id
        WHERE m.patient_id = ?
        ORDER BY COALESCE(m.date_prescribed, m.created_date) DESC";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $medicationRows = fetch_all_assoc($stmt);
    $stmt->close();
}

$latestMedNote = null;
$sql = "SELECT notes
        FROM medication
        WHERE patient_id = ? AND notes IS NOT NULL AND TRIM(notes) <> ''
        ORDER BY COALESCE(date_prescribed, created_date) DESC
        LIMIT 1";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $stmt->bind_result($notes);
    if ($stmt->fetch()) {
        $latestMedNote = $notes;
    }
    $stmt->close();
}

$labResults = [];
$sql = "SELECT lr.*, n.nurse_id, u.first_name AS nurse_fn, u.last_name AS nurse_ln
        FROM lab_results lr
        LEFT JOIN nurse n ON lr.nurse_id = n.nurse_id
        LEFT JOIN users u ON n.user_id = u.user_id
        WHERE lr.patient_id = ?
        ORDER BY lr.uploaded_at DESC";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $labResults = fetch_all_assoc($stmt);
    $stmt->close();
}

$appointmentRows = [];
$sql = "SELECT a.*, n.nurse_id, u.first_name AS nurse_fn, u.last_name AS nurse_ln
        FROM appointment a
        LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
        LEFT JOIN users u ON n.user_id = u.user_id
        WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date, a.appointment_time";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $appointmentRows = fetch_all_assoc($stmt);
    $stmt->close();
}

$currentNurseName = '';
$sql = "SELECT u.first_name, u.last_name FROM nurse n JOIN users u ON n.user_id = u.user_id WHERE n.nurse_id = ? LIMIT 1";
if ($stmt = $con->prepare($sql)) {
    $stmt->bind_param('i', $nurse_id);
    $stmt->execute();
    $stmt->bind_result($cn_fn, $cn_ln);
    if ($stmt->fetch()) {
        $currentNurseName = trim($cn_fn . ' ' . $cn_ln);
    }
    $stmt->close();
}

function getFrequencyDisplay($m) {
    if (!empty($m['times_per_day']) && $m['times_per_day'] > 0) {
        return htmlspecialchars($m['times_per_day']) . ' time(s) per day';
    }
    if (!empty($m['interval_minutes']) && $m['interval_minutes'] > 0) {
        $min = (int)$m['interval_minutes'];
        if ($min >= 1440) {
            $days = floor($min / 1440);
            return 'Every ' . $days . ' day(s)';
        } elseif ($min >= 60) {
            $hours = floor($min / 60);
            return 'Every ' . $hours . ' hour(s)';
        } else {
            return 'Every ' . $min . ' minute(s)';
        }
    }
    return 'N/A';
}
function getStartDisplay($m) {
    $start_dt = !empty($m['start_datetime']) ? $m['start_datetime'] : null;
    $prescribed_dt = !empty($m['date_prescribed']) ? $m['date_prescribed'] : null;
    if ($start_dt) {
        return date("F j, Y, g:i A", strtotime($start_dt)) . " (Start)";
    } elseif ($prescribed_dt) {
         return date("F j, Y, g:i A", strtotime($prescribed_dt)) . " (Prescribed)";
    }
    return 'N/A';
}
function getNextIntakeTime($m) {
    $start_dt = !empty($m['start_datetime']) ? $m['start_datetime'] : null;
    $prescribed_dt = !empty($m['date_prescribed']) ? $m['date_prescribed'] : null;
    $interval = !empty($m['interval_minutes']) ? (int)$m['interval_minutes'] : 0;
    $baseTime = $start_dt ? strtotime($start_dt) : ($prescribed_dt ? strtotime($prescribed_dt) : null);
    if (!$baseTime || $interval <= 0) {
        return 'N/A';
    }
    $now = time();
    $nextIntake = $baseTime;
    while ($nextIntake <= $now) {
        $nextIntake += ($interval * 60);
    }
    $minutesUntil = round(($nextIntake - $now) / 60);
    return 'Next intake in ' . htmlspecialchars($minutesUntil) . ' minute(s) (' . date("g:i A", $nextIntake) . ')';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile</title>
    <script src="./Javascript/javascript.js" defer></script>
    <script src="./Javascript/logoutFunction.js" defer></script>
    <link rel="stylesheet" href="./Styles/create-appointment.css">
</head>
<body>
    <?php include "adm-nav.php"; ?>
        <form method="post" action="">
        <input type="hidden" name="action" value="create_appointment">
        <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">
        <input type="hidden" name="nurse_id" value="<?php echo htmlspecialchars($nurse_id); ?>">
    <div class="wrapper">
        <?php if (!empty($createAppointmentError)): ?>
            <div class="alert alert-error" role="alert" style="color:#fff;background:#d9534f;padding:10px;border-radius:6px;margin-bottom:12px;">
                <?php echo htmlspecialchars($createAppointmentError); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($createAppointmentSuccess)): ?>
            <div class="alert alert-success" role="alert" style="color:#fff;background:#5cb85c;padding:10px;border-radius:6px;margin-bottom:12px;">
                <?php echo htmlspecialchars($createAppointmentSuccess); ?>
            </div>
        <?php endif; ?>
        <div class="container">
            <div class="profile-card">
                <div class="profile-details">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . " " . $patient['last_name']); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars(!empty($patient['date_of_birth']) ? date("F j, Y", strtotime($patient['date_of_birth'])) : 'N/A'); ?></p>
                    <p><strong>Age:</strong> <?php echo htmlspecialchars($age); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($patient['contact_number']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($patient['patient_status']); ?></p>

                    <?php if (!empty($admittedByName)): ?>
                        <p><strong>Admitted by:</strong> <?php echo htmlspecialchars($admittedByName); ?></p>
                    <?php endif; ?>

                    <?php
                        if (!empty($admissionRows) && !empty($admissionRows[0]['updated_by'])) {
                            $lastUpdatedName = trim($admissionRows[0]['updated_fn'] . ' ' . $admissionRows[0]['updated_ln']);
                            echo '<p><strong>Last Updated by:</strong> ' . htmlspecialchars($lastUpdatedName) . '</p>';
                        }
                    ?>

            <div class="button-container">
                <button type="submit" name="create-appointment">Appoint</button>
            </div>
                </div>
            </div>

            <div class="content">
                <div class="content-section">
                    <h2 class="content-title">Description:
                    <?php if ($latestMedNote === null || trim($latestMedNote) === ''): ?>
                        <p class="description-text">No medication notes available.</p>
                    <?php else: ?>
                        <p class="description-text"><?php echo htmlspecialchars($latestMedNote); ?></p>
                    <?php endif; ?>
                    </h2>

                    <div class="upload-box">
                        <div class="printable-group">
                            <strong>History</strong>
                            <?php if (count($historyRows) === 0): ?>
                                <p>No history records found.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($historyRows as $row): ?>
                                        <?php $nurseLabel = (!empty($row['nurse_fn']) || !empty($row['nurse_ln'])) ? trim($row['nurse_fn'].' '.$row['nurse_ln']) : ''; ?>
                                        <li>
                                            <a class="print-link" title="Print Data?" href="lab_print.php?type=history&record_id=<?php echo $row['history_id']; ?>&patient_id=<?php echo $patient_id; ?>" target="_blank" rel="noopener">
                                                <?php
                                                    $label = !empty($row['history_date']) ? date("F j, Y", strtotime($row['history_date'])) : 'Unknown date';
                                                    echo htmlspecialchars($label);
                                                ?>
                                            </a>
                                            <?php if ($nurseLabel): ?><span class="nurse-by">— by <?php echo htmlspecialchars($nurseLabel); ?></span><?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="printable-group">
                            <strong>Physical Assessment</strong>
                            <?php if (count($physicalRows) === 0): ?>
                                <p>No physical assessment records found.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($physicalRows as $row): ?>
                                        <?php $nurseLabel = (!empty($row['nurse_fn']) || !empty($row['nurse_ln'])) ? trim($row['nurse_fn'].' '.$row['nurse_ln']) : ''; ?>
                                        <li>
                                            <a class="print-link" title="Print Data?" href="lab_print.php?type=physical&record_id=<?php echo $row['physical_assessment_id']; ?>&patient_id=<?php echo $patient_id; ?>" target="_blank" rel="noopener">
                                                <?php
                                                    $label = !empty($row['created_date']) ? date("F j, Y", strtotime($row['created_date'])) : 'Unknown date';
                                                    echo htmlspecialchars($label);
                                                ?>
                                            </a>
                                            <?php if ($nurseLabel): ?><span class="nurse-by">— by <?php echo htmlspecialchars($nurseLabel); ?></span><?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="printable-group">
                            <strong>Admission Data</strong>
                            <?php if (count($admissionRows) === 0): ?>
                                <p>No admission records found.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($admissionRows as $row): ?>
                                        <?php $nurseLabel = (!empty($row['nurse_fn']) || !empty($row['nurse_ln'])) ? trim($row['nurse_fn'].' '.$row['nurse_ln']) : ''; ?>
                                        <li>
                                            <a class="print-link" title="Print Data?" href="lab_print.php?type=admission&record_id=<?php echo $row['admission_data_id']; ?>&patient_id=<?php echo $patient_id; ?>" target="_blank" rel="noopener">
                                                <?php
                                                    $labelDate = !empty($row['admission_date']) ? $row['admission_date'] : 'Unknown date';
                                                    $labelTime = !empty($row['admission_time']) ? $row['admission_time'] : '';
                                                    $label = $labelDate . ' - ' . $labelTime;
                                                    echo htmlspecialchars(trim($label));
                                                ?>
                                            </a>
                                            <?php if ($nurseLabel): ?><span class="nurse-by">— by <?php echo htmlspecialchars($nurseLabel); ?></span><?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
                <div class="content-section2">
                    <form>
                        <div class="form-group">
                            <h2 class="content-title2">Come back by:</h2>
                                 <label for="appointment_date">Date:</label>
                                <input type="date" id="appointment_date" name="appointment_date" class="frequency-input"
                                    value="<?php echo htmlspecialchars($_POST['appointment_date'] ?? ''); ?>" required>

                                <label for="appointment_time">Time:</label>
                                <input type="time" id="appointment_time" name="appointment_time" class="frequency-input"
                                    value="<?php echo htmlspecialchars($_POST['appointment_time'] ?? ''); ?>" required>
                        </div>
                    </form>
                    <div class="prescription-box">
                        <h3>Prescription of Medication</h3>

                        <?php if (count($medicationRows) === 0): ?>
                            <p>No prescriptions found.</p>
                        <?php else: ?>
                            <?php foreach ($medicationRows as $m): ?>
                                <?php
                                    $prescribedBy = (!empty($m['nurse_fn']) || !empty($m['nurse_ln'])) ? trim($m['nurse_fn'].' '.$m['nurse_ln']) : 'N/A';
                                    $datePrescribed = !empty($m['date_prescribed']) ? date("F j, Y, g:i A", strtotime($m['date_prescribed'])) : 'Unknown date';

                                    $frequencyDisplay = getFrequencyDisplay($m);
                                    $startDisplay = getStartDisplay($m);

                                    // CALL THE NEW FUNCTION HERE
                                    $nextIntakeDisplay = getNextIntakeTime($m);
                                ?>
                                <div class="printable-group">
                                    <strong><?php echo htmlspecialchars($m['medication_name']); ?></strong> (Type: <?php echo htmlspecialchars($m['medication_type']); ?>)
                                    <ul>
                                        <li>
                                            <span class="print-link">Dosage: <strong><?php echo htmlspecialchars($m['dose']); ?></strong></span>
                                        </li>
                                        <li>
                                            <span class="print-link">Frequency: <strong><?php echo $frequencyDisplay; ?></strong></span>
                                        </li>
                                        <li>
                                            <span class="print-link">Schedule Start: <strong><?php echo $startDisplay; ?></strong></span>
                                        </li>

                                        <li>
                                            <span class="print-link" style="color: #d9534f;">Next Intake: <strong><?php echo $nextIntakeDisplay; ?></strong></span>
                                        </li>

                                        <li>
                                            <span class="print-link">Prescribed on: <?php echo htmlspecialchars($datePrescribed); ?></span>
                                            <?php if ($prescribedBy !== 'N/A'): ?>
                                                <span class="nurse-by">— by <?php echo htmlspecialchars($prescribedBy); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
        </form> <!-- end main form -->
</body>
</html>
