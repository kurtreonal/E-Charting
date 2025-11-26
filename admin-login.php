<?php
session_start();

include "./connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $email = mysqli_real_escape_string($con, $_POST["email"]);
    $password = $_POST["password"];

    $query = "
    SELECT n.nurse_id, u.password, ut.user_type_desc
    FROM nurse n
    INNER JOIN users u ON n.user_id = u.user_id
    INNER JOIN user_type ut ON u.user_type_id = ut.user_type_id
    WHERE u.email = '$email'
    ";

    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        if ($password === $row["password"]) { // or use password_verify()
            if ($row["user_type_desc"] === "nurse") {
                $_SESSION["nurse_id"] = $row["nurse_id"];
                $_SESSION["is_nurse"] = true;
                header("Location: ./adm-patient-list.php");
                exit();
            } else {
                $error = "Access denied. Nurse only.";
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./Styles/admin-login.css" />
  <title>Admin Login</title>
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
          <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="input-group">
          <input type="password" name="password" placeholder="Password" required>
        </div>

        <div class="divider"></div>
          <a href="">
              <button type="submit" name="submit">SIGN IN</button>
          </a>
      </form>
    </div>
  </div>
</body>
</html>