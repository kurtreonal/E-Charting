<?php
session_name('patient_session');
session_start();
include_once 'includes/notification.php';

//prevent saved account of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

include 'connection.php';

//get the patient_id from session
$patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : null;

if (!$patient_id) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'mark_read' && !empty($_POST['notification_id'])) {
        $nid = (int) $_POST['notification_id'];

        // ensure the notification belongs to this patient
        $chk = $con->prepare("SELECT notification_id FROM notification WHERE notification_id = ? AND patient_id = ? LIMIT 1");
        $chk->bind_param('ii', $nid, $patient_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 1) {
            $upd = $con->prepare("UPDATE notification SET is_read = 1 WHERE notification_id = ?");
            $upd->bind_param('i', $nid);
            $upd->execute();
            $upd->close();
        }
        $chk->close();

        // stay on page
        header("Location: patient-profile.php");
        exit();

    } elseif ($action === 'confirm_appointment' && !empty($_POST['notification_id']) && !empty($_POST['appointment_id'])) {
        $nid = (int) $_POST['notification_id'];
        $appt_id = (int) $_POST['appointment_id'];

        //ensure the notification belongs to this patient and matches the appointment
        $chk = $con->prepare("SELECT notification_id FROM notification WHERE notification_id = ? AND patient_id = ? AND appointment_id = ? LIMIT 1");
        $chk->bind_param('iii', $nid, $patient_id, $appt_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 1) {
            // mark notification confirmed + read
            $upd = $con->prepare("UPDATE notification SET is_confirmed = 1, message_status = 'confirmed', is_read = 1 WHERE notification_id = ?");
            $upd->bind_param('i', $nid);
            $upd->execute();
            $upd->close();
        }
        $chk->close();

        header("Location: patient-profile.php");
        exit();
    }
}
$sql = "
SELECT u.first_name, u.last_name, u.middle_name, p.gender, p.date_of_birth, p.contact_number, p.address, p.patient_status
FROM patients p
JOIN users u ON p.user_id = u.user_id
WHERE p.patient_id = ?
";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$patient = null;
$age = 0;

