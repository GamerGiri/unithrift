<?php
// install.php
// UniThrift - Fresh Installation & Database Setup Wizard

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_check.php';

// Helper function to write updated database constants into config/config.php
function update_config_file(string $host, string $user, string $pass, string $name, int $port): bool {
    $configFile = __DIR__ . '/config/config.php';
    if (!file_exists($configFile)) {
        return false;
    }
    $content = file_get_contents($configFile);
    if ($content === false) {
        return false;
    }

    $content = preg_replace("/define\s*\(\s*'DB_HOST'\s*,\s*.*?\s*\);/", "define('DB_HOST', " . var_export($host, true) . ");", $content);
    $content = preg_replace("/define\s*\(\s*'DB_USER'\s*,\s*.*?\s*\);/", "define('DB_USER', " . var_export($user, true) . ");", $content);
    $content = preg_replace("/define\s*\(\s*'DB_PASS'\s*,\s*.*?\s*\);/", "define('DB_PASS', " . var_export($pass, true) . ");", $content);
    $content = preg_replace("/define\s*\(\s*'DB_NAME'\s*,\s*.*?\s*\);/", "define('DB_NAME', " . var_export($name, true) . ");", $content);
    $content = preg_replace("/define\s*\(\s*'DB_PORT'\s*,\s*.*?\s*\);/", "define('DB_PORT', " . (int)$port . ");", $content);

    return file_put_contents($configFile, $content) !== false;
}

$error = '';
$success = '';
$initialConnError = '';
$alreadyInstalled = false;

// Default inputs pre-populated from current config constants or POST
$inputDbHost      = trim($_POST['db_host'] ?? (defined('DB_HOST') ? DB_HOST : '127.0.0.1'));
$inputDbPort      = (int)($_POST['db_port'] ?? (defined('DB_PORT') ? DB_PORT : 3306));
$inputDbUser      = trim($_POST['db_user'] ?? (defined('DB_USER') ? DB_USER : 'root'));
$inputDbPass      = $_POST['db_pass'] ?? (defined('DB_PASS') ? DB_PASS : '');
$inputDbName      = trim($_POST['db_name'] ?? (defined('DB_NAME') ? DB_NAME : 'unithrift_db'));

$inputAdminName   = trim($_POST['admin_name'] ?? 'Admin Student');
$inputStudentId   = trim($_POST['student_id'] ?? '2021000000001');
$inputAdminEmail  = trim($_POST['admin_email'] ?? 'admin@seu.edu.bd');
$inputAdminPhone  = trim($_POST['admin_phone'] ?? '01711223344');
$inputAdminPass   = $_POST['admin_pass'] ?? 'admin123';
$inputSeedSamples = isset($_POST['seed_samples']) || ($_SERVER['REQUEST_METHOD'] !== 'POST');

// Check current installation status (unless user explicitly requests reconfigure)
if (!isset($_GET['reconfigure']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $testPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        $hasUsers = $testPdo->query("SHOW TABLES LIKE 'users'")->fetch();
        $hasItems = $testPdo->query("SHOW TABLES LIKE 'items'")->fetch();
        if ($hasUsers && $hasItems) {
            $alreadyInstalled = true;
        }
    } catch (PDOException $e) {
        $initialConnError = $e->getMessage();
    }
}

