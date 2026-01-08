<?php
session_start();
include 'connection.php';
require('./lib/fpdf.php'); // put fpdf.php in project root or adjust path

// params
$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : null;
$type = $_GET['type'] ?? null;
$record_id = isset($_GET['record_id']) ? intval($_GET['record_id']) : null;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : ($_SESSION['patient_id'] ?? null);

if ($file_id) {
    // stream previously uploaded file
    $stmt = $con->prepare("SELECT file_path, file_name, file_type FROM lab_results WHERE lab_result_id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows !== 1) { echo "File not found."; exit(); }
    $row = $res->fetch_assoc();
    $stmt->close();

    $fullpath = __DIR__ . '/' . $row['file_path'];
    if (!file_exists($fullpath)) { echo "File missing on server."; exit(); }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($row['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . basename($row['file_name']) . '"');
    header('Content-Length: ' . filesize($fullpath));
    readfile($fullpath);
    exit();
}

// require type + record_id
if (!$type || !$record_id || !$patient_id) {
    echo "Missing parameters for printing.";
    exit();
}

// helper: get patient name (for header)
$stmt = $con->prepare("SELECT u.first_name, u.last_name FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$pRes = $stmt->get_result();
if ($pRes->num_rows !== 1) { echo "Patient not found."; exit(); }
$pRow = $pRes->fetch_assoc();
$stmt->close();
$fullname = $pRow['first_name'] . ' ' . $pRow['last_name'];

// create pdf
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8, 'Patient Report', 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('Arial','',11);
$pdf->Cell(40,7,'Patient:',0,0);
$pdf->Cell(0,7,$fullname,0,1);
$pdf->Cell(40,7,'Patient ID:',0,0);
$pdf->Cell(0,7,$patient_id,0,1);
$pdf->Ln(6);

// print specific record based on type
switch ($type) {
    case 'history':
        // fetch history record and nurse who created it
        $stmt = $con->prepare("
            SELECT h.history_date, h.allergies, h.duration_of_symptoms, h.regular_medication, h.dietary_habits, h.elimination_habits, h.sleep_patterns, h.personal_care, h.ambulation, h.communication_problem, h.isolation, h.skin_care, h.wound_care, h.others,
                   nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
            FROM history h
            LEFT JOIN nurse n ON h.nurse_id = n.nurse_id
            LEFT JOIN users nu ON n.user_id = nu.user_id
            WHERE h.history_id = ? AND h.patient_id = ?
        ");
        $stmt->bind_param("ii", $record_id, $patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows !== 1) { echo "History record not found."; exit(); }
        $r = $res->fetch_assoc();
        $stmt->close();

        $nurseName = (!empty($r['nurse_fn']) || !empty($r['nurse_ln'])) ? trim($r['nurse_fn'].' '.$r['nurse_ln']) : 'N/A';

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'History Record',0,1);
        $pdf->Ln(2);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(45,6,'Recorded by:',0,0);
        $pdf->Cell(0,6,$nurseName,0,1);
        $pdf->Cell(45,6,'Record Date:',0,0);
        $pdf->Cell(0,6,$r['history_date'],0,1);
        $pdf->Ln(2);
        $pdf->MultiCell(0,6,'Allergies: ' . ($r['allergies'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Duration of symptoms: ' . ($r['duration_of_symptoms'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Regular medication: ' . ($r['regular_medication'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Dietary habits: ' . ($r['dietary_habits'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Elimination habits: ' . ($r['elimination_habits'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Sleep patterns: ' . ($r['sleep_patterns'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Personal care: ' . ($r['personal_care'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Ambulation: ' . ($r['ambulation'] ?: 'N/A'));
        $pdf->MultiCell(0,6,'Communication problem: ' . ($r['communication_problem'] ?: 'N/A'));
        if (!empty($r['others'])) $pdf->MultiCell(0,6,'Others: ' . $r['others']);
        break;

    case 'physical':
        // fetch physical assessment and nurse
        $stmt = $con->prepare("
            SELECT p.created_date, p.height, p.weight, p.bp_lft, p.pulse, p.strong, p.status, p.orientation, p.skin_color, p.skin_turgor, p.skin_temp, p.mucous_membrane, p.peripheral_sounds, p.neck_vein_distention, p.respiratory_status, p.respiratory_sounds, p.cough, p.sputum, p.temp_ranges, p.temperature,
                   nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
            FROM physical_assessment p
            LEFT JOIN nurse n ON p.nurse_id = n.nurse_id
            LEFT JOIN users nu ON n.user_id = nu.user_id
            WHERE p.physical_assessment_id = ? AND p.patient_id = ?
        ");
        $stmt->bind_param("ii", $record_id, $patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows !== 1) { echo "Physical assessment record not found."; exit(); }
        $r = $res->fetch_assoc();
        $stmt->close();

        $nurseName = (!empty($r['nurse_fn']) || !empty($r['nurse_ln'])) ? trim($r['nurse_fn'].' '.$r['nurse_ln']) : 'N/A';

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'Physical Assessment',0,1);
        $pdf->Ln(2);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(45,6,'Recorded by:',0,0);
        $pdf->Cell(0,6,$nurseName,0,1);
        $pdf->Cell(45,6,'Assessment Date:',0,0);
        $pdf->Cell(0,6,$r['created_date'],0,1);
        $pdf->Ln(2);
        $pdf->Cell(60,6,'Height (cm):',0,0);
        $pdf->Cell(0,6,$r['height'],0,1);
        $pdf->Cell(60,6,'Weight (kg):',0,0);
        $pdf->Cell(0,6,$r['weight'],0,1);
        $pdf->Cell(60,6,'BP (left):',0,0);
        $pdf->Cell(0,6,$r['bp_lft'],0,1);
        $pdf->Cell(60,6,'Pulse:',0,0);
        $pdf->Cell(0,6,$r['pulse'],0,1);
        $pdf->Cell(60,6,'Status:',0,0);
        $pdf->Cell(0,6,$r['status'],0,1);
        $pdf->Cell(60,6,'Orientation:',0,0);
        $pdf->Cell(0,6,$r['orientation'],0,1);
        $pdf->Cell(60,6,'Skin color:',0,0);
        $pdf->Cell(0,6,$r['skin_color'],0,1);
        $pdf->Cell(60,6,'Skin turgor:',0,0);
        $pdf->Cell(0,6,$r['skin_turgor'],0,1);
        $pdf->Cell(60,6,'Skin temp:',0,0);
        $pdf->Cell(0,6,$r['skin_temp'],0,1);
        $pdf->Cell(60,6,'Mucous membrane:',0,0);
        $pdf->Cell(0,6,$r['mucous_membrane'],0,1);
        $pdf->Cell(60,6,'Respiratory status:',0,0);
        $pdf->Cell(0,6,$r['respiratory_status'],0,1);
        $pdf->Cell(60,6,'Cough:',0,0);
        $pdf->Cell(0,6,$r['cough'],0,1);
        break;

    case 'admission':
        // fetch admission record and nurse who admitted
        $stmt = $con->prepare("
            SELECT a.admission_date, a.admission_time, a.mode_of_arrival, a.instructed, a.glasses_or_contactlens, a.dentures, a.ambulatory_or_prosthesis, a.smoker, a.drinker, a.created_date,
                   nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
            FROM admission_data a
            LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
            LEFT JOIN users nu ON n.user_id = nu.user_id
            WHERE a.admission_data_id = ? AND a.patient_id = ?
        ");
        $stmt->bind_param("ii", $record_id, $patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows !== 1) { echo "Admission record not found."; exit(); }
        $r = $res->fetch_assoc();
        $stmt->close();

        $nurseName = (!empty($r['nurse_fn']) || !empty($r['nurse_ln'])) ? trim($r['nurse_fn'].' '.$r['nurse_ln']) : 'N/A';

        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'Admission Data',0,1);
        $pdf->Ln(2);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(45,6,'Admitted by:',0,0);
        $pdf->Cell(0,6,$nurseName,0,1);
        $pdf->Cell(45,6,'Admission Date:',0,0);
        $pdf->Cell(0,6,$r['admission_date'] . ' ' . $r['admission_time'],0,1);
        $pdf->Ln(2);
        $pdf->MultiCell(0,6, 'Mode of arrival: ' . ($r['mode_of_arrival'] ?: 'N/A'));
        $pdf->MultiCell(0,6, 'Instructed: ' . ($r['instructed'] ?: 'N/A'));
        $pdf->MultiCell(0,6, 'Glasses/contact lens: ' . ($r['glasses_or_contactlens'] ?: 'N/A'));
        $pdf->MultiCell(0,6, 'Dentures: ' . ($r['dentures'] ?: 'N/A'));
        $pdf->MultiCell(0,6, 'Ambulatory/prosthesis: ' . ($r['ambulatory_or_prosthesis'] ?: 'N/A'));
        $pdf->MultiCell(0,6, 'Smoker: ' . ($r['smoker'] ?: 'N/A'));
        $pdf->MultiCell(0,6, 'Drinker: ' . ($r['drinker'] ?: 'N/A'));
        break;

    case 'comprehensive':
    // ==============================================================
    // COMPREHENSIVE PATIENT REPORT
    // ==============================================================
    // This generates a complete medical record report including:
    // - Patient Demographics
    // - Admission Data
    // - Medical History
    // - Physical Assessment
    // - Medications/Orders
    // - Lab Results
    // - Appointments
    // ==============================================================

    $pdf->SetFont('Arial','B',16);
    $pdf->SetFillColor(66, 63, 62);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'COMPREHENSIVE MEDICAL REPORT', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    // ==============================================================
    // SECTION 1: PATIENT DEMOGRAPHICS
    // ==============================================================
    $stmt = $con->prepare("
        SELECT u.first_name, u.last_name, u.middle_name, u.email,
               p.date_of_birth, p.gender, p.contact_number, p.address, p.patient_status,
               p.created_date as registration_date
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = ?
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient_res = $stmt->get_result();
    $patient_data = $patient_res->fetch_assoc();
    $stmt->close();

    if (!$patient_data) {
        echo "Patient not found.";
        exit();
    }

    // Calculate age
    $dob = new DateTime($patient_data['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '1. PATIENT DEMOGRAPHICS', 0, 1, 'L', true);
    $pdf->Ln(2);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(50, 6, 'Full Name:', 0, 0, 'L');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0, 6, $patient_data['first_name'] . ' ' . ($patient_data['middle_name'] ? $patient_data['middle_name'] . ' ' : '') . $patient_data['last_name'], 0, 1);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(50, 6, 'Patient ID:', 0, 0);
    $pdf->Cell(0, 6, $patient_id, 0, 1);

    $pdf->Cell(50, 6, 'Date of Birth:', 0, 0);
    $pdf->Cell(60, 6, date('F d, Y', strtotime($patient_data['date_of_birth'])), 0, 0);
    $pdf->Cell(20, 6, 'Age:', 0, 0);
    $pdf->Cell(0, 6, $age . ' years old', 0, 1);

    $pdf->Cell(50, 6, 'Gender:', 0, 0);
    $pdf->Cell(0, 6, ucfirst($patient_data['gender']), 0, 1);

    $pdf->Cell(50, 6, 'Contact Number:', 0, 0);
    $pdf->Cell(0, 6, $patient_data['contact_number'] ?? 'N/A', 0, 1);

    $pdf->Cell(50, 6, 'Email:', 0, 0);
    $pdf->Cell(0, 6, $patient_data['email'], 0, 1);

    $pdf->Cell(50, 6, 'Address:', 0, 0);
    $pdf->MultiCell(0, 6, $patient_data['address']);

    $pdf->Cell(50, 6, 'Patient Status:', 0, 0);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0, 6, strtoupper($patient_data['patient_status']), 0, 1);

    $pdf->SetFont('Arial','',10);
    $pdf->Cell(50, 6, 'Registration Date:', 0, 0);
    $pdf->Cell(0, 6, date('F d, Y', strtotime($patient_data['registration_date'])), 0, 1);

    $pdf->Ln(5);

    // ==============================================================
    // SECTION 2: ADMISSION DATA
    // ==============================================================
    $stmt = $con->prepare("
        SELECT ad.admission_date, ad.admission_time, ad.mode_of_arrival, ad.instructed,
               ad.glasses_or_contactlens, ad.dentures, ad.ambulatory_or_prosthesis,
               ad.smoker, ad.drinker, ad.created_date,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM admission_data ad
        LEFT JOIN nurse n ON ad.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE ad.patient_id = ?
        ORDER BY ad.admission_date DESC, ad.admission_time DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $admission_res = $stmt->get_result();
    $admission_data = $admission_res->fetch_assoc();
    $stmt->close();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '2. ADMISSION DATA', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($admission_data) {
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(50, 6, 'Admission Date:', 0, 0);
        $pdf->Cell(0, 6, date('F d, Y', strtotime($admission_data['admission_date'])) . ' at ' . date('g:i A', strtotime($admission_data['admission_time'])), 0, 1);

        $pdf->Cell(50, 6, 'Mode of Arrival:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($admission_data['mode_of_arrival']), 0, 1);

        $pdf->Cell(50, 6, 'Instructed:', 0, 0);
        $pdf->Cell(0, 6, ucfirst(str_replace('-', ' ', $admission_data['instructed'])), 0, 1);

        $pdf->Cell(50, 6, 'Glasses/Contact Lens:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($admission_data['glasses_or_contactlens']), 0, 1);

        $pdf->Cell(50, 6, 'Dentures:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($admission_data['dentures']), 0, 1);

        $pdf->Cell(50, 6, 'Ambulatory/Prosthesis:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($admission_data['ambulatory_or_prosthesis']), 0, 1);

        $pdf->Cell(50, 6, 'Smoker:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($admission_data['smoker']), 0, 1);

        $pdf->Cell(50, 6, 'Drinker:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($admission_data['drinker']), 0, 1);

        $nurse_name = trim(($admission_data['nurse_fn'] ?? '') . ' ' . ($admission_data['nurse_ln'] ?? ''));
        if ($nurse_name) {
            $pdf->Cell(50, 6, 'Admitted by:', 0, 0);
            $pdf->Cell(0, 6, $nurse_name, 0, 1);
        }
    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No admission data available.', 0, 1);
    }

    $pdf->Ln(5);

    // ==============================================================
    // SECTION 3: MEDICAL HISTORY
    // ==============================================================
    $stmt = $con->prepare("
        SELECT h.history_date, h.allergies, h.duration_of_symptoms, h.regular_medication,
               h.dietary_habits, h.elimination_habits, h.sleep_patterns, h.personal_care,
               h.ambulation, h.communication_problem, h.isolation, h.skin_care,
               h.wound_care, h.others,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM history h
        LEFT JOIN nurse n ON h.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE h.patient_id = ?
        ORDER BY h.history_date DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $history_res = $stmt->get_result();
    $history_data = $history_res->fetch_assoc();
    $stmt->close();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '3. MEDICAL HISTORY', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($history_data) {
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(50, 6, 'Record Date:', 0, 0);
        $pdf->Cell(0, 6, date('F d, Y', strtotime($history_data['history_date'])), 0, 1);

        $pdf->MultiCell(0, 6, 'Allergies: ' . ($history_data['allergies'] ?: 'None reported'));
        $pdf->MultiCell(0, 6, 'Duration of Symptoms: ' . ($history_data['duration_of_symptoms'] ?: 'N/A'));
        $pdf->MultiCell(0, 6, 'Regular Medication: ' . ($history_data['regular_medication'] ?: 'None'));
        $pdf->MultiCell(0, 6, 'Dietary Habits: ' . ($history_data['dietary_habits'] ?: 'N/A'));
        $pdf->MultiCell(0, 6, 'Elimination Habits: ' . ($history_data['elimination_habits'] ?: 'N/A'));
        $pdf->MultiCell(0, 6, 'Sleep Patterns: ' . ($history_data['sleep_patterns'] ?: 'N/A'));

        $pdf->Cell(50, 6, 'Personal Care:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($history_data['personal_care']), 0, 1);

        $pdf->Cell(50, 6, 'Ambulation:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($history_data['ambulation']), 0, 1);

        $pdf->Cell(50, 6, 'Communication Problem:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($history_data['communication_problem']), 0, 1);

        if ($history_data['others']) {
            $pdf->MultiCell(0, 6, 'Other Notes: ' . $history_data['others']);
        }

        $nurse_name = trim(($history_data['nurse_fn'] ?? '') . ' ' . ($history_data['nurse_ln'] ?? ''));
        if ($nurse_name) {
            $pdf->Cell(50, 6, 'Recorded by:', 0, 0);
            $pdf->Cell(0, 6, $nurse_name, 0, 1);
        }
    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No medical history available.', 0, 1);
    }

    $pdf->Ln(5);

    // ==============================================================
    // SECTION 4: PHYSICAL ASSESSMENT
    // ==============================================================
    $stmt = $con->prepare("
        SELECT pa.height, pa.weight, pa.bp_lft, pa.pulse, pa.status, pa.orientation,
               pa.skin_color, pa.skin_turgor, pa.skin_temp, pa.mucous_membrane,
               pa.peripheral_sounds, pa.neck_vein_distention, pa.respiratory_status,
               pa.respiratory_sounds, pa.cough, pa.sputum, pa.temp_ranges, pa.temperature,
               pa.created_date,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM physical_assessment pa
        LEFT JOIN nurse n ON pa.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE pa.patient_id = ?
        ORDER BY pa.created_date DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $physical_res = $stmt->get_result();
    $physical_data = $physical_res->fetch_assoc();
    $stmt->close();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '4. PHYSICAL ASSESSMENT', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($physical_data) {
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(50, 6, 'Assessment Date:', 0, 0);
        $pdf->Cell(0, 6, date('F d, Y', strtotime($physical_data['created_date'])), 0, 1);

        // Vital Signs
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(0, 6, 'Vital Signs:', 0, 1);
        $pdf->SetFont('Arial','',10);

        $pdf->Cell(50, 6, '  Height:', 0, 0);
        $pdf->Cell(40, 6, $physical_data['height'] . ' cm', 0, 0);
        $pdf->Cell(30, 6, 'Weight:', 0, 0);
        $pdf->Cell(0, 6, $physical_data['weight'] . ' kg', 0, 1);

        // Calculate BMI
        $bmi = ($physical_data['weight'] / (($physical_data['height']/100) * ($physical_data['height']/100)));
        $pdf->Cell(50, 6, '  BMI:', 0, 0);
        $pdf->Cell(0, 6, number_format($bmi, 1), 0, 1);

        $pdf->Cell(50, 6, '  Blood Pressure:', 0, 0);
        $pdf->Cell(0, 6, $physical_data['bp_lft'] . ' mmHg', 0, 1);

        $pdf->Cell(50, 6, '  Pulse:', 0, 0);
        $pdf->Cell(0, 6, $physical_data['pulse'] . ' bpm', 0, 1);

        $pdf->Cell(50, 6, '  Temperature:', 0, 0);
        $pdf->Cell(0, 6, $physical_data['temp_ranges'] . '°C (' . ucfirst($physical_data['temperature']) . ')', 0, 1);

        $pdf->Ln(2);

        // Physical Examination
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(0, 6, 'Physical Examination:', 0, 1);
        $pdf->SetFont('Arial','',10);

        $pdf->Cell(50, 6, '  Status:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['status'] ?? 'Normal'), 0, 1);

        $pdf->Cell(50, 6, '  Orientation:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['orientation']), 0, 1);

        $pdf->Cell(50, 6, '  Skin Color:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['skin_color']), 0, 1);

        $pdf->Cell(50, 6, '  Skin Turgor:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['skin_turgor']), 0, 1);

        $pdf->Cell(50, 6, '  Skin Temperature:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['skin_temp']), 0, 1);

        $pdf->Cell(50, 6, '  Mucous Membrane:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['mucous_membrane']), 0, 1);

        $pdf->Cell(50, 6, '  Respiratory Status:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['respiratory_status']), 0, 1);

        $pdf->Cell(50, 6, '  Respiratory Sounds:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['respiratory_sounds']), 0, 1);

        $pdf->Cell(50, 6, '  Cough:', 0, 0);
        $pdf->Cell(0, 6, ucfirst($physical_data['cough']), 0, 1);

        $nurse_name = trim(($physical_data['nurse_fn'] ?? '') . ' ' . ($physical_data['nurse_ln'] ?? ''));
        if ($nurse_name) {
            $pdf->Ln(2);
            $pdf->Cell(50, 6, 'Assessed by:', 0, 0);
            $pdf->Cell(0, 6, $nurse_name, 0, 1);
        }
    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No physical assessment available.', 0, 1);
    }

    $pdf->Ln(5);

    // Add new page for medications and lab results
    $pdf->AddPage();

    // ==============================================================
    // SECTION 5: MEDICATIONS / DOCTOR'S ORDERS
    // ==============================================================
    $stmt = $con->prepare("
        SELECT m.medication_name, m.medication_type, m.dose, m.times_per_day,
               m.interval_minutes, m.start_datetime, m.date_prescribed, m.notes,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM medication m
        LEFT JOIN nurse n ON m.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE m.patient_id = ?
        ORDER BY m.date_prescribed DESC
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $medication_res = $stmt->get_result();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '5. MEDICATIONS / DOCTOR\'S ORDERS', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($medication_res->num_rows > 0) {
        $pdf->SetFont('Arial','',10);
        $count = 1;
        while ($med = $medication_res->fetch_assoc()) {
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(0, 6, $count . '. ' . $med['medication_name'], 0, 1);
            $pdf->SetFont('Arial','',10);

            $pdf->Cell(50, 6, '   Type:', 0, 0);
            $pdf->Cell(0, 6, $med['medication_type'], 0, 1);

            $pdf->Cell(50, 6, '   Dosage:', 0, 0);
            $pdf->Cell(0, 6, $med['dose'], 0, 1);

            if ($med['times_per_day']) {
                $pdf->Cell(50, 6, '   Frequency:', 0, 0);
                $pdf->Cell(0, 6, $med['times_per_day'] . ' times per day', 0, 1);
            } elseif ($med['interval_minutes']) {
                $interval_hours = $med['interval_minutes'] / 60;
                $pdf->Cell(50, 6, '   Frequency:', 0, 0);
                $pdf->Cell(0, 6, 'Every ' . $interval_hours . ' hours', 0, 1);
            }

            $pdf->Cell(50, 6, '   Prescribed:', 0, 0);
            $pdf->Cell(0, 6, date('F d, Y', strtotime($med['date_prescribed'])), 0, 1);

            if ($med['notes']) {
                $pdf->Cell(50, 6, '   Notes:', 0, 0);
                $pdf->MultiCell(0, 6, $med['notes']);
            }

            $nurse_name = trim(($med['nurse_fn'] ?? '') . ' ' . ($med['nurse_ln'] ?? ''));
            if ($nurse_name) {
                $pdf->Cell(50, 6, '   Prescribed by:', 0, 0);
                $pdf->Cell(0, 6, $nurse_name, 0, 1);
            }

            $pdf->Ln(3);
            $count++;
        }
    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No medications prescribed.', 0, 1);
    }
    $stmt->close();

    $pdf->Ln(5);

    // ==============================================================
    // SECTION 6: LABORATORY RESULTS
    // ==============================================================
    $stmt = $con->prepare("
        SELECT lr.image_name, lr.upload_date, lr.notes,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM lab_results lr
        LEFT JOIN nurse n ON lr.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE lr.patient_id = ?
        ORDER BY lr.upload_date DESC
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $lab_res = $stmt->get_result();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '6. LABORATORY RESULTS', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($lab_res->num_rows > 0) {
        $pdf->SetFont('Arial','',10);
        $count = 1;
        while ($lab = $lab_res->fetch_assoc()) {
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(0, 6, $count . '. ' . $lab['image_name'], 0, 1);
            $pdf->SetFont('Arial','',10);

            $pdf->Cell(50, 6, '   Upload Date:', 0, 0);
            $pdf->Cell(0, 6, date('F d, Y g:i A', strtotime($lab['upload_date'])), 0, 1);

            if ($lab['notes']) {
                $pdf->Cell(50, 6, '   Notes:', 0, 0);
                $pdf->MultiCell(0, 6, $lab['notes']);
            }

            $nurse_name = trim(($lab['nurse_fn'] ?? '') . ' ' . ($lab['nurse_ln'] ?? ''));
            if ($nurse_name) {
                $pdf->Cell(50, 6, '   Uploaded by:', 0, 0);
                $pdf->Cell(0, 6, $nurse_name, 0, 1);
            }

            $pdf->Ln(2);
            $count++;
        }
    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No laboratory results available.', 0, 1);
    }
    $stmt->close();

    $pdf->Ln(5);

    // ==============================================================
    // SECTION 7: APPOINTMENTS
    // ==============================================================
    $stmt = $con->prepare("
        SELECT a.appointment_date, a.appointment_time, a.appointment_status,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM appointment a
        LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointment_res = $stmt->get_result();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '7. APPOINTMENTS', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($appointment_res->num_rows > 0) {
        $pdf->SetFont('Arial','',10);
        $count = 1;
        while ($appt = $appointment_res->fetch_assoc()) {
            $pdf->Cell(8, 6, $count . '.', 0, 0);
            $pdf->Cell(45, 6, date('M d, Y', strtotime($appt['appointment_date'])), 0, 0);
            $pdf->Cell(30, 6, date('g:i A', strtotime($appt['appointment_time'])), 0, 0);
            $pdf->Cell(40, 6, ucfirst($appt['appointment_status']), 0, 0);

            $nurse_name = trim(($appt['nurse_fn'] ?? '') . ' ' . ($appt['nurse_ln'] ?? ''));
            if ($nurse_name) {
                $pdf->Cell(0, 6, 'with ' . $nurse_name, 0, 1);
            } else {
                $pdf->Ln();
            }

            $count++;
        }
    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No appointments scheduled.', 0, 1);
    }
    $stmt->close();

    $pdf->Ln(5);

    // Add new page for additional sections
    $pdf->AddPage();

    // ==============================================================
    // SECTION 8: ACTIVITY LOG
    // ==============================================================
    $stmt = $con->prepare("
        SELECT al.action_type, al.action_description, al.created_at,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM activity_log al
        LEFT JOIN nurse n ON al.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE al.patient_id = ?
        ORDER BY al.created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $activity_res = $stmt->get_result();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '8. ACTIVITY LOG', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($activity_res->num_rows > 0) {
        $pdf->SetFont('Arial','',10);
        $count = 1;
        while ($activity = $activity_res->fetch_assoc()) {
            $nurse_name = trim(($activity['nurse_fn'] ?? '') . ' ' . ($activity['nurse_ln'] ?? ''));

            // Date and action type
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(8, 6, $count . '.', 0, 0);
            $pdf->Cell(45, 6, date('M d, Y g:i A', strtotime($activity['created_at'])), 0, 0);
            $pdf->Cell(0, 6, ucfirst(str_replace('_', ' ', $activity['action_type'])), 0, 1);

            // Description
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(8, 5, '', 0, 0); // Indent
            $pdf->MultiCell(0, 5, '   ' . substr($activity['action_description'], 0, 100) . (strlen($activity['action_description']) > 100 ? '...' : ''));

            // Performed by
            if ($nurse_name) {
                $pdf->SetFont('Arial','I',9);
                $pdf->Cell(8, 5, '', 0, 0); // Indent
                $pdf->Cell(0, 5, '   Performed by: ' . $nurse_name, 0, 1);
            }

            $pdf->Ln(2);
            $count++;
        }
    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No activity log entries available.', 0, 1);
    }
    $stmt->close();

    $pdf->Ln(5);

    // ==============================================================
    // SECTION 9: ADMISSIONS & READMISSIONS HISTORY
    // ==============================================================
    $stmt = $con->prepare("
        SELECT ad.admission_date, ad.admission_time, ad.mode_of_arrival,
               ad.created_date,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln,
               DATEDIFF(CURDATE(), ad.admission_date) as days_ago
        FROM admission_data ad
        LEFT JOIN nurse n ON ad.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE ad.patient_id = ?
        ORDER BY ad.admission_date DESC, ad.admission_time DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $admissions_res = $stmt->get_result();

    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240, 235, 229);
    $pdf->Cell(0, 8, '9. ADMISSIONS & READMISSIONS HISTORY', 0, 1, 'L', true);
    $pdf->Ln(2);

    if ($admissions_res->num_rows > 0) {
        $pdf->SetFont('Arial','',10);

        // Check for readmissions (multiple admissions)
        $total_admissions = $admissions_res->num_rows;
        if ($total_admissions > 1) {
            $pdf->SetFont('Arial','B',10);
            $pdf->SetTextColor(255, 0, 0); // Red color for readmission alert
            $pdf->Cell(0, 6, 'READMISSION DETECTED: ' . $total_admissions . ' admissions found', 0, 1);
            $pdf->SetTextColor(0, 0, 0); // Reset to black
            $pdf->Ln(2);
        }

        $count = 1;
        $admission_data = [];
        while ($adm = $admissions_res->fetch_assoc()) {
            $admission_data[] = $adm;
        }

        foreach ($admission_data as $adm) {
            $nurse_name = trim(($adm['nurse_fn'] ?? '') . ' ' . ($adm['nurse_ln'] ?? ''));

            // Admission number and date
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(8, 6, $count . '.', 0, 0);

            // Check if this is most recent admission
            if ($count === 1) {
                $pdf->SetTextColor(0, 128, 0); // Green for current
                $pdf->Cell(0, 6, 'CURRENT ADMISSION - ' . date('F d, Y', strtotime($adm['admission_date'])) . ' at ' . date('g:i A', strtotime($adm['admission_time'])), 0, 1);
                $pdf->SetTextColor(0, 0, 0);
            } else {
                // Check if readmission within 30 days
                $previous_adm = $admission_data[$count - 2];
                $days_between = abs((strtotime($previous_adm['admission_date']) - strtotime($adm['admission_date'])) / (60 * 60 * 24));

                if ($days_between <= 30) {
                    $pdf->SetTextColor(255, 140, 0); // Orange for readmission within 30 days
                    $pdf->Cell(0, 6, 'READMISSION (' . round($days_between) . ' days after previous) - ' . date('F d, Y', strtotime($adm['admission_date'])) . ' at ' . date('g:i A', strtotime($adm['admission_time'])), 0, 1);
                    $pdf->SetTextColor(0, 0, 0);
                } else {
                    $pdf->Cell(0, 6, date('F d, Y', strtotime($adm['admission_date'])) . ' at ' . date('g:i A', strtotime($adm['admission_time'])), 0, 1);
                }
            }

            // Details
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(8, 5, '', 0, 0); // Indent
            $pdf->Cell(40, 5, 'Mode of Arrival:', 0, 0);
            $pdf->Cell(0, 5, ucfirst($adm['mode_of_arrival']), 0, 1);

            if ($nurse_name) {
                $pdf->Cell(8, 5, '', 0, 0); // Indent
                $pdf->Cell(40, 5, 'Admitted by:', 0, 0);
                $pdf->Cell(0, 5, $nurse_name, 0, 1);
            }

            // Days ago
            $pdf->SetFont('Arial','I',9);
            $pdf->Cell(8, 5, '', 0, 0); // Indent
            if ($adm['days_ago'] == 0) {
                $pdf->Cell(0, 5, '   (Today)', 0, 1);
            } elseif ($adm['days_ago'] == 1) {
                $pdf->Cell(0, 5, '   (Yesterday)', 0, 1);
            } else {
                $pdf->Cell(0, 5, '   (' . $adm['days_ago'] . ' days ago)', 0, 1);
            }

            $pdf->Ln(3);
            $count++;
        }

        // Readmission analysis summary
        if ($total_admissions > 1) {
            $pdf->Ln(3);
            $pdf->SetFont('Arial','B',10);
            $pdf->SetFillColor(255, 240, 240);
            $pdf->Cell(0, 7, 'READMISSION ANALYSIS:', 0, 1, 'L', true);
            $pdf->SetFont('Arial','',9);

            // Calculate readmission rate
            $readmissions_within_30 = 0;
            for ($i = 1; $i < count($admission_data); $i++) {
                $days_between = abs((strtotime($admission_data[$i-1]['admission_date']) - strtotime($admission_data[$i]['admission_date'])) / (60 * 60 * 24));
                if ($days_between <= 30) {
                    $readmissions_within_30++;
                }
            }

            $pdf->Cell(8, 5, '', 0, 0);
            $pdf->Cell(0, 5, '• Total Admissions: ' . $total_admissions, 0, 1);
            $pdf->Cell(8, 5, '', 0, 0);
            $pdf->Cell(0, 5, '• Readmissions within 30 days: ' . $readmissions_within_30, 0, 1);

            if ($readmissions_within_30 > 0) {
                $pdf->SetTextColor(255, 0, 0);
                $pdf->Cell(8, 5, '', 0, 0);
                $pdf->Cell(0, 5, '• HIGH READMISSION RISK - Recommend follow-up care review', 0, 1);
                $pdf->SetTextColor(0, 0, 0);
            }
        }

    } else {
        $pdf->SetFont('Arial','I',10);
        $pdf->Cell(0, 6, 'No admission records available.', 0, 1);
    }
    $stmt->close();

    $pdf->Ln(10);

    $pdf->SetFont('Arial','I',8);
    $pdf->SetTextColor(128, 128, 128);
    $pdf->Cell(0, 5, 'Report Generated: ' . date('F d, Y g:i A'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'MYCA HOSPITAL - Confidential Patient Information', 0, 1, 'C');

    break;

    default:
        echo "Invalid print type.";
        exit();

}

// output inline for printing
$pdf->Output('I', "patient_{$patient_id}_{$type}_{$record_id}.pdf");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Report</title>
</head>
<body>

</body>
</html>