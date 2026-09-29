<?php
// config/db.php
// PDO Database Connection with Automatic First-Time Install Detection

require_once __DIR__ . '/config.php';

function getDbConnection($forceCheckTables = true) {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $isInstallPage = (basename($_SERVER['PHP_SELF'] ?? '') === 'install.php');

    try {
        // Attempt connection to the target database
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Verify required tables exist
        if ($forceCheckTables && !$isInstallPage) {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $hasUsers = $stmt->fetch();
            $stmt = $pdo->query("SHOW TABLES LIKE 'items'");
            $hasItems = $stmt->fetch();

            if (!$hasUsers || !$hasItems) {
                header("Location: install.php?reason=missing_tables");
                exit();
            }
        }

        return $pdo;

    } catch (PDOException $e) {
        // If DB does not exist or connection fails and we are not on install.php, redirect to install.php
        if (!$isInstallPage) {
            header("Location: install.php?reason=db_not_found");
            exit();
        }
        return null;
    }
}

// Global $pdo instance for inclusion
$pdo = getDbConnection();
