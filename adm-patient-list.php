<?php
include 'authcheck.php';

include 'connection.php';
include_once 'includes/notification.php';
include_once 'includes/activity-logger.php';
include_once 'includes/patient-metrics.php';

// Get filter parameters
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';
$instructed_filter = isset($_GET['instructed']) ? $_GET['instructed'] : '';
$status_filter = isset($_GET['statuses']) ? explode(',', $_GET['statuses']) : [];

// Calculate metrics using existing data
$avg_length_of_stay = calculate_average_length_of_stay($con);
$recovery_rate = calculate_recovery_rate($con);
$readmission_rate = calculate_readmission_rate($con);
$patient_satisfaction = calculate_patient_satisfaction($con);

// Get distribution data for charts
$age_distribution = get_age_distribution($con);
$gender_distribution = get_gender_distribution($con);

// Fetch UNIQUE patients with filtering
$sql = "
    SELECT DISTINCT usr.first_name, usr.middle_name, usr.last_name, p.patient_id, p.patient_status
    FROM patients p
    LEFT JOIN users usr ON p.user_id = usr.user_id
    WHERE 1=1
";

$conditions = [];
$params = [];
$param_types = '';

// Date range filter
if ($date_range !== 'all' && !empty($date_range)) {
    $days = (int)$date_range;
    $conditions[] = "p.patient_id IN (
        SELECT DISTINCT patient_id
        FROM admission_data
        WHERE admission_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
    )";
}

// Instructed filter
if (!empty($instructed_filter)) {
    $conditions[] = "p.patient_id IN (
        SELECT DISTINCT patient_id
        FROM admission_data
        WHERE instructed = ?
    )";
    $params[] = $instructed_filter;
    $param_types .= 's';
}

