<?php
include 'connection.php';
session_name('nurse_session');
session_start();

$nurse_id = isset($_SESSION['nurse_id']) ? $_SESSION['nurse_id'] : null;

if (!$nurse_id) {
    header("Location: admin-login.php");
    exit();
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if ($patient_id <= 0) {
    header("Location: adm-patient-list.php");
    exit();
}

// Fetch patient details
$patient_sql = "
    SELECT p.*, u.first_name, u.middle_name, u.last_name, u.email
    FROM patients p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.patient_id = ?
";

$stmt = $con->prepare($patient_sql);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient_result = $stmt->get_result();

if ($patient_result->num_rows === 0) {
    echo "Patient not found.";
    exit();
}

$patient = $patient_result->fetch_assoc();
$stmt->close();

// Check for previous admissions (readmission check)
$admission_check_sql = "
    SELECT COUNT(*) as admission_count,
           MAX(admission_date) as last_admission
    FROM admission_data
    WHERE patient_id = ?
";

$stmt = $con->prepare($admission_check_sql);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$admission_result = $stmt->get_result();
$admission_info = $admission_result->fetch_assoc();
$stmt->close();

$is_readmission = ($admission_info['admission_count'] > 0);
$admission_count = $admission_info['admission_count'];
$last_admission = $admission_info['last_admission'];

// Handle form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_date = $_POST['admission_date'] ?? '';
    $admission_time = $_POST['admission_time'] ?? '';
    $mode_of_arrival = $_POST['mode_of_arrival'] ?? '';
    $instructed = $_POST['instructed'] ?? '';
    $glasses_or_contactlens = $_POST['glasses_or_contactlens'] ?? 'no';
    $dentures = $_POST['dentures'] ?? 'no';
    $ambulatory_or_prosthesis = $_POST['ambulatory_or_prosthesis'] ?? 'no';
    $smoker = $_POST['smoker'] ?? 'no';
    $drinker = $_POST['drinker'] ?? 'no';

    // Validate required fields
    if (empty($admission_date) || empty($admission_time) || empty($mode_of_arrival) || empty($instructed)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Insert new admission record
        $insert_sql = "
            INSERT INTO admission_data
            (patient_id, nurse_id, admission_date, admission_time, mode_of_arrival,
             instructed, glasses_or_contactlens, dentures, ambulatory_or_prosthesis,
             smoker, drinker, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $con->prepare($insert_sql);
        $stmt->bind_param(
            'iisssssssssi',
            $patient_id, $nurse_id, $admission_date, $admission_time, $mode_of_arrival,
            $instructed, $glasses_or_contactlens, $dentures, $ambulatory_or_prosthesis,
            $smoker, $drinker, $nurse_id
        );

        if ($stmt->execute()) {
            // Update patient status to in-patient
            $update_status_sql = "UPDATE patients SET patient_status = 'in-patient' WHERE patient_id = ?";
            $update_stmt = $con->prepare($update_status_sql);
            $update_stmt->bind_param('i', $patient_id);
            $update_stmt->execute();
            $update_stmt->close();

            $success_message = $is_readmission
                ? "Patient successfully readmitted! This is admission #" . ($admission_count + 1)
                : "Patient successfully admitted!";

            // Redirect after 2 seconds
            header("refresh:2;url=adm-patient-list.php");
        } else {
            $error_message = "Error admitting patient: " . $stmt->error;
        }

        $stmt->close();
    }
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Patient - E-Charting System</title>
    <link rel="stylesheet" href="./Styles/admit-patient.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <?php include "adm-nav.php"; ?>

    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="admission-header">
                <h1><?php echo $is_readmission ? 'Readmit Patient' : 'Admit Patient'; ?></h1>
                <p class="patient-name">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']); ?>
                </p>
            </div>

            <!-- Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <!-- Readmission Warning -->
            <?php if ($is_readmission): ?>
                <div class="readmission-banner">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Readmission Alert:</strong> This patient has been admitted <?php echo $admission_count; ?> time(s) previously.
                        Last admission: <?php echo date('F j, Y', strtotime($last_admission)); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Admission Form -->
            <form method="POST" action="" class="admission-form">
                <!-- Date & Time -->
                <div class="form-section">
                    <h2>Admission Details</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Admission Date <span class="required">*</span></label>
                            <input type="date" name="admission_date" required
                                   value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label>Admission Time <span class="required">*</span></label>
                            <input type="time" name="admission_time" required
                                   value="<?php echo date('H:i'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Arrival & Instructions -->
                <div class="form-section">
                    <h2>Arrival Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Mode of Arrival <span class="required">*</span></label>
                            <select name="mode_of_arrival" required>
                                <option value="">Select...</option>
                                <option value="wheelchair">Wheelchair</option>
                                <option value="stretcher">Stretcher</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Patient Instructed <span class="required">*</span></label>
                            <select name="instructed" required>
                                <option value="">Select...</option>
                                <option value="wardset">Ward Set</option>
                                <option value="medication">Medication</option>
                                <option value="hospital-rules">Hospital Rules</option>
                                <option value="special">Special Procedure</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Medical Equipment -->
                <div class="form-section">
                    <h2>Medical Equipment & Aids</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Glasses or Contact Lens</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="glasses_or_contactlens" value="yes"> Yes
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="glasses_or_contactlens" value="no" checked> No
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Dentures</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="dentures" value="yes"> Yes
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="dentures" value="no" checked> No
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ambulatory or Prosthesis</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="ambulatory_or_prosthesis" value="yes"> Yes
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="ambulatory_or_prosthesis" value="no" checked> No
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Lifestyle -->
                <div class="form-section">
                    <h2>Lifestyle Information</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Smoker</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="smoker" value="yes"> Yes
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="smoker" value="no" checked> No
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Drinker</label>
                            <div class="radio-group">
                                <label class="radio-label">
                                    <input type="radio" name="drinker" value="yes"> Yes
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="drinker" value="no" checked> No
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-hospital"></i>
                        <?php echo $is_readmission ? 'Readmit Patient' : 'Admit Patient'; ?>
                    </button>
                    <a href="adm-patient-list.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>