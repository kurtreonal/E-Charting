<?php
session_start();

//database connection
include 'connection.php';

//get the patient user_id from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

//redirect to login if user_id is not set
if (!$user_id) {
    header("Location: login.php");
    exit();
}

//query to get patient data from users and patients table
$sql = "SELECT u.first_name, u.last_name, u.middle_name, p.gender, p.date_of_birth, p.contact_number, p.address
        FROM users u
        JOIN patients p ON u.user_id = p.user_id
        WHERE u.user_id = ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$patient = null;
$age = 0;

if ($result->num_rows === 1) {
    $patient = $result->fetch_assoc();

    //calculate age from date of birth
    $dob = new DateTime($patient['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
} else {
    echo "Patient profile not found.";
    exit();
}

$stmt->close();
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Profile</title>
    <script src="./Javascript/javascript.js" defer></script>
    <link rel="stylesheet" href="./Styles/patient-profile.css">
</head>
<body>
    <?php include "nav.php"; ?>
    <div class="wrapper">
        <div class="container">
            <div class="profile-card">
                <div class="profile-details">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . " " . $patient['last_name']); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars(date("F j, Y", strtotime($patient['date_of_birth']))); ?></p>
                    <p><strong>Age:</strong> <?php echo $age; ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($patient['contact_number']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address']); ?></p>
                </div>
            </div>
            <div class="content">
                <div class="content-section">
                    <h2 class="content-title">Description</h2>
                    <p class="description-text"></p>
                    <div class="upload-box">
                        <p>Upload Lab Results</p>
                    </div>
                </div>

                <div class="content-section2">
                    <h2 class="content-title">Come back by:</h2>
                    <div class="prescription-box">
                        <h3>Prescription of Medication</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>