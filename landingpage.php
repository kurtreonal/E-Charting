<?php
session_name('patient_session');
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

include 'connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mica Hospital</title>
    <link rel="stylesheet" href="./Styles/landingpage.css">
    <script src="./Javascript/javascript.js" defer></script>
</head>
<body>
    <?php include "nav.php"; ?>

    <div class="main-content" id="home">
        <img src="./Assets/img1.svg" alt="hospital image" class="main-image">
        <div class="content-overlay">
            <h1>The Mica Hospital</h1>
            <p>
                Welcome to Mica Hospital! At Mica Hospital, your health is our priority.
                We provide compassionate, patient-centered care supported by advanced medical
                technology and a dedicated team of healthcare professionals. From emergency
                services to specialized treatments, we are here to ensure the well-being of
                you and your family every step of the way.
            </p>
        </div>
    </div>

    <div class="services-section" id="services">
        <div class="services-content">
            <h2 class="section-title">0UR SERVICES</h2>
            <div class="services-container">
                <div class="service-card">
                    <h3 class="service-title">CLINICAL SERVICES</h3>
                    <p class="service-description">
                        Our clinical assistance team is here to support you every step of the way.
                        Whether it's helping with daily care, guiding you through treatments,
                        or answering your questions, we make sure you feel safe, comfortable,
                        and well-informed during your stay at Mica Hospital.
                    </p>
                </div>
                <div class="service-card">
                    <h3 class="service-title">DIAGN0STICS</h3>
                    <p class="service-description">
                        At Mica Hospital, our advanced diagnostic services help doctors detect
                        and understand health conditions with accuracy and speed.
                        Using modern equipment and skilled specialists,
                        we provide reliable lab tests, imaging, and screenings to ensure
                        you receive the right care at the right time.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="about-section" id="contacts">
        <div class="about-content">
            <h2 class="section-title">AB0UT US</h2>
            <div class="about-container">
                <div class="about-card">
                    <h3 class="about-title">VISIT US</h3>
                    <img src="./Assets/heart-detector.svg" alt="heart detector">
                    <p class="about-description">
                        123 Health St., Etivac City, HC 45678
                    </p>
                </div>
                <div class="about-card">
                    <h3 class="about-title">C0NTACTS</h3>
                    <img src="./Assets/contacticon.svg" alt="contact icon">
                    <p class="about-description">
                        Phone: 0939-123-4567<br>
                        Email: micahospital@gmail.com
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Mica Hospital. All rights reserved.</p>
    </footer>
</body>
</html>
