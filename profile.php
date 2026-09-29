<?php
// profile.php - Student Profile & Mobile Number Update
$pageTitle = "My Profile";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$csrfToken = get_csrf_token();
$error = '';

// Handle Phone Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_phone') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Security token mismatch. Please try again.');
        header("Location: profile.php");
        exit();
    }

    $newPhone = trim($_POST['phone'] ?? '');

    // Validation: Phone must not be empty and reasonably formatted (8 to 20 digits/plus)
    $cleanPhone = preg_replace('/[^0-9+]/', '', $newPhone);

    if (empty($newPhone)) {
        $error = "Mobile phone number cannot be blank.";
    } elseif (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 16) {
        $error = "Please enter a valid mobile number (e.g. 01712345678).";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET phone = :phone WHERE id = :id");
            $stmt->execute([
                ':phone' => $newPhone,
                ':id'    => $userId
            ]);

            // Update session value
            $_SESSION['phone'] = $newPhone;

            set_flash('success', 'Your mobile contact number has been updated successfully.');
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $error = "Failed to update phone number: " . $e->getMessage();
        }
    }
}

// Fetch fresh user profile & stats from database
$user = null;
$stats = ['total' => 0, 'available' => 0, 'sold' => 0];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if ($user) {
        $statStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'Sold' THEN 1 ELSE 0 END) as sold
            FROM items WHERE user_id = :uid
        ");
        $statStmt->execute([':uid' => $userId]);
        $stats = $statStmt->fetch() ?: $stats;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 820px; padding-top: 2.5rem; padding-bottom: 3.5rem;">

    <!-- Profile Overview Card -->
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2.25rem; box-shadow: var(--shadow-md); margin-bottom: 2rem;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                <div class="user-avatar-sm" style="width: 64px; height: 64px; font-size: 1.75rem; font-weight: 800; border-radius: var(--radius-lg);">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
                <div>
                    <h1 style="font-size: 1.6rem; font-weight: 800; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                        <?= htmlspecialchars($user['full_name']) ?>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span style="font-size: 0.75rem; background: var(--warning); color: #000; padding: 2px 8px; border-radius: var(--radius-full); font-weight: 800;">ADMIN</span>
                        <?php else: ?>
                            <span style="font-size: 0.75rem; background: var(--accent-light); color: var(--accent); padding: 2px 8px; border-radius: var(--radius-full); font-weight: 700;">STUDENT</span>
                        <?php endif; ?>
                    </h1>
                    <p style="color: var(--text-secondary); font-size: 0.92rem;">
                        Dept. of <?= htmlspecialchars($user['department']) ?> &bull; Student ID: <strong><?= htmlspecialchars($user['student_id']) ?></strong>
                    </p>
                </div>
            </div>

            <div>
                <a href="my_listings.php" class="btn btn-outline btn-sm">
                    📦 View My Listings
                </a>
            </div>
        </div>

        <!-- Activity Counters -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--border-color);">
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary);"><?= (int)$stats['total'] ?></div>
                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Total Listings</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--border-color);">
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent);"><?= (int)$stats['available'] ?></div>
                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Active On Market</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 1.15rem; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--border-color);">
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?= (int)$stats['sold'] ?></div>
                <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Items Re-Homed / Sold</div>
            </div>
        </div>

        <!-- Notification Banner if Error -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <span>⚠️ <?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Academic Integrity Notice -->
        <div style="background: var(--primary-light); border: 1px solid var(--primary-border); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.75rem; font-size: 0.88rem; color: var(--primary); display: flex; align-items: flex-start; gap: 0.75rem;">
            <span style="font-size: 1.3rem; line-height: 1;">🔒</span>
            <div>
                <strong>Identity Integrity Protected:</strong> 
                Your Name, Student ID, Department, and University Email are permanently verified for campus peer trust. You can update your <strong>Contact / WhatsApp Mobile Number</strong> below at any time for buyer inquiries.
            </div>
        </div>

        <!-- Edit Profile Form (Mobile Number Only Editable) -->
        <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="update_phone">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" style="display: flex; justify-content: space-between;">
                        <span>Full Name</span>
                        <span style="color: var(--text-muted); font-size: 0.78rem;">🔒 Verified</span>
                    </label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" disabled style="background: var(--bg-subtle); opacity: 0.85; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: flex; justify-content: space-between;">
                        <span>Student ID</span>
                        <span style="color: var(--text-muted); font-size: 0.78rem;">🔒 Verified</span>
                    </label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['student_id']) ?>" disabled style="background: var(--bg-subtle); opacity: 0.85; cursor: not-allowed;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" style="display: flex; justify-content: space-between;">
                        <span>Department</span>
                        <span style="color: var(--text-muted); font-size: 0.78rem;">🔒 Verified</span>
                    </label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['department']) ?>" disabled style="background: var(--bg-subtle); opacity: 0.85; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label class="form-label" style="display: flex; justify-content: space-between;">
                        <span>University Email</span>
                        <span style="color: var(--text-muted); font-size: 0.78rem;">🔒 Verified</span>
                    </label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled style="background: var(--bg-subtle); opacity: 0.85; cursor: not-allowed;">
                </div>
            </div>

            <!-- EDITABLE MOBILE NUMBER FIELD -->
            <div class="form-group" style="background: var(--bg-subtle); padding: 1.25rem; border-radius: var(--radius-md); border: 2px dashed var(--accent);">
                <label class="form-label" style="font-size: 0.98rem; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 0.4rem;">
                    <span>📱</span> Mobile &amp; WhatsApp Number (Editable) *
                </label>
                <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem; flex-wrap: wrap;">
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="e.g. 01712345678" required style="flex: 1; min-width: 200px; font-weight: 600; font-size: 1.05rem;">
                    <button type="submit" class="btn btn-accent" style="padding: 0.75rem 1.5rem;">
                        💾 Save Mobile Number
                    </button>
                </div>
                <small style="display: block; margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.82rem;">
                    This number will be displayed on your item listings for 1-click WhatsApp messaging and calls.
                </small>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; font-size: 0.82rem; color: var(--text-muted);">
                <span>Member since <?= date('F d, Y', strtotime($user['created_at'])) ?></span>
                <a href="logout.php" style="color: var(--danger); font-weight: 600;">Sign Out of Account</a>
            </div>
        </form>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
