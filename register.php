<?php
session_start();
require_once "connection.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: ./adm-patient-list.php");
    exit();
}

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        if (empty($email) || empty($password)) {
            throw new Exception("Please fill out all fields.");
        }

        // Check for duplicate email
        $checkQuery = "SELECT * FROM admin WHERE email = ?";
        $stmt = mysqli_prepare($con, $checkQuery);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            throw new Exception("Email already registered.");
        }

        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $insertQuery = "INSERT INTO admin (email, password) VALUES (?, ?)";
        $stmt = mysqli_prepare($con, $insertQuery);
        mysqli_stmt_bind_param($stmt, "ss", $email, $hashedPassword);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Registration failed. Please try again.");
        }

        // Success: redirect or display success message
        $_SESSION['success'] = "Account successfully created!";
        header("Location: login.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./Styles/register.css">
  <title>Register</title>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      <div class="logo-container">
        <img src="./Assets/logo.svg" alt="Hospital Logo" class="logo">
      </div>
      <div class="mica-container">
        <p class="mica-desc">
          MICA HOSPITAL PROVIDES COMPASSIONATE CARE AND ADVANCED MEDICAL SERVICES TO KEEP YOU AND YOUR FAMILY HEALTHY.
        </p>
      </div>

      <?php if (!empty($error)): ?>
        <p style="color:red; text-align:center;"><?php echo htmlspecialchars($error); ?></p>
      <?php endif; ?>
      <form method="POST" action="" class="form">
        <div class="input-group">
          <input type="email" name="email" id="email" placeholder="Email" required>
        </div>
        <div class="input-group">
          <input type="password" name="password" id="password" placeholder="Password" required>
        </div>

        <div class="divider"></div>

        <a href="./landingpage.php">
          <button type="submit">SIGN UP</button>
        </a>
      </form>
    </div>
  </div>
</body>
</html>
