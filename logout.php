<?php
// logout.php - Safely destroys student session and redirects
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_check.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session for flash message
session_start();
set_flash('info', 'You have been safely logged out. See you soon!');
header("Location: index.php");
exit();
