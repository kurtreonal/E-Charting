<?php
// Database connection
include 'connection.php';
session_start();

// Only allow nurse
if (!isset($_SESSION["is_nurse"]) || $_SESSION["is_nurse"] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Error / success messages
$error = "";
$success = "";
$errors = [];
$patient_data = [];

// Helper to get POST values
function p($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

// Get patient ID from URL
$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($patient_id === 0) {
    $error = "Invalid Patient ID.";
} else {
    // Fetch patient data - FIX: JOIN with users table to get names
    $stmt = $con->prepare("
        SELECT
            p.patient_id,
            p.date_of_birth,
            p.gender,
            p.contact_number,
            p.address,
            p.patient_status,
            n.nurse_id,
            u.user_id,
            u.email,
            u.first_name,
            u.last_name,
            u.middle_name,
            a.admission_date,
            a.admission_time,
            a.mode_of_arrival,
            a.instructed,
            a.glasses_or_contactlens,
            a.dentures,
            a.ambulatory_or_prosthesis,
            a.smoker,
            a.drinker,
            h.history_date,
            h.allergies,
            h.duration_of_symptoms,
            h.regular_medication,
            h.dietary_habits,
            h.elimination_habits,
            h.sleep_patterns,
            h.personal_care,
            h.ambulation,
            h.communication_problem,
            h.isolation,
            h.skin_care,
            h.wound_care,
            h.others,
            pa.height,
            pa.weight,
            pa.bp_lft,
            pa.pulse,
            pa.strong,
            pa.status,
            pa.orientation,
            pa.skin_color,
            pa.skin_turgor,
            pa.skin_temp,
            pa.mucous_membrane,
            pa.peripheral_sounds,
            pa.neck_vein_distention,
            pa.respiratory_status,
            pa.respiratory_sounds,
            pa.cough,
            pa.sputum,
            pa.temp_ranges,
            pa.temperature
        FROM patients p
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN admission_data a ON p.patient_id = a.patient_id
        LEFT JOIN history h ON p.patient_id = h.patient_id
        LEFT JOIN physical_assessment pa ON p.patient_id = pa.patient_id
        LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
        WHERE p.patient_id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        $error = "Prepare statement failed: " . $con->error;
    } else {
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $patient_data = $result->fetch_assoc();
        } else {
            $error = "Patient not found.";
        }
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_patient'])) {

    // --- Patient personal info ---
    $first_name = p('first_name');
    $middle_name = p('middle_name') ?: null;
    $last_name = p('last_name');
    $date_of_birth = p('date_of_birth');
    $gender = p('gender');
    $contact_number = p('contact_number');
    $address = p('address');
    $patient_status = p('patient_status') ?: null;
    $email = p('email');
    $password = p('password');

    // --- Admission info ---
    $admission_date = p('admission_date');
    $admission_time = p('admission_time');
    $mode_of_arrival = p('mode_of_arrival') ?: null;
    $instructed = p('instructed') ?: null;
    $glasses_or_contactlens = p('glasses_contact') ?: null;
    $dentures = p('dentures') ?: null;
    $ambulatory_or_prosthesis = p('ambulatory') ?: null;
    $smoker = p('smoker') ?: null;
    $drinker = p('drinker') ?: null;

    // --- History ---
    $history_date = p('history_date');
    $allergies = p('allergies');
    $duration_of_symptoms = p('duration_of_symptoms');
    $regular_medication = p('regular_medication');
    $dietary_habits = p('dietary_habits');
    $elimination_habits = p('elimination_habits');
    $sleep_patterns = p('sleep_patterns');
    $personal_care = p('personal-care') ?: null;
    $ambulation = p('ambulation') ?: null;
    $communication_problem = p('communication') ?: null;
    $isolation = p('isolation') ?: null;
    $skin_care = p('skin-care') ?: null;
    $wound_care = p('wound-care') ?: null;
    $others = p('others') ?: '';

    // --- Physical assessment ---
    $height = p('height');
    $weight = p('weight');
    $bp_lft = p('BP_lft');
    $pulse = p('pulse');
    $strong = p('strong');
    $temp_ranges = p('temp_ranges');
    $status = p('respiration') ?: null;
    $orientation = p('orientation') ?: null;
    $skin_color = p('skin') ?: null;
    $skin_turgor = p('skin-turgor') ?: null;
    $skin_temp = p('skin-temp') ?: null;
    $mucous_membrane = p('mucous-membrane') ?: null;
    $peripheral_sounds = p('peripheral-sounds') ?: null;
    $neck_vein_distention = p('neck-vein-distention') ?: null;
    $respiratory_status = p('respiratory-status') ?: null;
    $respiratory_sounds = p('respiratory-sounds') ?: null;
    $cough = p('cough') ?: null;
    $sputum = p('sputum') ?: null;
    $temperature = p('temperature') ?: null;

    // --- Validation ---
    if (empty($first_name)) $errors[] = "First Name is required.";
    if (empty($last_name)) $errors[] = "Last Name is required.";
    if (empty($date_of_birth)) $errors[] = "Date of Birth is required.";
    if (!empty($date_of_birth)) {
        $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if (!$dob || $dob->format('Y-m-d') !== $date_of_birth) {
            $errors[] = "Date of Birth must be in YYYY-MM-DD format.";
        }
    }
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($admission_date)) $errors[] = "Admission Date is required.";
    if (!empty($admission_date)) {
        $adm_date = DateTime::createFromFormat('Y-m-d', $admission_date);
        if (!$adm_date || $adm_date->format('Y-m-d') !== $admission_date) {
            $errors[] = "Admission Date must be in YYYY-MM-DD format.";
        }
    }
    if (empty($admission_time)) $errors[] = "Admission Time is required.";

    // Physical numeric checks
    foreach (['height','weight','bp_lft','pulse','strong','temp_ranges'] as $field) {
        if (!is_numeric($$field)) $errors[] = ucfirst($field) . " must be numeric.";
    }

    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    } else {
        // Begin transaction
        $con->begin_transaction();
        try {
            $user_id = $patient_data['user_id'];

            // --- 1. Update users table ---
            $stmt = $con->prepare("
                UPDATE users
                SET first_name = ?, middle_name = ?, last_name = ?, email = ?
                WHERE user_id = ?
            ");
            if (!$stmt) throw new Exception("Prepare users update failed: " . $con->error);
            $stmt->bind_param("ssssi", $first_name, $middle_name, $last_name, $email, $user_id);
            if (!$stmt->execute()) throw new Exception("Update users failed: " . $stmt->error);
            $stmt->close();

            // --- 2. Update patients table ---
            $stmt = $con->prepare("
                UPDATE patients
                SET date_of_birth = ?, gender = ?, contact_number = ?, address = ?, patient_status = ?
                WHERE patient_id = ?
            ");
            if (!$stmt) throw new Exception("Prepare patients update failed: " . $con->error);
            $stmt->bind_param("ssissi", $date_of_birth, $gender, $contact_number, $address, $patient_status, $patient_id);
            if (!$stmt->execute()) throw new Exception("Update patients failed: " . $stmt->error);
            $stmt->close();

            // --- 3. Update or insert admission_data ---
            $chk = $con->prepare("SELECT admission_data_id FROM admission_data WHERE patient_id = ?");
            $chk->bind_param("i", $patient_id);
            $chk->execute(); $chk->store_result();
            $admission_exists = $chk->num_rows > 0;
            $chk->close();

            if ($admission_exists) {
                $stmt = $con->prepare("
                    UPDATE admission_data
                    SET admission_date = ?, admission_time = ?, mode_of_arrival = ?, instructed = ?,
                        glasses_or_contactlens = ?, dentures = ?, ambulatory_or_prosthesis = ?, smoker = ?, drinker = ?
                    WHERE patient_id = ?
                ");
                $stmt->bind_param(
                    "sssssssssi",
                    $admission_date, $admission_time, $mode_of_arrival, $instructed,
                    $glasses_or_contactlens, $dentures, $ambulatory_or_prosthesis, $smoker, $drinker, $patient_id
                );
            } else {
                $stmt = $con->prepare("
                    INSERT INTO admission_data (patient_id, nurse_id, admission_date, admission_time, mode_of_arrival, instructed,
                                               glasses_or_contactlens, dentures, ambulatory_or_prosthesis, smoker, drinker, created_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $nurse_id = $patient_data['nurse_id'];
                $stmt->bind_param(
                    "iisssssssss",
                    $patient_id, $nurse_id, $admission_date, $admission_time, $mode_of_arrival, $instructed,
                    $glasses_or_contactlens, $dentures, $ambulatory_or_prosthesis, $smoker, $drinker
                );
            }
            if (!$stmt->execute()) throw new Exception("Admission update/insert failed: " . $stmt->error);
            $stmt->close();

            // --- 4. Update or insert history ---
            $chk = $con->prepare("SELECT history_id FROM history WHERE patient_id = ?");
            $chk->bind_param("i", $patient_id);
            $chk->execute(); $chk->store_result();
            $history_exists = $chk->num_rows > 0;
            $chk->close();

            if ($history_exists) {
                $stmt = $con->prepare("
                    UPDATE history
                    SET history_date = ?, allergies = ?, duration_of_symptoms = ?, regular_medication = ?,
                        dietary_habits = ?, elimination_habits = ?, sleep_patterns = ?, personal_care = ?,
                        ambulation = ?, communication_problem = ?, isolation = ?, skin_care = ?, wound_care = ?, others = ?
                    WHERE patient_id = ?
                ");
                $stmt->bind_param(
                    "ssssssssssssssi",
                    $history_date, $allergies, $duration_of_symptoms, $regular_medication,
                    $dietary_habits, $elimination_habits, $sleep_patterns, $personal_care, $ambulation,
                    $communication_problem, $isolation, $skin_care, $wound_care, $others, $patient_id
                );
            } else {
                $stmt = $con->prepare("
                    INSERT INTO history (patient_id, nurse_id, history_date, allergies, duration_of_symptoms, regular_medication,
                                         dietary_habits, elimination_habits, sleep_patterns, personal_care, ambulation,
                                         communication_problem, isolation, skin_care, wound_care, others, created_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $nurse_id = $patient_data['nurse_id'];
                $stmt->bind_param(
                    "iisssssssssssss",
                    $patient_id, $nurse_id, $history_date, $allergies, $duration_of_symptoms,
                    $regular_medication, $dietary_habits, $elimination_habits, $sleep_patterns, $personal_care,
                    $ambulation, $communication_problem, $isolation, $skin_care, $wound_care, $others
                );
            }
            if (!$stmt->execute()) throw new Exception("History update/insert failed: " . $stmt->error);
            $stmt->close();

            // --- 5. Update or insert physical_assessment ---
            $height = (int)$height; $weight = (int)$weight; $bp_lft = (int)$bp_lft;
            $pulse = (int)$pulse; $strong = (int)$strong; $temp_ranges = (int)$temp_ranges;

            $chk = $con->prepare("SELECT physical_assessment_id FROM physical_assessment WHERE patient_id = ?");
            $chk->bind_param("i", $patient_id);
            $chk->execute(); $chk->store_result();
            $physical_exists = $chk->num_rows > 0;
            $chk->close();

            if ($physical_exists) {
                $stmt = $con->prepare("
                    UPDATE physical_assessment
                    SET height = ?, weight = ?, bp_lft = ?, pulse = ?, strong = ?, status = ?, orientation = ?,
                        skin_color = ?, skin_turgor = ?, skin_temp = ?, mucous_membrane = ?, peripheral_sounds = ?,
                        neck_vein_distention = ?, respiratory_status = ?, respiratory_sounds = ?, cough = ?,
                        sputum = ?, temp_ranges = ?, temperature = ?
                    WHERE patient_id = ?
                ");
                $stmt->bind_param(
                    "iiiiissssssssssssisi",
                    $height, $weight, $bp_lft, $pulse, $strong, $status, $orientation,
                    $skin_color, $skin_turgor, $skin_temp, $mucous_membrane, $peripheral_sounds,
                    $neck_vein_distention, $respiratory_status, $respiratory_sounds, $cough, $sputum,
                    $temp_ranges, $temperature, $patient_id
                );
            } else {
                $stmt = $con->prepare("
                    INSERT INTO physical_assessment (patient_id, nurse_id, height, weight, bp_lft, pulse, strong, status,
                                                    orientation, skin_color, skin_turgor, skin_temp, mucous_membrane,
                                                    peripheral_sounds, neck_vein_distention, respiratory_status,
                                                    respiratory_sounds, cough, sputum, temp_ranges, temperature, created_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $nurse_id = $patient_data['nurse_id'];
                $stmt->bind_param(
                    "iiiiiisssssssssssssi",
                    $patient_id, $nurse_id, $height, $weight, $bp_lft, $pulse, $strong,
                    $status, $orientation, $skin_color, $skin_turgor, $skin_temp, $mucous_membrane,
                    $peripheral_sounds, $neck_vein_distention, $respiratory_status, $respiratory_sounds,
                    $cough, $sputum, $temp_ranges, $temperature
                );
            }
            if (!$stmt->execute()) throw new Exception("Physical assessment update/insert failed: " . $stmt->error);
            $stmt->close();

            // Commit transaction
            $con->commit();
            $success = "Patient record updated successfully!";

            // Refresh patient data
            $stmt = $con->prepare("
                SELECT
                    p.patient_id,
                    p.date_of_birth,
                    p.gender,
                    p.contact_number,
                    p.address,
                    p.patient_status,
                    n.nurse_id,
                    u.user_id,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.middle_name,
                    a.admission_date,
                    a.admission_time,
                    a.mode_of_arrival,
                    a.instructed,
                    a.glasses_or_contactlens,
                    a.dentures,
                    a.ambulatory_or_prosthesis,
                    a.smoker,
                    a.drinker,
                    h.history_date,
                    h.allergies,
                    h.duration_of_symptoms,
                    h.regular_medication,
                    h.dietary_habits,
                    h.elimination_habits,
                    h.sleep_patterns,
                    h.personal_care,
                    h.ambulation,
                    h.communication_problem,
                    h.isolation,
                    h.skin_care,
                    h.wound_care,
                    h.others,
                    pa.height,
                    pa.weight,
                    pa.bp_lft,
                    pa.pulse,
                    pa.strong,
                    pa.status,
                    pa.orientation,
                    pa.skin_color,
                    pa.skin_turgor,
                    pa.skin_temp,
                    pa.mucous_membrane,
                    pa.peripheral_sounds,
                    pa.neck_vein_distention,
                    pa.respiratory_status,
                    pa.respiratory_sounds,
                    pa.cough,
                    pa.sputum,
                    pa.temp_ranges,
                    pa.temperature
                FROM patients p
                LEFT JOIN users u ON p.user_id = u.user_id
                LEFT JOIN admission_data a ON p.patient_id = a.patient_id
                LEFT JOIN history h ON p.patient_id = h.patient_id
                LEFT JOIN physical_assessment pa ON p.patient_id = pa.patient_id
                LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
                WHERE p.patient_id = ?
                LIMIT 1
            ");

            $stmt->bind_param("i", $patient_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $patient_data = $result->fetch_assoc();
            $stmt->close();

        } catch (Exception $e) {
            $con->rollback();
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Patient</title>
    <script src="./Javascript/javascript.js" defer></script>
    <link rel="stylesheet" href="./Styles/add-update-patient.css">
</head>
<body>
    <?php include "adm-nav.php" ?>
    <div class="wrapper">
        <div class="container">
            <!-- Error/Success Messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 4px;">
                    <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border: 1px solid #c3e6cb; border-radius: 4px;">
                    <strong>Success:</strong> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h3>NURSING ADMISSION DATA</h3>
            </div>

            <form action="" method="POST" id="add-patient-form">
                <div class="add-patient-form">
                <div class="main-form-section">
                    <div class="datetime-wrapper">
                        <div class="form-group">
                            <label for="admission_date">Date:</label>
                            <input class="signature-input-adm" type="date" id="admission_date" name="admission_date" required>
                        </div>
                        <div class="form-group">
                            <label for="admission_time">Time:</label>
                            <input class="signature-input-adm" type="time" id="admission_time" name="admission_time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <span>Mode of Arrival</span>
                        <div class="radio-group">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="wheelchair" class="item">
                                    <input type="radio" id="wheelchair" name="mode_of_arrival" value="wheelchair" class="hidden toggle-radio"/>
                                    <label for="wheelchair" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="wheelchair" class="cbx-lbl">Wheelchair</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="stretcher" class="item">
                                    <input type="radio" id="stretcher" name="mode_of_arrival" value="stretcher" class="hidden toggle-radio"/>
                                    <label for="stretcher" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="stretcher" class="cbx-lbl">Stretcher</label>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <span>Patient and Family Instructed</span>
                        <div class="radio-group">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="wardset" class="item">
                                    <input type="radio" id="wardset" name="instructed" value="wardset" class="hidden toggle-radio"/>
                                    <label for="wardset" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="wardset" class="cbx-lbl">Ward Set</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="medication" class="item">
                                    <input type="radio" id="medication" name="instructed" value="medication" class="hidden toggle-radio"/>
                                    <label for="medication" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="medication" class="cbx-lbl">Medication</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="hospital-rules" class="item">
                                    <input type="radio" id="hospital-rules" name="instructed" value="hospital-rules" class="hidden toggle-radio"/>
                                    <label for="hospital-rules" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="hospital-rules" class="cbx-lbl">Hospital Rules</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="special" class="item">
                                    <input type="radio" id="special" name="instructed" value="special" class="hidden toggle-radio"/>
                                    <label for="special" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="special" class="cbx-lbl">Special Procedure Next of Kin</label>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <span>Patient Status</span>
                        <div class="radio-group">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="out-patient" class="item">
                                    <input type="radio" id="out-patient" name="patient_status" value="out-patient" class="hidden toggle-radio"/>
                                    <label for="out-patient" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="wout-patient" class="cbx-lbl">Out-patient</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="in-patient" class="item">
                                    <input type="radio" id="in-patient" name="patient_status" value="in-patient" class="hidden toggle-radio"/>
                                    <label for="in-patient" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="in-patient" class="cbx-lbl">In-Patient</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="active" class="item">
                                    <input type="radio" id="active" name="patient_status" value="active" class="hidden toggle-radio"/>
                                    <label for="active" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="active" class="cbx-lbl">Active</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="deceased" class="item">
                                    <input type="radio" id="deceased" name="patient_status" value="deceased" class="hidden toggle-radio"/>
                                    <label for="deceased" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="deceased" class="cbx-lbl">Deceased</label>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="side-form-section">
                    <div class="form-group">
                        <span>Glasses/Contact Lenses</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="glasses-yes" class="item">
                                    <input type="radio" id="glasses-yes" name="glasses_contact" value="yes" />
                                    <label for="glasses-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="glasses-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="glasses-no" class="item">
                                    <input type="radio" id="glasses-no" name="glasses_contact" value="no" />
                                    <label for="glasses-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="glasses-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Dentures</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="dentures-yes" class="item">
                                    <input type="radio" id="dentures-yes" name="dentures" value="yes" />
                                    <label for="dentures-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="dentures-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="dentures-no" class="item">
                                    <input type="radio" id="dentures-no" name="dentures" value="no" />
                                    <label for="dentures-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="dentures-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Ambulatory/Prosthesis</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="ambulatory-yes" class="item">
                                    <input type="radio" id="ambulatory-yes" name="ambulatory" value="yes"/>
                                    <label for="ambulatory-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="ambulatory-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="ambulatory-no" class="item">
                                    <input type="radio" id="ambulatory-no" name="ambulatory" value="no" />
                                    <label for="ambulatory-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="ambulatory-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Smoker</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="smoker-yes" class="item">
                                    <input type="radio" id="smoker-yes" name="smoker" value="yes"/>
                                    <label for="smoker-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="smoker-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="smoker-no" class="item">
                                    <input type="radio" id="smoker-no" name="smoker" value="no" />
                                    <label for="smoker-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="smoker-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <span>Drinker</span>
                        <div class="radio-options">
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="drinker-yes" class="item">
                                    <input type="radio" id="drinker-yes" name="drinker" value="yes" />
                                    <label for="drinker-yes" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="drinker-yes" class="cbx-lbl">Yes</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                <label for="drinker-no" class="item">
                                    <input type="radio" id="drinker-no" name="drinker" value="no" />
                                    <label for="drinker-no" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="drinker-no" class="cbx-lbl">No</label>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-section">
                <h3>NURSING HISTORY</h3>
            </div>

            <div class="add-patient-form">
                <div class="">
                    <div class="history-wrapper">
                        <div class="history-input-wrapper">
                            <div class="form-group">
                                <label for="history_date">Date:</label>
                                <input class="signature-input-adm" type="date" id="history_date" name="history_date" value="" required>
                            </div>
                            <div class="form-group">
                                <label for="allergies">Allergies:</label>
                                <input type="text" id="allergies" name="allergies" placeholder="eg., Penicillin" required>
                            </div>
                            <div class="form-group">
                                <label for="duration_of_symptoms">Reaction for Hospitalization: Duration of Symptoms:</label>
                                <input type="text" id="duration_of_symptoms" name="duration_of_symptoms" placeholder="eg., hives" required>
                            </div>
                            <div class="form-group">
                                <label for="regular_medication">Regular Medication:</label>
                                <input type="text" id="regular_medication" name="regular_medication" placeholder="eg., Lisinopril 10mg, once daily" required>
                            </div>
                            <div class="habits">
                                <div class="form-group">
                                    <label for="dietary_habits">Dietary Habits:</label>
                                    <input type="text" id="dietary_habits" name="dietary_habits" placeholder="eg., Balanced diet" required>
                                </div>
                                <div class="form-group">
                                    <label for="elimination_habits">Elimination Habits:</label>
                                    <input type="text" id="elimination_habits" name="elimination_habits" placeholder="eg., Daily bowel movements" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="sleep_patterns">Sleep Patterns:</label>
                                <input type="text" id="sleep_patterns" name="sleep_patterns" placeholder="eg., Difficulty falling asleep (insomnia)" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="label-headers">
                <h3 class="section-title-patient-needs">PATIENT NEEDS</h3>
                <h3 class="section-title-radio-options">FAMILY HISTORY</h3>
                <h3 class="section-title-others">OTHERS/RELATIONSHIP</h3>
            </div>
            <div class="label-section">
                <div class="form-group">
                    <div class="needs-labels">

                        <!-- Personal Care -->
                        <div class="label-row">
                            <span>Personal Care</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="personal-care-yes" class="item">
                                            <input type="radio" id="personal-care-yes" name="personal-care" value="yes"/>
                                            <label for="personal-care-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="personal-care-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="personal-care-no" class="item">
                                            <input type="radio" id="personal-care-no" name="personal-care" value="no" />
                                            <label for="personal-care-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="personal-care-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="personal-care-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Ambulation -->
                        <div class="label-row">
                            <span>Ambulation</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="ambulation-yes" class="item">
                                            <input type="radio" id="ambulation-yes" name="ambulation" value="yes" />
                                            <label for="ambulation-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="ambulation-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="ambulation-no" class="item">
                                            <input type="radio" id="ambulation-no" name="ambulation" value="no"/>
                                            <label for="ambulation-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="ambulation-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="ambulation-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Communication Problem -->
                        <div class="label-row">
                            <span>Communication Problem</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="communication-yes" class="item">
                                            <input type="radio" id="communication-yes" name="communication" value="yes"/>
                                            <label for="communication-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="communication-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="communication-no" class="item">
                                            <input type="radio" id="communication-no" name="communication" value="no" />
                                            <label for="communication-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="communication-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="communication-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Isolation -->
                        <div class="label-row">
                            <span>Isolation</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="isolation-yes" class="item">
                                            <input type="radio" id="isolation-yes" name="isolation" value="yes" />
                                            <label for="isolation-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="isolation-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="isolation-no" class="item">
                                            <input type="radio" id="isolation-no" name="isolation" value="no" />
                                            <label for="isolation-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="isolation-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="isolation-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Skin Care -->
                        <div class="label-row">
                            <span>Skin Care</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="skin-care-yes" class="item">
                                            <input type="radio" id="skin-care-yes" name="skin-care" value="yes"/>
                                            <label for="skin-care-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="skin-care-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="skin-care-no" class="item">
                                            <input type="radio" id="skin-care-no" name="skin-care" value="no"/>
                                            <label for="skin-care-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="skin-care-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="skin-care-others" class="others-input">
                                </div>
                            </div>
                        </div>

                        <!-- Wound Care -->
                        <div class="label-row">
                            <span>Wound Care</span>
                            <div class="radio-row">
                                <div class="radio-options">
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="wound-care-yes" class="item">
                                            <input type="radio" id="wound-care-yes" name="wound-care" value="yes" />
                                            <label for="wound-care-yes" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="wound-care-yes" class="cbx-lbl">Yes</label>
                                        </label>
                                    </div>
                                    <div class="checkbox-wrapper-52" style="min-width: 40px;">
                                        <label for="wound-care-no" class="item">
                                            <input type="radio" id="wound-care-no" name="wound-care" value="no"/>
                                            <label for="wound-care-no" class="cbx">
                                                <svg width="14px" height="12px" viewBox="0 0 14 12">
                                                    <polyline points="1 7.6 5 11 13 1"></polyline>
                                                </svg>
                                            </label>
                                            <label for="wound-care-no" class="cbx-lbl">No</label>
                                        </label>
                                    </div>
                                </div>
                                <div class="others-group">
                                    <input type="text" name="wound-care-others" class="others-input">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="header-section">
                <h3>NURSING PHYSICAL ASSESSMENT</h3>
            </div>

            <div class="fr">
                <div class="first-row">
                    <span>Ht:</span>
                    <div><input class="fr-input1" type="text" name="height" placeholder="Height" required ></div>

                    <span>Wt:</span>
                    <div><input class="fr-input2" type="text" name="weight" placeholder="Weight" required ></div>

                    <span>BP lft:</span>
                    <div><input class="fr-input3" type="text" name="BP_lft" placeholder="BP reading" required></div>

                    <span>Pulse:</span>
                    <div><input class="fr-input4" type="text" name="pulse" placeholder="Pulse rate" required></div>

                    <span>Strong:</span>
                    <div><input class="fr-input5" type="text" name="strong" required></div>

                    <div class="radio-cell">
                        <div class="checkbox-wrapper-52" id="left" style="margin-right: 30px; margin-left: 2rem; min-width: 0;">
                            <label for="respiration-weak" class="item">
                                <input type="radio" id="respiration-weak" name="respiration" value="weak" class="hidden toggle-radio"/>
                                <label for="respiration-weak" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="respiration-weak" class="cbx-lbl">Weak</label>
                            </label>
                        </div>

                        <div class="checkbox-wrapper-52" id="left" style="margin-right: 30px;min-width: 0;">
                            <label for="respiration-irregular" class="item">
                                <input type="radio" id="respiration-irregular" name="respiration" value="irregular" class="hidden toggle-radio"/>
                                <label for="respiration-irregular" class="cbx">
                                    <svg width="14px" height="12px" viewBox="0 0 14 12">
                                        <polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="respiration-irregular" class="cbx-lbl">Irregular</label>
                            </label>
                        </div>
                    </div>
                </div>

            <div class="assessment-form">
                <table class="assessment-table">
                    <tr>
                        <th>Orientation:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <label for="orientation-time" class="item">
                                    <input type="radio" id="orientation-time" name="orientation" value="time" class="hidden toggle-radio"/>
                                    <label for="orientation-time" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-time" class="cbx-lbl">Time</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="orientation-person" class="item">
                                    <input type="radio" id="orientation-person" name="orientation" value="person" class="hidden toggle-radio"/>
                                    <label for="orientation-person" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-person" class="cbx-lbl">Person</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="orientation-event-disoriented" class="item">
                                    <input type="radio" id="orientation-event-disoriented" name="orientation" value="event-disoriented" class="hidden toggle-radio"/>
                                    <label for="orientation-event-disoriented" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-event-disoriented" class="cbx-lbl">Event Disoriented</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="orientation-confused" class="item">
                                    <input type="radio" id="orientation-confused" name="orientation" value="confused" class="hidden toggle-radio"/>
                                    <label for="orientation-confused" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="orientation-confused" class="cbx-lbl">Confused Behavior</label>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>Skin Color:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <label for="skin-normal" class="item">
                                    <input type="radio" id="skin-normal" name="skin" value="normal" class="hidden toggle-radio"/>
                                    <label for="skin-normal" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-normal" class="cbx-lbl">Normal</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-pale" class="item">
                                    <input type="radio" id="skin-pale" name="skin" value="pale" class="hidden toggle-radio"/>
                                    <label for="skin-pale" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-pale" class="cbx-lbl">Pale</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-cyanotic" class="item">
                                    <input type="radio" id="skin-cyanotic" name="skin" value="cyanotic" class="hidden toggle-radio"/>
                                    <label for="skin-cyanotic" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-cyanotic" class="cbx-lbl">Cyanotic</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-jaundiced" class="item">
                                    <input type="radio" id="skin-jaundiced" name="skin" value="jaundiced" class="hidden toggle-radio"/>
                                    <label for="skin-jaundiced" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-jaundiced" class="cbx-lbl">Jaundiced</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-dusky" class="item">
                                    <input type="radio" id="skin-dusky" name="skin" value="dusky" class="hidden toggle-radio"/>
                                    <label for="skin-dusky" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-dusky" class="cbx-lbl">Dusky</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-modified" class="item">
                                    <input type="radio" id="skin-modified" name="skin" value="modified" class="hidden toggle-radio"/>
                                    <label for="skin-modified" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-modified" class="cbx-lbl">Modified</label>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>Skin Turgor:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <label for="skin-turgor-loose" class="item">
                                    <input type="radio" id="skin-turgor-loose" name="skin-turgor" value="loose" class="hidden toggle-radio"/>
                                    <label for="skin-turgor-loose" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-turgor-loose" class="cbx-lbl">Loose</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-turgor-tight" class="item">
                                    <input type="radio" id="skin-turgor-tight" name="skin-turgor" value="tight" class="hidden toggle-radio"/>
                                    <label for="skin-turgor-tight" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-turgor-tight" class="cbx-lbl">Tight</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-turgor-edema" class="item">
                                    <input type="radio" id="skin-turgor-edema" name="skin-turgor" value="edema" class="hidden toggle-radio"/>
                                    <label for="skin-turgor-edema" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-turgor-edema" class="cbx-lbl">Edema</label>
                                </label>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>Skin Temp:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-warm" class="item">
                                    <input type="radio" id="skin-temp-warm" name="skin-temp" value="warm" class="hidden toggle-radio"/>
                                    <label for="skin-temp-warm" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-warm" class="cbx-lbl">Warm</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-dry" class="item">
                                    <input type="radio" id="skin-temp-dry" name="skin-temp" value="dry" class="hidden toggle-radio"/>
                                    <label for="skin-temp-dry" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-dry" class="cbx-lbl">Dry</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-clammy" class="item">
                                    <input type="radio" id="skin-temp-clammy" name="skin-temp" value="clammy" class="hidden toggle-radio"/>
                                    <label for="skin-temp-clammy" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-clammy" class="cbx-lbl">Clammy</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-cool" class="item">
                                    <input type="radio" id="skin-temp-cool" name="skin-temp" value="cool" class="hidden toggle-radio"/>
                                    <label for="skin-temp-cool" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-cool" class="cbx-lbl">Cool</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-diaphoretic" class="item">
                                    <input type="radio" id="skin-temp-diaphoretic" name="skin-temp" value="diaphoretic" class="hidden toggle-radio"/>
                                    <label for="skin-temp-diaphoretic" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-diaphoretic" class="cbx-lbl">Diaphoretic</label>
                                </label>
                            </div>

                            <div class="checkbox-wrapper-52">
                                <label for="skin-temp-moist" class="item">
                                    <input type="radio" id="skin-temp-moist" name="skin-temp" value="moist" class="hidden toggle-radio"/>
                                    <label for="skin-temp-moist" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="skin-temp-moist" class="cbx-lbl">Moist</label>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Mucous Membrane:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="mucous-moist" class="item"><input type="radio" id="mucous-moist" name="mucous-membrane" value="moist" class="hidden toggle-radio"/><label for="mucous-moist" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-moist" class="cbx-lbl">Moist</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="mucous-dry" class="item"><input type="radio" id="mucous-dry" name="mucous-membrane" value="dry" class="hidden toggle-radio"/><label for="mucous-dry" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-dry" class="cbx-lbl">Dry</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="mucous-cracked" class="item"><input type="radio" id="mucous-cracked" name="mucous-membrane" value="cracked" class="hidden toggle-radio"/><label for="mucous-cracked" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-cracked" class="cbx-lbl">Cracked</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="mucous-sore" class="item"><input type="radio" id="mucous-sore" name="mucous-membrane" value="sore" class="hidden toggle-radio"/><label for="mucous-sore" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="mucous-sore" class="cbx-lbl">Sore</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Peripheral Sounds:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="peripheral-audible" class="item"><input type="radio" id="peripheral-audible" name="peripheral-sounds" value="audible" class="hidden toggle-radio"/><label for="peripheral-audible" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="peripheral-audible" class="cbx-lbl">Audible</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="peripheral-sound" class="item"><input type="radio" id="peripheral-sound" name="peripheral-sounds" value="sound" class="hidden toggle-radio"/><label for="peripheral-sound" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="peripheral-sound" class="cbx-lbl">Sound</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Neck Vein Distention a! 45:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="neck-absent" class="item"><input type="radio" id="neck-absent" name="neck-vein-distention" value="absent" class="hidden toggle-radio"/><label for="neck-absent" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="neck-absent" class="cbx-lbl">Absent</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="neck-flat" class="item"><input type="radio" id="neck-flat" name="neck-vein-distention" value="flat" class="hidden toggle-radio"/><label for="neck-flat" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="neck-flat" class="cbx-lbl">Flat</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Respiratory Status:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="resp-labored" class="item"><input type="radio" id="resp-labored" name="respiratory-status" value="labored" class="hidden toggle-radio"/><label for="resp-labored" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-labored" class="cbx-lbl">Labored</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-unlabored" class="item"><input type="radio" id="resp-unlabored" name="respiratory-status" value="unlabored" class="hidden toggle-radio"/><label for="resp-unlabored" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-unlabored" class="cbx-lbl">Unlabored</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-sob" class="item"><input type="radio" id="resp-sob" name="respiratory-status" value="sob" class="hidden toggle-radio"/><label for="resp-sob" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-sob" class="cbx-lbl">SOB</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-accessory" class="item"><input type="radio" id="resp-accessory" name="respiratory-status" value="accessory" class="hidden toggle-radio"/><label for="resp-accessory" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline>
                                    </svg>
                                </label>
                                <label for="resp-accessory" class="cbx-lbl">Accessory Muscles</label>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th>Respiratory Sounds:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="resp-clear" class="item"><input type="radio" id="resp-clear" name="respiratory-sounds" value="clear" class="hidden toggle-radio"/><label for="resp-clear" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-clear" class="cbx-lbl">Rules</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-rhonchi" class="item"><input type="radio" id="resp-rhonchi" name="respiratory-sounds" value="rhonchi" class="hidden toggle-radio"/><label for="resp-rhonchi" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-rhonchi" class="cbx-lbl">Bhonchi Wheezing</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="resp-crackles" class="item"><input type="radio" id="resp-crackles" name="respiratory-sounds" value="crackles" class="hidden toggle-radio"/><label for="resp-crackles" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="resp-crackles" class="cbx-lbl">Clear</label></label></div>
                        </td>
                    </tr>
                        <th>Cough:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="cough-none" class="item"><input type="radio" id="cough-none" name="cough" value="none" class="hidden toggle-radio"/><label for="cough-none" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="cough-none" class="cbx-lbl">None</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="cough-productive" class="item"><input type="radio" id="cough-productive" name="cough" value="productive" class="hidden toggle-radio"/><label for="cough-productive" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="cough-productive" class="cbx-lbl">Productive</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="cough-dry" class="item"><input type="radio" id="cough-dry" name="cough" value="dry" class="hidden toggle-radio"/><label for="cough-dry" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="cough-dry" class="cbx-lbl">None Productive</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Sputum:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52"><label for="sputum-moderate" class="item"><input type="radio" id="sputum-moderate" name="sputum" value="moderate" class="hidden toggle-radio"/><label for="sputum-moderate" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-moderate" class="cbx-lbl">Moderate</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-large" class="item"><input type="radio" id="sputum-large" name="sputum" value="large" class="hidden toggle-radio"/><label for="sputum-large" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-large" class="cbx-lbl">Large</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-thin" class="item"><input type="radio" id="sputum-thin" name="sputum" value="thin" class="hidden toggle-radio"/><label for="sputum-thin" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-thin" class="cbx-lbl">Thin</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-thick" class="item"><input type="radio" id="sputum-thick" name="sputum" value="thick" class="hidden toggle-radio"/><label for="sputum-thick" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-thick" class="cbx-lbl">Thick</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-mucoid" class="item"><input type="radio" id="sputum-mucoid" name="sputum" value="mucoid" class="hidden toggle-radio"/><label for="sputum-mucoid" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-mucoid" class="cbx-lbl">Mucoid</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-tenacious" class="item"><input type="radio" id="sputum-tenacious" name="sputum" value="tenacious" class="hidden toggle-radio"/><label for="sputum-tenacious" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-tenacious" class="cbx-lbl">Frothy Tenacious</label></label></div>
                        </td>
                    </tr>

                    <tr>
                        <th>Temperature:</th>
                        <td colspan="10">
                            <div class="checkbox-wrapper-52">
                                <input class="temp-input" type="number" name="temp_ranges" id="temp-ranges" placeholder="Degrees" required>
                            </div>
                            <div class="checkbox-wrapper-52">
                                <label for="temperature-oral" class="item">
                                    <input type="radio" id="temperature-oral" name="temperature" value="oral" class="hidden toggle-radio"/>
                                    <label for="temperature-oral" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="temperature-oral" class="cbx-lbl">Oral</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52">
                                <label for="temperature-axilla" class="item">
                                    <input type="radio" id="temperature-axilla" name="temperature" value="axilla" class="hidden toggle-radio"/>
                                    <label for="temperature-axilla" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="temperature-axilla" class="cbx-lbl">Axilla</label>
                                </label>
                            </div>
                            <div class="checkbox-wrapper-52">
                                <label for="temperature-rectal" class="item">
                                    <input type="radio" id="temperature-rectal" name="temperature" value="rectal" class="hidden toggle-radio"/>
                                    <label for="temperature-rectal" class="cbx">
                                        <svg width="14px" height="12px" viewBox="0 0 14 12">
                                            <polyline points="1 7.6 5 11 13 1"></polyline>
                                        </svg>
                                    </label>
                                    <label for="temperature-rectal" class="cbx-lbl">Rectal</label>
                                </label>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <br>
            <div class="header-section">
                <h3>PATIENT INFORMATION</h3>
            </div>

            <div class="signature-wrapper">
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($patient_data['last_name'] ?? ''); ?>" required>
                    </div>
                    <span class="signature-text">Last Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="first_name" name="first_name"
                        value="<?php echo htmlspecialchars($patient_data['first_name'] ?? ''); ?>" required>
                    </div>
                    <span class="signature-text">First Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="middle_name" name="middle_name"
                        value="<?php echo htmlspecialchars($patient_data['middle_name'] ?? ''); ?>" >
                    </div>
                    <span class="signature-text">Middle Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="date" id="date_of_birth" name="date_of_birth"
                        value="<?php echo htmlspecialchars($patient_data['date_of_birth'] ?? ''); ?>" required>
                    </div>
                    <span class="signature-text">Date of Birth</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="gender" name="gender"
                        value="<?php echo htmlspecialchars($patient_data['gender'] ?? ''); ?>" required>
                    </div>
                    <span class="signature-text">Gender</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="number" id="contact_number" name="contact_number"
                        value="<?php echo htmlspecialchars($patient_data['contact_number'] ?? ''); ?>">
                    </div>
                    <span class="signature-text">Contact Number</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="address" name="address"
                        value="<?php echo htmlspecialchars($patient_data['address'] ?? ''); ?>" required>
                    </div>
                    <span class="signature-text">Address</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="email" id="email" name="email"
                        value="<?php echo htmlspecialchars($patient_data['email'] ?? ''); ?>" required>
                    </div>
                    <span class="signature-text">Email</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="password" id="password" name="password" value="" required>
                    </div>
                    <span class="signature-text">Password</span>
                </div>
            </div>
            <div class="signature-button">
                <button type="submit" name="save_patient" value="update" id="submitBtn">Update Patient</button>
            </div>
            </form>
        </div>
    </div>
    <script src="./Javascript/toggle-radio.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const data = <?php echo json_encode($patient_data); ?>;

    if (Object.keys(data).length === 0) return;

    // Populate patient info - use correct field names from patients table
    const firstNameEl = document.getElementById('first_name');
    const lastNameEl = document.getElementById('last_name');
    const middleNameEl = document.getElementById('middle_name');

    if (firstNameEl) firstNameEl.value = data.first_name || '';
    if (lastNameEl) lastNameEl.value = data.last_name || '';
    if (middleNameEl) middleNameEl.value = data.middle_name || '';

    document.getElementById('date_of_birth').value = data.date_of_birth || '';
    document.getElementById('gender').value = data.gender || '';
    document.getElementById('contact_number').value = data.contact_number || '';
    document.getElementById('address').value = data.address || '';
    document.getElementById('email').value = data.email || '';
    document.getElementById('admission_date').value = data.admission_date || '';
    document.getElementById('admission_time').value = data.admission_time || '';
    document.getElementById('history_date').value = data.history_date || '';
    document.getElementById('allergies').value = data.allergies || '';
    document.getElementById('duration_of_symptoms').value = data.duration_of_symptoms || '';
    document.getElementById('regular_medication').value = data.regular_medication || '';
    document.getElementById('dietary_habits').value = data.dietary_habits || '';
    document.getElementById('elimination_habits').value = data.elimination_habits || '';
    document.getElementById('sleep_patterns').value = data.sleep_patterns || '';

    // Physical assessment
    document.querySelector('input[name="height"]').value = data.height || '';
    document.querySelector('input[name="weight"]').value = data.weight || '';
    document.querySelector('input[name="BP_lft"]').value = data.bp_lft || '';
    document.querySelector('input[name="pulse"]').value = data.pulse || '';
    document.querySelector('input[name="strong"]').value = data.strong || '';
    document.querySelector('input[name="temp_ranges"]').value = data.temp_ranges || '';

    // Set radio buttons
    const radioFields = {
        'mode_of_arrival': data.mode_of_arrival,
        'instructed': data.instructed,
        'patient_status': data.patient_status,
        'glasses_contact': data.glasses_or_contactlens,
        'dentures': data.dentures,
        'ambulatory': data.ambulatory_or_prosthesis,
        'smoker': data.smoker,
        'drinker': data.drinker,
        'respiration': data.status,
        'orientation': data.orientation,
        'skin': data.skin_color,
        'skin-turgor': data.skin_turgor,
        'skin-temp': data.skin_temp,
        'mucous-membrane': data.mucous_membrane,
        'peripheral-sounds': data.peripheral_sounds,
        'neck-vein-distention': data.neck_vein_distention,
        'respiratory-status': data.respiratory_status,
        'respiratory-sounds': data.respiratory_sounds,
        'cough': data.cough,
        'sputum': data.sputum,
        'temperature': data.temperature,
        'personal-care': data.personal_care,
        'ambulation': data.ambulation,
        'communication': data.communication_problem,
        'isolation': data.isolation,
        'skin-care': data.skin_care,
        'wound-care': data.wound_care
    };

    Object.keys(radioFields).forEach(fieldName => {
        const value = radioFields[fieldName];
        if (value) {
            const radio = document.querySelector(`input[name="${fieldName}"][value="${value}"]`);
            if (radio) radio.checked = true;
        }
    });
});
    </script>
</body>
</html>