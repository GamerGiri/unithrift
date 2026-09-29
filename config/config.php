<?php
// config/config.php
// Application Configuration

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Default for local XAMPP/MySQL)
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'unithrift_db');
define('DB_PORT', 3306);

// Application Meta
define('APP_NAME', 'UniThrift');
define('APP_TAGLINE', 'Campus Academic ReUse & Pre-Owned Gear Marketplace');
define('APP_CURRENCY', '৳');
define('BASE_URL', ''); // Auto-detected or relative