// Patient status filter
if (!empty($status_filter)) {
    $status_placeholders = [];
    foreach ($status_filter as $status) {
        $db_status = trim($status);
        if (in_array($db_status, ['active', 'in-patient', 'out-patient', 'deceased'])) {
            $status_placeholders[] = '?';
            $params[] = $db_status;
            $param_types .= 's';
        }
    }

    if (!empty($status_placeholders)) {
        $conditions[] = "p.patient_status IN (" . implode(',', $status_placeholders) . ")";
    }
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY usr.first_name ASC";

// Execute query
if (!empty($params)) {
    $stmt = $con->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $con->query($sql);
}

$patients = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - E-Charting System</title>
    <link rel="stylesheet" href="./Styles/adm-patient-list.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="./Javascript/logoutFunction.js" defer></script>
    <script src="./Javascript/adm-patient-list.js" defer></script>
    <!-- PDF Report Modal Styles -->
    <style>
        /* PDF Report Modal Styles */
        .report-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .report-modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .report-modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 2px solid #f0ebe5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #423f3e;
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .report-modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .report-modal-close {
            font-size: 2rem;
            cursor: pointer;
            color: white;
            transition: transform 0.3s;
            line-height: 1;
        }

        .report-modal-close:hover {
            transform: rotate(90deg);
        }

        .report-modal-body {
            padding: 2rem;
        }

        .report-modal-body p {
            margin-bottom: 1rem;
            color: #666;
        }

        .report-patient-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background-color: white;
            cursor: pointer;
        }

        .report-patient-select:focus {
            outline: none;
            border-color: #423f3e;
        }

        .report-info {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f0ebe5;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #423f3e;
        }

        .report-modal-footer {
            padding: 1.5rem 2rem;
            border-top: 2px solid #f0ebe5;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn-cancel,
        .btn-generate {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-cancel {
            background-color: #e0e0e0;
            color: #666;
        }

        .btn-cancel:hover {
            background-color: #d0d0d0;
        }

        .btn-generate {
            background-color: #423f3e;
            color: white;
        }

        .btn-generate:hover {
            background-color: #2d2b2a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 63, 62, 0.3);
        }

        .btn-generate:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        /* Excel Export Modal Specific Styles */
        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .export-option-card {
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .export-option-card:hover {
            border-color: #423f3e;
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(66, 63, 62, 0.2);
        }

        .export-option-card.selected {
            border-color: #423f3e;
            background-color: #f0ebe5;
        }

        .export-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .export-option-card h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #423f3e;
        }

        .export-option-card p {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .export-details {
            font-size: 0.85rem !important;
            color: #888 !important;
            font-style: italic;
        }

        .excel-info {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #e8f5e9;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }

        .excel-info strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #1b5e20;
        }

        #singlePatientSelection label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #423f3e;
        }
    </style>
</head>
<body>
    <?php include "adm-nav.php"; ?>

    <div class="wrapper">
        <div class="dashboard-container">
            <!-- Sidebar Filters -->
            <aside class="sidebar">
                <div class="filter-section">
                    <h3>Filters</h3>

                    <div class="filter-group">
                        <label>Date Range</label>
                        <select class="filter-select" id="dateRangeFilter">
                            <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 3 Months</option>
                            <option value="180" <?php echo $date_range == '180' ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="365" <?php echo $date_range == '365' ? 'selected' : ''; ?>>Last Year</option>
                            <option value="all" <?php echo $date_range == 'all' ? 'selected' : ''; ?>>All Time</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Patient Status</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="active"> Active
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="in-patient"> In-Patient
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="out-patient"> Out-Patient
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="deceased"> Deceased
                            </label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Patient Instructed</label>
                        <select class="filter-select" id="instructedFilter">
                            <option value="" <?php echo $instructed_filter == '' ? 'selected' : ''; ?>>All Instructions</option>
                            <option value="wardset" <?php echo $instructed_filter == 'wardset' ? 'selected' : ''; ?>>Ward Set</option>
                            <option value="medication" <?php echo $instructed_filter == 'medication' ? 'selected' : ''; ?>>Medication</option>
                            <option value="hospital-rules" <?php echo $instructed_filter == 'hospital-rules' ? 'selected' : ''; ?>>Hospital Rules</option>
                            <option value="special" <?php echo $instructed_filter == 'special' ? 'selected' : ''; ?>>Special Procedure</option>
                        </select>
                    </div>

                    <button class="apply-filters-btn">Apply Filters</button>
                </div>

                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <button class="action-btn" onclick="window.location.href='add-patient.php'">
                        <i class="fas fa-user-plus"></i> Add New Patient
                    </button>
                    <button class="action-btn" onclick="openPdfModal()">
                        <i class="fas fa-file-pdf"></i> Generate PDF Report
                    </button>
                    <button class="action-btn" onclick="openExcelModal()">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </div>
            </aside>

            <!-- Main Dashboard Content -->
            <main class="dashboard-content">
                <div class="container">
                    <!-- Header Section -->
                    <div class="header-section">
                        <div class="header-content">
                            <h1>Patient Outcomes & Metrics</h1>
                            <p class="section-subtitle">Track patient health indicators and outcomes</p>
                        </div>
                        <div class="btn-add-patient">
                            <a href="add-patient.php">
                                <button><i class="fas fa-plus"></i> Add Patient</button>
                            </a>
                        </div>
                    </div>

                    <!-- Patient List Section -->
                    <div class="patient-list-section" data-animate="fade-up">
                        <div class="patient-list-header">
                            <h3>Patient List</h3>
                            <p class="list-info">Showing unique patients (<?php echo count($patients); ?> total)</p>
                        </div>

                        <table class="patient-list-table">
                            <thead>
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="patientTableBody">
                                <?php if (count($patients) > 0): ?>
                                    <?php foreach($patients as $patient): ?>
                                    <tr data-patient-id="<?php echo (int)$patient['patient_id']; ?>" data-status="<?php echo strtolower(str_replace('-', '', $patient['patient_status'] ?? 'active')); ?>">
                                        <td><a href="view-patient-information.php?patient_id=<?php echo (int)$patient['patient_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']); ?></a></td>
                                        <td><span class="status-badge status-<?php echo strtolower(str_replace('-', '', $patient['patient_status'] ?? 'active')); ?>"><?php echo htmlspecialchars($patient['patient_status'] ?? 'N/A'); ?></span></td>
                                        <td class="actions-cell">
                                            <?php if ($patient['patient_status'] === 'deceased'): ?>
                                                <span class="btn btn-success" style="opacity: 0.5; cursor: not-allowed; pointer-events: none;" title="Cannot admit deceased patient">Admit Patient</span>
                                            <?php else: ?>
                                                <a href="admit-patient.php?patient_id=<?php echo (int)$patient['patient_id']; ?>" class="btn btn-success" aria-label="Admit patient <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>">Admit Patient</a>
                                            <?php endif; ?>
                                            <span class="separator">|</span>
                                            <a href="update-patient.php?id=<?php echo (int)$patient['patient_id']; ?>" class="btn btn-secondary">Edit</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 20px;">No patients found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Patient Metrics Grid -->
                    <div class="metrics-grid">
                        <div class="metric-card" data-animate="fade-up">
                            <div class="metric-header">
                                <h4>Average Length of Stay</h4>
                                <i class="fas fa-bed metric-icon"></i>
                            </div>
                            <div class="metric-value">
                                <span class="value" data-count="<?php echo $avg_length_of_stay; ?>">0</span>
                                <span class="unit">days</span>
                            </div>
                            <div class="metric-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(($avg_length_of_stay / 6) * 100, 100); ?>%; background: #2196F3;"></div>
                                </div>
                                <span class="progress-label">Target: 6 days</span>
                            </div>
                        </div>

                        <div class="metric-card" data-animate="fade-up" style="animation-delay: 0.1s">
                            <div class="metric-header">
                                <h4>Recovery Rate</h4>
                                <i class="fas fa-smile metric-icon"></i>
                            </div>
                            <div class="metric-value">
                                <span class="value" data-count="<?php echo $recovery_rate; ?>">0</span>
                                <span class="unit">%</span>
                            </div>
                            <div class="metric-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $recovery_rate; ?>%; background: #4CAF50;"></div>
                                </div>
                                <span class="progress-label">Target: 85%</span>
                            </div>
                        </div>

                        <div class="metric-card" data-animate="fade-up" style="animation-delay: 0.2s">
                            <div class="metric-header">
                                <h4>Re-admission Rate</h4>
                                <i class="fas fa-redo metric-icon"></i>
                            </div>
                            <div class="metric-value">
                                <span class="value" data-count="<?php echo $readmission_rate; ?>">0</span>
                                <span class="unit">%</span>
                            </div>
                            <div class="metric-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min($readmission_rate * 10, 100); ?>%; background: #FFC107;"></div>
                                </div>
                                <span class="progress-label">Target: <10%</span>
                            </div>
                        </div>

                        <div class="metric-card" data-animate="fade-up" style="animation-delay: 0.3s">
                            <div class="metric-header">
                                <h4>Patient Satisfaction</h4>
                                <i class="fas fa-star metric-icon"></i>
                            </div>
                            <div class="metric-value">
                                <span class="value" data-count="<?php echo $patient_satisfaction; ?>">0</span>
                                <span class="unit">/ 5</span>
                            </div>
                            <div class="metric-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo ($patient_satisfaction / 5) * 100; ?>%; background: #FF9800;"></div>
                                </div>
                                <span class="progress-label">Target: 4.5/5</span>
                            </div>
                        </div>
                    </div>

                    <!-- Patient Demographics Charts -->
                    <div class="charts-row">
                        <div class="chart-card" data-animate="fade-up">
                            <div class="chart-header">
                                <h3>Age Distribution</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="ageChart"></canvas>
                            </div>
                        </div>

                        <div class="chart-card" data-animate="fade-up" style="animation-delay: 0.1s">
                            <div class="chart-header">
                                <h3>Gender Distribution</h3>
                            </div>
                            <div class="chart-container">
                                <canvas id="genderChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logout Button -->
                <div class="signature-button">
                    <form method="POST" action="./staff-logout.php" onsubmit="return confirmLogout()">
                        <div class="button-container">
                            <button type="submit" name="logout" id="logout">Logout</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Pass PHP data to JavaScript -->
    <script>
        const ageDistributionData = <?php echo json_encode(array_values($age_distribution)); ?>;
        const genderDistributionData = <?php echo json_encode(array_values($gender_distribution)); ?>;
    </script>

    <!-- Patient Selection Modal for PDF Report -->
    <div id="pdfReportModal" class="report-modal" style="display: none;">
        <div class="report-modal-content">
            <div class="report-modal-header">
                <h2>Generate Comprehensive Patient Report</h2>
                <span class="report-modal-close" onclick="closePdfModal()">&times;</span>
            </div>
            <div class="report-modal-body">
                <p>Select a patient to generate their comprehensive medical report:</p>
                <select id="patientSelectForReport" class="report-patient-select">
                    <option value="">-- Select Patient --</option>
                    <?php foreach($patients as $patient): ?>
                        <option value="<?php echo (int)$patient['patient_id']; ?>">
                            <?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="report-info">This report includes: Demographics, Activity Log (all nurse actions), and Recent Admissions & Readmissions.</p>
            </div>
            <div class="report-modal-footer">
                <button onclick="closePdfModal()" class="btn-cancel">Cancel</button>
                <button onclick="generatePdfReport()" class="btn-generate">Generate Report</button>
            </div>
        </div>
    </div>

    <!-- PDF Report Modal JavaScript -->
    <script>
    function openPdfModal() {
        document.getElementById('pdfReportModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closePdfModal() {
        document.getElementById('pdfReportModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function generatePdfReport() {
        const patientId = document.getElementById('patientSelectForReport').value;

        if (!patientId) {
            alert('Please select a patient first.');
            return;
        }

        // Open PDF in new tab
        const url = `lab_print.php?type=comprehensive&record_id=1&patient_id=${patientId}`;
        window.open(url, '_blank');

        // Close modal
        closePdfModal();
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('pdfReportModal');
        if (event.target === modal) {
            closePdfModal();
        }
    });

    // Close modal on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closePdfModal();
        }
    });
    </script>

    <!-- Excel Export Modal -->
    <div id="excelExportModal" class="report-modal" style="display: none;">
        <div class="report-modal-content">
            <div class="report-modal-header">
                <h2>📊 Export Patient Data to Excel</h2>
                <span class="report-modal-close" onclick="closeExcelModal()">&times;</span>
            </div>
            <div class="report-modal-body">
                <p>Choose export type:</p>

                <div class="export-options">
                    <div class="export-option-card" onclick="selectExportType('single')">
                        <div class="export-icon">📄</div>
                        <h3>Single Patient</h3>
                        <p>Export complete medical records for one patient</p>
                        <p class="export-details">Includes: Demographics, Admission, History, Physical Assessment, Medications, Lab Results, Appointments</p>
                    </div>

                    <div class="export-option-card" onclick="selectExportType('all')">
                        <div class="export-icon">📋</div>
                        <h3>All Patients</h3>
                        <p>Export summary report for all patients</p>
                        <p class="export-details">Includes: Patient list with key information and statistics</p>
                    </div>
                </div>

                <!-- Single Patient Selection -->
                <div id="singlePatientSelection" style="display: none; margin-top: 1.5rem;">
                    <label for="patientSelectForExcel">Select Patient:</label>
                    <select id="patientSelectForExcel" class="report-patient-select">
                        <option value="">-- Select Patient --</option>
                        <?php foreach($patients as $patient): ?>
                            <option value="<?php echo (int)$patient['patient_id']; ?>">
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="report-modal-footer">
                <button onclick="closeExcelModal()" class="btn-cancel">Cancel</button>
                <button onclick="generateExcelReport()" class="btn-generate" id="btnGenerateExcel">
                    Generate Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Excel Export Modal JavaScript -->
    <script>
    let selectedExportType = null;

    function openExcelModal() {
        document.getElementById('excelExportModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        selectedExportType = null;
        document.getElementById('singlePatientSelection').style.display = 'none';

        // Remove selected class from all cards
        document.querySelectorAll('.export-option-card').forEach(card => {
            card.classList.remove('selected');
        });
    }

    function closeExcelModal() {
        document.getElementById('excelExportModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        selectedExportType = null;
    }

    function selectExportType(type) {
        selectedExportType = type;

        // Update UI - highlight selected card
        document.querySelectorAll('.export-option-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');

        // Show/hide patient selection
        if (type === 'single') {
            document.getElementById('singlePatientSelection').style.display = 'block';
        } else {
            document.getElementById('singlePatientSelection').style.display = 'none';
        }
    }

    function generateExcelReport() {
        if (!selectedExportType) {
            alert('Please select an export type first.');
            return;
        }

        let url = 'export-excel.php?export_type=' + selectedExportType;

        if (selectedExportType === 'single') {
            const patientId = document.getElementById('patientSelectForExcel').value;

            if (!patientId) {
                alert('Please select a patient first.');
                return;
            }

            url += '&patient_id=' + patientId;
        }

        // Show loading state
        const btn = document.getElementById('btnGenerateExcel');
        const originalText = btn.innerHTML;
        btn.innerHTML = '⏳ Generating...';
        btn.disabled = true;

        // Open export URL (will trigger download)
        window.location.href = url;

        // Reset button after delay
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            closeExcelModal();
        }, 2000);
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('excelExportModal');
        if (event.target === modal) {
            closeExcelModal();
        }
    });

    // Close modal on ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeExcelModal();
        }
    });
    </script>
</body>
</html>
