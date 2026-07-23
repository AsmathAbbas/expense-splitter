<?php
/**
 * Configuration File – SplitEase
 * 
 * Copy this file to config.php and update the values below with your own credentials.
 * Never commit your real config.php to version control.
 */

// ============================================
// DATABASE CONNECTION
// ============================================

session_start();

// Replace these with your actual database credentials
$host    = "your_db_hostname";   // e.g. sql123.infinityfree.com or localhost
$db_user = "your_db_username";   // e.g. epiz_12345678 or root
$db_pass = "your_db_password";
$db_name = "your_db_name";       // e.g. epiz_12345678_expense_splitter

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ============================================
// GOOGLE reCAPTCHA
// ============================================

// Get your keys free at https://www.google.com/recaptcha/admin/create
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key_here');

// ============================================
// SENDGRID EMAIL API
// ============================================

// Sign up at https://sendgrid.com/ and get your API key
define('SENDGRID_API_KEY', 'your_sendgrid_api_key_here');
define('SENDGRID_FROM_EMAIL', 'your-verified-sender@example.com');
define('SENDGRID_FROM_NAME', 'SplitEase');

?>