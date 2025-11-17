<?php
// Database connection
include 'connection.php';

// Fetch patient names from database
$sql = "SELECT u.first_name, u.middle_name, u.last_name, p.patient_id
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        ORDER BY u.first_name ASC";

$result = $con->query($sql);
$patients = [];

if ($result->num_rows > 0) {
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
</head>
<body>
    <?php include "adm-nav.php"; ?>

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
                    <div class="card-title">
                        In-Patient
                    </div>
                </div>
                <div class="card">
                    <div class="card-title">
                        Out-Patient
                    </div>
                </div>
                <div class="card">
                    <div class="card-title">
                        New
                    </div>
                </div>
                <div class="card">
                    <div class="card-title">
                        Old
                    </div>
                </div>
                <div class="card">
                    <div class="card-title">
                        Active
                    </div>
                </div>
                <div class="card">
                    <div class="card-title">
                        Deceased
                    </div>
                </div>
            </div>
            <!--Patient List Table-->
            <table class="patient-list-table">
                <tbody>
                    <?php foreach($patients as $patient): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['middle_name'] . ' ' . $patient['last_name']); ?></td>
                        <td>
                            <a href="edit-patient.php?patient_id=<?php echo $patient['patient_id']; ?>" class="">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>