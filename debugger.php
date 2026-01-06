<?php
/**
 * Session Diagnostic Page
 * Tests all aspects of session handling
 */

echo "<!DOCTYPE html><html><head><title>Session Diagnostic</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#0f0;}";
echo "h2{color:#0ff;border-bottom:2px solid #0ff;padding-bottom:5px;}";
echo ".pass{color:#0f0;font-weight:bold;} .fail{color:#f00;font-weight:bold;}";
echo ".section{background:#2a2a2a;padding:15px;margin:10px 0;border-left:4px solid #0ff;}";
echo "pre{background:#000;padding:10px;border:1px solid #0f0;overflow-x:auto;}</style></head><body>";

echo "<h1>🔍 SESSION DIAGNOSTIC TOOL</h1>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// TEST 1: PHP Session Configuration
echo "<div class='section'>";
echo "<h2>TEST 1: PHP Session Configuration</h2>";
echo "<pre>";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "</pre>";
echo "</div>";

// TEST 2: Check Available Cookies
echo "<div class='section'>";
echo "<h2>TEST 2: Browser Cookies</h2>";
if (empty($_COOKIE)) {
    echo "<p class='fail'>❌ NO COOKIES FOUND!</p>";
} else {
    echo "<p class='pass'>✓ Found " . count($_COOKIE) . " cookie(s)</p>";
    echo "<pre>";
    foreach ($_COOKIE as $name => $value) {
        echo "$name => " . substr($value, 0, 20) . "...\n";
    }
    echo "</pre>";
}
echo "</div>";

