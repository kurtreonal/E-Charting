<?php
session_start();

// Database connection

include 'connection.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
        // Query to get user by email
        $sql = "SELECT u.user_id, u.password, u.first_name, u.last_name, ut.user_type_desc
                FROM users u
                JOIN user_type ut ON u.user_type_id = ut.user_type_id
                WHERE u.email = ?";

        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = $user['user_type_desc'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];

                // Redirect based on user type
                switch ($user['user_type_desc']) {
                    case 'admin':
                        header("Location: ./adm-patient-list.php");
                        break;
                    case 'nurse':
                        header("Location: ./dashboard/nurse_dashboard.php");
                        break;
                    case 'patient':
                        header("Location: ./landingpage.php");
                        break;
                }
                exit();
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }

        $stmt->close();
    }
}

$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="./Styles/login.css">
</head>
<body>

    <div class="wrapper">
        <div class="left-section">
            <!-- Logo and hospital name section -->
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
                <h1>Log In Your Account</h1>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message" style="color: red; margin-bottom: 15px; padding: 10px; background-color: #ffe6e6; border-radius: 4px;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="input-group">
                        <input type="email" id="email" placeholder="Email" name="email" required>
                    </div>

                    <div class="input-group">
                        <input type="password" id="password" placeholder="Password" name="password" required>
                    </div>

                    <div class="divider"></div>
                    <a href="">
                        <button type="submit" name="submit">SIGN IN</button>
                    </a>
                    <div class="divider-hide"></div>

                    <div class="social-login">
                        <div class="social-btn">
                            <i class="fa-brands fa-square-facebook"></i>
                        </div>
                        <div class="social-btn">
                            <i class="fa-brands fa-square-instagram"></i>
                        </div>
                        <div class="social-btn">
                            <i class="fa-brands fa-square-twitter"></i>
                        </div>
                    </div>

                    <div class="footer">
                        Don't have an account? <a href="">Sign up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>