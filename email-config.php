<?php
/**
 * Email Configuration for E-Charting System
 * Loads email settings from .env configuration file
 * 
 * NOTE: Do NOT hardcode credentials here.
 * All sensitive information should be stored in .env file only.
 */

// Load configuration from .env file
require_once dirname(__FILE__) . '/includes/config.php';

// ========================================
// SMTP SERVER SETTINGS (from .env)
// ========================================
define('MAIL_HOST', Config::get('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int)Config::get('MAIL_PORT', '587'));
define('MAIL_ENCRYPTION', Config::get('MAIL_ENCRYPTION', 'tls'));

// ========================================
// AUTHENTICATION (from .env)
// ========================================
define('MAIL_USERNAME', Config::get('MAIL_USERNAME'));
define('MAIL_PASSWORD', Config::get('MAIL_PASSWORD'));

// ========================================
// FROM ADDRESS (from .env)
// ========================================
define('MAIL_FROM', Config::get('MAIL_FROM'));
define('MAIL_FROM_NAME', Config::get('MAIL_FROM_NAME', 'Mica Hospital'));

// ========================================
// HOSPITAL INFORMATION (from .env)
// ========================================
define('HOSPITAL_NAME', Config::get('HOSPITAL_NAME', 'Mica Hospital'));
define('HOSPITAL_ADDRESS', Config::get('HOSPITAL_ADDRESS'));
define('HOSPITAL_PHONE', Config::get('HOSPITAL_PHONE'));
define('HOSPITAL_EMAIL', Config::get('HOSPITAL_EMAIL'));
define('HOSPITAL_WEBSITE', Config::get('HOSPITAL_WEBSITE'));

// ========================================
// OPTIONAL SETTINGS
// ========================================
define('MAIL_REPLY_TO', Config::get('MAIL_FROM'));
define('MAIL_REPLY_TO_NAME', Config::get('MAIL_FROM_NAME', 'Mica Hospital Support'));
define('MAIL_DEBUG', 0);  // Set to 0 in production

// ========================================
// VALIDATION
// ========================================
// Verify required email configuration
if (!MAIL_USERNAME || !MAIL_PASSWORD) {
    trigger_error('Email configuration incomplete. Check MAIL_USERNAME and MAIL_PASSWORD in .env file.', E_USER_WARNING);
}

?>
