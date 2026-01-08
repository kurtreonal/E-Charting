<?php
/**
 * PATIENT DATA EXCEL EXPORT
 * Exports comprehensive patient information to Excel using PhpSpreadsheet
 */

session_name('nurse_session');
session_start();
include 'connection.php';

// Check authentication
if (!isset($_SESSION['nurse_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Get patient_id from URL
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;
$export_type = isset($_GET['export_type']) ? $_GET['export_type'] : 'single'; // single or all

// Load PhpSpreadsheet
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// ==============================================================
// EXPORT SINGLE PATIENT
// ==============================================================
if ($export_type === 'single' && $patient_id) {

    // Get patient basic info
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
        die("Patient not found.");
    }

    $full_name = $patient_data['first_name'] . ' ' . ($patient_data['middle_name'] ? $patient_data['middle_name'] . ' ' : '') . $patient_data['last_name'];

    // Calculate age
    $dob = new DateTime($patient_data['date_of_birth']);
    $now = new DateTime();
    $age = $now->diff($dob)->y;

    // ========================================
    // SHEET 1: PATIENT DEMOGRAPHICS
    // ========================================
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Patient Info');

    // Header styling
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '423F3E']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ];

    $labelStyle = [
        'font' => ['bold' => true, 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0EBE5']]
    ];

    // Title
    $sheet->setCellValue('A1', 'PATIENT INFORMATION REPORT');
    $sheet->mergeCells('A1:D1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    // Patient Demographics
    $row = 3;
    $sheet->setCellValue('A' . $row, 'DEMOGRAPHICS');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->applyFromArray($labelStyle);
    $row++;

    $demographics = [
        'Full Name' => $full_name,
        'Patient ID' => $patient_id,
        'Date of Birth' => date('F d, Y', strtotime($patient_data['date_of_birth'])),
        'Age' => $age . ' years old',
        'Gender' => ucfirst($patient_data['gender']),
        'Contact Number' => $patient_data['contact_number'] ?? 'N/A',
        'Email' => $patient_data['email'],
        'Address' => $patient_data['address'],
        'Patient Status' => strtoupper($patient_data['patient_status']),
        'Registration Date' => date('F d, Y', strtotime($patient_data['registration_date']))
    ];

    foreach ($demographics as $label => $value) {
        $sheet->setCellValue('A' . $row, $label);
        $sheet->setCellValue('B' . $row, $value);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
    }

    // Auto-size columns
    $sheet->getColumnDimension('A')->setWidth(25);
    $sheet->getColumnDimension('B')->setWidth(40);

    // ========================================
    // SHEET 2: ADMISSION DATA
    // ========================================
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Admission');

    // Title
    $sheet2->setCellValue('A1', 'ADMISSION DATA');
    $sheet2->mergeCells('A1:D1');
    $sheet2->getStyle('A1')->applyFromArray($headerStyle);
    $sheet2->getRowDimension(1)->setRowHeight(30);

    // Get admission data
    $stmt = $con->prepare("
        SELECT ad.admission_date, ad.admission_time, ad.mode_of_arrival, ad.instructed,
               ad.glasses_or_contactlens, ad.dentures, ad.ambulatory_or_prosthesis,
               ad.smoker, ad.drinker,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM admission_data ad
        LEFT JOIN nurse n ON ad.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE ad.patient_id = ?
        ORDER BY ad.admission_date DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $admission_res = $stmt->get_result();
    $admission_data = $admission_res->fetch_assoc();
    $stmt->close();

    $row = 3;
    if ($admission_data) {
        $nurse_name = trim(($admission_data['nurse_fn'] ?? '') . ' ' . ($admission_data['nurse_ln'] ?? ''));

        $admissionInfo = [
            'Admission Date' => date('F d, Y', strtotime($admission_data['admission_date'])),
            'Admission Time' => date('g:i A', strtotime($admission_data['admission_time'])),
            'Mode of Arrival' => ucfirst($admission_data['mode_of_arrival']),
            'Instructed' => ucfirst(str_replace('-', ' ', $admission_data['instructed'])),
            'Glasses/Contact Lens' => ucfirst($admission_data['glasses_or_contactlens']),
            'Dentures' => ucfirst($admission_data['dentures']),
            'Ambulatory/Prosthesis' => ucfirst($admission_data['ambulatory_or_prosthesis']),
            'Smoker' => ucfirst($admission_data['smoker']),
            'Drinker' => ucfirst($admission_data['drinker']),
            'Admitted By' => $nurse_name ?: 'N/A'
        ];

        foreach ($admissionInfo as $label => $value) {
            $sheet2->setCellValue('A' . $row, $label);
            $sheet2->setCellValue('B' . $row, $value);
            $sheet2->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
    } else {
        $sheet2->setCellValue('A3', 'No admission data available');
    }

    $sheet2->getColumnDimension('A')->setWidth(25);
    $sheet2->getColumnDimension('B')->setWidth(40);

    // ========================================
    // SHEET 3: MEDICAL HISTORY
    // ========================================
    $sheet3 = $spreadsheet->createSheet();
    $sheet3->setTitle('Medical History');

    // Title
    $sheet3->setCellValue('A1', 'MEDICAL HISTORY');
    $sheet3->mergeCells('A1:D1');
    $sheet3->getStyle('A1')->applyFromArray($headerStyle);
    $sheet3->getRowDimension(1)->setRowHeight(30);

    // Get history data
    $stmt = $con->prepare("
        SELECT h.history_date, h.allergies, h.duration_of_symptoms, h.regular_medication,
               h.dietary_habits, h.elimination_habits, h.sleep_patterns, h.personal_care,
               h.ambulation, h.communication_problem, h.others,
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

    $row = 3;
    if ($history_data) {
        $nurse_name = trim(($history_data['nurse_fn'] ?? '') . ' ' . ($history_data['nurse_ln'] ?? ''));

        $historyInfo = [
            'Record Date' => date('F d, Y', strtotime($history_data['history_date'])),
            'Allergies' => $history_data['allergies'] ?: 'None reported',
            'Duration of Symptoms' => $history_data['duration_of_symptoms'] ?: 'N/A',
            'Regular Medication' => $history_data['regular_medication'] ?: 'None',
            'Dietary Habits' => $history_data['dietary_habits'] ?: 'N/A',
            'Elimination Habits' => $history_data['elimination_habits'] ?: 'N/A',
            'Sleep Patterns' => $history_data['sleep_patterns'] ?: 'N/A',
            'Personal Care' => ucfirst($history_data['personal_care']),
            'Ambulation' => ucfirst($history_data['ambulation']),
            'Communication Problem' => ucfirst($history_data['communication_problem']),
            'Other Notes' => $history_data['others'] ?: 'None',
            'Recorded By' => $nurse_name ?: 'N/A'
        ];

        foreach ($historyInfo as $label => $value) {
            $sheet3->setCellValue('A' . $row, $label);
            $sheet3->setCellValue('B' . $row, $value);
            $sheet3->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
    } else {
        $sheet3->setCellValue('A3', 'No medical history available');
    }

    $sheet3->getColumnDimension('A')->setWidth(25);
    $sheet3->getColumnDimension('B')->setWidth(50);

    // ========================================
    // SHEET 4: PHYSICAL ASSESSMENT
    // ========================================
    $sheet4 = $spreadsheet->createSheet();
    $sheet4->setTitle('Physical Assessment');

    // Title
    $sheet4->setCellValue('A1', 'PHYSICAL ASSESSMENT');
    $sheet4->mergeCells('A1:D1');
    $sheet4->getStyle('A1')->applyFromArray($headerStyle);
    $sheet4->getRowDimension(1)->setRowHeight(30);

    // Get physical assessment data
    $stmt = $con->prepare("
        SELECT pa.height, pa.weight, pa.bp_lft, pa.pulse, pa.status, pa.orientation,
               pa.skin_color, pa.skin_turgor, pa.skin_temp, pa.respiratory_status,
               pa.temp_ranges, pa.temperature, pa.created_date,
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

    $row = 3;
    if ($physical_data) {
        $nurse_name = trim(($physical_data['nurse_fn'] ?? '') . ' ' . ($physical_data['nurse_ln'] ?? ''));

        // Calculate BMI
        $bmi = ($physical_data['weight'] / (($physical_data['height']/100) * ($physical_data['height']/100)));

        $sheet4->setCellValue('A' . $row, 'VITAL SIGNS');
        $sheet4->mergeCells('A' . $row . ':D' . $row);
        $sheet4->getStyle('A' . $row)->applyFromArray($labelStyle);
        $row++;

        $vitals = [
            'Assessment Date' => date('F d, Y', strtotime($physical_data['created_date'])),
            'Height' => $physical_data['height'] . ' cm',
            'Weight' => $physical_data['weight'] . ' kg',
            'BMI' => number_format($bmi, 1),
            'Blood Pressure' => $physical_data['bp_lft'] . ' mmHg',
            'Pulse' => $physical_data['pulse'] . ' bpm',
            'Temperature' => $physical_data['temp_ranges'] . '°C (' . ucfirst($physical_data['temperature']) . ')'
        ];

        foreach ($vitals as $label => $value) {
            $sheet4->setCellValue('A' . $row, $label);
            $sheet4->setCellValue('B' . $row, $value);
            $sheet4->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $row++;
        $sheet4->setCellValue('A' . $row, 'PHYSICAL EXAMINATION');
        $sheet4->mergeCells('A' . $row . ':D' . $row);
        $sheet4->getStyle('A' . $row)->applyFromArray($labelStyle);
        $row++;

        $examination = [
            'Status' => ucfirst($physical_data['status'] ?? 'Normal'),
            'Orientation' => ucfirst($physical_data['orientation']),
            'Skin Color' => ucfirst($physical_data['skin_color']),
            'Skin Turgor' => ucfirst($physical_data['skin_turgor']),
            'Skin Temperature' => ucfirst($physical_data['skin_temp']),
            'Respiratory Status' => ucfirst($physical_data['respiratory_status']),
            'Assessed By' => $nurse_name ?: 'N/A'
        ];

        foreach ($examination as $label => $value) {
            $sheet4->setCellValue('A' . $row, $label);
            $sheet4->setCellValue('B' . $row, $value);
            $sheet4->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
    } else {
        $sheet4->setCellValue('A3', 'No physical assessment available');
    }

    $sheet4->getColumnDimension('A')->setWidth(25);
    $sheet4->getColumnDimension('B')->setWidth(40);

    // ========================================
    // SHEET 5: MEDICATIONS
    // ========================================
    $sheet5 = $spreadsheet->createSheet();
    $sheet5->setTitle('Medications');

    // Title
    $sheet5->setCellValue('A1', 'MEDICATIONS / DOCTOR\'S ORDERS');
    $sheet5->mergeCells('A1:G1');
    $sheet5->getStyle('A1')->applyFromArray($headerStyle);
    $sheet5->getRowDimension(1)->setRowHeight(30);

    // Column headers
    $headers = ['Medication', 'Type', 'Dosage', 'Frequency', 'Date Prescribed', 'Notes', 'Prescribed By'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet5->setCellValue($col . '2', $header);
        $sheet5->getStyle($col . '2')->applyFromArray($labelStyle);
        $col++;
    }

    // Get medications
    $stmt = $con->prepare("
        SELECT m.medication_name, m.medication_type, m.dose, m.times_per_day,
               m.interval_minutes, m.date_prescribed, m.notes,
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

    $row = 3;
    if ($medication_res->num_rows > 0) {
        while ($med = $medication_res->fetch_assoc()) {
            $nurse_name = trim(($med['nurse_fn'] ?? '') . ' ' . ($med['nurse_ln'] ?? ''));

            // Format frequency
            $frequency = 'N/A';
            if ($med['times_per_day']) {
                $frequency = $med['times_per_day'] . 'x daily';
            } elseif ($med['interval_minutes']) {
                $hours = $med['interval_minutes'] / 60;
                $frequency = 'Every ' . $hours . ' hours';
            }

            $sheet5->setCellValue('A' . $row, $med['medication_name']);
            $sheet5->setCellValue('B' . $row, $med['medication_type']);
            $sheet5->setCellValue('C' . $row, $med['dose']);
            $sheet5->setCellValue('D' . $row, $frequency);
            $sheet5->setCellValue('E' . $row, date('M d, Y', strtotime($med['date_prescribed'])));
            $sheet5->setCellValue('F' . $row, $med['notes'] ?: '');
            $sheet5->setCellValue('G' . $row, $nurse_name ?: 'N/A');

            $row++;
        }
    } else {
        $sheet5->setCellValue('A3', 'No medications prescribed');
    }
    $stmt->close();

    // Auto-size columns
    foreach (range('A', 'G') as $col) {
        $sheet5->getColumnDimension($col)->setAutoSize(true);
    }

    // ========================================
    // SHEET 6: LAB RESULTS
    // ========================================
    $sheet6 = $spreadsheet->createSheet();
    $sheet6->setTitle('Lab Results');

    // Title
    $sheet6->setCellValue('A1', 'LABORATORY RESULTS');
    $sheet6->mergeCells('A1:E1');
    $sheet6->getStyle('A1')->applyFromArray($headerStyle);
    $sheet6->getRowDimension(1)->setRowHeight(30);

    // Column headers
    $headers = ['#', 'File Name', 'Upload Date', 'Notes', 'Uploaded By'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet6->setCellValue($col . '2', $header);
        $sheet6->getStyle($col . '2')->applyFromArray($labelStyle);
        $col++;
    }

    // Get lab results
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

    $row = 3;
    $count = 1;
    if ($lab_res->num_rows > 0) {
        while ($lab = $lab_res->fetch_assoc()) {
            $nurse_name = trim(($lab['nurse_fn'] ?? '') . ' ' . ($lab['nurse_ln'] ?? ''));

            $sheet6->setCellValue('A' . $row, $count);
            $sheet6->setCellValue('B' . $row, $lab['image_name']);
            $sheet6->setCellValue('C' . $row, date('M d, Y g:i A', strtotime($lab['upload_date'])));
            $sheet6->setCellValue('D' . $row, $lab['notes'] ?: '');
            $sheet6->setCellValue('E' . $row, $nurse_name ?: 'N/A');

            $row++;
            $count++;
        }
    } else {
        $sheet6->setCellValue('A3', 'No laboratory results available');
    }
    $stmt->close();

    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet6->getColumnDimension($col)->setAutoSize(true);
    }

    // ========================================
    // SHEET 7: APPOINTMENTS
    // ========================================
    $sheet7 = $spreadsheet->createSheet();
    $sheet7->setTitle('Appointments');

    // Title
    $sheet7->setCellValue('A1', 'APPOINTMENTS');
    $sheet7->mergeCells('A1:E1');
    $sheet7->getStyle('A1')->applyFromArray($headerStyle);
    $sheet7->getRowDimension(1)->setRowHeight(30);

    // Column headers
    $headers = ['#', 'Date', 'Time', 'Status', 'With'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet7->setCellValue($col . '2', $header);
        $sheet7->getStyle($col . '2')->applyFromArray($labelStyle);
        $col++;
    }

    // Get appointments
    $stmt = $con->prepare("
        SELECT a.appointment_date, a.appointment_time, a.appointment_status,
               nu.first_name AS nurse_fn, nu.last_name AS nurse_ln
        FROM appointment a
        LEFT JOIN nurse n ON a.nurse_id = n.nurse_id
        LEFT JOIN users nu ON n.user_id = nu.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointment_res = $stmt->get_result();

    $row = 3;
    $count = 1;
    if ($appointment_res->num_rows > 0) {
        while ($appt = $appointment_res->fetch_assoc()) {
            $nurse_name = trim(($appt['nurse_fn'] ?? '') . ' ' . ($appt['nurse_ln'] ?? ''));

            $sheet7->setCellValue('A' . $row, $count);
            $sheet7->setCellValue('B' . $row, date('M d, Y', strtotime($appt['appointment_date'])));
            $sheet7->setCellValue('C' . $row, date('g:i A', strtotime($appt['appointment_time'])));
            $sheet7->setCellValue('D' . $row, ucfirst($appt['appointment_status']));
            $sheet7->setCellValue('E' . $row, $nurse_name ?: 'N/A');

            $row++;
            $count++;
        }
    } else {
        $sheet7->setCellValue('A3', 'No appointments scheduled');
    }
    $stmt->close();

    // Auto-size columns
    foreach (range('A', 'E') as $col) {
        $sheet7->getColumnDimension($col)->setAutoSize(true);
    }

    // Set active sheet to first sheet
    $spreadsheet->setActiveSheetIndex(0);

    // Generate filename
    $filename = 'Patient_Report_' . $full_name . '_' . date('Y-m-d') . '.xlsx';
    $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

} else {
    // ==============================================================
    // EXPORT ALL PATIENTS (SUMMARY)
    // ==============================================================

    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('All Patients');

    // Title
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '423F3E']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    $sheet->setCellValue('A1', 'ALL PATIENTS REPORT');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->applyFromArray($headerStyle);
    $sheet->getRowDimension(1)->setRowHeight(30);

    // Column headers
    $labelStyle = [
        'font' => ['bold' => true, 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0EBE5']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    $headers = ['ID', 'Full Name', 'Age', 'Gender', 'Contact', 'Status', 'Last Admission', 'Medications'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '2', $header);
        $sheet->getStyle($col . '2')->applyFromArray($labelStyle);
        $col++;
    }

    // Get all patients
    $stmt = $con->prepare("
        SELECT p.patient_id, u.first_name, u.last_name, u.middle_name,
               p.date_of_birth, p.gender, p.contact_number, p.patient_status
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute();
    $patients_res = $stmt->get_result();

    $row = 3;
    while ($patient = $patients_res->fetch_assoc()) {
        $full_name = $patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name'];

        // Calculate age
        $dob = new DateTime($patient['date_of_birth']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;

        // Get last admission
        $admStmt = $con->prepare("SELECT admission_date FROM admission_data WHERE patient_id = ? ORDER BY admission_date DESC LIMIT 1");
        $admStmt->bind_param("i", $patient['patient_id']);
        $admStmt->execute();
        $admRes = $admStmt->get_result();
        $lastAdm = $admRes->fetch_assoc();
        $admStmt->close();

        // Count medications
        $medStmt = $con->prepare("SELECT COUNT(*) as med_count FROM medication WHERE patient_id = ?");
        $medStmt->bind_param("i", $patient['patient_id']);
        $medStmt->execute();
        $medRes = $medStmt->get_result();
        $medCount = $medRes->fetch_assoc();
        $medStmt->close();

        $sheet->setCellValue('A' . $row, $patient['patient_id']);
        $sheet->setCellValue('B' . $row, $full_name);
        $sheet->setCellValue('C' . $row, $age);
        $sheet->setCellValue('D' . $row, ucfirst($patient['gender']));
        $sheet->setCellValue('E' . $row, $patient['contact_number'] ?? 'N/A');
        $sheet->setCellValue('F' . $row, ucfirst($patient['patient_status']));
        $sheet->setCellValue('G' . $row, $lastAdm ? date('M d, Y', strtotime($lastAdm['admission_date'])) : 'Never');
        $sheet->setCellValue('H' . $row, $medCount['med_count']);

        $row++;
    }
    $stmt->close();

    // Auto-size all columns
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'All_Patients_Report_' . date('Y-m-d') . '.xlsx';
}

// ==============================================================
// GENERATE AND DOWNLOAD EXCEL FILE
// ==============================================================

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create writer and output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit();
?>