<?php
/**
 * Environment Configuration Verification Script
 *
 * Use this to verify that .env file is properly configured
 * URL: http://localhost/E-Charting/verify-config.php
 *
 * DELETE THIS FILE after verification in production!
 */

// Prevent access in production
if (php_uname('s') !== 'Windows' && php_uname('s') !== 'Linux' && php_uname('s') !== 'Darwin') {
    die('Verification script access denied.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Charting Configuration Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .header {
            background: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 30px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 30px;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h2 {
            color: #2d3748;
            margin-top: 0;
            font-size: 20px;
            display: flex;
            align-items: center;
        }
        .status-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .check-item {
            display: flex;
            align-items: center;
            margin: 12px 0;
            padding: 10px;
            background: #f7fafc;
            border-left: 4px solid #cbd5e0;
            border-radius: 4px;
        }
        .check-item.pass {
            background: #f0fff4;
            border-left-color: #48bb78;
        }
        .check-item.fail {
            background: #fff5f5;
            border-left-color: #f56565;
        }
        .check-item.warning {
            background: #fffaf0;
            border-left-color: #ed8936;
        }
        .check-label {
            font-weight: 600;
            color: #2d3748;
            flex: 1;
        }
        .check-value {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #718096;
            max-width: 400px;
            overflow: auto;
            word-break: break-all;
        }
        .check-icon {
            font-size: 20px;
            margin-right: 10px;
            min-width: 25px;
        }
        .pass .check-icon { color: #48bb78; }
        .fail .check-icon { color: #f56565; }
        .warning .check-icon { color: #ed8936; }

        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #edf2f7;
            border-radius: 4px;
            border-left: 4px solid #667eea;
        }
        .summary h3 {
            margin-top: 0;
            color: #2d3748;
        }
        .summary-item {
            margin: 5px 0;
            font-size: 14px;
        }
        .code-block {
            background: #2d3748;
            color: #48bb78;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .warning-box {
            background: #fff5f5;
            border: 2px solid #fc8181;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            color: #c53030;
        }
        .warning-box strong {
            display: block;
            margin-bottom: 5px;
        }
        .footer {
            background: #f7fafc;
            padding: 20px 30px;
            text-align: center;
            color: #718096;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
        }
        .footer strong {
            color: #f56565;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 E-Charting Configuration Verification</h1>
            <p>Verify that your .env file is properly configured</p>
        </div>

        <div class="content">
            <?php
            require_once dirname(__FILE__) . '/includes/config.php';

            $checks = [
                'pass' => 0,
                'fail' => 0,
                'warning' => 0
            ];

            $all_checks = [];

            // Check 1: .env file exists
            $env_file = dirname(__FILE__) . '/.env';
            if (file_exists($env_file)) {
                $all_checks[] = [
                    'status' => 'pass',
                    'label' => '.env File Exists',
                    'value' => $env_file
                ];
                $checks['pass']++;
            } else {
                $all_checks[] = [
                    'status' => 'fail',
                    'label' => '.env File Exists',
                    'value' => 'File not found at: ' . $env_file
                ];
                $checks['fail']++;
            }

            // Check 2: .env file is readable
            if (file_exists($env_file) && is_readable($env_file)) {
                $all_checks[] = [
                    'status' => 'pass',
                    'label' => '.env File is Readable',
                    'value' => 'Permissions OK'
                ];
                $checks['pass']++;
            } else {
                $all_checks[] = [
                    'status' => 'fail',
                    'label' => '.env File is Readable',
                    'value' => 'Cannot read file - check permissions'
                ];
                $checks['fail']++;
            }

            // Check 3: Database Host
            $db_host = Config::get('DB_HOST');
            if ($db_host) {
                $all_checks[] = [
                    'status' => 'pass',
                    'label' => 'Database Host Configured',
                    'value' => $db_host
                ];
                $checks['pass']++;
            } else {
                $all_checks[] = [
                    'status' => 'fail',
                    'label' => 'Database Host Configured',
                    'value' => 'Not set - check .env'
                ];
                $checks['fail']++;
            }

            // Check 4: Database User
            $db_user = Config::get('DB_USER');
            if ($db_user) {
                $all_checks[] = [
                    'status' => 'pass',
                    'label' => 'Database User Configured',
                    'value' => $db_user
                ];
                $checks['pass']++;
            } else {
                $all_checks[] = [
                    'status' => 'warning',
                    'label' => 'Database User Configured',
                    'value' => 'Not set - may default to root'
                ];
                $checks['warning']++;
            }

            // Check 5: Database Password
            $db_pass = Config::get('DB_PASS');
            if ($db_pass !== '' && $db_pass !== null) {
                $all_checks[] = [
                    'status' => 'pass',
                    'label' => 'Database Password Set',
                    'value' => '(hidden for security)'
                ];
                $checks['pass']++;
            } else {
                $all_checks[] = [
                    'status' => 'warning',
                    'label' => 'Database Password Set',
                    'value' => 'Empty password detected (OK for localhost only)'
                ];
                $checks['warning']++;
            }

            // Check 6: Database Name
            $db_name = Config::get('DB_NAME');
            if ($db_name) {
                $all_checks[] = [
                    'status' => 'pass',
                    'label' => 'Database Name Configured',
                    'value' => $db_name
                ];
                $checks['pass']++;
            } else {
                $all_checks[] = [
                    'status' => 'fail',
                    'label' => 'Database Name Configured',
                    'value' => 'Not set - check .env'
                ];
                $checks['fail']++;
            }

            // Check 7: Database Connection
            ?>
            <div class="section">
                <h2><span class="status-icon">📋</span> Configuration Files</h2>
                <?php
                foreach ($all_checks as $check) {
                    $icon = $check['status'] === 'pass' ? '✓' : ($check['status'] === 'fail' ? '✕' : '⚠');
                    echo '<div class="check-item ' . $check['status'] . '">';
                    echo '<span class="check-icon">' . $icon . '</span>';
                    echo '<span class="check-label">' . htmlspecialchars($check['label']) . '</span>';
                    echo '<span class="check-value">' . htmlspecialchars($check['value']) . '</span>';
                    echo '</div>';
                }
                ?>
            </div>

            <?php
            // Check Database Connection
            $db_conn_check = 'fail';
            $db_conn_message = 'Not attempted';

            try {
                $test_host = Config::get('DB_HOST', 'localhost');
                $test_user = Config::get('DB_USER', 'root');
                $test_pass = Config::get('DB_PASS', '');
                $test_name = Config::get('DB_NAME', 'e_charting');

                $test_con = new mysqli($test_host, $test_user, $test_pass, $test_name);

                if ($test_con->connect_error) {
                    $db_conn_check = 'fail';
                    $db_conn_message = 'Connection Failed: ' . $test_con->connect_error;
                } else {
                    $db_conn_check = 'pass';
                    $db_conn_message = 'Successfully connected';
                    $test_con->close();
                }
            } catch (Exception $e) {
                $db_conn_check = 'fail';
                $db_conn_message = 'Error: ' . $e->getMessage();
            }

            if ($db_conn_check === 'pass') {
                $checks['pass']++;
            } else {
                $checks['fail']++;
            }
            ?>

            <div class="section">
                <h2><span class="status-icon">🗄️</span> Database Connection</h2>
                <?php
                $icon = $db_conn_check === 'pass' ? '✓' : '✕';
                echo '<div class="check-item ' . $db_conn_check . '">';
                echo '<span class="check-icon">' . $icon . '</span>';
                echo '<span class="check-label">Database Connection Test</span>';
                echo '<span class="check-value">' . htmlspecialchars($db_conn_message) . '</span>';
                echo '</div>';
                ?>
            </div>

            <?php
            // Check Email Configuration
            $mail_user = Config::get('MAIL_USERNAME');
            $mail_pass = Config::get('MAIL_PASSWORD');
            $mail_host = Config::get('MAIL_HOST');

            $email_checks = [];

            if ($mail_user) {
                $email_checks[] = ['pass', 'Email Username', $mail_user];
            } else {
                $email_checks[] = ['fail', 'Email Username', 'Not configured'];
            }

            if ($mail_pass) {
                $email_checks[] = ['pass', 'Email Password', '(hidden for security)'];
            } else {
                $email_checks[] = ['fail', 'Email Password', 'Not configured'];
            }

            if ($mail_host) {
                $email_checks[] = ['pass', 'Email Host', $mail_host];
            } else {
                $email_checks[] = ['warning', 'Email Host', 'Not configured'];
            }
            ?>

            <div class="section">
                <h2><span class="status-icon">📧</span> Email Configuration</h2>
                <?php
                foreach ($email_checks as $check) {
                    $icon = $check[0] === 'pass' ? '✓' : ($check[0] === 'fail' ? '✕' : '⚠');
                    $status_class = $check[0];
                    echo '<div class="check-item ' . $status_class . '">';
                    echo '<span class="check-icon">' . $icon . '</span>';
                    echo '<span class="check-label">' . htmlspecialchars($check[1]) . '</span>';
                    echo '<span class="check-value">' . htmlspecialchars($check[2]) . '</span>';
                    echo '</div>';
                    if ($check[0] === 'pass') $checks['pass']++;
                    else if ($check[0] === 'fail') $checks['fail']++;
                    else $checks['warning']++;
                }
                ?>
            </div>

            <?php
            // Check Hospital Information
            $hospital_checks = [];

            $hospital_name = Config::get('HOSPITAL_NAME');
            $hospital_email = Config::get('HOSPITAL_EMAIL');
            $hospital_phone = Config::get('HOSPITAL_PHONE');

            if ($hospital_name) {
                $hospital_checks[] = ['pass', 'Hospital Name', $hospital_name];
            } else {
                $hospital_checks[] = ['warning', 'Hospital Name', 'Not configured'];
            }

            if ($hospital_email) {
                $hospital_checks[] = ['pass', 'Hospital Email', $hospital_email];
            } else {
                $hospital_checks[] = ['warning', 'Hospital Email', 'Not configured'];
            }

            if ($hospital_phone) {
                $hospital_checks[] = ['pass', 'Hospital Phone', $hospital_phone];
            } else {
                $hospital_checks[] = ['warning', 'Hospital Phone', 'Not configured'];
            }
            ?>

            <div class="section">
                <h2><span class="status-icon">🏥</span> Hospital Information</h2>
                <?php
                foreach ($hospital_checks as $check) {
                    $icon = $check[0] === 'pass' ? '✓' : '⚠';
                    $status_class = $check[0];
                    echo '<div class="check-item ' . $status_class . '">';
                    echo '<span class="check-icon">' . $icon . '</span>';
                    echo '<span class="check-label">' . htmlspecialchars($check[1]) . '</span>';
                    echo '<span class="check-value">' . htmlspecialchars($check[2]) . '</span>';
                    echo '</div>';
                    if ($check[0] === 'pass') $checks['pass']++;
                    else $checks['warning']++;
                }
                ?>
            </div>

            <div class="summary">
                <h3>Summary</h3>
                <div class="summary-item">✓ Passed: <strong><?php echo $checks['pass']; ?></strong></div>
                <div class="summary-item">✕ Failed: <strong><?php echo $checks['fail']; ?></strong></div>
                <div class="summary-item">⚠ Warnings: <strong><?php echo $checks['warning']; ?></strong></div>

                <?php if ($checks['fail'] > 0): ?>
                    <div class="warning-box" style="margin-top: 20px;">
                        <strong>⚠️ Configuration Issues Detected</strong>
                        Please review the failed items above and update your .env file accordingly.
                    </div>
                <?php else: ?>
                    <div style="background: #f0fff4; border: 2px solid #48bb78; border-radius: 4px; padding: 15px; margin-top: 20px; color: #22543d;">
                        <strong style="display: block; margin-bottom: 5px;">✓ Configuration OK</strong>
                        Your .env file is properly configured!
                    </div>
                <?php endif; ?>
            </div>

            <div class="warning-box">
                <strong>⚠️ SECURITY REMINDER</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>The .env file contains sensitive credentials</li>
                    <li>Never commit .env to version control</li>
                    <li>Never share .env file in emails or insecure channels</li>
                    <li>Delete this verification script (verify-config.php) from production</li>
                    <li>Set proper file permissions on .env (chmod 600 on Linux/Mac)</li>
                </ul>
            </div>
        </div>

        <div class="footer">
            <strong>DELETE THIS FILE</strong> (verify-config.php) after verification in production environments
        </div>
    </div>
</body>
</html>
