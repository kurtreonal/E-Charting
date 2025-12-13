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