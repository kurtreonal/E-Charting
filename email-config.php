<?php
/**
 * Email Configuration for E-Charting System
 * Configure your email settings here
 */

// ========================================
// SMTP SERVER SETTINGS
// ========================================

// For Gmail (Recommended for testing)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');  // 'tls' or 'ssl'

// For Mailtrap (Best for development/testing - catches all emails)
// define('MAIL_HOST', 'smtp.mailtrap.io');
// define('MAIL_PORT', 2525);
// define('MAIL_ENCRYPTION', 'tls');

// ========================================
// AUTHENTICATION
// ========================================

// IMPORTANT: For Gmail, use App Password, NOT your regular password
// See guide for how to generate App Password
define('MAIL_USERNAME', 'shizukuosaka26@gmail.com');      // Your email address
define('MAIL_PASSWORD', 'fjqtypzfejcuvwue');     // Gmail App Password (16 chars, no spaces)

// ========================================
// FROM ADDRESS
// ========================================

define('MAIL_FROM', 'shizukuosaka26@gmail.com');   // From email (can be same as MAIL_USERNAME)
define('MAIL_FROM_NAME', 'Mica Hospital');        // Display name

// ========================================
// HOSPITAL INFORMATION
// ========================================

define('HOSPITAL_NAME', 'Mica Hospital');
define('HOSPITAL_ADDRESS', '123 Health St., Etivac City, HC 45678');
define('HOSPITAL_PHONE', '0939-123-4567');
define('HOSPITAL_EMAIL', 'shizukuosaka26@gmail.com');
define('HOSPITAL_WEBSITE', 'http://localhost/E-Charting');

// ========================================
// OPTIONAL SETTINGS
// ========================================

define('MAIL_REPLY_TO', 'shizukuosaka26@gmail.com'); // Reply-to email
define('MAIL_REPLY_TO_NAME', 'Mica Hospital Support');
define('MAIL_DEBUG', 0);  // 0 = off, 1 = client, 2 = server, 3 = connection, 4 = lowlevel

?>
