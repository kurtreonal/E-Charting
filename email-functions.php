<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/email-config.php';

use PHPMailer\PHPMailer\PHPMailer;   // ← ADD THIS
use PHPMailer\PHPMailer\Exception;    // ← ADD THIS

/**
 * Send Patient Update Notification Email
 *
 * @param string $to_email Patient's email address
 * @param string $patient_name Patient's full name
 * @param string $patient_id Patient ID
 * @param array $changes Array of changes made (optional)
 * @return array ['success' => bool, 'message' => string]
 */
function sendPatientUpdateEmail($to_email, $patient_name, $patient_id, $changes = []) {
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
        $mail->SMTPDebug  = MAIL_DEBUG;

        // Recipients
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $patient_name);
        $mail->addReplyTo(MAIL_REPLY_TO, MAIL_REPLY_TO_NAME);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Patient Record Has Been Updated - ' . HOSPITAL_NAME;
        $mail->Body    = getUpdateEmailTemplate($patient_name, $patient_id, $changes);
        $mail->AltBody = getUpdateEmailPlainText($patient_name, $patient_id, $changes);

        $mail->send();
        return ['success' => true, 'message' => 'Update notification email sent successfully'];

    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}

/**
 * Send Password Reset Email
 *
 * @param string $to_email Patient's email address
 * @param string $patient_name Patient's full name
 * @param string $reset_token Reset token
 * @param string $new_password New temporary password (if generated)
 * @return array ['success' => bool, 'message' => string]
 */
function sendPasswordResetEmail($to_email, $patient_name, $reset_token = null, $new_password = null) {
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
        $mail->SMTPDebug  = MAIL_DEBUG;

        // Recipients
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $patient_name);
        $mail->addReplyTo(MAIL_REPLY_TO, MAIL_REPLY_TO_NAME);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - ' . HOSPITAL_NAME;
        $mail->Body    = getPasswordResetEmailTemplate($patient_name, $reset_token, $new_password);
        $mail->AltBody = getPasswordResetEmailPlainText($patient_name, $reset_token, $new_password);

        $mail->send();
        return ['success' => true, 'message' => 'Password reset email sent successfully'];

    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}

/**
 * Get HTML Email Template for Update Notification
 */
