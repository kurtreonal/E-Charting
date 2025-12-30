<?php
include 'connection.php';
include_once 'includes/notification.php';
session_name('nurse_session');
session_start();

// Get the nurse_id from session
$nurse_id = isset($_SESSION['nurse_id']) ? $_SESSION['nurse_id'] : null;

if (!$nurse_id) {
    header("Location: admin-login.php");
    exit();
}

// Fetch patients for the patient list section
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

// Fetch KPI data
$total_patients_result = $con->query("SELECT COUNT(*) as count FROM patients");
$total_patients = $total_patients_result ? $total_patients_result->fetch_assoc()['count'] : 0;

$active_patients_result = $con->query("SELECT COUNT(*) as count FROM patients WHERE patient_status = 'active'");
$active_patients = $active_patients_result ? $active_patients_result->fetch_assoc()['count'] : 0;

$in_patients_result = $con->query("SELECT COUNT(*) as count FROM patients WHERE patient_status = 'in-patient'");
$in_patients = $in_patients_result ? $in_patients_result->fetch_assoc()['count'] : 0;

$out_patients_result = $con->query("SELECT COUNT(*) as count FROM patients WHERE patient_status = 'out-patient'");
$out_patients = $out_patients_result ? $out_patients_result->fetch_assoc()['count'] : 0;

// Count appointments for today
$today = date('Y-m-d');
$appointments_today_result = $con->query("SELECT COUNT(*) as count FROM appointment WHERE appointment_date = '$today'");
$appointments_today = $appointments_today_result ? $appointments_today_result->fetch_assoc()['count'] : 0;

// Calculate medication adherence (confirmed vs total medication notifications)
$total_med_notifs_result = $con->query("SELECT COUNT(*) as count FROM notification WHERE medication_id IS NOT NULL");
$total_med_notifs = $total_med_notifs_result ? $total_med_notifs_result->fetch_assoc()['count'] : 0;

$confirmed_med_notifs_result = $con->query("SELECT COUNT(*) as count FROM notification WHERE medication_id IS NOT NULL AND is_read = 1");
$confirmed_med_notifs = $confirmed_med_notifs_result ? $confirmed_med_notifs_result->fetch_assoc()['count'] : 0;

$medication_adherence = $total_med_notifs > 0 ? round(($confirmed_med_notifs / $total_med_notifs) * 100) : 0;

// Fetch notifications for the current nurse using the helper function
$notifications = fetch_notifications_for_nurse($con, $nurse_id, null, 50);
$unread_count = count(array_filter($notifications, function($n) { return $n['is_read'] == 0; }));

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - E-Charting System</title>
    <link rel="stylesheet" href="./Styles/analytics-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="./Javascript/javascript.js" defer></script>
