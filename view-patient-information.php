<?php
// Use authcheck.php for proper nurse authentication (handles nurse_session)
include_once 'authcheck.php';
include_once 'includes/notification.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

//prevent saved account of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

include 'connection.php';

// Get patient_id from URL parameter
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;

if (!$patient_id) {
    header("Location: adm-patient-list.php");
    exit();
}

// Get patient information
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
    // Patient not found
    header("Location: adm-patient-list.php");
    exit();
}

$stmt->close();

//medication records (most recent first) including prescribing nurse AND nurse_id
$medicationRows = [];
$medStmt = $con->prepare("
    SELECT m.medication_id, m.medication_name, m.medication_type, m.dose,
           m.times_per_day, m.interval_minutes, m.start_datetime, m.date_prescribed, m.notes,
           m.nurse_id,
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

//fetch lab results for this patient (simplified - only images)
$labResultsRows = [];
$labStmt = $con->prepare("
    SELECT lr.lab_result_id, lr.image_path, lr.image_name, lr.upload_date, lr.notes,
           nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
    FROM lab_results lr
    LEFT JOIN nurse n ON lr.nurse_id = n.nurse_id
    LEFT JOIN users nu ON n.user_id = nu.user_id
    WHERE lr.patient_id = ?
    ORDER BY lr.upload_date DESC
");
$labStmt->bind_param("i", $patient_id);
$labStmt->execute();
$labRes = $labStmt->get_result();
while ($r = $labRes->fetch_assoc()) $labResultsRows[] = $r;
$labStmt->close();

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Patient Information - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></title>
    <script src="./Javascript/javascript.js" defer></script>
    <link rel="stylesheet" href="./Styles/patient-profile.css">

    <style>
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 9998;
        }

        .modal-overlay.active {
            display: block;
        }

        .lab-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: var(--secondary);
            border: 2px solid var(--primary);
            border-radius: 8px;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            overflow-y: auto;
            z-index: 9999;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .lab-modal.active {
            display: block;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translate(-50%, -45%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
        }

        .modal-header h2 {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            color: var(--primary);
            font-size: 1.8rem;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: 2px solid var(--primary);
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary);
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background-color: var(--primary);
            color: var(--secondary);
            transform: rotate(90deg);
        }

        /* Upload Modal Styles */
        .upload-area {
            border: 3px dashed var(--primary);
            border-radius: 8px;
            padding: 3rem;
            text-align: center;
            background-color: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }

        .upload-area:hover,
        .upload-area.drag-over {
            background-color: #e8f4f8;
            border-color: #2d2b2a;
        }

        .upload-area.drag-over {
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .upload-text {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .upload-text strong {
            color: var(--primary);
        }

        .file-input {
            display: none;
        }

        .preview-container {
            margin: 1.5rem 0;
            text-align: center;
        }

        .preview-image {
            max-width: 100%;
            max-height: 400px;
            border: 2px solid var(--primary);
            border-radius: 8px;
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--primary);
            border-radius: 5px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            resize: vertical;
            min-height: 100px;
        }

        .upload-btn {
            background-color: var(--primary);
            color: var(--secondary);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 5px;
            cursor: pointer;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
            margin-right: 0.5rem;
        }

        .upload-btn:hover:not(:disabled) {
            background-color: #2d2b2a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .upload-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 5px;
            cursor: pointer;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
        }

        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            animation: messageSlideIn 0.3s ease-out;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Success Animation */
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto;
        }

        .success-checkmark .check-icon {
            width: 80px;
            height: 80px;
            position: relative;
            border-radius: 50%;
            box-sizing: content-box;
            border: 4px solid #4CAF50;
        }

        .success-checkmark .check-icon::before {
            top: 3px;
            left: -2px;
            width: 30px;
            transform-origin: 100% 50%;
            border-radius: 100px 0 0 100px;
        }

        .success-checkmark .check-icon::after {
            top: 0;
            left: 30px;
            width: 60px;
            transform-origin: 0 50%;
            border-radius: 0 100px 100px 0;
            animation: rotate-circle 4.25s ease-in;
        }

        .success-checkmark .check-icon::before, .success-checkmark .check-icon::after {
            content: '';
            height: 100px;
            position: absolute;
            background: #FFFFFF;
            transform: rotate(-45deg);
        }

        .success-checkmark .check-icon .icon-line {
            height: 5px;
            background-color: #4CAF50;
            display: block;
            border-radius: 2px;
            position: absolute;
            z-index: 10;
        }

        .success-checkmark .check-icon .icon-line.line-tip {
            top: 46px;
            left: 14px;
            width: 25px;
            transform: rotate(45deg);
            animation: icon-line-tip 0.75s;
        }

        .success-checkmark .check-icon .icon-line.line-long {
            top: 38px;
            right: 8px;
            width: 47px;
            transform: rotate(-45deg);
            animation: icon-line-long 0.75s;
        }

        @keyframes icon-line-tip {
            0% {
                width: 0;
                left: 1px;
                top: 19px;
            }
            54% {
                width: 0;
                left: 1px;
                top: 19px;
            }
            70% {
                width: 50px;
                left: -8px;
                top: 37px;
            }
            84% {
                width: 17px;
                left: 21px;
                top: 48px;
            }
            100% {
                width: 25px;
                left: 14px;
                top: 45px;
            }
        }

        @keyframes icon-line-long {
            0% {
                width: 0;
                right: 46px;
                top: 54px;
            }
            65% {
                width: 0;
                right: 46px;
                top: 54px;
            }
            84% {
                width: 55px;
                right: 0px;
                top: 35px;
            }
            100% {
                width: 47px;
                right: 8px;
                top: 38px;
            }
        }

        /* View Lab Results Grid */
        .lab-results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .lab-result-card {
            border: 2px solid var(--primary);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            background: white;
        }

        .lab-result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .lab-result-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .lab-result-info {
            padding: 1rem;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        .lab-result-date {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .lab-result-nurse {
            font-size: 0.85rem;
            color: #888;
        }

        /* Image Viewer Modal */
        .image-viewer-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: var(--secondary);
            border: 2px solid var(--primary);
            border-radius: 8px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 10000;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .image-viewer-modal.active {
            display: block;
        }

        .full-image {
            max-width: 100%;
            max-height: 70vh;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }

        /* Blur content when modal is active */
        body.modal-active .wrapper {
            filter: blur(3px);
            pointer-events: none;
        }

        body.modal-active nav {
            filter: blur(3px);
        }

        .back-button {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .back-button:hover {
            background-color: #2d2b2a;
            transform: translateY(-2px);
        }

        .view-update-btn {
            width: 100%;
            margin-top: 1.5rem;
            padding: 0.75rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .view-update-btn:hover {
            background-color: #2d2b2a;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include "adm-nav.php"; ?>

    <div class="wrapper">
        <!-- Back Button -->
        <a href="adm-patient-list.php" class="back-button">← Back to Patient List</a>

        <div class="container">
            <!-- Left Sidebar: Date of Doctor's Orders -->
            <div class="profile-card">
                <div class="profile-details">
                    <p style="font-size: 1rem; font-weight: 600; line-height: 1.4; margin-bottom: 1rem;">
                        DATE OF<br>DOCTOR'S ORDER
                    </p>
                    <hr style="border: 1px solid var(--primary); margin: 1rem 0;">
                    <?php if (count($medicationRows) === 0): ?>
                        <p style="font-size: 0.9rem;">No orders yet</p>
                    <?php else: ?>
                        <?php foreach ($medicationRows as $m): ?>
                            <?php if (!empty($m['date_prescribed'])): ?>
                                <p style="font-size: 0.95rem; margin-bottom: 0.3rem; line-height: 1.6;">
                                    <?php echo strtoupper(date("F  j, Y", strtotime($m['date_prescribed']))); ?>
                                </p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content">
                <!-- Main Section: Doctor's Order -->
                <div class="content-section">
                    <h2 class="content-title" style="border: 2px solid var(--primary); padding: 1rem; margin-bottom: 1.5rem; display: block;">
                        Doctor's Order
                    </h2>

                    <div class="upload-box">
                        <!-- Patient Info Header -->
                        <div style="margin-bottom: 2.5rem; line-height: 1.9; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 1.2rem;">
                            <p style="font-size: 1rem; margin-bottom: 0.5rem; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;">
                                <strong><?php echo strtoupper(htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name'] . ' ' . (isset($patient['middle_name']) ? $patient['middle_name'] : ''))); ?></strong>
                                &nbsp;&nbsp;&nbsp;
                                <strong><?php echo htmlspecialchars($age); ?></strong>
                                &nbsp;&nbsp;&nbsp;
                                <strong><?php echo strtoupper(htmlspecialchars($patient['gender'])); ?></strong>
                            </p>
                            <p style="font-size: 0.95rem; margin-top: 0.5rem; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;">
                                <?php echo htmlspecialchars(!empty($patient['date_of_birth']) ? date("m/d/Y", strtotime($patient['date_of_birth'])) : 'N/A'); ?>
                            </p>
                            <p style="font-size: 0.95rem; margin-top: 0.5rem; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; color: #666;">
                                <strong>Patient ID:</strong> <?php echo htmlspecialchars($patient_id); ?>
                            </p>
                        </div>

                        <!-- Medication Notes/Order Details -->
                        <?php if (count($medicationRows) > 0 && !empty($medicationRows[0]['notes'])): ?>
                            <div style="margin-bottom: 2.5rem; line-height: 1.9; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 1.2rem;">
                                <p><?php echo htmlspecialchars($medicationRows[0]['notes']); ?></p>
                            </div>
                        <?php else: ?>
                            <div style="margin-bottom: 2.5rem; line-height: 1.9; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 1.2rem;">
                                <p>No doctor's orders available.</p>
                            </div>
                        <?php endif; ?>

                        <!-- Physician Information -->
                        <?php if (count($medicationRows) > 0): ?>
                            <?php
                                $prescribedBy = (!empty($medicationRows[0]['nurse_fn']) || !empty($medicationRows[0]['nurse_ln']))
                                    ? trim($medicationRows[0]['nurse_fn'].' '.$medicationRows[0]['nurse_ln'])
                                    : 'N/A';
                                $nurseLastName = !empty($medicationRows[0]['nurse_ln']) ? $medicationRows[0]['nurse_ln'] : '';
                                $nurseId = !empty($medicationRows[0]['nurse_id']) ? $medicationRows[0]['nurse_id'] : 'N/A';
                            ?>
                            <div style="margin-top: 2.5rem; padding-top: 1.5rem; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 0.95rem;">
                                <p style="margin-bottom: 0.7rem;">
                                    <strong>Physician Name:</strong> <?php echo htmlspecialchars($prescribedBy); ?>
                                </p>
                                <p>
                                    <strong>License Number:</strong> <?php echo htmlspecialchars($nurseId); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Section: Medications Table -->
                <div class="content-section2" style="margin-top: 0;">
                    <div class="prescription-box" style="border: 2px solid var(--primary); height: 100%; width: 100%; padding: 1.5rem;">
                        <!-- Table -->
                        <table style="width: 100%; border-collapse: collapse; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--primary);">
                                    <th style="text-align: left; padding: 1rem; font-size: 1.2rem; font-weight: 600;">
                                        Medications
                                    </th>
                                    <th style="text-align: left; padding: 1rem; font-size: 1.2rem; font-weight: 600;">
                                        Date
                                    </th>
                                    <th style="text-align: left; padding: 1rem; font-size: 1.2rem; font-weight: 600;">
                                        Time
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($medicationRows) === 0): ?>
                                    <tr>
                                        <td colspan="3" style="padding: 2rem; text-align: center; font-size: 1.2rem;">No medications found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($medicationRows as $m): ?>
                                        <?php
                                            $frequencyDisplay = getFrequencyDisplay($m);
                                            $startDisplay = getStartDisplay($m);
                                            $nextIntakeDisplay = getNextIntakeTime($m);
                                            $dateOnly = !empty($m['date_prescribed']) ? date("F j, Y", strtotime($m['date_prescribed'])) : 'N/A';
                                        ?>
                                        <tr style="border-bottom: 1px solid #ddd;font-size: 1.2rem;">
                                            <td style="padding: 1rem; vertical-align: top;">
                                                <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($m['medication_name']); ?></strong>
                                                <p style="margin: 0.3rem 0; color: #555;">
                                                    Type: <?php echo htmlspecialchars($m['medication_type']); ?> |
                                                    Dose: <?php echo htmlspecialchars($m['dose']); ?> |
                                                    Frequency: <?php echo htmlspecialchars($frequencyDisplay); ?>
                                                </p>
                                                <p style="margin: 0.3rem 0; color: #555;">
                                                    Start: <?php echo htmlspecialchars($startDisplay); ?>
                                                </p>
                                                <p style="margin: 0.3rem 0; color: #d9534f; font-weight: 600;">
                                                    <?php echo htmlspecialchars($nextIntakeDisplay); ?>
                                                </p>
                                            </td>
                                            <td style="padding: 1rem; vertical-align: top;">
                                                <?php echo htmlspecialchars($dateOnly); ?>
                                            </td>
                                            <td style="padding: 1rem; vertical-align: top;">
                                                <?php
                                                    $timeOnly = !empty($m['date_prescribed']) ? date("g:i A", strtotime($m['date_prescribed'])) : 'N/A';
                                                    echo htmlspecialchars($timeOnly);
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="signature-button">
            <div class="button-container">
                <button onclick="openLabResultsModal()" style="background: none;border: none;color: var(--primary);font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;"font-size: 1rem;font-weight: 500;cursor: pointer;">
                    <?php echo count($labResultsRows) > 0 ? 'View/Update Lab Results' : 'Upload Lab Results'; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="modal-overlay"></div>

    <!-- View Lab Results Modal (shows existing results OR upload form) -->
    <div id="labResultsModal" class="lab-modal">
        <div class="modal-header">
            <h2 id="modalTitle">🔬 Laboratory Results</h2>
            <button class="modal-close" onclick="closeAllModals()">×</button>
        </div>

        <!-- View Lab Results Section (shown when results exist) -->
        <div id="viewLabResultsSection" style="display: none;">
            <div class="lab-results-grid">
                <?php foreach ($labResultsRows as $lab): ?>
                    <div class="lab-result-card" onclick="viewFullImage('<?php echo htmlspecialchars(addslashes($lab['image_path'])); ?>', '<?php echo htmlspecialchars(addslashes($lab['image_name'])); ?>', '<?php echo htmlspecialchars(addslashes($lab['upload_date'])); ?>', '<?php echo htmlspecialchars(addslashes(trim($lab['nurse_fn'] . ' ' . $lab['nurse_ln']))); ?>', '<?php echo htmlspecialchars(addslashes($lab['notes'] ?? '')); ?>')">
                        <img src="<?php echo htmlspecialchars($lab['image_path']); ?>" alt="Lab Result" class="lab-result-image" onerror="this.style.display='none'; const err = document.createElement('div'); err.style.cssText = 'width:100%;height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;font-size:0.9rem;text-align:center;'; err.textContent = 'Image not found'; this.parentElement.appendChild(err);">
                        <div class="lab-result-info">
                            <div class="lab-result-date">
                                <strong>📅 <?php echo htmlspecialchars(date("M d, Y", strtotime($lab['upload_date']))); ?></strong>
                            </div>
                            <div class="lab-result-date">
                                🕐 <?php echo htmlspecialchars(date("g:i A", strtotime($lab['upload_date']))); ?>
                            </div>
                            <div class="lab-result-nurse">
                                Uploaded by: <?php echo htmlspecialchars(trim($lab['nurse_fn'] . ' ' . $lab['nurse_ln'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="view-update-btn" onclick="showUploadSection()"> Upload New Lab Result</button>
        </div>

        <!-- Upload Section (shown when no results OR when uploading new) -->
        <div id="uploadSection" style="display: none;">
            <div id="uploadMessage" class="message" style="display: none;"></div>

            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📁</div>
                <p class="upload-text"><strong>Click to upload</strong> or drag and drop</p>
                <p class="upload-text">PNG, JPG, GIF up to 5MB</p>
                <input type="file" id="fileInput" class="file-input" accept="image/*">
            </div>

            <div class="preview-container" id="previewContainer" style="display: none;">
                <img id="previewImage" class="preview-image" alt="Preview">
            </div>

            <div class="form-group">
                <label for="notesInput">Notes (Optional)</label>
                <textarea id="notesInput" placeholder="Add any notes about this lab result..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button class="cancel-btn" onclick="cancelUpload()">Cancel</button>
                <button class="upload-btn" id="uploadButton" onclick="uploadLabResult()" disabled> Upload</button>
            </div>
        </div>

        <!-- Success Animation (hidden by default) -->
        <div id="successSection" style="display: none; text-align: center; padding: 3rem;">
            <div class="success-checkmark">
                <div class="check-icon">
                    <span class="icon-line line-tip"></span>
                    <span class="icon-line line-long"></span>
                    <div class="icon-circle"></div>
                    <div class="icon-fix"></div>
                </div>
            </div>
            <h2 style="color: #4CAF50; margin-top: 1.5rem;">Upload Successful!</h2>
            <p style="color: #666; margin-top: 1rem;">The lab result has been saved successfully.</p>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageViewerModal" class="image-viewer-modal">
        <div class="modal-header">
            <h2>🔬 Lab Result Details</h2>
            <button class="modal-close" onclick="closeImageViewer()">×</button>
        </div>
        <div style="text-align: center;">
            <img id="fullImage" class="full-image" src="" alt="Lab Result">
            <div id="imageInfo" style="margin-top: 1.5rem; text-align: left; padding: 1rem; background: #f9f9f9; border-radius: 8px;"></div>
        </div>
    </div>

    <script>
let selectedFile = null;
const hasLabResults = <?php echo count($labResultsRows) > 0 ? 'true' : 'false'; ?>;

function openLabResultsModal() {
    document.getElementById('labResultsModal').classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
    document.body.classList.add('modal-active');

    if (hasLabResults) {
        showViewSection();
    } else {
        showUploadSection();
    }
}

function showViewSection() {
    document.getElementById('modalTitle').textContent = '🔬 Laboratory Results';
    document.getElementById('viewLabResultsSection').style.display = 'block';
    document.getElementById('uploadSection').style.display = 'none';
    document.getElementById('successSection').style.display = 'none';
}

function showUploadSection() {
    document.getElementById('modalTitle').textContent = hasLabResults ? ' Upload New Lab Result' : ' Upload Lab Result';
    document.getElementById('viewLabResultsSection').style.display = 'none';
    document.getElementById('uploadSection').style.display = 'block';
    document.getElementById('successSection').style.display = 'none';
    resetUploadForm();
}

function cancelUpload() {
    if (hasLabResults) {
        showViewSection();
    } else {
        closeAllModals();
    }
}

function closeAllModals() {
    document.getElementById('labResultsModal').classList.remove('active');
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.classList.remove('modal-active');
    resetUploadForm();
}

function closeImageViewer() {
    document.getElementById('imageViewerModal').classList.remove('active');
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.classList.remove('modal-active');
}

function resetUploadForm() {
    selectedFile = null;
    document.getElementById('fileInput').value = '';
    document.getElementById('notesInput').value = '';
    document.getElementById('previewContainer').style.display = 'none';
    document.getElementById('previewImage').src = '';
    document.getElementById('uploadButton').disabled = true;
    hideMessage();
}

function hideMessage() {
    const msgEl = document.getElementById('uploadMessage');
    if (msgEl) msgEl.style.display = 'none';
}

function showMessage(message, isSuccess) {
    const msgEl = document.getElementById('uploadMessage');
    msgEl.textContent = message;
    msgEl.className = 'message ' + (isSuccess ? 'success' : 'error');
    msgEl.style.display = 'block';
}

// File input change handler
document.getElementById('fileInput').addEventListener('change', function(e) {
    handleFileSelect(e.target.files[0]);
});

// Drag and drop handlers
const uploadArea = document.getElementById('uploadArea');

uploadArea.addEventListener('click', function() {
    document.getElementById('fileInput').click();
});

uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.add('drag-over');
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.remove('drag-over');
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.remove('drag-over');

    const file = e.dataTransfer.files[0];
    handleFileSelect(file);
});

// Modal overlay click handler
document.getElementById('modalOverlay').addEventListener('click', function() {
    closeAllModals();
    closeImageViewer();
});

function handleFileSelect(file) {
    if (!file) return;

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showMessage('Invalid file type. Only JPG, PNG, and GIF are allowed.', false);
        return;
    }

    // Validate file size (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        showMessage('File too large. Maximum size is 5MB.', false);
        return;
    }

    selectedFile = file;

    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImage').src = e.target.result;
        document.getElementById('previewContainer').style.display = 'block';
        document.getElementById('uploadButton').disabled = false;
        hideMessage();
    };
    reader.readAsDataURL(file);
}

function uploadLabResult() {
    if (!selectedFile) {
        showMessage('Please select an image first.', false);
        return;
    }

    const formData = new FormData();
    formData.append('lab_image', selectedFile);
    formData.append('patient_id', <?php echo $patient_id; ?>);
    formData.append('notes', document.getElementById('notesInput').value);

    // Disable upload button
    const uploadBtn = document.getElementById('uploadButton');
    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';

    fetch('includes/upload_lab_result.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success animation
            document.getElementById('uploadSection').style.display = 'none';
            document.getElementById('successSection').style.display = 'block';

            // Reload page after 2 seconds
            setTimeout(function() {
                location.reload();
            }, 2000);
        } else {
            showMessage(data.message || 'Upload failed. Please try again.', false);
            uploadBtn.disabled = false;
            uploadBtn.textContent = ' Upload';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Upload failed. Please check your connection and try again.', false);
        uploadBtn.disabled = false;
        uploadBtn.textContent = ' Upload';
    });
}

function viewFullImage(imagePath, imageName, uploadDate, nurseName, notes) {
    document.getElementById('fullImage').src = imagePath;

    const uploadDateObj = new Date(uploadDate);
    const formattedDate = uploadDateObj.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    const formattedTime = uploadDateObj.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit'
    });

    let infoHTML = `
        <p style="margin-bottom: 0.75rem;"><strong>File Name:</strong> ${imageName}</p>
        <p style="margin-bottom: 0.75rem;"><strong>Upload Date:</strong> ${formattedDate} at ${formattedTime}</p>
        <p style="margin-bottom: 0.75rem;"><strong>Uploaded By:</strong> ${nurseName}</p>
    `;

    if (notes && notes.trim() !== '') {
        infoHTML += `<p style="margin-top: 1rem;"><strong>Notes:</strong> ${notes}</p>`;
    }

    document.getElementById('imageInfo').innerHTML = infoHTML;

    // Close the lab results modal first
    document.getElementById('labResultsModal').classList.remove('active');

    // Then open the image viewer
    document.getElementById('imageViewerModal').classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
    document.body.classList.add('modal-active');
}

// Close modals on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAllModals();
        closeImageViewer();
    }
});
</script>
</body>
</html>