function getUpdateEmailTemplate($patient_name, $patient_id, $changes) {
    $login_url = HOSPITAL_WEBSITE . '/login.php';
    $hospital_name = HOSPITAL_NAME;
    $hospital_phone = HOSPITAL_PHONE;
    $hospital_email = HOSPITAL_EMAIL;
    $current_year = date('Y');
    $update_date = date('F j, Y \a\t g:i A');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7fa; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 700;">
                                {$hospital_name}
                            </h1>
                            <p style="color: #e8e8ff; margin: 10px 0 0 0; font-size: 16px;">
                                Patient Record Update Notification
                            </p>
                        </td>
                    </tr>

                    <!-- Message -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #2d3748; margin: 0 0 20px 0; font-size: 24px; font-weight: 600;">
                                Hello, {$patient_name}!
                            </h2>
                            <p style="color: #4a5568; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Your patient record has been updated by our medical staff.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7fafc; border-radius: 8px; border: 2px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="color: #4a5568; margin: 0 0 10px 0; font-size: 14px;">
                                            <strong>Patient ID:</strong> {$patient_id}<br>
                                            <strong>Updated:</strong> {$update_date}
                                        </p>
                                        <div style="background-color: #ebf8ff; border-left: 4px solid #4299e1; padding: 12px; margin-top: 15px;">
                                            <p style="color: #2c5282; margin: 0; font-size: 13px;">
                                                <strong>ℹ️ Note:</strong> Please login to your patient portal to view your complete updated medical records.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Button -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <a href="{$login_url}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                🔐 Access Your Records
                            </a>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 30px;">
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">
                        </td>
                    </tr>

                    <!-- Contact -->
                    <tr>
                        <td style="padding: 30px;">
                            <h3 style="color: #2d3748; margin: 0 0 15px 0; font-size: 16px; font-weight: 600;">
                                Questions About This Update?
                            </h3>
                            <p style="color: #4a5568; margin: 0 0 10px 0; font-size: 14px;">
                                Contact us at:
                            </p>
                            <p style="color: #4a5568; font-size: 14px; margin: 5px 0;">
                                📞 {$hospital_phone}<br>
                                ✉️ <a href="mailto:{$hospital_email}" style="color: #667eea;">{$hospital_email}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2d3748; padding: 20px 30px; text-align: center;">
                            <p style="color: #a0aec0; margin: 0; font-size: 13px;">
                                © {$current_year} {$hospital_name}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Get Plain Text Version of Update Email
 */
function getUpdateEmailPlainText($patient_name, $patient_id, $changes) {
    $login_url = HOSPITAL_WEBSITE . '/login.php';
    $hospital_name = HOSPITAL_NAME;
    $update_date = date('F j, Y \a\t g:i A');

    return <<<TEXT
Hello {$patient_name},

Your patient record has been updated by our medical staff.

Patient ID: {$patient_id}
Updated: {$update_date}

Please login to your patient portal to view your complete updated medical records.

Access your records at: {$login_url}

If you have any questions about this update, please contact us at {$hospital_name}.

© {$hospital_name}. All rights reserved.
TEXT;
}

/**
 * Get HTML Email Template for Password Reset
 */
function getPasswordResetEmailTemplate($patient_name, $reset_token, $new_password) {
    $reset_url = HOSPITAL_WEBSITE . '/reset-password.php?token=' . urlencode($reset_token);
    $login_url = HOSPITAL_WEBSITE . '/login.php';
    $hospital_name = HOSPITAL_NAME;
    $hospital_phone = HOSPITAL_PHONE;
    $hospital_email = HOSPITAL_EMAIL;
    $current_year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f7fa; padding: 20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 700;">
                                {$hospital_name}
                            </h1>
                            <p style="color: #e8e8ff; margin: 10px 0 0 0; font-size: 16px;">
                                Password Reset Request
                            </p>
                        </td>
                    </tr>

                    <!-- Message -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #2d3748; margin: 0 0 20px 0; font-size: 24px; font-weight: 600;">
                                Hello, {$patient_name}!
                            </h2>
                            <p style="color: #4a5568; line-height: 1.6; margin: 0 0 20px 0; font-size: 16px;">
                                Your password has been reset by our administrative staff.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f7fafc; border-radius: 8px; border: 2px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <h3 style="color: #2d3748; margin: 0 0 15px 0; font-size: 18px; font-weight: 600;">
                                            🔑 Your New Temporary Password:
                                        </h3>
                                        <div style="background-color: #ffffff; padding: 15px; border-radius: 4px; text-align: center;">
                                            <code style="font-size: 24px; font-weight: 700; color: #2d3748; font-family: 'Courier New', monospace;">
                                                {$new_password}
                                            </code>
                                        </div>
                                        <div style="background-color: #fff5f5; border-left: 4px solid #f56565; padding: 12px; margin-top: 15px;">
                                            <p style="color: #742a2a; margin: 0; font-size: 13px;">
                                                <strong>⚠️ Important:</strong> Please change this password immediately after logging in for security reasons.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Button -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px; text-align: center;">
                            <a href="{$login_url}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                                🔐 Login Now
                            </a>
                        </td>
                    </tr>

                    <!-- Security Notice -->
                    <tr>
                        <td style="padding: 0 30px 30px 30px;">
                            <div style="background-color: #ebf8ff; border-radius: 8px; padding: 20px;">
                                <h3 style="color: #2c5282; margin: 0 0 10px 0; font-size: 16px;">
                                    🛡️ Security Tips:
                                </h3>
                                <ul style="color: #2c5282; margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.8;">
                                    <li>Change your password immediately after logging in</li>
                                    <li>Use a strong, unique password</li>
                                    <li>Never share your password with anyone</li>
                                    <li>If you didn't request this reset, contact us immediately</li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 30px;">
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">
                        </td>
                    </tr>

                    <!-- Contact -->
                    <tr>
                        <td style="padding: 30px;">
                            <h3 style="color: #2d3748; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">
                                Need Help?
                            </h3>
                            <p style="color: #4a5568; margin: 0; font-size: 14px;">
                                📞 {$hospital_phone}<br>
                                ✉️ <a href="mailto:{$hospital_email}" style="color: #667eea;">{$hospital_email}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2d3748; padding: 20px 30px; text-align: center;">
                            <p style="color: #a0aec0; margin: 0; font-size: 13px;">
                                © {$current_year} {$hospital_name}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Get Plain Text Version of Password Reset Email
 */
function getPasswordResetEmailPlainText($patient_name, $reset_token, $new_password) {
    $login_url = HOSPITAL_WEBSITE . '/login.php';
    $hospital_name = HOSPITAL_NAME;

    return <<<TEXT
Hello {$patient_name},

Your password has been reset by our administrative staff.

YOUR NEW TEMPORARY PASSWORD:
{$new_password}

IMPORTANT: Please change this password immediately after logging in for security reasons.

Login at: {$login_url}

SECURITY TIPS:
- Change your password right after logging in
- Use a strong, unique password
- Never share your password with anyone
- If you didn't request this reset, contact us immediately

© {$hospital_name}. All rights reserved.
TEXT;
}

?>
