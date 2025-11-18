<?php
//database connection
include 'connection.php';

session_start();
//Only allow admin
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("Location: admin-login.php");
    exit();
}

//error/success messages generator
$error = "";
$success = "";
$errors = [];

//Helper to get POST values
function p($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

// Ensure user_type exists, return its id
function ensure_user_type($con, $desc) {
    //Look up first
    $stmt = $con->prepare("SELECT user_type_id FROM user_type WHERE user_type_desc = ?");
    if (!$stmt) throw new Exception("Prepared statement user_type selection failed: " . $con->error);
    $stmt->bind_param("s", $desc);
    $stmt->execute();
    $stmt->bind_result($utid);
    if ($stmt->fetch()) {
        $stmt->close();
        return (int)$utid;
    }
    $stmt->close();

    //if Not found — insert
    $stmt = $con->prepare("INSERT INTO user_type (user_type_desc) VALUES (?)");
    if (!$stmt) throw new Exception("Prepare user_type insert failed: " . $con->error);
    $stmt->bind_param("s", $desc);
    if (!$stmt->execute()) {
        $stmt->close();
        $stmt2 = $con->prepare("SELECT user_type_id FROM user_type WHERE user_type_desc = ?");
        if (!$stmt2) throw new Exception("Prepare user_type select retry failed: " . $con->error);
        $stmt2->bind_param("s", $desc);
        $stmt2->execute();
        $stmt2->bind_result($utid2);
        if ($stmt2->fetch()) {
            $stmt2->close();
            return (int)$utid2;
        }
        $stmt2->close();
        throw new Exception("Could not insert or find user_type: " . $desc);
    }
    $new_id = $stmt->insert_id;
    $stmt->close();
    return (int)$new_id;
}

//handles form submition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_patient'])) {

    //patient personal info
    $date_of_birth = p('date_of_birth');
    $gender = p('gender');
    $contact_number = p('contact_number');
    $address = p('address');
    $first_name = p('first_name');
    $middle_name = p('middle_name') ?: null;
    $last_name = p('last_name');

    //Admission
    $admission_date = p('admission_date');
    $admission_time = p('admission_time');

    //History
    $history_date = p('history_date');
    $allergies = p('allergies');
    $duration_of_symptoms = p('duration_of_symptoms');
    $regular_medication = p('regular_medication');
    $dietary_habits = p('dietary_habits');
    $elimination_habits = p('elimination_habits');
    $sleep_patterns = p('sleep_patterns');

    //Physical assessment
    $height = p('height');
    $weight = p('weight');
    $bp_lft = p('BP_lft');
    $pulse = p('pulse');
    $strong = p('strong');
    $temp_ranges = p('temp_ranges');

    //Additional physical fields (strings)
    $status = p('respiration') ?: null;
    $orientation = p('orientation') ?: null;
    $skin_color = p('skin_color') ?: null;
    $skin_turgor = p('skin_turgor') ?: null;
    $skin_temp = p('skin_temp') ?: null;
    $mucous_membrane = p('mucous_membrane') ?: null;
    $peripheral_sounds = p('peripheral_sounds') ?: null;
    $neck_vein_distention = p('neck_vein_distention') ?: null;
    $respiratory_status = p('respiratory_status') ?: null;
    $respiratory_sounds = p('respiratory_sounds') ?: null;
    $cough = p('cough') ?: null;
    $sputum = p('sputum') ?: null;
    $temperature = p('temperature') ?: null;

    //New account fields
    $email = p('email');
    $password = p('password');

    //validations for required fields
    if (empty($first_name)) $errors[] = "First Name is required.";
    if (empty($last_name)) $errors[] = "Last Name is required.";
    if (empty($date_of_birth)) {
        $errors[] = "Date of Birth is required.";
    } else {
        $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if (!$dob || $dob->format('Y-m-d') !== $date_of_birth) {
            $errors[] = "Date of Birth must be in YYYY-MM-DD format.";
        }
    }
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($address)) $errors[] = "Address is required.";

    // Admission validations
    if (empty($admission_date)) {
        $errors[] = "Admission Date is required.";
    } else {
        $adm_date = DateTime::createFromFormat('Y-m-d', $admission_date);
        if (!$adm_date || $adm_date->format('Y-m-d') !== $admission_date) {
            $errors[] = "Admission Date must be in YYYY-MM-DD format.";
        }
    }
    if (empty($admission_time)) {
        $errors[] = "Admission Time is required.";
    } else {
        $time_obj = DateTime::createFromFormat('H:i', $admission_time);
        if (!$time_obj || $time_obj->format('H:i') !== $admission_time) {
            $errors[] = "Admission Time must be in HH:MM format.";
        }
    }

    //History validations
    if (empty($history_date)) $errors[] = "History Date is required.";
    if (empty($allergies)) $errors[] = "Allergies field is required.";
    if (empty($duration_of_symptoms)) $errors[] = "Duration of Symptoms is required.";
    if (empty($regular_medication)) $errors[] = "Regular Medication is required.";
    if (empty($dietary_habits)) $errors[] = "Dietary Habits is required.";
    if (empty($elimination_habits)) $errors[] = "Elimination Habits is required.";
    if (empty($sleep_patterns)) $errors[] = "Sleep Patterns is required.";

    //Physical validations
    if ($height === null || $height === '' || !is_numeric($height)) $errors[] = "Height is required and must be numeric.";
    if ($weight === null || $weight === '' || !is_numeric($weight)) $errors[] = "Weight is required and must be numeric.";
    if ($bp_lft === null || $bp_lft === '' || !is_numeric($bp_lft)) $errors[] = "BP Left is required and must be numeric.";
    if ($pulse === null || $pulse === '' || !is_numeric($pulse)) $errors[] = "Pulse is required and must be numeric.";
    if ($strong === null || $strong === '' || !is_numeric($strong)) $errors[] = "Strong is required and must be numeric.";
    if ($temp_ranges === null || $temp_ranges === '' || !is_numeric($temp_ranges)) $errors[] = "Temperature is required and must be numeric.";

    //New account validations
    if (empty($email)) $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email format is invalid.";
    if (empty($password)) $errors[] = "Password is required.";
    elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";

    //If validation errors collected, set $error and stop
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    } else {
        //Begin transaction
        $con->begin_transaction();

        try {
            //Ensure patient user_type exists and get id
            $user_type_desc = 'patient';
            $user_type_id = ensure_user_type($con, $user_type_desc);

            //Check unique email
            $chk = $con->prepare("SELECT user_id FROM users WHERE email = ?");
            if (!$chk) throw new Exception("Prepare users select failed: " . $con->error);
            $chk->bind_param("s", $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                throw new Exception("Email already exists. Please use a different email.");
            }
            $chk->close();

            //Create users row
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $con->prepare("
                INSERT INTO users (user_type_id, email, password, first_name, last_name, middle_name)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) throw new Exception("Prepare users failed: " . $con->error);

            // bind types: user_type_id (i), email (s), password (s), first_name (s), last_name (s), middle_name (s)
            $middle_name_bind = $middle_name;
            $stmt->bind_param("isssss", $user_type_id, $email, $hashed_password, $first_name, $last_name, $middle_name_bind);
            if (!$stmt->execute()) {
                throw new Exception("Insert users failed: " . $stmt->error);
            }
            $user_id = $stmt->insert_id;
            $stmt->close();

            //1. INSERT INTO patients (link user_id)
            $stmt = $con->prepare("INSERT INTO patients (user_id, date_of_birth, gender, contact_number, address, created_date) VALUES (?, ?, ?, ?, ?, NOW())");
            if (!$stmt) throw new Exception("Prepare patients failed: " . $con->error);

            //convert contact_number to integer or null. we convert it because we use a text field input instead of number
            $contact_number_val = !empty($contact_number) ? (int)$contact_number : null;
            //bind types: user_id (i), date_of_birth (s), gender (s), contact_number (i), address (s)
            $stmt->bind_param("issis", $user_id, $date_of_birth, $gender, $contact_number_val, $address);
            if (!$stmt->execute()) {
                throw new Exception("Insert patients failed: " . $stmt->error);
            }
            $patient_id = $stmt->insert_id;
            $stmt->close();

            //2. INSERT INTO admission_data
            $mode_of_arrival = p('mode_of_arrival') ?: null;
            $instructed = p('instructed') ?: null;
            $glasses_or_contactlens = p('glasses_contact') ?: null;
            $dentures = p('dentures') ?: null;
            $ambulatory_or_prosthesis = p('ambulatory') ?: null;
            $smoker = p('smoker') ?: null;
            $drinker = p('drinker') ?: null;

            $stmt = $con->prepare("INSERT INTO admisison_data (patient_id, user_id, admission_date, admission_time, mode_of_arrival, instructed, glasses_or_contactlens, dentures, ambulatory_or_prosthesis, smoker, drinker, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) throw new Exception("Prepare admission_data failed: " . $con->error);

            $stmt->bind_param("iisssssssss", $patient_id, $user_id, $admission_date, $admission_time, $mode_of_arrival, $instructed, $glasses_or_contactlens, $dentures, $ambulatory_or_prosthesis, $smoker, $drinker);
            if (!$stmt->execute()) {
                throw new Exception("Insert admission_data failed: " . $stmt->error);
            }
            $stmt->close();

            //3. INSERT INTO history
            $personal_care = p('personal-care') ?: null;
            $ambulation = p('ambulation') ?: null;
            $communication_problem = p('communication') ?: null;
            $isolation = p('isolation') ?: null;
            $skin_care = p('skin-care') ?: null;
            $wound_care = p('wound-care') ?: null;
            $others = p('others') ?: '';

            $stmt = $con->prepare("INSERT INTO history (patient_id, user_id, history_date, allergies, duration_of_symptoms, regular_medication, dietary_habits, elimination_habits, sleep_patterns, personal_care, ambulation, communication_problem, isolation, skin_care, wound_care, others, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) throw new Exception("Prepare history failed: " . $con->error);

            $stmt->bind_param("iissssssssssssss", $patient_id, $user_id, $history_date, $allergies, $duration_of_symptoms, $regular_medication, $dietary_habits, $elimination_habits, $sleep_patterns, $personal_care, $ambulation, $communication_problem, $isolation, $skin_care, $wound_care, $others);
            if (!$stmt->execute()) {
                throw new Exception("Insert history failed: " . $stmt->error);
            }
            $stmt->close();

            //4. INSERT INTO physical_assessment
            $height = (int)$height;
            $weight = (int)$weight;
            $bp_lft = (int)$bp_lft;
            $pulse = (int)$pulse;
            $strong = (int)$strong;
            $temp_ranges = (int)$temp_ranges;

            // Prepare physical_assessment insert
            $stmt = $con->prepare(
                "INSERT INTO physical_assessment
                (patient_id, user_id, height, weight, bp_lft, pulse, strong, status, orientation, skin_color, skin_turgor, skin_temp, mucous_membrane, peripheral_sounds, neck_vein_distention, respiratory_status, respiratory_sounds, cough, sputum, temp_ranges, temperature)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)" //must match to $vars
            );

            if (!$stmt) throw new Exception("Prepare physical_assessment failed: " . $con->error);

            $vars = [
                $patient_id,
                $user_id,
                $height,
                $weight,
                $bp_lft,
                $pulse,
                $strong,
                $status,
                $orientation,
                $skin_color,
                $skin_turgor,
                $skin_temp,
                $mucous_membrane,
                $peripheral_sounds,
                $neck_vein_distention,
                $respiratory_status,
                $respiratory_sounds,
                $cough,
                $sputum,
                $temp_ranges,
                $temperature
            ];

            //types string: ints = 'i', string or everything else = 's'
            $types = '';
            foreach ($vars as $v) {
                if (is_int($v)) $types .= 'i';
                else $types .= 's';
            }

            //Prepare params by reference to call it then bind the params
            $a_params = [];
            $a_params[] = $types;
            for ($i = 0; $i < count($vars); $i++) {
                $a_params[] = &$vars[$i];
            }

            call_user_func_array([$stmt, 'bind_param'], $a_params);

            if (!$stmt->execute()) {
                throw new Exception("Insert physical_assessment failed: " . $stmt->error);
            }
            $stmt->close();

            //All inserts successful
            $con->commit();
            $success = "Patient record created successfully! Patient ID: " . $patient_id;

            //clear local storage and redirect
            ?>
            <script>
                localStorage.removeItem('formData');
                setTimeout(function() {
                    window.location.href = 'add-patient.php';
                }, 3000);
            </script>
            <?php

        } catch (Exception $e) {
            $con->rollback(); //go back
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
    <title>Add Patient</title>
    <script src="./Javascript/javascript.js" defer></script>
    <link rel="stylesheet" href="./Styles/add-patient.css">
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

                    <tr>
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
                            <div class="checkbox-wrapper-52"><label for="sputum-thick" class="item"><input type="radio" id="sputum-thick" name="sputum" value="thick" class="hidden toggle-radio"/><label for="sputum-thick" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-thick" class="cbx-lbl">Thick</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-mucoid" class="item"><input type="radio" id="sputum-mucoid" name="sputum" value="mucoid" class="hidden toggle-radio"/><label for="sputum-mucoid" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-mucoid" class="cbx-lbl">Mucoid</label></label></div>
                            <div class="checkbox-wrapper-52"><label for="sputum-tenacious" class="item"><input type="radio" id="sputum-tenacious" name="sputum" value="tenacious" class="hidden toggle-radio"/><label for="sputum-tenacious" class="cbx"><svg width="14px" height="12px" viewBox="0 0 14 12"><polyline points="1 7.6 5 11 13 1"></polyline></svg></label><label for="sputum-tenacious" class="cbx-lbl">Frothy Tenacious</label></label></div>
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
                        <input class="signature-input" type="text" id="last_name" name="last_name" required>
                    </div>
                    <span class="signature-text">Last Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="first_name" name="first_name" required>
                    </div>
                    <span class="signature-text">First Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="middle_name" name="middle_name">
                    </div>
                    <span class="signature-text">Middle Name</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    <span class="signature-text">Date of Birth</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="gender" name="gender" required>
                    </div>
                    <span class="signature-text">Gender</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="number" id="contact_number" name="contact_number">
                    </div>
                    <span class="signature-text">Contact Number</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="text" id="address" name="address" required>
                    </div>
                    <span class="signature-text">Address</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="email" id="email" name="email" required>
                    </div>
                    <span class="signature-text">Email</span>
                </div>
                <div class="signature-container">
                    <div class="">
                        <input class="signature-input" type="password" id="password" name="password" required>
                    </div>
                    <span class="signature-text">Password</span>
                </div>

                    <div class="signature-button">
                        <button type="submit" name="save_patient" value="save" id="submitBtn">Save</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

<!--IMPORTANT! DON'T REMOVE OR SEPARATE THIS BLOCK OF CODE-->
    <script>
        //wait for the document to be fully loaded before running the script
        document.addEventListener('DOMContentLoaded', function() {

        //select all radio buttons with the 'toggle-radio' class.
        const toggleRadios = document.querySelectorAll('.toggle-radio');

        toggleRadios.forEach(radio => {
        radio.addEventListener('click', function() {
            //'this' refers to the radio button that was just clicked
            const clickedRadio = this;

            //check if the button was already checked
            if (clickedRadio.dataset.wasChecked === 'true') {
            //it was already checked, so uncheck it
            clickedRadio.checked = false;
            clickedRadio.dataset.wasChecked = 'false';
            } else {
            //it was not checked, so mark it as 'wasChecked'
            clickedRadio.dataset.wasChecked = 'true';

            //go through all *other* radio buttons
            toggleRadios.forEach(otherRadio => {
                //reset the 'wasChecked' status for any other button in the *same group*
                if (otherRadio !== clickedRadio && otherRadio.name === clickedRadio.name) {
                otherRadio.dataset.wasChecked = 'false';
                }
            });
            }
        });
        });
    });
    </script>
</body>
</html>