<?php
include 'connection.php';
include_once 'includes/notification.php';
include_once 'includes/patient-metrics.php';
session_name('nurse_session');
session_start();

//get the nurse_id from session
$nurse_id = isset($_SESSION['nurse_id']) ? $_SESSION['nurse_id'] : null;

if (!$nurse_id) {
    header("Location: admin-login.php");
    exit();
}

// Calculate metrics using existing data
$avg_length_of_stay = calculate_average_length_of_stay($con);
$recovery_rate = calculate_recovery_rate($con);
$readmission_rate = calculate_readmission_rate($con);
$patient_satisfaction = calculate_patient_satisfaction($con);

// Get distribution data for charts
$age_distribution = get_age_distribution($con);
$gender_distribution = get_gender_distribution($con);

// Fetch all patients
$sql = "
    SELECT usr.first_name, usr.middle_name, usr.last_name, p.patient_id, p.patient_status
    FROM patients p
    LEFT JOIN users usr ON p.user_id = usr.user_id
    ORDER BY usr.first_name ASC
";

$result = $con->query($sql);
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
                            <option>Last 7 Days</option>
                            <option selected>Last 30 Days</option>
                            <option>Last 3 Months</option>
                            <option>Last 6 Months</option>
                            <option>Last Year</option>
                            <option>Custom Range</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Patient Status</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="all"> All
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="active"> Active
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="in-patient"> In-Patient
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" checked value="out-patient"> Out-Patient
                            </label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Patient Instructed</label>
                        <select class="filter-select" id="instructedFilter">
                            <option value="">All Instructions</option>
                            <option value="wardset">Ward Set</option>
                            <option value="medication">Medication</option>
                            <option value="hospital-rules">Hospital Rules</option>
                            <option value="special">Special Procedure</option>
                        </select>
                    </div>

                    <button class="apply-filters-btn">Apply Filters</button>
                </div>

                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <button class="action-btn" onclick="window.location.href='add-patient.php'">
                        <i class="fas fa-user-plus"></i> Add New Patient
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-file-pdf"></i> Generate PDF Report
                    </button>
                    <button class="action-btn">
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
                                <h4>Readmission Rate</h4>
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

                    <!-- Patient List Section -->
                    <div class="patient-list-section" data-animate="fade-up">
                        <div class="patient-list-header">
                            <h3>Patient List</h3>
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
                                    <tr data-status="<?php echo strtolower(str_replace('-', '', $patient['patient_status'] ?? 'active')); ?>">
                                        <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . ($patient['middle_name'] ? $patient['middle_name'] . ' ' : '') . $patient['last_name']); ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower(str_replace('-', '', $patient['patient_status'] ?? 'active')); ?>"><?php echo htmlspecialchars($patient['patient_status'] ?? 'N/A'); ?></span></td>
                                        <td class="actions-cell">
                                            <a href="create-appointment.php?patient_id=<?php echo (int)$patient['patient_id']; ?>" class="btn btn-primary" aria-label="Create appointment for <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>">Create Appointment</a>
                                            <span class="separator">|</span>
                                            <a href="admit-patient.php?patient_id=<?php echo (int)$patient['patient_id']; ?>" class="btn btn-success" aria-label="Admit patient <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>">Admit Patient</a>
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
</body>
</html>