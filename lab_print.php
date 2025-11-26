<?php
session_start();
include 'connection.php';
require('fpdf.php'); // ensure fpdf.php is in same directory or update path

// either file_id OR type + patient_id
$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : null;
$type = $_GET['type'] ?? null;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : ($_SESSION['patient_id'] ?? null);

if ($file_id) {
    // stream file from uploads
    $stmt = $con->prepare("SELECT file_path, file_name, file_type FROM lab_results WHERE lab_result_id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows !== 1) {
        echo "File not found.";
        exit();
    }
    $row = $res->fetch_assoc();
    $stmt->close();

    $fullpath = __DIR__ . '/' . $row['file_path'];
    if (!file_exists($fullpath)) { echo "File missing."; exit(); }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . ($row['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . basename($row['file_name']) . '"');
    header('Content-Length: ' . filesize($fullpath));
    readfile($fullpath);
    exit();
}

if (!$type || !$patient_id) {
    echo "Missing parameters.";
    exit();
}

// helper to get patient name
$stmt = $con->prepare("SELECT u.first_name, u.last_name, p.date_of_birth FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$pRes = $stmt->get_result();
if ($pRes->num_rows !== 1) { echo "Patient not found."; exit(); }
$pRow = $pRes->fetch_assoc();
$stmt->close();

$fullname = $pRow['first_name'] . ' ' . $pRow['last_name'];

// create PDF
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
$pdf->Cell(40,7,'DOB:',0,0);
$pdf->Cell(0,7,$pRow['date_of_birth'],0,1);
$pdf->Ln(6);

switch ($type) {
    case 'history':
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'History',0,1);
        $pdf->Ln(3);
        $stmt = $con->prepare("SELECT history_date, allergies, duration_of_symptoms, regular_medication, dietary_habits, elimination_habits, sleep_patterns, others FROM history WHERE patient_id = ? ORDER BY history_date DESC");
        $stmt->bind_param("i",$patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pdf->SetFont('Arial','',10);
        if ($res->num_rows === 0) {
            $pdf->Cell(0,6,'No history records found.',0,1);
        } else {
            while ($r = $res->fetch_assoc()) {
                $pdf->SetFont('Arial','B',10);
                $pdf->Cell(0,6,'Record Date: ' . $r['history_date'],0,1);
                $pdf->SetFont('Arial','',10);
                $pdf->MultiCell(0,6, "Allergies: " . ($r['allergies'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Duration of symptoms: " . ($r['duration_of_symptoms'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Regular medication: " . ($r['regular_medication'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Dietary habits: " . ($r['dietary_habits'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Elimination habits: " . ($r['elimination_habits'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Sleep patterns: " . ($r['sleep_patterns'] ?: 'N/A'));
                if (!empty($r['others'])) $pdf->MultiCell(0,6, "Other notes: " . $r['others']);
                $pdf->Ln(4);
            }
        }
        $stmt->close();
        break;

    case 'physical':
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'Physical Assessment',0,1);
        $pdf->Ln(3);
        $stmt = $con->prepare("SELECT created_date, nurse_id, height, weight, bp_lft, pulse, strong, status, orientation, skin_color, skin_turgor, skin_temp, mucous_membrane, peripheral_sounds, neck_vein_distention, respiratory_status, respiratory_sounds, cough, sputum, temp_ranges, temperature FROM physical_assessment WHERE patient_id = ? ORDER BY created_date DESC");
        $stmt->bind_param("i",$patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pdf->SetFont('Arial','',10);
        if ($res->num_rows === 0) {
            $pdf->Cell(0,6,'No physical assessment records found.',0,1);
        } else {
            while ($r = $res->fetch_assoc()) {
                $pdf->SetFont('Arial','B',10);
                $pdf->Cell(0,6,'Assessment Date: ' . $r['created_date'],0,1);
                $pdf->SetFont('Arial','',10);
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
                $pdf->Ln(4);
            }
        }
        $stmt->close();
        break;

    case 'admission':
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,7,'Admission Data',0,1);
        $pdf->Ln(3);
        $stmt = $con->prepare("SELECT admission_date, admission_time, mode_of_arrival, instructed, glasses_or_contactlens, dentures, ambulatory_or_prosthesis, smoker, drinker, created_date FROM admission_data WHERE patient_id = ? ORDER BY created_date DESC");
        $stmt->bind_param("i",$patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pdf->SetFont('Arial','',10);
        if ($res->num_rows === 0) {
            $pdf->Cell(0,6,'No admission records found.',0,1);
        } else {
            while ($r = $res->fetch_assoc()) {
                $pdf->SetFont('Arial','B',10);
                $pdf->Cell(0,6,'Admission Date: ' . $r['admission_date'] . ' ' . $r['admission_time'],0,1);
                $pdf->SetFont('Arial','',10);
                $pdf->MultiCell(0,6, "Mode of arrival: " . ($r['mode_of_arrival'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Instructed: " . ($r['instructed'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Glasses or contact lens: " . ($r['glasses_or_contactlens'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Dentures: " . ($r['dentures'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Ambulatory or prosthesis: " . ($r['ambulatory_or_prosthesis'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Smoker: " . ($r['smoker'] ?: 'N/A'));
                $pdf->MultiCell(0,6, "Drinker: " . ($r['drinker'] ?: 'N/A'));
                $pdf->Ln(4);
            }
        }
        $stmt->close();
        break;

    default:
        echo "Invalid print type.";
        exit();
}

// Send PDF to browser inline
$pdf->Output('I', "patient_{$patient_id}_{$type}.pdf");
exit();
