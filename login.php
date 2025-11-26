<?php
session_start();

include "./connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $email = mysqli_real_escape_string($con, $_POST["email"]);
    $password = $_POST["password"];

    $query = "
    SELECT n.patient_id, u.password, ut.user_type_desc
    FROM patients n
    INNER JOIN users u ON n.user_id = u.user_id
    INNER JOIN user_type ut ON u.user_type_id = ut.user_type_id
    WHERE u.email = '$email'
    ";

    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row["password"])) {
            if ($row["user_type_desc"] === "patient") {
                $_SESSION["patient_id"] = $row["patient_id"];
                $_SESSION["is_patient"] = true;
                header("Location: ./landingpage.php");
                exit();
            } else {
                $error = "Access denied! Shoo!";
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
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