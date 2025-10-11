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
    <div class="wrapper"> <!--background-color: --secondary width: 98%-->
        <div class="container"> <!---->
            <div class="profile-card">
                <div class="profile-details"> <!--border for details bottom left of profile header-->
                    <p>Test Name</p>
                    <p>Female</p>
                    <p>January 1, 2004</p>
                    <p>21</p>
                </div>
            </div>
            <div class="content"> <!-- 90% of total width just a simple box with a border aligned with the profile located at the right of header-->
                <div class="content-section">
                    <h2 class="content-title">Description</h2>
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