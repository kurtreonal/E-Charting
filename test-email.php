<?php
/**
 * Test Email Configuration
 * Run this file to verify your PHPMailer setup is working
 * URL: http://localhost/E-Charting/test-email.php
 */

require 'vendor/autoload.php';
require 'email-config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .config-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .config-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .config-table td:first-child {
            font-weight: bold;
            width: 200px;
            color: #667eea;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Configuration Test</h1>

        <div class="info">
            <strong>ℹ️ Current Configuration:</strong>
        </div>

        <table class="config-table">
            <tr>
                <td>SMTP Host:</td>
                <td><?php echo MAIL_HOST; ?></td>
            </tr>
            <tr>
                <td>SMTP Port:</td>
                <td><?php echo MAIL_PORT; ?></td>
            </tr>
            <tr>
                <td>Encryption:</td>
                <td><?php echo MAIL_ENCRYPTION; ?></td>
            </tr>
            <tr>
                <td>Username:</td>
                <td><?php echo MAIL_USERNAME; ?></td>
            </tr>
            <tr>
                <td>From Email:</td>
                <td><?php echo MAIL_FROM; ?></td>
            </tr>
            <tr>
                <td>From Name:</td>
                <td><?php echo MAIL_FROM_NAME; ?></td>
            </tr>
        </table>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
            $test_email = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);

            if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                echo '<div class="error">❌ Invalid email address format</div>';
            } else {
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = MAIL_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = MAIL_USERNAME;
                    $mail->Password   = MAIL_PASSWORD;
                    $mail->SMTPSecure = MAIL_ENCRYPTION;
                    $mail->Port       = MAIL_PORT;

                    // Recipients
                    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                    $mail->addAddress($test_email, 'Test Recipient');

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'PHPMailer Test Email - ' . date('Y-m-d H:i:s');
                    $mail->Body    = '
                    <html>
                    <body style="font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;">
                        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;">
                            <h1 style="color: #667eea; margin-bottom: 20px;">✅ Success!</h1>
                            <p style="font-size: 16px; color: #333; line-height: 1.6;">
                                PHPMailer is configured correctly and working!
                            </p>
                            <p style="font-size: 14px; color: #666; margin-top: 20px;">
                                <strong>Test Details:</strong><br>
                                Sent at: ' . date('Y-m-d H:i:s') . '<br>
                                From: ' . MAIL_FROM . '<br>
                                To: ' . $test_email . '
                            </p>
                            <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-top: 20px;">
                                <p style="margin: 0; color: #155724;">
                                    <strong>🎉 Your email system is ready to use!</strong><br>
                                    You can now integrate PHPMailer into your application.
                                </p>
                            </div>
                        </div>
                    </body>
                    </html>';

                    $mail->AltBody = 'PHPMailer is working correctly! This is the plain text version.';

                    $mail->send();

                    echo '<div class="success">
                        <strong>✅ Test Email Sent Successfully!</strong><br>
                        Check your inbox at: <strong>' . htmlspecialchars($test_email) . '</strong><br>
                        <small>Note: Check spam folder if you don\'t see it in inbox</small>
                    </div>';

                } catch (Exception $e) {
                    echo '<div class="error">
                        <strong>❌ Email Sending Failed</strong><br>
                        Error: ' . htmlspecialchars($mail->ErrorInfo) . '<br><br>
                        <strong>Common Solutions:</strong><br>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>For Gmail: Make sure you\'re using an App Password, not your regular password</li>
                            <li>Enable 2-Step Verification in your Google Account</li>
                            <li>Check that XAMPP is not blocking port ' . MAIL_PORT . '</li>
                            <li>Verify your credentials in email-config.php</li>
                        </ul>
                    </div>';
                }
            }
        }
        ?>

        <form method="POST" style="margin-top: 30px;">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                    Enter your email address to receive a test email:
                </label>
                <input type="email" name="test_email" required
                       style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; font-size: 14px;"
                       placeholder="your-email@example.com">
            </div>
            <button type="submit">Send Test Email</button>
        </form>

        <div class="warning" style="margin-top: 30px;">
            <strong>⚠️ Important Setup Steps:</strong>
            <ol style="margin: 10px 0 0 20px; line-height: 1.8;">
                <li>Run: <code>composer require phpmailer/phpmailer</code> in your project root</li>
                <li>Edit <strong>email-config.php</strong> with your email credentials</li>
                <li>For Gmail: Generate an App Password (don't use regular password)</li>
                <li>Test the email configuration using this page</li>
            </ol>
        </div>
    </div>
</body>
</html>
