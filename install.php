<?php
// install.php
// UniThrift - Fresh Installation & Database Setup Wizard

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_check.php';

$error = '';
$success = '';
$alreadyInstalled = false;

// Check current installation status
try {
    $testPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check if unithrift_db exists
    $stmt = $testPdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->fetch()) {
        $dbPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $hasUsers = $dbPdo->query("SHOW TABLES LIKE 'users'")->fetch();
        $hasItems = $dbPdo->query("SHOW TABLES LIKE 'items'")->fetch();
        if ($hasUsers && $hasItems) {
            $alreadyInstalled = true;
        }
    }
} catch (PDOException $e) {
    $error = "Could not connect to MySQL server: " . $e->getMessage() . ". Please ensure MySQL is running.";
}

// Handle Installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    $adminName   = trim($_POST['admin_name'] ?? 'Admin Student');
    $studentId   = trim($_POST['student_id'] ?? '2021000000001');
    $adminEmail  = trim($_POST['admin_email'] ?? 'admin@seu.edu.bd');
    $adminPass   = $_POST['admin_pass'] ?? 'admin123';
    $adminPhone  = trim($_POST['admin_phone'] ?? '01700000000');
    $loadSamples = isset($_POST['seed_samples']);

    if (empty($adminName) || empty($studentId) || empty($adminEmail) || empty($adminPass)) {
        $error = "Please fill in all required administrator account fields.";
    } else {
        try {
            $rootPdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // 1. Create Database
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $rootPdo->exec("USE `" . DB_NAME . "`;");

            // 2. Create Users Table
            $rootPdo->exec("
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

            // 3. Create Items Table (Main CRUD Entity)
            $rootPdo->exec("
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

            // 4. Create Admin / Primary Account
            $hashedPass = password_hash($adminPass, PASSWORD_BCRYPT);
            $userStmt = $rootPdo->prepare("
                INSERT INTO `users` (`student_id`, `full_name`, `email`, `phone`, `department`, `role`, `password_hash`)
                VALUES (:student_id, :full_name, :email, :phone, 'CSE', 'admin', :password_hash)
                ON DUPLICATE KEY UPDATE `password_hash` = :password_hash_update
            ");
            $userStmt->execute([
                ':student_id'            => $studentId,
                ':full_name'             => $adminName,
                ':email'                 => $adminEmail,
                ':phone'                 => $adminPhone,
                ':password_hash'         => $hashedPass,
                ':password_hash_update'  => $hashedPass
            ]);

            $adminUserId = $rootPdo->lastInsertId();
            if (!$adminUserId) {
                // If it was duplicate key, fetch id
                $getIdStmt = $rootPdo->prepare("SELECT id FROM users WHERE email = :email");
                $getIdStmt->execute([':email' => $adminEmail]);
                $adminUserId = $getIdStmt->fetchColumn();
            }

            // 5. Seed Pre-Loaded Academic Sample Data if checked
            if ($loadSamples) {
                $sampleItems = [
                    [
                        'title'           => 'Introduction to Algorithms (CLRS 3rd Edition)',
                        'category'        => 'Textbooks',
                        'course_code'     => 'CSE 311',
                        'item_condition'  => 'Gently Used',
                        'original_price'  => 1200.00,
                        'selling_price'   => 450.00,
                        'description'     => 'Standard algorithms textbook required for CSE 311. Includes clean pages, all key chapter markers intact, and zero pencil marks on exercise sections.',
                        'meetup_location' => 'SEU Main Cafeteria or Library Ground Floor',
                        'image_icon'      => '📚'
                    ],
                    [
                        'title'           => 'Arduino Uno R3 + Sensor Starter Kit (16 Sensors + Cables)',
                        'category'        => 'Lab Gear & Kits',
                        'course_code'     => 'CSE 316',
                        'item_condition'  => 'Like New',
                        'original_price'  => 2800.00,
                        'selling_price'   => 1250.00,
                        'description'     => 'Complete kit used for Microprocessor & Interfacing lab. Contains Arduino Uno microcontroller board, ultrasonic sensor, IR sensor, servo motor, and 65x jumper wires.',
                        'meetup_location' => 'CSE Hardware Lab 4, 5th Floor',
                        'image_icon'      => '🔬'
                    ],
                    [
                        'title'           => 'Casio fx-991EX ClassWiz Scientific Calculator (Original)',
                        'category'        => 'Electronics & Calculators',
                        'course_code'     => 'MAT 101',
                        'item_condition'  => 'Like New',
                        'original_price'  => 2400.00,
                        'selling_price'   => 1100.00,
                        'description'     => 'Original natural textbook display scientific calculator with 552 functions, matrix calculation, vector and quadratic solvers. Dual solar and battery power.',
                        'meetup_location' => 'Campus Reception Lobby',
                        'image_icon'      => '🔢'
                    ],
                    [
                        'title'           => 'Rotring Engineering Drawing Board (A2 Size) + T-Square & Set Squares',
                        'category'        => 'Drawing & Tools',
                        'course_code'     => 'ENG 103',
                        'item_condition'  => 'Gently Used',
                        'original_price'  => 3200.00,
                        'selling_price'   => 1300.00,
                        'description'     => 'Complete Engineering Graphics drafting board set with parallel motion ruler, 45/90 degree set squares, and clip locks. Essential for 1st-year engineering graphics.',
                        'meetup_location' => 'Architecture Dept Studio or Main Gate',
                        'image_icon'      => '📐'
                    ],
                    [
                        'title'           => 'Digital Multimeter DT-830D + Test Leads & 9V Battery',
                        'category'        => 'Lab Gear & Kits',
                        'course_code'     => 'EEE 102',
                        'item_condition'  => 'Like New',
                        'original_price'  => 750.00,
                        'selling_price'   => 300.00,
                        'description'     => 'Compact digital multimeter for measuring AC/DC voltage, DC current, and resistance. Used in Basic Electrical Engineering Lab. Tested and working 100%.',
                        'meetup_location' => 'EEE Lab 2, 4th Floor',
                        'image_icon'      => '⚡'
                    ],
                    [
                        'title'           => 'Database System Concepts (Silberschatz, Korth 7th Edition)',
                        'category'        => 'Textbooks',
                        'course_code'     => 'CSE 341',
                        'item_condition'  => 'Gently Used',
                        'original_price'  => 950.00,
                        'selling_price'   => 380.00,
                        'description'     => 'Must-have book for Database Management Systems. Covers relational algebra, SQL optimization, transaction management, and indexing in depth.',
                        'meetup_location' => 'Study Zone, 3rd Floor',
                        'image_icon'      => '📖'
                    ],
                    [
                        'title'           => 'Solderless Breadboard (830 Tie Points) + 4x IC 7400/7408/7432 Chips',
                        'category'        => 'Lab Gear & Kits',
                        'course_code'     => 'CSE 225',
                        'item_condition'  => 'Fair',
                        'original_price'  => 600.00,
                        'selling_price'   => 200.00,
                        'description'     => 'Digital Logic Design lab essentials. Includes standard full-size breadboard with power rails and 4 basic logic gate ICs. Ideal for DLD experiments.',
                        'meetup_location' => 'Main Gate or Canteen',
                        'image_icon'      => '💡'
                    ],
                    [
                        'title'           => 'USB 3.0 to Gigabit Ethernet Adapter (Aluminium Body)',
                        'category'        => 'Electronics & Calculators',
                        'course_code'     => 'CSE 411',
                        'item_condition'  => 'Like New',
                        'original_price'  => 1400.00,
                        'selling_price'   => 650.00,
                        'description'     => 'High-speed network adapter used in Computer Networks lab to connect thin laptops lacking RJ45 ports to campus LAN switch configurations.',
                        'meetup_location' => 'CSE Computer Lab 6',
                        'image_icon'      => '💻'
                    ]
                ];

                $itemStmt = $rootPdo->prepare("
                    INSERT INTO `items` 
                    (`user_id`, `title`, `category`, `course_code`, `item_condition`, `original_price`, `selling_price`, `description`, `meetup_location`, `image_icon`, `status`)
                    VALUES 
                    (:user_id, :title, :category, :course_code, :item_condition, :original_price, :selling_price, :description, :meetup_location, :image_icon, 'Available')
                ");

                foreach ($sampleItems as $item) {
                    $itemStmt->execute([
                        ':user_id'         => $adminUserId,
                        ':title'           => $item['title'],
                        ':category'        => $item['category'],
                        ':course_code'     => $item['course_code'],
                        ':item_condition'  => $item['item_condition'],
                        ':original_price'  => $item['original_price'],
                        ':selling_price'   => $item['selling_price'],
                        ':description'     => $item['description'],
                        ':meetup_location' => $item['meetup_location'],
                        ':image_icon'      => $item['image_icon']
                    ]);
                }
            }

            $success = "Database initialized and administrator account created successfully! You can now log in.";
            $alreadyInstalled = true;

        } catch (PDOException $e) {
            $error = "Installation failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install & Setup | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .setup-container {
            max-width: 680px;
            margin: 3.5rem auto;
            padding: 0 1.25rem;
        }
        .setup-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
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
                <h1 style="font-size: 1.85rem; margin-bottom: 0.5rem;">Fresh Installation Wizard</h1>
                <p style="color: var(--text-secondary); font-size: 0.95rem;">
                    Configure your MySQL database (<code><?= DB_NAME ?></code>) and create your administrator/student demo account.
                </p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <span>⚠️ <?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✅ <?= htmlspecialchars($success) ?></span>
                </div>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="auth.php" class="btn btn-primary btn-lg" style="width: 100%;">
                        🔑 Proceed to Login
                    </a>
                    <div style="margin-top: 0.85rem;">
                        <a href="index.php" style="font-size: 0.9rem;">or browse Marketplace as Guest</a>
                    </div>
                </div>
            <?php elseif ($alreadyInstalled): ?>
                <div class="alert alert-info">
                    <span>ℹ️ Database is already configured and tables are ready!</span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.5rem;">
                    <a href="index.php" class="btn btn-primary btn-lg">🚀 Launch <?= APP_NAME ?></a>
                    <a href="auth.php" class="btn btn-outline">🔑 Sign In to Your Account</a>
                </div>
            <?php else: ?>
                <form method="POST" action="install.php">
                    <input type="hidden" name="action" value="install">

                    <h3 style="font-size: 1.1rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                        👤 Administrator / Primary Account
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="admin_name" class="form-control" value="Admin Student" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Student ID *</label>
                            <input type="text" name="student_id" class="form-control" value="2021000000001" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">University Email *</label>
                            <input type="email" name="admin_email" class="form-control" value="admin@seu.edu.bd" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone / WhatsApp *</label>
                            <input type="text" name="admin_phone" class="form-control" value="01711223344" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="admin_pass" class="form-control" value="admin123" required>
                        <small style="color: var(--text-muted); font-size: 0.8rem;">
                            Default: <code>admin123</code> (secured using BCRYPT hashing).
                        </small>
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="seed_samples" name="seed_samples" value="1" checked style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="seed_samples" style="font-size: 0.92rem; font-weight: 500; cursor: pointer;">
                            Pre-populate 8 academic listings (Algorithms textbook, Arduino kit, Drawing board, Casio calculator, etc.)
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                        ⚡ Initialize Database & Complete Setup
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script src="assets/js/main.js"></script>
</body>
</html>
