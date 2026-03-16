<?php
// config.php
// ==========================================
// 🔴 CHANGE THESE ON PRODUCTION HOSTING 🔴
// ==========================================
define('DB_HOST', 'localhost'); // Usually 'localhost', but sometimes provided by your host (e.g., a specific IP or URL)
define('DB_USER', 'farmsell_btc');      // Change 'root' to your live database username
define('DB_PASS', '(a1$OO]WWw*L&6LX');          // Change '' to your live database password
define('DB_NAME', 'farmsell_btc'); // Change 'pos_systems' to your live database name

// App Settings
define('APP_NAME', 'Beetech Mini SuperShop');
define('CURRENCY', '৳');
define('TIMEZONE', 'Asia/Dhaka');

// Business Logic Settings


// Error Reporting (Turn off for public production, On for dev)
// 🔴 CHANGE IN PRODUCTION: Set display_errors to 0 🔴
error_reporting(E_ALL);
ini_set('display_errors', 1); // <-- Change 1 to 0 on your live hosting

// Start Session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set Timezone
date_default_timezone_set(TIMEZONE);
