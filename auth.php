<?php
// auth.php - Page 2: Student Login & Registration
$pageTitle = "Sign In / Register";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// If already logged in, redirect to marketplace
if (is_logged_in()) {
    header("Location: marketplace.php");
    exit();
}

$loginError = '';
$registerError = '';
$activeTab = $_GET['tab'] ?? 'login';

// Handle Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $activeTab = 'login';
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $loginError = "Please enter your email or Student ID and password.";
    } else {
        try {
            // Find by Email OR Student ID using Prepared Statement
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE email = :ident OR student_id = :ident2 
                LIMIT 1
            ");
            $stmt->execute([':ident' => $identifier, ':ident2' => $identifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Password matches, regenerate session ID for session fixation protection
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['phone']      = $user['phone'];
                $_SESSION['department'] = $user['department'];
                $_SESSION['role']       = $user['role'];

                $redirectUrl = !empty($_REQUEST['redirect']) ? trim($_REQUEST['redirect']) : 'marketplace.php';
                // Sanitize redirect: only allow relative URLs without scheme or double slashes
                if (str_starts_with($redirectUrl, '/') || str_contains($redirectUrl, '://') || str_starts_with($redirectUrl, '\\')) {
                    $redirectUrl = 'marketplace.php';
                }

                set_flash('success', "Welcome back, {$user['full_name']}!");
                header("Location: {$redirectUrl}");
                exit();
            } else {
                $loginError = "Invalid credentials. Please check your email/ID and password.";
            }
        } catch (PDOException $e) {
            $loginError = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Registration POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $activeTab = 'register';
    $fullName   = trim($_POST['full_name'] ?? '');
    $studentId  = trim($_POST['student_id'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? 'CSE');
    $phone      = trim($_POST['phone'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Extract email domain
    $emailLower = strtolower($email);
    $emailDomain = substr(strrchr($emailLower, "@"), 1) ?: '';

    // Validation
    if (empty($fullName) || empty($studentId) || empty($email) || empty($phone) || empty($password)) {
        $registerError = "All required fields must be filled.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerError = "Please provide a valid university email address.";
    } elseif ($emailDomain !== 'seu.edu.bd' && !str_ends_with($emailDomain, '.seu.edu.bd')) {
        $registerError = "Public registration is strictly restricted to Southeast University emails (@seu.edu.bd). Non-SEU accounts can only be provisioned by the Administrator.";
    } elseif (strlen($password) < 6) {
        $registerError = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPass) {
        $registerError = "Passwords do not match.";
    } else {
        try {
            // Check for duplicate Student ID or Email
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR student_id = :sid LIMIT 1");
            $checkStmt->execute([':email' => $email, ':sid' => $studentId]);
            if ($checkStmt->fetch()) {
                $registerError = "A user with this Student ID or Email already exists.";
            } else {
                // Securely hash password using BCRYPT
                $hashed = password_hash($password, PASSWORD_BCRYPT);

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (student_id, full_name, email, phone, department, role, password_hash)
                    VALUES (:sid, :name, :email, :phone, :dept, 'student', :hash)
                ");
                $insertStmt->execute([
                    ':sid'   => $studentId,
                    ':name'  => $fullName,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':dept'  => $department,
                    ':hash'  => $hashed
                ]);

                $newId = $pdo->lastInsertId();

                // Auto-login newly registered student
                session_regenerate_id(true);
                $_SESSION['user_id']    = $newId;
                $_SESSION['student_id'] = $studentId;
                $_SESSION['full_name']  = $fullName;
                $_SESSION['email']      = $email;
                $_SESSION['phone']      = $phone;
                $_SESSION['department'] = $department;
                $_SESSION['role']       = 'student';

                set_flash('success', "Registration successful! Welcome to UniThrift, {$fullName}.");
                header("Location: marketplace.php");
                exit();
            }
        } catch (PDOException $e) {
            $registerError = "Registration failed: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 520px; margin: 3rem auto; padding: 0 1rem;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2.25rem; box-shadow: var(--shadow-lg);">
        
        <!-- Tab Navigation Switcher -->
        <div style="display: flex; background: var(--bg-subtle); border-radius: var(--radius-md); padding: 4px; margin-bottom: 2rem; border: 1px solid var(--border-color);">
            <button type="button" id="tabBtnLogin" onclick="switchAuthTab('login')" 
                style="flex: 1; padding: 0.65rem; border: none; border-radius: var(--radius-sm); font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: var(--transition); background: <?= $activeTab === 'login' ? 'var(--primary)' : 'transparent' ?>; color: <?= $activeTab === 'login' ? '#ffffff' : 'var(--text-secondary)' ?>;">
                Sign In
            </button>
            <button type="button" id="tabBtnRegister" onclick="switchAuthTab('register')" 
                style="flex: 1; padding: 0.65rem; border: none; border-radius: var(--radius-sm); font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: var(--transition); background: <?= $activeTab === 'register' ? 'var(--primary)' : 'transparent' ?>; color: <?= $activeTab === 'register' ? '#ffffff' : 'var(--text-secondary)' ?>;">
                Create Account
            </button>
        </div>

        <!-- 1. LOGIN FORM -->
        <div id="loginFormSection" style="display: <?= $activeTab === 'login' ? 'block' : 'none' ?>;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 0.35rem;">Student Sign In</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Access your academic listings and campus inquiries</p>
            </div>

            <?php if (!empty($loginError)): ?>
                <div class="alert alert-error">
                    <span>⚠️ <?= htmlspecialchars($loginError) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="auth.php?tab=login">
                <input type="hidden" name="action" value="login">
                <?php if (!empty($_REQUEST['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_REQUEST['redirect']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">University Email or Student ID *</label>
                    <input type="text" name="identifier" class="form-control" placeholder="e.g., 2021000000001 or student@seu.edu.bd" value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                    Sign In to UniThrift
                </button>

                <div style="text-align: center; margin-top: 1.5rem; font-size: 0.88rem; color: var(--text-secondary);">
                    Don't have an account? 
                    <a href="javascript:void(0)" onclick="switchAuthTab('register')" style="font-weight: 700;">Create one now</a>
                </div>

                <div style="margin-top: 1.5rem; padding: 0.85rem; background: var(--bg-subtle); border-radius: var(--radius-md); font-size: 0.82rem; border: 1px dashed var(--border-color); color: var(--text-muted); text-align: center;">
                    💡 Demo Account: <code>admin@seu.edu.bd</code> / <code>admin123</code>
                </div>
            </form>
        </div>

        <!-- 2. REGISTRATION FORM -->
        <div id="registerFormSection" style="display: <?= $activeTab === 'register' ? 'block' : 'none' ?>;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 0.35rem;">Student Registration</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">Join the campus academic equipment reuse network</p>
            </div>

            <?php if (!empty($registerError)): ?>
                <div class="alert alert-error">
                    <span>⚠️ <?= htmlspecialchars($registerError) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="auth.php?tab=register">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g., Md. Tanvir Hasan" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Student ID *</label>
                        <input type="text" name="student_id" class="form-control" placeholder="2021100000045" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select name="department" class="form-control">
                            <option value="CSE" <?= (($_POST['department'] ?? '') === 'CSE') ? 'selected' : '' ?>>CSE</option>
                            <option value="EEE" <?= (($_POST['department'] ?? '') === 'EEE') ? 'selected' : '' ?>>EEE</option>
                            <option value="Architecture" <?= (($_POST['department'] ?? '') === 'Architecture') ? 'selected' : '' ?>>Architecture</option>
                            <option value="BBA" <?= (($_POST['department'] ?? '') === 'BBA') ? 'selected' : '' ?>>BBA</option>
                            <option value="Pharmacy" <?= (($_POST['department'] ?? '') === 'Pharmacy') ? 'selected' : '' ?>>Pharmacy</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">University Email (@seu.edu.bd only) *</label>
                    <input type="email" name="email" class="form-control" placeholder="your.name@seu.edu.bd" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" pattern="^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)?seu\.edu\.bd$" title="Please enter your official @seu.edu.bd university email" required>
                    <small style="color: var(--text-muted); font-size: 0.78rem;">Public registration requires an official Southeast University email ending with <strong>@seu.edu.bd</strong>.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone / WhatsApp Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="01712345678" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                    <small style="color: var(--text-muted); font-size: 0.78rem;">Allows buyers to reach you for campus item handovers.</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-accent btn-lg" style="width: 100%; margin-top: 0.5rem;">
                    Create Student Account
                </button>

                <div style="text-align: center; margin-top: 1.5rem; font-size: 0.88rem; color: var(--text-secondary);">
                    Already registered? 
                    <a href="javascript:void(0)" onclick="switchAuthTab('login')" style="font-weight: 700;">Sign in here</a>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
function switchAuthTab(tab) {
    const loginSec = document.getElementById('loginFormSection');
    const regSec = document.getElementById('registerFormSection');
    const tabLogin = document.getElementById('tabBtnLogin');
    const tabReg = document.getElementById('tabBtnRegister');

    if (tab === 'login') {
        loginSec.style.display = 'block';
        regSec.style.display = 'none';
        tabLogin.style.background = 'var(--primary)';
        tabLogin.style.color = '#ffffff';
        tabReg.style.background = 'transparent';
        tabReg.style.color = 'var(--text-secondary)';
    } else {
        loginSec.style.display = 'none';
        regSec.style.display = 'block';
        tabReg.style.background = 'var(--primary)';
        tabReg.style.color = '#ffffff';
        tabLogin.style.background = 'transparent';
        tabLogin.style.color = 'var(--text-secondary)';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
