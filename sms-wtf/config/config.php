<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sms_wtf');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'SMS WTF');
define('ADMIN_SESSION_NAME', 'sms_wtf_admin');
define('USER_SESSION_NAME', 'sms_wtf_user');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Paths
define('BASE_PATH', __DIR__);
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ADMIN_PATH', BASE_PATH . '/admin');

// Timezone
date_default_timezone_set('UTC');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
