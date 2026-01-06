<?php
include 'authcheck.php';

include 'connection.php';
include_once 'includes/notification.php';
include_once 'includes/activity-logger.php';
$nurse_id = isset($_SESSION['nurse_id']) ? $_SESSION['nurse_id'] : null;

// Get filter parameters
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';
$instructed_filter = isset($_GET['instructed']) ? $_GET['instructed'] : '';
$status_filter = isset($_GET['statuses']) ? explode(',', $_GET['statuses']) : [];

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

// ============================================
// CHART DATA: Patient Admissions Trend (Last 12 Months)
// ============================================
$admission_trend_sql = "
    SELECT
        DATE_FORMAT(admission_date, '%Y-%m') as month,
        DATE_FORMAT(admission_date, '%b %Y') as month_label,
        COUNT(*) as admission_count
    FROM admission_data
    WHERE admission_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(admission_date, '%Y-%m'), DATE_FORMAT(admission_date, '%b %Y')
    ORDER BY month ASC
";

$admission_trend_result = $con->query($admission_trend_sql);
$admission_months = [];
$admission_counts = [];

if ($admission_trend_result && $admission_trend_result->num_rows > 0) {
    while ($row = $admission_trend_result->fetch_assoc()) {
        $admission_months[] = $row['month_label'];
        $admission_counts[] = (int)$row['admission_count'];
    }
} else {
    // If no data, show last 6 months with zeros
    for ($i = 5; $i >= 0; $i--) {
        $date = date('M Y', strtotime("-$i months"));
        $admission_months[] = $date;
        $admission_counts[] = 0;
    }
}

// ============================================
// CHART DATA: Patient Status Distribution
// ============================================
$status_distribution_sql = "
    SELECT
        patient_status,
        COUNT(*) as count
    FROM patients
    GROUP BY patient_status
    ORDER BY
        CASE patient_status
            WHEN 'active' THEN 1
            WHEN 'in-patient' THEN 2
            WHEN 'out-patient' THEN 3
            WHEN 'deceased' THEN 4
            ELSE 5
        END
";

$status_distribution_result = $con->query($status_distribution_sql);
$status_labels = [];
$status_counts = [];
$status_colors = [];

// Define colors for each status
$color_map = [
    'active' => '#28a745',
    'in-patient' => '#17a2b8',
    'out-patient' => '#ffc107',
    'deceased' => '#dc3545'
];

if ($status_distribution_result && $status_distribution_result->num_rows > 0) {
    while ($row = $status_distribution_result->fetch_assoc()) {
        $status = ucwords(str_replace('-', ' ', $row['patient_status']));
        $status_labels[] = $status;
        $status_counts[] = (int)$row['count'];
        $status_colors[] = $color_map[$row['patient_status']] ?? '#6c757d';
    }
} else {
    $status_labels = ['Active', 'In-Patient', 'Out-Patient', 'Deceased'];
    $status_counts = [0, 0, 0, 0];
    $status_colors = ['#28a745', '#17a2b8', '#ffc107', '#dc3545'];
}

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

                    <button class="apply-filters-btn" onclick="applyFiltersAndRedirect()">Apply Filters</button>
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
            <!-- Navigation Tabs for Dashboard Sections -->
            <div class="dashboard-nav-tabs">
                <button class="dashboard-tab active" data-section="overview">
                    <i class="fas fa-chart-pie"></i> Overview
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
                </div>

                <!-- Charts Row 1 -->
                <div class="charts-row">
                    <div class="chart-card" data-animate="fade-up">
                        <div class="chart-header">
                            <h3>Patient Admissions Trend</h3>
                            <div class="chart-actions">
                                <div class="dropdown">
                                    <button class="chart-action-btn dropdown-toggle" data-chart="admissions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" id="admissionsMenu">
                                        <button class="dropdown-item" data-period="6">Last 6 Months</button>
                                        <button class="dropdown-item active" data-period="12">Last 12 Months</button>
                                        <button class="dropdown-item" data-period="24">Last 2 Years</button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item" data-action="export">
                                            <i class="fas fa-download"></i> Export Data
                                        </button>
                                    </div>
                                </div>
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
                                <div class="dropdown">
                                    <button class="chart-action-btn dropdown-toggle" data-chart="status">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu" id="statusMenu">
                                        <div class="filter-options">
                                            <label class="dropdown-item">
                                                <input type="checkbox" checked data-status="active"> Active
                                            </label>
                                            <label class="dropdown-item">
                                                <input type="checkbox" checked data-status="in-patient"> In-Patient
                                            </label>
                                            <label class="dropdown-item">
                                                <input type="checkbox" checked data-status="out-patient"> Out-Patient
                                            </label>
                                            <label class="dropdown-item">
                                                <input type="checkbox" checked data-status="deceased"> Deceased
                                            </label>
                                        </div>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item" data-action="export">
                                            <i class="fas fa-download"></i> Export Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
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

    <!-- Pass PHP data to JavaScript -->
    <script>
        // Admission Trend Data
        const admissionMonths = <?php echo json_encode($admission_months); ?>;
        const admissionCounts = <?php echo json_encode($admission_counts); ?>;

        // Status Distribution Data
        const statusLabels = <?php echo json_encode($status_labels); ?>;
        const statusCounts = <?php echo json_encode($status_counts); ?>;
        const statusColors = <?php echo json_encode($status_colors); ?>;
    </script>

    <!-- Analytics Dashboard Script - Load AFTER notification system from adm-nav.php -->
    <script src="./Javascript/analytics-dashboard.js"></script>

    <!-- Filter Redirect Function -->
    <script>
        function applyFiltersAndRedirect() {
            // Get filter values
            const dateRange = document.getElementById('dateRangeFilter').value;
            const instructed = document.getElementById('instructedFilter').value;

            // Get status filters
            const statusCheckboxes = document.querySelectorAll('.checkbox-label input[type="checkbox"]:checked');
            const statuses = [];

            statusCheckboxes.forEach(checkbox => {
                const value = checkbox.getAttribute('value');
                if (value && value !== 'all') {
                    statuses.push(value);
                }
            });

            // Build URL
            let url = 'adm-patient-list.php?date_range=' + encodeURIComponent(dateRange);

            if (instructed) {
                url += '&instructed=' + encodeURIComponent(instructed);
            }

            if (statuses.length > 0) {
                url += '&statuses=' + encodeURIComponent(statuses.join(','));
            }

            // Redirect to patient list with filters
            window.location.href = url;
        }
    </script>
</body>
</html>
