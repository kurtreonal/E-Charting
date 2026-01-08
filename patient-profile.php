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

        header("Location: patient-profile.php");
        exit();

    } elseif ($action === 'confirm_appointment' && !empty($_POST['notification_id']) && !empty($_POST['appointment_id'])) {
        $nid = (int) $_POST['notification_id'];
        $appt_id = (int) $_POST['appointment_id'];

        $chk = $con->prepare("SELECT notification_id FROM notification WHERE notification_id = ? AND patient_id = ? AND appointment_id = ? LIMIT 1");
        $chk->bind_param('iii', $nid, $patient_id, $appt_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 1) {
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

//medication records
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

//fetch lab results (simplified - only images)
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

$latestMedNote = null;
if (count($medicationRows) > 0) {
    $latestMedNote = $medicationRows[0]['notes'];
}

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
    <title>Patient Profile - Doctor's Order</title>
    <script src="./Javascript/javascript.js" defer></script>
    <script src="./Javascript/logoutFunction.js" defer></script>
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
        animation: modalSlideIn 0.3s ease-out;  /* ← NEW ANIMATION */
    }

    /* NEW: Modal Animation */
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
        transform: rotate(90deg);  /* ← NEW ROTATION */
    }

    /* Lab Results Grid */
    .lab-results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
        padding: 1rem 0;
    }

    .lab-result-card {
        border: 2px solid var(--primary);
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s;
        background: white;
    }

    .lab-result-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .lab-result-image {
        width: 100%;
        height: 200px;
        object-fit: cover;  /* ← CRITICAL FOR DISPLAY */
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

    /* NEW: Image Viewer Modal */
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
        z-index: 10000;  /* ← Higher than lab modal */
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .image-viewer-modal.active {
        display: block;
        animation: modalSlideIn 0.3s ease-out;
    }

    .full-image {
        max-width: 100%;
        max-height: 70vh;
        display: block;
        margin: 0 auto;
        border-radius: 8px;
    }

    .image-info {
        margin-top: 1.5rem;
        padding: 1rem;
        background-color: #f9f9f9;
        border-radius: 8px;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }

    .image-info p {
        margin-bottom: 0.5rem;
        line-height: 1.6;
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
</style>
</head>
<body>
    <?php include "nav.php"; ?>

    <div class="wrapper">
        <div class="container">
            <div class="profile-card">
                <div class="profile-details">
                    <p style="font-size: 1rem; font-weight: 600; line-height: 1.4; margin-bottom: 1rem;">
                        DATE OF<br>DOCTOR'S ORDER
                    </p>
                    <hr style="border: 1px solid var(--primary); margin: 1rem 0;">
                    <?php if (count($medicationRows) === 0): ?>
                        <p>No orders yet</p>
                    <?php else: ?>
                        <?php foreach ($medicationRows as $m): ?>
                            <?php if (!empty($m['date_prescribed'])): ?>
                                <p style="font-size: 1.2rem; margin-bottom: 0.3rem; line-height: 1.6;">
                                    <?php echo strtoupper(date("F  j, Y", strtotime($m['date_prescribed']))); ?>
                                </p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content">
                <div class="content-section">
                    <h2 class="content-title" style="border: 2px solid var(--primary); padding: 1rem; margin-bottom: 1.5rem; display: block;">
                        Doctor's Order
                    </h2>

                    <div class="upload-box">
                        <div style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--primary);">
                            <p style="font-size: 1rem; margin-bottom: 0.5rem; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;">
                                <strong><?php echo strtoupper(htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name'] . ' ' . (isset($patient['middle_name']) ? $patient['middle_name'] : ''))); ?></strong>
                                &nbsp;&nbsp;&nbsp;
                                <strong><?php echo htmlspecialchars($age); ?></strong>
                                &nbsp;&nbsp;&nbsp;
                                <strong><?php echo strtoupper(htmlspecialchars($patient['gender'])); ?></strong>
                            </p>
                            <p style="font-size: 1.2rem; margin-top: 0.5rem; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;">
                                <?php echo htmlspecialchars(!empty($patient['date_of_birth']) ? date("m/d/Y", strtotime($patient['date_of_birth'])) : 'N/A'); ?>
                            </p>
                        </div>

                        <?php if (count($medicationRows) > 0 && !empty($medicationRows[0]['notes'])): ?>
                            <div style="margin-bottom: 2.5rem; line-height: 1.9; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 1.2rem;">
                                <p><?php echo htmlspecialchars($medicationRows[0]['notes']); ?></p>
                            </div>
                        <?php else: ?>
                            <div style="margin-bottom: 2.5rem; line-height: 1.9; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 1.2rem;">
                                <p>No doctor's orders available.</p>
                            </div>
                        <?php endif; ?>

                        <?php if (count($medicationRows) > 0): ?>
                            <?php
                                $prescribedBy = (!empty($medicationRows[0]['nurse_fn']) || !empty($medicationRows[0]['nurse_ln']))
                                    ? trim($medicationRows[0]['nurse_fn'].' '.$medicationRows[0]['nurse_ln'])
                                    : 'N/A';
                                $nurseId = !empty($medicationRows[0]['nurse_id']) ? $medicationRows[0]['nurse_id'] : 'N/A';
                            ?>
                            <div style="margin-top: 2.5rem; padding-top: 1.5rem; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;", sans-serif; font-size: 1.2rem;">
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

                <div class="content-section2" style="margin-top: 0;">
                    <div class="prescription-box" style="border: 2px solid var(--primary); height: 95%; width: 100%; padding: 1.5rem;">
                        <table style="width: 100%; border-collapse: collapse; font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--primary);">
                                    <th style="text-align: left; padding: 1rem; font-size: 1.05rem; font-weight: 600;">
                                        Medications
                                    </th>
                                    <th style="text-align: left; padding: 1rem; font-size: 1.05rem; font-weight: 600;">
                                        Date
                                    </th>
                                    <th style="text-align: left; padding: 1rem; font-size: 1.05rem; font-weight: 600;">
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
                                            $dateOnly = !empty($m['date_prescribed']) ? date("m/d/Y", strtotime($m['date_prescribed'])) : 'N/A';
                                            $timeOnly = !empty($m['date_prescribed']) ? date("g:i A", strtotime($m['date_prescribed'])) : 'N/A';
                                        ?>
                                        <tr style="border-bottom: 1px solid #ddd;font-size: 1.2rem;">
                                            <td style="padding: 1rem; vertical-align: top;">
                                                <div>
                                                    <p style="margin-bottom: 0.4rem;">
                                                        <strong><?php echo htmlspecialchars($m['medication_name']); ?></strong>
                                                        (Type: <?php echo htmlspecialchars($m['medication_type']); ?>)
                                                    </p>
                                                    <p style="margin-bottom: 0.3rem;">
                                                        Dosage: <?php echo htmlspecialchars($m['dose']); ?>
                                                    </p>
                                                    <p style="margin-bottom: 0.3rem;">
                                                        Frequency: <?php echo $frequencyDisplay; ?>
                                                    </p>
                                                    <p style="margin-bottom: 0.3rem;">
                                                        Schedule Start: <?php echo $startDisplay; ?>
                                                    </p>
                                                    <p style="color: #d9534f;">
                                                        Next Intake: <?php echo $nextIntakeDisplay; ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td style="padding: 1rem; vertical-align: top; font-size: 0.9rem;">
                                                <?php echo $dateOnly; ?>
                                            </td>
                                            <td style="padding: 1rem; vertical-align: top; font-size: 0.9rem;">
                                                <?php echo $timeOnly; ?>
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
                <button onclick="openLabModal()" style="background: none;border: none;color: var(--primary);font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;", sans-serif;font-size: 1rem;font-weight: 500;cursor: pointer;">Lab Results</button>
            </div>
            <form method="POST" action="./logout.php" onsubmit="return confirmLogout()">
                <div class="button-container">
                    <button type="submit" name="logout" id="logout">Logout</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay" onclick="closeLabModal()"></div>

    <!-- Lab Results Modal -->
    <div class="lab-modal" id="labModal">
        <div class="modal-header">
            <h2>📋 Laboratory Results</h2>
            <button class="modal-close" onclick="closeLabModal()">&times;</button>
        </div>

        <div class="modal-body">
            <?php if (count($labResultsRows) === 0): ?>
                <p class="no-results">No laboratory results available.</p>
            <?php else: ?>
                <div class="lab-results-grid">
                    <?php foreach ($labResultsRows as $lab): ?>
                        <div class="lab-result-card" onclick="viewImage(<?php echo $lab['lab_result_id']; ?>, '<?php echo htmlspecialchars(addslashes($lab['image_path'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($lab['image_name'] ?? 'Lab Result')); ?>', '<?php echo htmlspecialchars(addslashes($lab['upload_date'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes(trim(($lab['nurse_fn'] ?? '') . ' ' . ($lab['nurse_ln'] ?? '')))); ?>', '<?php echo htmlspecialchars(addslashes($lab['notes'] ?? '')); ?>')">
                            <img src="<?php echo htmlspecialchars($lab['image_path'] ?? ''); ?>"
                                alt="Lab Result"
                                class="lab-result-image"
                                onerror="this.src='Assets/placeholder.jpg'">
                            <div class="lab-result-info">
                                <div class="lab-result-date">
                                    <strong>📅 <?php echo htmlspecialchars(date("M d, Y", strtotime($lab['upload_date']))); ?></strong>
                                </div>
                                <div class="lab-result-date">
                                    🕐 <?php echo htmlspecialchars(date("g:i A", strtotime($lab['upload_date']))); ?>
                                </div>
                                <div class="lab-result-nurse">
                                    Uploaded by: <?php echo htmlspecialchars(trim(($lab['nurse_fn'] ?? 'N/A') . ' ' . ($lab['nurse_ln'] ?? ''))); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- NEW: Image Viewer Modal (for full-size view) -->
    <div class="modal-overlay" id="imageViewerOverlay" onclick="closeImageViewer()"></div>

    <div class="image-viewer-modal" id="imageViewerModal">
        <div class="modal-header">
            <h2>🔬 Lab Result Details</h2>
            <button class="modal-close" onclick="closeImageViewer()">&times;</button>
        </div>

        <div class="modal-body">
            <img id="fullImage" class="full-image" src="" alt="Lab Result">
            <div class="image-info" id="imageInfo"></div>
        </div>
    </div>

    <script>
    function openLabModal() {
        document.getElementById('labModal').classList.add('active');
        document.getElementById('modalOverlay').classList.add('active');
        document.body.classList.add('modal-active');
    }

    function closeLabModal() {
        document.getElementById('labModal').classList.remove('active');
        document.getElementById('modalOverlay').classList.remove('active');
        document.body.classList.remove('modal-active');
    }

    function viewImage(labId, imagePath, imageName, uploadDate, nurseName, notes) {
        // Set the image source
        document.getElementById('fullImage').src = imagePath;

        // Format the date
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

        // Build info HTML
        let infoHTML = `
            <p><strong>File Name:</strong> ${imageName}</p>
            <p><strong>Upload Date:</strong> ${formattedDate} at ${formattedTime}</p>
            <p><strong>Uploaded By:</strong> ${nurseName || 'N/A'}</p>
        `;

        if (notes && notes.trim() !== '') {
            infoHTML += `<p><strong>Notes:</strong> ${notes}</p>`;
        }

        document.getElementById('imageInfo').innerHTML = infoHTML;

        // Show the image viewer modal
        document.getElementById('imageViewerModal').classList.add('active');
        document.getElementById('imageViewerOverlay').classList.add('active');
        document.body.classList.add('modal-active');
    }

    function closeImageViewer() {
        document.getElementById('imageViewerModal').classList.remove('active');
        document.getElementById('imageViewerOverlay').classList.remove('active');
        // Only remove modal-active if lab modal is also closed
        if (!document.getElementById('labModal').classList.contains('active')) {
            document.body.classList.remove('modal-active');
        }
    }

    // Close modals on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageViewer();
            closeLabModal();
        }
    });
    </script>
</body>
</html>