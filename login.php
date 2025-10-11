<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="./Styles/login.css">
</head>
<body>
    <div class="wrapper">
        <div class="left-section">
            <!-- Logo and hospital name section -->
            <div class="header-section">
                <div class="logo-container">
                    <img src="./Assets/logo.svg" alt="Hospital Logo" class="logo">
                </div>
                <div class="mica-container">
                    <p class="mica-desc">
                        Mica Hospital provides compassionate care and advanced medical services to keep you and your family healthy.
                    </p>
                </div>
            </div>
        </div>

        <div class="right-section">
            <div class="login-container">
                <h1>Log In Your Account</h1>

                <div class="input-group">
                    <input type="email" id="email" placeholder="Email">
                </div>

                <div class="input-group">
                    <input type="password" id="password" placeholder="Password">
                </div>

                <div class="divider"></div>
                <a href="./landingpage.php">
                    <button type="submit">SIGN IN</button>
                </a>
                <div class="divider-hide"></div>

                <div class="social-login">
                    <div class="social-btn">
                        <i class="fa-brands fa-square-facebook"></i>
                    </div>
                    <div class="social-btn">
                        <i class="fa-brands fa-square-instagram"></i>
                    </div>
                    <div class="social-btn">
                        <i class="fa-brands fa-square-twitter"></i>
                    </div>
                </div>

                <div class="footer">
                    Don't have an account? <a href="">Sign up</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>