// Handle Installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    if (empty($inputDbHost) || empty($inputDbUser) || empty($inputDbName)) {
        $error = "Please fill in Database Host, Database Username, and Database Name.";
    } elseif (empty($inputAdminName) || empty($inputStudentId) || empty($inputAdminEmail) || empty($inputAdminPass)) {
        $error = "Please fill in all required administrator account fields.";
    } else {
        try {
            $targetPdo = null;
            $dbCreatedNote = "";

            // Step A: Attempt server connection to create database if permitted
            try {
                $serverPdo = new PDO("mysql:host={$inputDbHost};port={$inputDbPort}", $inputDbUser, $inputDbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 6
                ]);
                
                // Attempt to create database if not exists
                try {
                    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$inputDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $dbCreatedNote = "Database `{$inputDbName}` was verified / created.";
                } catch (PDOException $createEx) {
                    // Shared hosting user might not have global CREATE DATABASE privilege, which is okay if the DB was already created in cPanel.
                }

                $serverPdo->exec("USE `{$inputDbName}`;");
                $targetPdo = $serverPdo;

            } catch (PDOException $eServer) {
                // Step B: Direct connection to target database (typical for shared hosts like InfinityFree)
                try {
                    $targetPdo = new PDO("mysql:host={$inputDbHost};port={$inputDbPort};dbname={$inputDbName};charset=utf8mb4", $inputDbUser, $inputDbPass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 6
                    ]);
                    $dbCreatedNote = "Connected directly to database `{$inputDbName}`.";
                } catch (PDOException $eDb) {
                    throw new Exception("Could not connect to MySQL server or database '{$inputDbName}'. Error: " . $eDb->getMessage());
                }
            }

            // Step C: Update config/config.php file
            $configUpdated = update_config_file($inputDbHost, $inputDbUser, $inputDbPass, $inputDbName, $inputDbPort);

            // Step D: Create Tables
            // 1. Users Table
            $targetPdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `student_id` VARCHAR(30) NOT NULL UNIQUE,
                    `full_name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(100) NOT NULL UNIQUE,
                    `phone` VARCHAR(25) NOT NULL,
                    `department` VARCHAR(50) DEFAULT 'CSE',
                    `role` VARCHAR(20) DEFAULT 'student',
                    `password_hash` VARCHAR(255) NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 2. Items Table
            $targetPdo->exec("
                CREATE TABLE IF NOT EXISTS `items` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT NOT NULL,
                    `title` VARCHAR(150) NOT NULL,
                    `category` ENUM('Textbooks', 'Lab Gear & Kits', 'Drawing & Tools', 'Electronics & Calculators', 'Other') NOT NULL,
                    `course_code` VARCHAR(20) DEFAULT NULL,
                    `item_condition` ENUM('Like New', 'Gently Used', 'Fair') NOT NULL,
                    `original_price` DECIMAL(10,2) NOT NULL,
                    `selling_price` DECIMAL(10,2) NOT NULL,
                    `description` TEXT NOT NULL,
                    `meetup_location` VARCHAR(120) NOT NULL,
                    `image_icon` VARCHAR(10) DEFAULT '📦',
                    `image_url` VARCHAR(255) DEFAULT NULL,
                    `status` ENUM('Available', 'Reserved', 'Sold') DEFAULT 'Available',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 3. Reports Table (Moderation)
            $targetPdo->exec("
                CREATE TABLE IF NOT EXISTS `reports` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `item_id` INT NOT NULL,
                    `reporter_id` INT DEFAULT NULL,
                    `reason` VARCHAR(100) NOT NULL,
                    `details` TEXT NOT NULL,
                    `status` ENUM('Pending', 'Reviewed', 'Ignored') DEFAULT 'Pending',
                    `admin_notes` TEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 4. Notifications Table
            $targetPdo->exec("
                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT DEFAULT NULL,
                    `item_id` INT DEFAULT NULL,
                    `title` VARCHAR(150) NOT NULL,
                    `message` TEXT NOT NULL,
                    `target_audience` VARCHAR(30) DEFAULT 'user',
                    `alert_type` VARCHAR(20) DEFAULT 'info',
                    `is_read` TINYINT(1) DEFAULT 0,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 5. Contact Messages Table
            $targetPdo->exec("
                CREATE TABLE IF NOT EXISTS `contact_messages` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(100) NOT NULL,
                    `phone` VARCHAR(30) DEFAULT NULL,
                    `subject` VARCHAR(150) NOT NULL,
                    `message` TEXT NOT NULL,
                    `status` ENUM('unread', 'read') DEFAULT 'unread',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Step E: Create Admin / Primary Account
            $hashedPass = password_hash($inputAdminPass, PASSWORD_BCRYPT);
            $userStmt = $targetPdo->prepare("
                INSERT INTO `users` (`student_id`, `full_name`, `email`, `phone`, `department`, `role`, `password_hash`)
                VALUES (:student_id, :full_name, :email, :phone, 'CSE', 'admin', :password_hash)
                ON DUPLICATE KEY UPDATE `password_hash` = :password_hash_update, `role` = 'admin', `full_name` = :full_name_update
            ");
            $userStmt->execute([
                ':student_id'            => $inputStudentId,
                ':full_name'             => $inputAdminName,
                ':email'                 => $inputAdminEmail,
                ':phone'                 => $inputAdminPhone,
                ':password_hash'         => $hashedPass,
                ':password_hash_update'  => $hashedPass,
                ':full_name_update'      => $inputAdminName
            ]);

            $adminUserId = $targetPdo->lastInsertId();
            if (!$adminUserId) {
                $getIdStmt = $targetPdo->prepare("SELECT id FROM users WHERE email = :email");
                $getIdStmt->execute([':email' => $inputAdminEmail]);
                $adminUserId = $getIdStmt->fetchColumn();
            }

            // Step F: Seed Sample Data (if requested and items table is empty)
            $existingItemCount = (int)$targetPdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
            if ($inputSeedSamples && $existingItemCount === 0) {
                $samplePass = password_hash('student123', PASSWORD_BCRYPT);
                $sampleStudents = [
                    'tanvir.cse@seu.edu.bd'  => ['2021100000145', 'Tanvir Ahmed', 'tanvir.cse@seu.edu.bd', '01811223344', 'CSE'],
                    'nusrat.eee@seu.edu.bd'  => ['2021200000098', 'Nusrat Jahan', 'nusrat.eee@seu.edu.bd', '01911445566', 'EEE'],
                    'sabbir.arc@seu.edu.bd'  => ['2021100000312', 'Sabbir Hossain', 'sabbir.arc@seu.edu.bd', '01722556677', 'Architecture']
                ];

                $studStmt = $targetPdo->prepare("
                    INSERT INTO `users` (`student_id`, `full_name`, `email`, `phone`, `department`, `role`, `password_hash`)
                    VALUES (?, ?, ?, ?, ?, 'student', ?)
                    ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`)
                ");
                $studentIds = [];
                foreach ($sampleStudents as $email => $s) {
                    $studStmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $samplePass]);
                    $sId = $targetPdo->lastInsertId();
                    if (!$sId) {
                        $fStmt = $targetPdo->prepare("SELECT id FROM users WHERE email = ?");
                        $fStmt->execute([$email]);
                        $sId = $fStmt->fetchColumn();
                    }
                    $studentIds[$email] = (int)$sId;
                }

                $sampleItems = [
                    [
                        'seller'          => $studentIds['tanvir.cse@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'Introduction to Algorithms (CLRS 3rd Edition)',
                        'category'        => 'Textbooks',
                        'course_code'     => 'CSE 311',
                        'item_condition'  => 'Gently Used',
                        'original_price'  => 1200.00,
                        'selling_price'   => 450.00,
                        'description'     => 'Standard algorithms textbook required for CSE 311. Includes clean pages, all key chapter markers intact, and zero pencil marks on exercise sections.',
                        'meetup_location' => 'SEU Main Cafeteria or Library Ground Floor',
                        'image_icon'      => '📚',
                        'image_url'       => 'assets/uploads/clrs_algorithms.jpg'
                    ],
                    [
                        'seller'          => $studentIds['nusrat.eee@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'Arduino Uno R3 + Sensor Starter Kit (16 Sensors + Cables)',
                        'category'        => 'Lab Gear & Kits',
                        'course_code'     => 'CSE 316',
                        'item_condition'  => 'Like New',
                        'original_price'  => 2800.00,
                        'selling_price'   => 1250.00,
                        'description'     => 'Complete kit used for Microprocessor & Interfacing lab. Contains Arduino Uno microcontroller board, ultrasonic sensor, IR sensor, servo motor, and 65x jumper wires.',
                        'meetup_location' => 'CSE Hardware Lab 4, 5th Floor',
                        'image_icon'      => '🔬',
                        'image_url'       => 'assets/uploads/arduino_kit.jpg'
                    ],
                    [
                        'seller'          => $studentIds['sabbir.arc@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'Casio fx-991EX ClassWiz Scientific Calculator (Original)',
                        'category'        => 'Electronics & Calculators',
                        'course_code'     => 'MAT 101',
                        'item_condition'  => 'Like New',
                        'original_price'  => 2400.00,
                        'selling_price'   => 1100.00,
                        'description'     => 'Original natural textbook display scientific calculator with 552 functions, matrix calculation, vector and quadratic solvers. Dual solar and battery power.',
                        'meetup_location' => 'Campus Reception Lobby',
                        'image_icon'      => '🔢',
                        'image_url'       => 'assets/uploads/casio_calculator.jpg'
                    ],
                    [
                        'seller'          => $studentIds['sabbir.arc@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'Rotring Engineering Drawing Board (A2 Size) + T-Square & Set Squares',
                        'category'        => 'Drawing & Tools',
                        'course_code'     => 'ENG 103',
                        'item_condition'  => 'Gently Used',
                        'original_price'  => 3200.00,
                        'selling_price'   => 1300.00,
                        'description'     => 'Complete Engineering Graphics drafting board set with parallel motion ruler, 45/90 degree set squares, and clip locks. Essential for 1st-year engineering graphics.',
                        'meetup_location' => 'Architecture Dept Studio or Main Gate',
                        'image_icon'      => '📐',
                        'image_url'       => 'assets/uploads/drawing_tools.jpg'
                    ],
                    [
                        'seller'          => $studentIds['nusrat.eee@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'Digital Multimeter DT-830D + Test Leads & 9V Battery',
                        'category'        => 'Lab Gear & Kits',
                        'course_code'     => 'EEE 102',
                        'item_condition'  => 'Like New',
                        'original_price'  => 750.00,
                        'selling_price'   => 300.00,
                        'description'     => 'Compact digital multimeter for measuring AC/DC voltage, DC current, and resistance. Used in Basic Electrical Engineering Lab. Tested and working 100%.',
                        'meetup_location' => 'EEE Lab 2, 4th Floor',
                        'image_icon'      => '⚡',
                        'image_url'       => 'assets/uploads/multimeter.jpg'
                    ],
                    [
                        'seller'          => $studentIds['tanvir.cse@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'Database System Concepts (Silberschatz, Korth 7th Edition)',
                        'category'        => 'Textbooks',
                        'course_code'     => 'CSE 341',
                        'item_condition'  => 'Gently Used',
                        'original_price'  => 950.00,
                        'selling_price'   => 380.00,
                        'description'     => 'Must-have book for Database Management Systems. Covers relational algebra, SQL optimization, transaction management, and indexing in depth.',
                        'meetup_location' => 'Study Zone, 3rd Floor',
                        'image_icon'      => '📖',
                        'image_url'       => 'assets/uploads/db_textbook.jpg'
                    ],
                    [
                        'seller'          => $studentIds['nusrat.eee@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'Solderless Breadboard (830 Tie Points) + 4x IC 7400/7408/7432 Chips',
                        'category'        => 'Lab Gear & Kits',
                        'course_code'     => 'CSE 225',
                        'item_condition'  => 'Fair',
                        'original_price'  => 600.00,
                        'selling_price'   => 200.00,
                        'description'     => 'Digital Logic Design lab essentials. Includes standard full-size breadboard with power rails and 4 basic logic gate ICs. Ideal for DLD experiments.',
                        'meetup_location' => 'Main Gate or Canteen',
                        'image_icon'      => '💡',
                        'image_url'       => 'assets/uploads/breadboard_ic.jpg'
                    ],
                    [
                        'seller'          => $studentIds['sabbir.arc@seu.edu.bd'] ?? $adminUserId,
                        'title'           => 'USB 3.0 to Gigabit Ethernet Adapter (Aluminium Body)',
                        'category'        => 'Electronics & Calculators',
                        'course_code'     => 'CSE 411',
                        'item_condition'  => 'Like New',
                        'original_price'  => 1400.00,
                        'selling_price'   => 650.00,
                        'description'     => 'High-speed network adapter used in Computer Networks lab to connect thin laptops lacking RJ45 ports to campus LAN switch configurations.',
                        'meetup_location' => 'CSE Computer Lab 6',
                        'image_icon'      => '💻',
                        'image_url'       => 'assets/uploads/ethernet_adapter.jpg'
                    ]
                ];

                $itemStmt = $targetPdo->prepare("
                    INSERT INTO `items` 
                    (`user_id`, `title`, `category`, `course_code`, `item_condition`, `original_price`, `selling_price`, `description`, `meetup_location`, `image_icon`, `image_url`, `status`)
                    VALUES 
                    (:user_id, :title, :category, :course_code, :item_condition, :original_price, :selling_price, :description, :meetup_location, :image_icon, :image_url, 'Available')
                ");

                foreach ($sampleItems as $item) {
                    $itemStmt->execute([
                        ':user_id'         => $item['seller'],
                        ':title'           => $item['title'],
                        ':category'        => $item['category'],
                        ':course_code'     => $item['course_code'],
                        ':item_condition'  => $item['item_condition'],
                        ':original_price'  => $item['original_price'],
                        ':selling_price'   => $item['selling_price'],
                        ':description'     => $item['description'],
                        ':meetup_location' => $item['meetup_location'],
                        ':image_icon'      => $item['image_icon'],
                        ':image_url'       => $item['image_url']
                    ]);
                }

                $firstItemId = (int)$targetPdo->query("SELECT id FROM items ORDER BY id ASC LIMIT 1")->fetchColumn();
                $firstStudentId = (int)$targetPdo->query("SELECT id FROM users WHERE role = 'student' ORDER BY id ASC LIMIT 1")->fetchColumn();
                $secondStudentId = (int)$targetPdo->query("SELECT id FROM users WHERE role = 'student' ORDER BY id DESC LIMIT 1")->fetchColumn();

                if ($firstItemId && $firstStudentId) {
                    $repStmt = $targetPdo->prepare("
                        INSERT INTO `reports` (`item_id`, `reporter_id`, `reason`, `details`, `status`, `admin_notes`)
                        VALUES (?, ?, 'Misleading Information', 'The textbook edition listed is slightly different from the course syllabus requirements.', 'Pending', NULL)
                    ");
                    $repStmt->execute([$firstItemId, $secondStudentId ?: $firstStudentId]);

                    $notifStmt = $targetPdo->prepare("
                        INSERT INTO `notifications` (`user_id`, `item_id`, `title`, `message`, `is_read`)
                        VALUES (?, ?, 'Welcome to UniThrift Campus Marketplace', 'Welcome! Please adhere to our campus handover guidelines and verify condition descriptions accurately.', 0)
                    ");
                    $notifStmt->execute([$firstStudentId, $firstItemId]);
                }
            }

            $successMsg = "Database `{$inputDbName}` initialized and administrator account created successfully!";
            if ($configUpdated) {
                $successMsg .= " Your configuration has been automatically saved to `config/config.php`.";
            } else {
                $successMsg .= " Please ensure `config/config.php` has permissions to be updated.";
            }
            $success = $successMsg;
            $alreadyInstalled = true;

        } catch (Exception $e) {
            $error = "Setup failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install &amp; Setup | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .setup-container {
            max-width: 720px;
            margin: 3rem auto;
            padding: 0 1.25rem;
        }
        .setup-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.25rem;
            box-shadow: var(--shadow-xl);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .setup-steps-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-light);
            color: var(--primary);
            padding: 0.35rem 0.85rem;
            border-radius: var(--radius-full);
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        .section-header {
            font-size: 1.15rem;
            font-weight: 800;
            margin: 1.75rem 0 0.85rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
        }
        .section-header:first-of-type {
            margin-top: 0;
        }
        .hosting-tip {
            background: rgba(37, 99, 235, 0.07);
            border-left: 4px solid var(--primary);
            padding: 0.85rem 1rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--text-secondary);
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            background: var(--bg-subtle);
            padding: 0.85rem 1rem;
            border-radius: var(--radius-md);
            margin: 1.25rem 0;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container navbar">
            <div class="brand-logo">
                <span class="logo-icon">🔄</span>
                <span><?= APP_NAME ?></span>
                <span class="brand-badge">Setup</span>
            </div>
            <div class="nav-actions">
                <button id="themeToggle" class="theme-toggle-btn" title="Toggle Theme">🌙</button>
            </div>
        </div>
    </header>

    <main class="container setup-container">
        <div class="setup-card">
            <div class="setup-header">
                <div class="setup-steps-pill">⚙️ System Initialization</div>
                <h1 style="font-size: 1.85rem; margin-bottom: 0.5rem;">Database &amp; Account Setup Wizard</h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;">
                    Configure your MySQL database connection, create schema tables, and register your administrator account.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                    <span>⚠️ <?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($initialConnError) && empty($error)): ?>
                <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                    <span>⚠️ <strong>MySQL Connection Notice:</strong> <?= htmlspecialchars($initialConnError) ?>.<br>
                    Please enter your valid database credentials below to create or connect the database.</span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    <span>✅ <?= htmlspecialchars($success) ?></span>
                </div>
                <div style="text-align: center; margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <a href="auth.php" class="btn btn-primary btn-lg" style="width: 100%;">
                        🔑 Proceed to Login
                    </a>
                    <a href="index.php" class="btn btn-outline" style="width: 100%;">
                        🏪 Browse Marketplace as Guest
                    </a>
                </div>

            <?php elseif ($alreadyInstalled && !isset($_GET['reconfigure'])): ?>
                <div class="alert alert-info">
                    <span>ℹ️ Database is already configured (<code><?= htmlspecialchars(DB_NAME) ?></code>) and schema tables are ready!</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
                    <a href="index.php" class="btn btn-primary btn-lg">🚀 Launch <?= APP_NAME ?></a>
                    <a href="auth.php" class="btn btn-outline">🔑 Sign In to Your Account</a>
                    <div style="text-align: center; margin-top: 0.75rem;">
                        <a href="install.php?reconfigure=1" style="font-size: 0.85rem; color: var(--text-muted); text-decoration: underline;">
                            ⚙️ Need to reconnect or change database? Reconfigure Setup
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <form method="POST" action="install.php">
                    <input type="hidden" name="action" value="install">

                    <!-- SECTION 1: DATABASE CREDENTIALS -->
                    <div class="section-header">
                        <span>🔌</span>
                        <span>Database Connection &amp; Creation</span>
                    </div>

                    <div class="hosting-tip">
                        <strong>💡 Live Hosting / InfinityFree / cPanel Tip:</strong><br>
                        Find your MySQL credentials in your hosting Control Panel under <strong>MySQL Databases</strong>:
                        <ul style="margin: 0.35rem 0 0 1.25rem; padding: 0;">
                            <li><strong>Host:</strong> e.g. <code>sql300.infinityfree.com</code> (or <code>127.0.0.1</code> for local XAMPP)</li>
                            <li><strong>Username:</strong> e.g. <code>if0_38xxxxxx</code> (or <code>root</code> for local XAMPP)</li>
                            <li><strong>Database Name:</strong> e.g. <code>if0_38xxxxxx_unithrift</code> (or <code>unithrift_db</code> for local)</li>
                        </ul>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label class="form-label">Database Host *</label>
                            <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($inputDbHost) ?>" placeholder="e.g. 127.0.0.1 or sql300.infinityfree.com" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Port</label>
                            <input type="number" name="db_port" class="form-control" value="<?= htmlspecialchars((string)$inputDbPort) ?>" placeholder="3306" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Database Username *</label>
                            <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($inputDbUser) ?>" placeholder="e.g. root or if0_38xxxxxx" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Database Password</label>
                            <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($inputDbPass) ?>" placeholder="Leave empty if none">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Database Name *</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($inputDbName) ?>" placeholder="e.g. unithrift_db or if0_38xxxxxx_unithrift" required>
                        <small style="color: var(--text-muted); font-size: 0.8rem;">
                            The installer will connect to this database and automatically create it if permitted by your MySQL server.
                        </small>
                    </div>

                    <!-- SECTION 2: ADMINISTRATOR ACCOUNT -->
                    <div class="section-header">
                        <span>👤</span>
                        <span>Administrator / Primary Account</span>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($inputAdminName) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Student ID *</label>
                            <input type="text" name="student_id" class="form-control" value="<?= htmlspecialchars($inputStudentId) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">University Email *</label>
                            <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($inputAdminEmail) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone / WhatsApp *</label>
                            <input type="text" name="admin_phone" class="form-control" value="<?= htmlspecialchars($inputAdminPhone) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Admin Password *</label>
                        <input type="password" name="admin_pass" class="form-control" value="<?= htmlspecialchars($inputAdminPass) ?>" required>
                        <small style="color: var(--text-muted); font-size: 0.8rem;">
                            Secured using industry-standard BCRYPT password hashing.
                        </small>
                    </div>

                    <!-- SECTION 3: SAMPLES -->
                    <div class="checkbox-group">
                        <input type="checkbox" id="seed_samples" name="seed_samples" value="1" <?= $inputSeedSamples ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="seed_samples" style="font-size: 0.92rem; font-weight: 500; cursor: pointer;">
                            Pre-populate 8 academic listings (Algorithms textbook, Arduino kit, Drawing board, Casio calculator, etc.)
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.75rem;">
                        ⚡ Connect, Create Database &amp; Complete Setup
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>
