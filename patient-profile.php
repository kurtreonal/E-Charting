<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="./Javascript/javascript.js" defer></script>
    <link rel="stylesheet" href="./Styles/patient-profile.css">
</head>
<body>
    <?php include "nav.php"; ?>
    <div class="wrapper"> <!--background-color: --secondary width: 98%-->
        <div class="container"> <!---->
            <div class="profile-card">
                <div class="profile-header"> <!-- 10% of total width with border upper left-->
                    <img src="./Assets/plimg2.png" alt="Patient Avatar" class="avatar">
                </div>
                <div class="profile-details"> <!--border for details bottom left of profile header-->
                    <p>Test Name</p>
                    <p>Female</p>
                    <p>January 1, 2004</p>
                    <p>21</p>
                </div>
            </div>
            <div class="content"> <!-- 90% of total width just a simple box with a border aligned with the profile located at the right of header-->

            </div>
        </div>
    </div>
</body>
</html>