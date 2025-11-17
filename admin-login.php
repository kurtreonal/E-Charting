<?php
session_start();
require_once "./connection.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT users.*, user_type.user_type_desc
            FROM users
            JOIN user_type ON users.user_type_id = user_type.user_type_id
            WHERE email = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $error = "Account not found.";
    } else {
        $user = $result->fetch_assoc();

        if ($user["user_type_desc"] !== "admin") {
            $error = "Access denied. Admins only.";
        } elseif ($password !== $user["password"]) {
            //password matching
            $error = "Incorrect password.";
        } else {

            //success connection
            $_SESSION["admin_id"] = $user["user_id"];
            $_SESSION["admin_email"] = $user["email"];
            $_SESSION["is_admin"] = true;

            header("Location: adm-patient-list.php");
            exit();
        }
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
        <a>
          <button type="submit" name="login">LOGIN</button>
        </a>
      </form>
    </div>
  </div>
</body>
</html>