</head>
<body>
    <?php include "adm-nav.php"; ?>
    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar Filters -->
        <aside class="sidebar">
            <div class="filter-section">
                <h3>Filters</h3>

                <div class="filter-group">
                    <label>Date Range</label>
                    <select class="filter-select">
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
                            <input type="checkbox" checked> All
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Active
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> In-Patient
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" checked> Out-Patient
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Department</label>
                    <select class="filter-select">
                        <option>All Departments</option>
                        <option>Emergency</option>
                        <option>ICU</option>
                        <option>General Ward</option>
                        <option>Pediatrics</option>
                    </select>
                </div>

                <button class="apply-filters-btn">Apply Filters</button>
            </div>

            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <button class="action-btn">
                    <i class="fas fa-file-pdf"></i> Generate PDF Report
                </button>
                <button class="action-btn">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <button class="action-btn">
                    <i class="fas fa-chart-line"></i> Custom Analytics
                </button>
            </div>
        </aside>

        <!-- Main Dashboard Content -->
        <main class="dashboard-content">
            <!-- Navigation Tabs for Dashboard Sections -->
            <div class="dashboard-nav-tabs">
                <button class="dashboard-tab active" data-section="overview">
                    <i class="fas fa-chart-pie"></i> Overview
                </button>
                <button class="dashboard-tab" data-section="performance">
                    <i class="fas fa-trophy"></i> Performance
                </button>
                <button class="dashboard-tab" data-section="reports">
                    <i class="fas fa-file-alt"></i> Reports
                </button>
            </div>

            <!-- Overview Section -->
            <section id="overview" class="dashboard-section">
                <div class="section-header">
                    <h2>Overview Dashboard</h2>
                    <p class="section-subtitle">Real-time system metrics and KPIs</p>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card" data-animate="fade-up">
                        <div class="kpi-icon patients">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="kpi-content">
                            <h3 class="kpi-value" data-count="<?php echo $total_patients; ?>">0</h3>
                            <p class="kpi-label">Total Patients</p>
                            <div class="kpi-trend positive">
                                <i class="fas fa-arrow-up"></i>
                                <span>System total</span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card" data-animate="fade-up" style="animation-delay: 0.1s">
                        <div class="kpi-icon active">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <div class="kpi-content">
                            <h3 class="kpi-value" data-count="<?php echo $active_patients; ?>">0</h3>
                            <p class="kpi-label">Active Patients</p>
                            <div class="kpi-trend positive">
                                <i class="fas fa-check-circle"></i>
                                <span>Currently active</span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card" data-animate="fade-up" style="animation-delay: 0.2s">
                        <div class="kpi-icon appointments">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="kpi-content">
                            <h3 class="kpi-value" data-count="<?php echo $appointments_today; ?>">0</h3>
                            <p class="kpi-label">Appointments Today</p>
                            <div class="kpi-trend neutral">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('F d, Y'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card" data-animate="fade-up" style="animation-delay: 0.3s">
                        <div class="kpi-icon adherence">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="kpi-content">
                            <h3 class="kpi-value" data-count="<?php echo $medication_adherence; ?>">0</h3>
                            <p class="kpi-label">Medication Adherence %</p>
                            <div class="kpi-trend <?php echo $medication_adherence >= 90 ? 'positive' : ($medication_adherence >= 75 ? 'neutral' : 'negative'); ?>">
                                <i class="fas fa-<?php echo $medication_adherence >= 90 ? 'arrow-up' : ($medication_adherence >= 75 ? 'minus' : 'arrow-down'); ?>"></i>
                                <span><?php echo $medication_adherence >= 90 ? 'Excellent' : ($medication_adherence >= 75 ? 'Good' : 'Needs attention'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="charts-row">
                    <div class="chart-card" data-animate="fade-up">
                        <div class="chart-header">
                            <h3>Patient Admissions Trend</h3>
                            <div class="chart-actions">
                                <button class="chart-action-btn">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="admissionsChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card" data-animate="fade-up" style="animation-delay: 0.1s">
                        <div class="chart-header">
                            <h3>Patient Status Distribution</h3>
                            <div class="chart-actions">
                                <button class="chart-action-btn">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="charts-row">
                    <div class="chart-card full-width" data-animate="fade-up">
                        <div class="chart-header">
                            <h3>Medication Adherence Over Time</h3>
                            <div class="chart-legend">
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #4CAF50;"></span>
                                    On Time
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #FFC107;"></span>
                                    Delayed
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color" style="background: #f44336;"></span>
                                    Missed
                                </span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="medicationChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Performance Section -->
            <section id="performance" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <h2>Nursing Performance Metrics</h2>
                    <p class="section-subtitle">Individual and team performance analytics</p>
                </div>

                <!-- Nurse Performance Cards -->
                <div class="performance-grid">
                    <div class="performance-card" data-animate="fade-up">
                        <div class="performance-header">
                            <div class="nurse-avatar">
                                <i class="fas fa-user-nurse"></i>
                            </div>
                            <div class="nurse-info">
                                <h4>Nurse Maria Santos</h4>
                                <p>Senior Nurse - Ward A</p>
                            </div>
                        </div>
                        <div class="performance-stats">
                            <div class="stat-item">
                                <span class="stat-label">Patients Cared</span>
                                <span class="stat-value">45</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Appointments</span>
                                <span class="stat-value">32</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg Response Time</span>
                                <span class="stat-value">8 min</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Performance Score</span>
                                <span class="stat-value highlight">96%</span>
                            </div>
                        </div>
                        <div class="performance-badge excellent">
                            <i class="fas fa-trophy"></i> Top Performer
                        </div>
                    </div>

                    <div class="performance-card" data-animate="fade-up" style="animation-delay: 0.1s">
                        <div class="performance-header">
                            <div class="nurse-avatar">
                                <i class="fas fa-user-nurse"></i>
                            </div>
                            <div class="nurse-info">
                                <h4>Nurse John Reyes</h4>
                                <p>Staff Nurse - Ward B</p>
                            </div>
                        </div>
                        <div class="performance-stats">
                            <div class="stat-item">
                                <span class="stat-label">Patients Cared</span>
                                <span class="stat-value">38</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Appointments</span>
                                <span class="stat-value">28</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg Response Time</span>
                                <span class="stat-value">10 min</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Performance Score</span>
                                <span class="stat-value highlight">92%</span>
                            </div>
                        </div>
                        <div class="performance-badge good">
                            <i class="fas fa-star"></i> Excellent
                        </div>
                    </div>

                    <div class="performance-card" data-animate="fade-up" style="animation-delay: 0.2s">
                        <div class="performance-header">
                            <div class="nurse-avatar">
                                <i class="fas fa-user-nurse"></i>
                            </div>
                            <div class="nurse-info">
                                <h4>Nurse Ana Cruz</h4>
                                <p>Staff Nurse - ICU</p>
                            </div>
                        </div>
                        <div class="performance-stats">
                            <div class="stat-item">
                                <span class="stat-label">Patients Cared</span>
                                <span class="stat-value">29</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Appointments</span>
                                <span class="stat-value">24</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg Response Time</span>
                                <span class="stat-value">6 min</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Performance Score</span>
                                <span class="stat-value highlight">98%</span>
                            </div>
                        </div>
                        <div class="performance-badge excellent">
                            <i class="fas fa-trophy"></i> Top Performer
                        </div>
                    </div>
                </div>

                <!-- Team Performance Chart -->
                <div class="chart-card full-width" data-animate="fade-up">
                    <div class="chart-header">
                        <h3>Team Performance Comparison</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="teamPerformanceChart"></canvas>
                    </div>
                </div>

                <!-- Activity Metrics -->
                <div class="charts-row">
                    <div class="chart-card" data-animate="fade-up">
                        <div class="chart-header">
                            <h3>Daily Activity Log</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card" data-animate="fade-up" style="animation-delay: 0.1s">
                        <div class="chart-header">
                            <h3>Response Time Trends</h3>
                        </div>
                        <div class="chart-container">
                            <canvas id="responseTimeChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reports Section -->
            <section id="reports" class="dashboard-section" style="display: none;">
                <div class="section-header">
                    <h2>Reports & Documentation</h2>
                    <p class="section-subtitle">Generate comprehensive reports and analytics</p>
                </div>

                <!-- Report Templates -->
                <div class="report-templates">
                    <div class="report-card" data-animate="fade-up">
                        <div class="report-icon">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <h3>Patient Summary Report</h3>
                        <p>Comprehensive patient data including admissions, treatments, and outcomes</p>
                        <button class="generate-report-btn">Generate Report</button>
                    </div>

                    <div class="report-card" data-animate="fade-up" style="animation-delay: 0.1s">
                        <div class="report-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3>Performance Analytics</h3>
                        <p>Detailed nursing staff performance metrics and KPIs</p>
                        <button class="generate-report-btn">Generate Report</button>
                    </div>

                    <div class="report-card" data-animate="fade-up" style="animation-delay: 0.2s">
                        <div class="report-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <h3>Medication Compliance</h3>
                        <p>Track medication adherence rates and patient compliance</p>
                        <button class="generate-report-btn">Generate Report</button>
                    </div>

                    <div class="report-card" data-animate="fade-up" style="animation-delay: 0.3s">
                        <div class="report-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3>Monthly Summary</h3>
                        <p>Complete monthly overview of all system activities</p>
                        <button class="generate-report-btn">Generate Report</button>
                    </div>

                    <div class="report-card" data-animate="fade-up" style="animation-delay: 0.4s">
                        <div class="report-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Critical Incidents</h3>
                        <p>Report on critical events and emergency responses</p>
                        <button class="generate-report-btn">Generate Report</button>
                    </div>

                    <div class="report-card" data-animate="fade-up" style="animation-delay: 0.5s">
                        <div class="report-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h3>Custom Report Builder</h3>
                        <p>Create custom reports with selected metrics and date ranges</p>
                        <button class="generate-report-btn primary">Build Custom Report</button>
                    </div>
                </div>

                <!-- Recent Reports Table -->
                <div class="recent-reports" data-animate="fade-up">
                    <h3>Recent Reports</h3>
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Report Name</th>
                                <th>Type</th>
                                <th>Generated By</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Monthly Patient Summary - November 2024</td>
                                <td><span class="report-type patient">Patient</span></td>
                                <td>Admin User</td>
                                <td>Dec 1, 2024</td>
                                <td><span class="status-badge completed">Completed</span></td>
                                <td>
                                    <button class="action-icon-btn" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="action-icon-btn" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-icon-btn" title="Share">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Nurse Performance Q4 2024</td>
                                <td><span class="report-type performance">Performance</span></td>
                                <td>Nurse Manager</td>
                                <td>Nov 28, 2024</td>
                                <td><span class="status-badge completed">Completed</span></td>
                                <td>
                                    <button class="action-icon-btn" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="action-icon-btn" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-icon-btn" title="Share">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>Medication Adherence Report</td>
                                <td><span class="report-type medication">Medication</span></td>
                                <td>System Generated</td>
                                <td>Nov 25, 2024</td>
                                <td><span class="status-badge processing">Processing</span></td>
                                <td>
                                    <button class="action-icon-btn disabled" title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="action-icon-btn" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-icon-btn disabled" title="Share">
                                        <i class="fas fa-share"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Analytics Dashboard Script - Load AFTER notification system from adm-nav.php -->
    <script src="./Javascript/analytics-dashboard.js"></script>
</body>
</html>