if ($result->num_rows === 1) {
    $patient = $result->fetch_assoc();

    //calculate age from date of birth (guard if null)
    if (!empty($patient['date_of_birth']) && $patient['date_of_birth'] !== '0000-00-00') {
        try {
            $dob = new DateTime($patient['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
        } catch (Exception $e) {
            $age = 0;
        }
    }
} else {
    header("Location: login.php");
    exit();
}

$stmt->close();

//fetch the nurse who admitted the patient (latest admission)
$admittedByName = null;
$admByStmt = $con->prepare("
    SELECT nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
    FROM admission_data a
    JOIN nurse n ON a.nurse_id = n.nurse_id
    JOIN users nu ON n.user_id = nu.user_id
    WHERE a.patient_id = ?
    ORDER BY a.created_date DESC
    LIMIT 1
");
$admByStmt->bind_param("i", $patient_id);
$admByStmt->execute();
$admByRes = $admByStmt->get_result();
if ($admByRes && $admByRes->num_rows === 1) {
    $r = $admByRes->fetch_assoc();
    $admittedByName = trim($r['nurse_fn'] . ' ' . $r['nurse_ln']);
}
$admByStmt->close();

//fetch related records for printable results (include nurse who recorded each)
//history records (include nurse name)
$historyRows = [];
$histStmt = $con->prepare("
    SELECT h.history_id, h.history_date, h.allergies, h.duration_of_symptoms,
            nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
    FROM history h
    LEFT JOIN nurse n ON h.nurse_id = n.nurse_id
    LEFT JOIN users nu ON n.user_id = nu.user_id
    WHERE h.patient_id = ?
    ORDER BY h.history_date DESC
");
$histStmt->bind_param("i", $patient_id);
$histStmt->execute();
$histRes = $histStmt->get_result();
while ($r = $histRes->fetch_assoc()) $historyRows[] = $r;
$histStmt->close();

//physical Assessment records (include nurse name)
$physicalRows = [];
$physStmt = $con->prepare("
    SELECT p.physical_assessment_id, p.created_date, p.height, p.weight,
            nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
    FROM physical_assessment p
    LEFT JOIN nurse n ON p.nurse_id = n.nurse_id
    LEFT JOIN users nu ON n.user_id = nu.user_id
    WHERE p.patient_id = ?
    ORDER BY p.created_date DESC
");
$physStmt->bind_param("i", $patient_id);
$physStmt->execute();
$physRes = $physStmt->get_result();
while ($r = $physRes->fetch_assoc()) $physicalRows[] = $r;
$physStmt->close();

//admission data records (show who LAST UPDATED)
$admissionRows = [];
$admStmt = $con->prepare("
    SELECT a.admission_data_id, a.admission_date, a.admission_time, a.mode_of_arrival,
            a.created_date, a.updated_date, a.updated_by,
            nu.first_name AS nurse_fn, nu.last_name AS nurse_ln,
            nu_upd.first_name AS updated_fn, nu_upd.last_name AS updated_ln
    FROM admission_data a
    LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
    LEFT JOIN users nu ON n.user_id = nu.user_id
    LEFT JOIN nurse n_upd ON a.updated_by = n_upd.nurse_id
    LEFT JOIN users nu_upd ON n_upd.user_id = nu_upd.user_id
    WHERE a.patient_id = ?
    ORDER BY a.updated_date DESC LIMIT 1
");
$admStmt->bind_param("i", $patient_id);
$admStmt->execute();
$admRes = $admStmt->get_result();
while ($r = $admRes->fetch_assoc()) $admissionRows[] = $r;
$admStmt->close();

//medication records (most recent first) including prescribing nurse
$medicationRows = [];
$medStmt = $con->prepare("
    SELECT m.medication_id, m.medication_name, m.medication_type, m.dose,
           m.times_per_day, m.interval_minutes, m.start_datetime, m.date_prescribed, m.notes,
            nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
    FROM medication m
    LEFT JOIN nurse n ON m.nurse_id = n.nurse_id
    LEFT JOIN users nu ON n.user_id = nu.user_id
    WHERE m.patient_id = ?
    ORDER BY m.date_prescribed DESC, m.created_date DESC
");
$medStmt->bind_param("i", $patient_id);
$medStmt->execute();
$medRes = $medStmt->get_result();
while ($r = $medRes->fetch_assoc()) $medicationRows[] = $r;
$medStmt->close();

//determine latest medication for showing notes in Description
$latestMedNote = null;
if (count($medicationRows) > 0) {
    $latestMedNote = $medicationRows[0]['notes'];
}

/* Helper functions are same as before (frequency/start/next intake) */
function getFrequencyDisplay($m) {
    if (!empty($m['times_per_day']) && $m['times_per_day'] > 0) {
        return htmlspecialchars($m['times_per_day']) . ' time(s) per intake';
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
generate_medication_notifications_for_patient($con, $patient_id, 5);

/* ------------------ FETCH NOTIFICATIONS FOR THIS PATIENT ------------------ */
$notifications = [];
$notStmt = $con->prepare("
    SELECT n.notification_id, n.title, n.message, n.created_date, n.is_read, n.is_confirmed, n.appointment_id, nt.notification_type
    FROM notification n
    LEFT JOIN notification_type nt ON n.notification_type_id = nt.notification_type_id
    WHERE n.patient_id = ?
    ORDER BY n.created_date DESC
");
if ($notStmt) {
    $notStmt->bind_param('i', $patient_id);
    $notStmt->execute();
    $res = $notStmt->get_result();
    while ($row = $res->fetch_assoc()) $notifications[] = $row;
    $notStmt->close();
}

// Fetch confirmed appointments for this patient (via notifications where message_status = 'confirmed')
$confirmedAppointments = [];
$apptStmt = $con->prepare("
    SELECT DISTINCT a.appointment_date, a.appointment_time
    FROM appointment a
    INNER JOIN notification n ON a.appointment_id = n.appointment_id
    WHERE a.patient_id = ? AND n.message_status = 'confirmed'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
if ($apptStmt) {
    $apptStmt->bind_param('i', $patient_id);
    $apptStmt->execute();
    $apptRes = $apptStmt->get_result();
    while ($row = $apptRes->fetch_assoc()) {
        $confirmedAppointments[] = $row;
    }
    $apptStmt->close();
}

// Format appointment display
$appointmentText = 'None scheduled';
if (count($confirmedAppointments) > 0) {
    $formattedDates = [];
    foreach ($confirmedAppointments as $appt) {
        $dateTime = date("M d, Y g:i A", strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']));
        $formattedDates[] = $dateTime;
    }
    $appointmentText = implode(' and ', $formattedDates);
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
    <link rel="stylesheet" href="./Styles/patient-profile.css">
</head>
<body>
    <?php include "nav.php"; ?>
    <div class="wrapper">
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
                        //show who last updated the admission data
                        if (!empty($admissionRows) && !empty($admissionRows[0]['updated_by'])) {
                            $lastUpdatedName = trim($admissionRows[0]['updated_fn'] . ' ' . $admissionRows[0]['updated_ln']);
                            echo '<p><strong>Last Updated by:</strong> ' . htmlspecialchars($lastUpdatedName) . '</p>';
                        }
                    ?>
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
                    <h2 class="content-title2">Come back by: <strong><?php echo htmlspecialchars($appointmentText); ?></strong></h2>
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

        <div class="signature-button">
            <form method="POST" action="./logout.php" onsubmit="return confirmLogout()">
                <div class="button-container">
                    <button type="submit" name="logout" id="logout">Logout</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>