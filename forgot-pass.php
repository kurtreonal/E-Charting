<?php
session_name('patient_session');
session_start();

include "./connection.php";

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $email = filter_var($_POST["email"] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $error_message = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if email exists in patients
        $query = "
            SELECT p.patient_id, u.user_id, u.first_name, u.middle_name, u.last_name
            FROM patients p
            INNER JOIN users u ON p.user_id = u.user_id
            INNER JOIN user_type ut ON u.user_type_id = ut.user_type_id
            WHERE u.email = ? AND ut.user_type_desc = 'patient'
        ";

        $stmt = $con->prepare($query);
        if (!$stmt) {
            $error_message = "Database error. Please try again later.";
            error_log("Prepare failed: " . $con->error);
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_id = $row['user_id'];
                $full_name = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']);

                // Generate new temporary password
                $new_password = 'Temp' . rand(1000, 9999) . '!';
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in database
                $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
                $update_stmt = $con->prepare($update_query);

                if ($update_stmt) {
                    $update_stmt->bind_param("si", $hashed_password, $user_id);

                    if ($update_stmt->execute()) {
                        // Try to send email with new password
                        if (file_exists('email-functions.php')) {
                            require_once 'email-functions.php';

                            // Generate a simple token (for display purposes)
                            $reset_token = bin2hex(random_bytes(16));

                            $email_result = sendPasswordResetEmail($email, $full_name, $reset_token, $new_password);

                            if ($email_result['success']) {
                                $success_message = "Password reset successful! Check your email for your new temporary password.";
                            } else {
                                // Password was reset but email failed - show password on screen
                                $success_message = "Password has been reset. Your new temporary password is: <strong style='font-size: 18px; color: #667eea;'>" . htmlspecialchars($new_password) . "</strong><br><small style='color: #666;'>(Email notification could not be sent. Please save this password.)</small>";
                            }
                        } else {
                            // Email functions not available - show password on screen
                            $success_message = "Password has been reset. Your new temporary password is: <strong style='font-size: 18px; color: #667eea;'>" . htmlspecialchars($new_password) . "</strong><br><small style='color: #666;'>Please save this password and change it after logging in.</small>";
                        }
                    } else {
                        $error_message = "Failed to reset password. Please try again later.";
                    }
                    $update_stmt->close();
                } else {
                    $error_message = "Database error. Please try again later.";
                }
            } else {
                // Don't reveal that email doesn't exist (security best practice)
                $success_message = "If this email is registered, a password reset link has been sent.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Patient Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="./Styles/login.css">
    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .back-link:hover {
            transform: translateX(-5px);
            color: #667eea;
        }
        .success-message {
            background-color: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.6;
        }
        .success-message i {
            color: #28a745;
            margin-right: 8px;
        }
        .error-message {
            background-color: #ffe6e6;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        .error-message i {
            color: #dc3545;
            margin-right: 8px;
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #0c5460;
            font-size: 16px;
            font-family: "Cormorant Garamond", serif;
        }
        .info-box p {
            margin: 5px 0;
            color: #0c5460;
            font-size: 14px;
            line-height: 1.6;
        }
        .help-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
        }
        .help-section p {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .help-section strong {
            color: var(--primary);
        }
        .return-login {
            text-align: center;
            margin-top: 20px;
        }
        .return-login a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .return-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="left-section">
            <div class="header-section">
                <div class="logo-container">
                    <img src="./Assets/logo.svg" alt="Hospital Logo" class="logo">
                </div>
                <div class="mica-container">
                    <p class="mica-desc">
                        MICA HOSPITAL PROVIDES COMPASSIONATE CARE AND ADVANCED MEDICAL SERVICES TO KEEP YOU AND YOUR FAMILY HEALTHY.
                    </p>
                </div>
            </div>
        </div>

        <div class="right-section">
            <div class="login-container">
                <a href="./login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>

                <h1>Forgot Password?</h1>

                <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_message; ?>
                    </div>
                    <div class="return-login">
                        <a href="./login.php">
                            <i class="fas fa-sign-in-alt"></i> Return to Login
                        </a>
                    </div>
                <?php else: ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="info-box">
                        <h3><i class="fas fa-info-circle"></i> How it works:</h3>
                        <p>1. Enter your registered email address</p>
                        <p>2. We'll send you a new temporary password</p>
                    </div>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="input-group">
                            <input type="email"
                                   id="email"
                                   name="email"
                                   placeholder="Enter your email address"
                                   required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="divider"></div>
                        <a href="">
                            <button type="submit" name="submit">RESET</button>
                        </a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>