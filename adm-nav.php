<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Styles/adm-nav.css">
    <script src="./Javascript/javascript.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <div class="left-section">
            <div class="nav-logo">
                <img src="./Assets/logo.svg" alt="Hospital Logo" class="logo">
            </div>
            <div class="page-title">
                <p>Nurse Department</p>
            </div>
        </div>

        <div class="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>

        <div class="nav-links">
            <a href="#" class="nav-item">Patient</a>
            <a href="#" class="nav-item">Profile</a>
            <a href="#" class="nav-item">
                <img src="./Assets/notification.svg" alt="Notifications" class="notification-icon">
            </a>
        </div>
    </nav>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    hamburger.addEventListener('click', () => {
        // Toggle hamburger active class
        hamburger.classList.toggle('active');
        navLinks.classList.toggle('active');
    });

    // Close menu when clicking a nav item
    document.querySelectorAll('.nav-item').forEach(n => n.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navLinks.classList.remove('active');
    }));
});
    </script>
</body>
</html>