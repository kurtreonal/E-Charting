<?php
session_start();
include 'connection.php';

// get the patient_id from session
$patient_id = isset($_SESSION['patient_id']) ? $_SESSION['patient_id'] : null;

if (!$patient_id) {
    header("Location: login.php");
    exit();
}

// query to get patient data including user info
$sql = "
SELECT u.first_name, u.last_name, u.middle_name, p.gender, p.date_of_birth, p.contact_number, p.address, p.patient_status
FROM patients p
JOIN users u ON p.user_id = u.user_id
WHERE p.patient_id = ?
";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$patient = null;
$age = 0;

if ($result->num_rows === 1) {
    $patient = $result->fetch_assoc();

    // calculate age from date of birth
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
    <script src="./Javascript/logoutFunction.js" defer></script>
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
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($patient['patient_status']); ?></p>
                </div>
            </div>
            <div class="content">
<?php
// after $patient is set earlier in your page
// Fetch uploaded lab files for this patient
$uploadedFiles = [];
$labStmt = $con->prepare("SELECT lab_result_id, file_name, file_path, uploaded_at FROM lab_results WHERE patient_id = ? ORDER BY uploaded_at DESC");
$labStmt->bind_param("i", $patient_id);
$labStmt->execute();
$labRes = $labStmt->get_result();
while ($r = $labRes->fetch_assoc()) $uploadedFiles[] = $r;
$labStmt->close();
?>

<!-- inside Description section -->
<div class="content-section">
    <h2 class="content-title">Description</h2>

    <p class="description-text">
        <!-- Clickable links to generate printable PDFs of DB records -->
        <strong>Printable Results:</strong>
        <ul>
            <li><a href="lab_print.php?type=history&patient_id=<?php echo $patient_id; ?>" target="_blank">History</a></li>
            <li><a href="lab_print.php?type=physical&patient_id=<?php echo $patient_id; ?>" target="_blank">Physical Assessment</a></li>
            <li><a href="lab_print.php?type=admission&patient_id=<?php echo $patient_id; ?>" target="_blank">Admission Data</a></li>
        </ul>

        <!-- Uploaded files -->
        <strong>Uploaded Lab Files:</strong>
        <?php if (count($uploadedFiles) === 0): ?>
            <p>No uploaded lab files.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($uploadedFiles as $file): ?>
                    <li>
                        <a href="lab_print.php?file_id=<?php echo $file['lab_result_id']; ?>" target="_blank">
                            <?php echo htmlspecialchars($file['file_name']); ?> — <?php echo htmlspecialchars($file['uploaded_at']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </p>

    <!-- Upload form -->
    <form action="upload_lab.php" method="POST" enctype="multipart/form-data">
        <label>Upload Lab Result (PDF / JPG / PNG):</label><br>
        <input type="file" name="lab_file" accept=".pdf,image/png,image/jpeg" required>
        <button type="submit">Upload</button>
    </form>

    <?php
    if (!empty($_SESSION['upload_error'])) {
        echo '<p style="color:red;">' . htmlspecialchars($_SESSION['upload_error']) . '</p>';
        unset($_SESSION['upload_error']);
    }
    if (!empty($_SESSION['upload_success'])) {
        echo '<p style="color:green;">' . htmlspecialchars($_SESSION['upload_success']) . '</p>';
        unset($_SESSION['upload_success']);
    }
    ?>
</div>


                <div class="content-section2">
                    <h2 class="content-title">Come back by:</h2>
                    <div class="prescription-box">
                        <h3>Prescription of Medication</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="signature-button">
            <form method="POST" action="./logout.php" onsubmit="return confirmLogout()">
                <button type="submit" name="logout" id="logout">Logout</button>
            </form>
        </div>
    </div>
</body>
</html>