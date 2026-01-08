<?php
session_name('patient_session');
session_start();

//security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

include "./connection.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $email = filter_var($_POST["email"] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = "Email and password are required.";
    } else {
        $query = "
            SELECT p.patient_id, u.password, ut.user_type_desc
            FROM patients p
            INNER JOIN users u ON p.user_id = u.user_id
            INNER JOIN user_type ut ON u.user_type_id = ut.user_type_id
            WHERE u.email = ?
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

                if (password_verify($password, $row["password"])) {
                    if ($row["user_type_desc"] === "patient") {
                        session_regenerate_id(true);
                        $_SESSION["patient_id"] = $row["patient_id"];
                        $_SESSION["is_patient"] = true;
                        $_SESSION["login_time"] = time();

                        header("Location: ./landingpage.php");
                        exit();
                    } else {
                        $error_message = "Access denied. Patient login only.";
                    }
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Invalid email or password.";
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
                    <img src="./Assets/logoP.png" alt="Hospital Logo" class="logo">
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
                    <div><center><a href="./forgot-pass.php">Forgot Password?</a></center></div>
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
                </form>
            </div>
        </div>
    </div>
</body>
</html>
