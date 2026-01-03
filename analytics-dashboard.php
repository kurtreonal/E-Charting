<?php
include 'authcheck.php';

include 'connection.php';
include_once 'includes/notification.php';
include_once 'includes/activity-logger.php';
$nurse_id = isset($_SESSION['nurse_id']) ? $_SESSION['nurse_id'] : null;


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

// Fetch activity logs for Reports section
$activity_logs = get_activity_logs($con, null, null, null, 30);

// Fetch recent admissions for Reports section
$history_sql = "
    SELECT
        u.first_name, u.middle_name, u.last_name,
        a.admission_date, a.admission_time, a.mode_of_arrival, a.instructed,
        a.admission_data_id,
        p.patient_status
    FROM admission_data a
    JOIN patients p ON a.patient_id = p.patient_id
    JOIN users u ON p.user_id = u.user_id
    ORDER BY a.admission_date DESC, a.admission_time DESC
    LIMIT 10
";

$history_result = $con->query($history_sql);
$history_records = [];

if ($history_result && $history_result->num_rows > 0) {
    while($row = $history_result->fetch_assoc()) {
        $history_records[] = $row;
    }
}

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
    <div class="wrapper">
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
                    <label>Patient Instructions</label>
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
            <div class="container">
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

                <!-- Activity Log - All Nurse Actions -->
                <div class="recent-reports" data-animate="fade-up" style="margin-bottom: 2rem;">
                    <h3><i class="fas fa-clipboard-list"></i> Activity Log</h3>
                    <p class="section-description">All nurse actions and system activities</p>
                    <table class="reports-table activity-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Nurse</th>
                                <th>Patient</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activity_logs) > 0): ?>
                                <?php foreach($activity_logs as $log): ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <i class="fas <?php echo get_action_icon($log['action_type']); ?>"
                                           style="color: <?php echo get_action_color($log['action_type']); ?>; font-size: 18px;"
                                           title="<?php echo format_action_type($log['action_type']); ?>"></i>
                                    </td>
                                    <td>
                                        <strong style="color: <?php echo get_action_color($log['action_type']); ?>;">
                                            <?php echo format_action_type($log['action_type']); ?>
                                        </strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                                    <td><span class="nurse-name"><?php echo htmlspecialchars($log['nurse_name']); ?></span></td>
                                    <td>
                                        <?php if ($log['patient_name']): ?>
                                            <span class="patient-name"><?php echo htmlspecialchars($log['patient_name']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #999;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $timestamp = strtotime($log['created_at']);
                                        $time_ago = time() - $timestamp;

                                        if ($time_ago < 60) {
                                            echo "Just now";
                                        } elseif ($time_ago < 3600) {
                                            echo floor($time_ago / 60) . " min ago";
                                        } elseif ($time_ago < 86400) {
                                            echo floor($time_ago / 3600) . " hours ago";
                                        } else {
                                            echo date('M j, Y g:i A', $timestamp);
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px; color: #999;">
                                        <i class="fas fa-info-circle"></i> No activity recorded yet. Actions will appear here as nurses work in the system.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Recent Patient Admissions & Readmissions -->
                <div class="recent-reports" data-animate="fade-up">
                    <h3><i class="fas fa-hospital-user"></i> Recent Patient Admissions & Readmissions</h3>
                    <p class="section-description">Latest patient admissions to the hospital</p>
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Admission Date</th>
                                <th>Time</th>
                                <th>Mode of Arrival</th>
                                <th>Instructed</th>
                                <th>Current Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($history_records) > 0): ?>
                                <?php foreach($history_records as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . ($record['middle_name'] ? $record['middle_name'] . ' ' : '') . $record['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($record['admission_date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($record['admission_time'])); ?></td>
                                    <td><span class="arrival-mode"><?php echo ucfirst($record['mode_of_arrival']); ?></span></td>
                                    <td><span class="instruction-type"><?php echo ucwords(str_replace('-', ' ', $record['instructed'])); ?></span></td>
                                    <td><span class="status-badge status-<?php echo strtolower(str_replace('-', '', $record['patient_status'] ?? 'active')); ?>"><?php echo htmlspecialchars($record['patient_status'] ?? 'N/A'); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px;">No admission history found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            </div>
        </main>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Analytics Dashboard Script - Load AFTER notification system from adm-nav.php -->
    <script src="./Javascript/analytics-dashboard.js"></script>
</body>
</html>