<?php
include 'connection.php';
include_once 'includes/notification.php';
session_name('nurse_session');
session_start();

//get the nurse_id from session
$nurse_id = isset($_SESSION['nurse_id']) ? $_SESSION['nurse_id'] : null;

if (!$nurse_id) {
    header("Location: admin-login.php");
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : null;

$sql = "
    SELECT usr.first_name, usr.middle_name, usr.last_name, p.patient_id, p.patient_status
    FROM patients p
    LEFT JOIN users usr ON p.user_id = usr.user_id
";

if ($status_filter) {
    $sql .= " WHERE p.patient_status = '" . $con->real_escape_string($status_filter) . "'";
}

$sql .= " ORDER BY usr.first_name ASC";

$result = $con->query($sql);
$patients = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Department Patient List</title>
    <link rel="stylesheet" href="./Styles/adm-patient-list.css">
    <script src="./Javascript/logoutFunction.js" defer></script>
</head>
<body>
    <?php
    include "adm-nav.php";
     ?>

    <!--Add patient-->
    <div class="wrapper">
        <div class="container">
            <div class="header-section">
                <h1>PATIENT</h1>
                <div class="btn-add-patient">
                    <a href="add-patient.php">
                        <button>Add Patient+</button>
                    </a>
                </div>
            </div>
            <!--Options to sort-->
            <div class="card-container">
                <div class="card">
                    <a href="adm-patient-list.php" style="text-decoration: none;">
                        <div class="card-title <?php echo ($status_filter === null) ? 'active' : ''; ?>">
                            All Patients
                        </div>
                    </a>
                </div>
                <div class="card">
                    <a href="adm-patient-list.php?status=in-patient" style="text-decoration: none;">
                        <div class="card-title <?php echo ($status_filter === 'in-patient') ? 'active' : ''; ?>">
                            In-Patient
                        </div>
                    </a>
                </div>
                <div class="card">
                    <a href="adm-patient-list.php?status=out-patient" style="text-decoration: none;">
                        <div class="card-title <?php echo ($status_filter === 'out-patient') ? 'active' : ''; ?>">
                            Out-Patient
                        </div>
                    </a>
                </div>
                <div class="card">
                    <a href="adm-patient-list.php?status=active" style="text-decoration: none;">
                        <div class="card-title <?php echo ($status_filter === 'active') ? 'active' : ''; ?>">
                            Active
                        </div>
                    </a>
                </div>
                <div class="card">
                    <a href="adm-patient-list.php?status=deceased" style="text-decoration: none;">
                        <div class="card-title <?php echo ($status_filter === 'deceased') ? 'active' : ''; ?>">
                            Deceased
                        </div>
                    </a>
                </div>
            </div>
            <!--Patient List Table-->
            <table class="patient-list-table">
                <tbody>
                    <?php if (count($patients) > 0): ?>
                        <?php foreach($patients as $patient): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['first_name'] . ' ' .($patient['middle_name'] ? $patient['middle_name'] . ' ' : '' ).$patient['last_name'])?></td>
                            <td><?php echo htmlspecialchars($patient['patient_status'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="create-appointment.php?patient_id=<?php echo (int)$patient['patient_id']; ?>" class="btn btn-primary" aria-label="Create appointment for <?php echo htmlspecialchars($patient['first_name'].' '.$patient['last_name']); ?>">Create Appointment</a>
                                <span style="font-style: normal;">|</span>
                                <a href="update-patient.php?id=<?php echo $patient['patient_id']; ?>" class="">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px;">No patients found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="signature-button">
            <form method="POST" action="./staff-logout.php" onsubmit="return confirmLogout()">
                <div  class="button-container">
                    <button type="submit" name="logout" id="logout">Logout</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>