// TEST 3: Try Patient Session
echo "<div class='section'>";
echo "<h2>TEST 3: Patient Session</h2>";
try {
    if (session_status() !== PHP_SESSION_NONE) {
        echo "<p>⚠️ Session already active: " . session_name() . "</p>";
        session_write_close();
    }

    session_name('patient_session');
    session_start();

    echo "<p class='pass'>✓ Patient session started</p>";
    echo "<pre>";
    echo "Session Name: " . session_name() . "\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Status: " . session_status() . " (1=disabled, 2=active)\n";
    echo "\nSession Data:\n";
    print_r($_SESSION);
    echo "</pre>";

    // Check if patient_id exists
    if (isset($_SESSION['patient_id'])) {
        echo "<p class='pass'>✓ patient_id found: " . $_SESSION['patient_id'] . "</p>";
    } else {
        echo "<p class='fail'>❌ patient_id NOT found in session</p>";
    }

    session_write_close();
} catch (Exception $e) {
    echo "<p class='fail'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// TEST 4: Try Nurse Session
echo "<div class='section'>";
echo "<h2>TEST 4: Nurse Session</h2>";
try {
    session_name('nurse_session');
    session_start();

    echo "<p class='pass'>✓ Nurse session started</p>";
    echo "<pre>";
    echo "Session Name: " . session_name() . "\n";
    echo "Session ID: " . session_id() . "\n";
    echo "\nSession Data:\n";
    print_r($_SESSION);
    echo "</pre>";

    if (isset($_SESSION['nurse_id'])) {
        echo "<p class='pass'>✓ nurse_id found: " . $_SESSION['nurse_id'] . "</p>";
    } else {
        echo "<p class='fail'>❌ nurse_id NOT found in session</p>";
    }

    session_write_close();
} catch (Exception $e) {
    echo "<p class='fail'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// TEST 5: Session File Check
echo "<div class='section'>";
echo "<h2>TEST 5: Session Files on Server</h2>";
$save_path = session_save_path() ?: sys_get_temp_dir();
echo "<p>Session save path: <code>$save_path</code></p>";

if (is_dir($save_path) && is_readable($save_path)) {
    $files = glob($save_path . '/sess_*');
    if ($files) {
        echo "<p class='pass'>✓ Found " . count($files) . " session file(s)</p>";
        echo "<pre>";
        foreach (array_slice($files, 0, 5) as $file) {
            $mtime = date('Y-m-d H:i:s', filemtime($file));
            $size = filesize($file);
            echo basename($file) . " | Modified: $mtime | Size: $size bytes\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='fail'>❌ No session files found!</p>";
    }
} else {
    echo "<p class='fail'>❌ Session directory not accessible</p>";
}
echo "</div>";

// TEST 6: Simulate Login
echo "<div class='section'>";
echo "<h2>TEST 6: Simulate Patient Login</h2>";

// Start fresh session
if (session_status() !== PHP_SESSION_NONE) {
    session_write_close();
}

session_name('patient_session');
session_start();

// Clear and set test data
$_SESSION = array();
$_SESSION['patient_id'] = 999;
$_SESSION['test_time'] = time();
$_SESSION['is_patient'] = true;

echo "<p class='pass'>✓ Set test patient_id = 999</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Try to read it back
$test_patient_id = $_SESSION['patient_id'] ?? null;
if ($test_patient_id === 999) {
    echo "<p class='pass'>✓ Session write/read successful!</p>";
} else {
    echo "<p class='fail'>❌ Session write/read failed!</p>";
}

// Clean up test data
$_SESSION = array();
session_write_close();
echo "</div>";

// TEST 7: Cookie Collision Check
echo "<div class='section'>";
echo "<h2>TEST 7: Cookie Collision Detection</h2>";
$session_cookies = array_filter($_COOKIE, function($key) {
    return strpos($key, 'session') !== false || $key === 'PHPSESSID';
}, ARRAY_FILTER_USE_KEY);

if (empty($session_cookies)) {
    echo "<p class='pass'>✓ No session cookies (expected before login)</p>";
} else {
    $unique_ids = array_unique(array_values($session_cookies));
    if (count($unique_ids) === 1 && count($session_cookies) > 1) {
        echo "<p class='fail'>❌ COOKIE COLLISION DETECTED!</p>";
        echo "<p>All session cookies have the same ID:</p>";
        echo "<pre>";
        foreach ($session_cookies as $name => $id) {
            echo "$name => $id\n";
        }
        echo "</pre>";
        echo "<p><strong>SOLUTION: Clear all cookies and start fresh!</strong></p>";
    } else {
        echo "<p class='pass'>✓ Session cookies look normal</p>";
        echo "<pre>";
        foreach ($session_cookies as $name => $id) {
            echo "$name => $id\n";
        }
        echo "</pre>";
    }
}
echo "</div>";

// RECOMMENDATIONS
echo "<div class='section'>";
echo "<h2>📋 RECOMMENDATIONS</h2>";

$issues = [];
$cookie_count = count($_COOKIE);
$has_collision = false;

if (!empty($session_cookies)) {
    $unique_ids = array_unique(array_values($session_cookies));
    if (count($unique_ids) === 1 && count($session_cookies) > 1) {
        $has_collision = true;
    }
}

if ($has_collision) {
    $issues[] = "Cookie collision detected - all sessions share the same ID";
}

if ($cookie_count > 5) {
    $issues[] = "Too many cookies ($cookie_count) - possible leftover sessions";
}

if (empty($issues)) {
    echo "<p class='pass'>✓ No major issues detected!</p>";
    echo "<p>If login still fails:</p>";
    echo "<ol>";
    echo "<li>Try the clear_all_sessions.php script</li>";
    echo "<li>Close browser completely and reopen in incognito</li>";
    echo "<li>Check your login.php file has correct session_name() call</li>";
    echo "</ol>";
} else {
    echo "<p class='fail'>⚠️ Issues Found:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";

    echo "<h3>🔧 FIX STEPS:</h3>";
    echo "<ol>";
    echo "<li><strong>Clear All Sessions:</strong> Visit <a href='clear_all_sessions.php' style='color:#0ff;'>clear_all_sessions.php</a></li>";
    echo "<li><strong>Clear Browser Cookies:</strong> Settings → Privacy → Clear browsing data</li>";
    echo "<li><strong>Close ALL browser windows</strong></li>";
    echo "<li><strong>Open NEW incognito window</strong></li>";
    echo "<li><strong>Try login again</strong></li>";
    echo "</ol>";
}

echo "</div>";

echo "<hr>";
echo "<p><a href='login.php' style='color:#0ff;'>→ Go to Login</a> | ";
echo "<a href='clear_all_sessions.php' style='color:#f00;'>→ Clear All Sessions</a> | ";
echo "<a href='debugger.php' style='color:#0ff;'>→ Simple Debug</a></p>";
echo "</body></html>";
?>

<?php
/**
 * TEST ACTIVITY LOGGING
 * Run this file to test if activity logging is working
 * Visit: http://localhost/E-Charting/test-activity-log.php
 */

include 'connection.php';
include_once './includes/activity-logger.php';

echo "<h1>Activity Logging Test</h1>";

// Test 1: Check if table exists
echo "<h2>Test 1: Check if activity_log table exists</h2>";
$result = $con->query("SHOW TABLES LIKE 'activity_log'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Table exists!</p>";
} else {
    echo "<p style='color: red;'>❌ Table does NOT exist! Run CREATE_ACTIVITY_LOG_TABLE.sql first!</p>";
    exit;
}

// Test 2: Try to log an activity
echo "<h2>Test 2: Try to log a test activity</h2>";

// Use nurse_id = 1 (change to a valid nurse_id from your database)
$test_nurse_id = 1;
$result = log_activity(
    $con,
    $test_nurse_id,
    'test',
    'Testing activity logging system',
    null,
    'test',
    1
);

if ($result) {
    echo "<p style='color: green;'>✅ Activity logged successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to log activity. Check error_log.</p>";
}

// Test 3: Retrieve recent logs
echo "<h2>Test 3: Retrieve recent activity logs</h2>";
$logs = get_activity_logs($con, null, null, null, 10);

if (count($logs) > 0) {
    echo "<p style='color: green;'>✅ Found " . count($logs) . " activity logs!</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<th>ID</th><th>Action</th><th>Description</th><th>Nurse</th><th>Patient</th><th>Date</th>";
    echo "</tr>";

    foreach ($logs as $log) {
        echo "<tr>";
        echo "<td>" . $log['log_id'] . "</td>";
        echo "<td>" . format_action_type($log['action_type']) . "</td>";
        echo "<td>" . htmlspecialchars($log['action_description']) . "</td>";
        echo "<td>" . htmlspecialchars($log['nurse_name']) . "</td>";
        echo "<td>" . ($log['patient_name'] ? htmlspecialchars($log['patient_name']) : 'N/A') . "</td>";
        echo "<td>" . $log['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No activity logs found yet. This is normal if you just set it up.</p>";
}

// Test 4: Check database connection
echo "<h2>Test 4: Database Connection</h2>";
if ($con && $con->ping()) {
    echo "<p style='color: green;'>✅ Database connected!</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
}

// Test 5: Check if nurses exist
echo "<h2>Test 5: Check if nurses exist</h2>";
$nurse_result = $con->query("SELECT COUNT(*) as count FROM nurse");
if ($nurse_result) {
    $nurse_count = $nurse_result->fetch_assoc()['count'];
    if ($nurse_count > 0) {
        echo "<p style='color: green;'>✅ Found $nurse_count nurse(s) in database!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No nurses found. Create a nurse account first!</p>";
    }
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If all tests pass, the system is working!</li>";
echo "<li>Now add log_activity() calls to your files (see integration guide)</li>";
echo "<li>Visit analytics-dashboard.php to see activity logs</li>";
echo "</ol>";

$con->close();
?>
