<?php
// admin.php - Administrator Control Panel & Moderation Center
$pageTitle = "Admin Control Panel";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Strict Admin Route Guard
require_admin();

$csrfToken = get_csrf_token();
$activeTab = $_GET['tab'] ?? 'listings';
$searchQuery = trim($_GET['q'] ?? '');

// =========================================================================
// 1. POST Actions: Moderation, Status Updates, User Management
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('error', 'CSRF verification failed.');
        header("Location: admin.php?tab={$activeTab}");
        exit();
    }

    // A. Admin Change Listing Status
    if ($action === 'change_item_status') {
        $itemId    = (int)($_POST['item_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'Available');
        if ($itemId > 0 && in_array($newStatus, ['Available', 'Reserved', 'Sold'])) {
            try {
                $stmt = $pdo->prepare("UPDATE items SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $itemId]);
                set_flash('success', "Listing #{$itemId} status updated to {$newStatus}.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to update status: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=listings");
        exit();
    }

    // B. Admin Delete Listing (Moderation)
    elseif ($action === 'admin_delete_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
                $stmt->execute([':id' => $itemId]);
                set_flash('success', "Listing #{$itemId} permanently removed by administrator.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to delete listing: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=listings");
        exit();
    }

    // C. Admin Toggle User Role (Student <-> Admin)
    elseif ($action === 'toggle_user_role') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newRole      = trim($_POST['role'] ?? 'student');

        // Prevent self-demotion
        if ($targetUserId === (int)$_SESSION['user_id']) {
            set_flash('error', 'You cannot change your own administrator role.');
        } elseif ($targetUserId > 0 && in_array($newRole, ['student', 'admin'])) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
                $stmt->execute([':role' => $newRole, ':id' => $targetUserId]);
                set_flash('success', "User #{$targetUserId} role updated to {$newRole}.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to update user role: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=users");
        exit();
    }

    // D. Admin Delete User Account
    elseif ($action === 'admin_delete_user') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);

        if ($targetUserId === (int)$_SESSION['user_id']) {
            set_flash('error', 'You cannot delete your own administrator account.');
        } elseif ($targetUserId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([':id' => $targetUserId]);
                set_flash('success', "User account and all associated listings removed.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to delete user: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=users");
        exit();
    }
}

// =========================================================================
// 2. Fetch System Metrics & Analytics
// =========================================================================
$totalUsers = 0;
$totalListings = 0;
$totalAvailable = 0;
$totalSold = 0;
$totalSavings = 0;
$categoryCounts = [];

try {
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalListings = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $totalAvailable = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetchColumn();
    $totalSold = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Sold'")->fetchColumn();
    $totalSavings = (float)$pdo->query("SELECT SUM(original_price - selling_price) FROM items WHERE original_price > selling_price")->fetchColumn();

    // Category distribution
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM items GROUP BY category");
    $categoryCounts = $stmt->fetchAll();

    // Department participation
    $stmt = $pdo->query("SELECT department, COUNT(*) as count FROM users GROUP BY department ORDER BY count DESC");
    $deptCounts = $stmt->fetchAll();

} catch (PDOException $e) {
    // Graceful error handle
}

// =========================================================================
// 3. Fetch Tab Data
// =========================================================================
$allListings = [];
$allUsers = [];

try {
    if ($activeTab === 'listings') {
        if (!empty($searchQuery)) {
            $stmt = $pdo->prepare("
                SELECT i.*, u.full_name as seller_name, u.student_id as seller_sid, u.phone as seller_phone
                FROM items i
                JOIN users u ON i.user_id = u.id
                WHERE i.title LIKE :q OR i.course_code LIKE :q2 OR u.full_name LIKE :q3 OR u.student_id LIKE :q4
                ORDER BY i.id DESC
            ");
            $searchTerm = "%{$searchQuery}%";
            $stmt->execute([':q' => $searchTerm, ':q2' => $searchTerm, ':q3' => $searchTerm, ':q4' => $searchTerm]);
        } else {
            $stmt = $pdo->query("
                SELECT i.*, u.full_name as seller_name, u.student_id as seller_sid, u.phone as seller_phone
                FROM items i
                JOIN users u ON i.user_id = u.id
                ORDER BY i.id DESC
            ");
        }
        $allListings = $stmt->fetchAll();
    } elseif ($activeTab === 'users') {
        $stmt = $pdo->query("
            SELECT u.*, COUNT(i.id) as item_count 
            FROM users u
            LEFT JOIN items i ON u.id = i.user_id
            GROUP BY u.id
            ORDER BY u.id DESC
        ");
        $allUsers = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Graceful error handle
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 3.5rem;">

    <!-- Admin Panel Header -->
    <div class="section-header" style="margin-bottom: 1.5rem;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--warning-light); color: var(--warning); padding: 3px 10px; border-radius: var(--radius-full); font-size: 0.78rem; font-weight: 800; margin-bottom: 0.5rem;">
                🛡️ System Administrator Mode
            </div>
            <h1 class="section-title">Administrator Command Center</h1>
            <p class="section-desc">Full platform oversight: moderate campus listings, manage student accounts, and view platform metrics</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="marketplace.php" class="btn btn-outline btn-sm">
                🏪 View Marketplace
            </a>
            <a href="my_listings.php" class="btn btn-primary btn-sm">
                ➕ Post Item as Admin
            </a>
        </div>
    </div>

    <!-- Platform KPI Overview Cards -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Registered Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= $totalListings ?></div>
            <div class="stat-label">Total Listings Posted</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🟢</div>
            <div class="stat-value"><?= $totalAvailable ?></div>
            <div class="stat-label">Active / Available</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?= $totalSold ?></div>
            <div class="stat-label">Items Re-Homed (Sold)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><?= APP_CURRENCY ?> <?= number_format($totalSavings) ?></div>
            <div class="stat-label">Total Student Savings</div>
        </div>
    </div>

    <!-- Navigation Tabs for Admin Panel -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 2px;">
        <a href="admin.php?tab=listings" class="btn <?= $activeTab === 'listings' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            📋 Listings Moderation (<?= $totalListings ?>)
        </a>
        <a href="admin.php?tab=users" class="btn <?= $activeTab === 'users' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            👥 Student Accounts (<?= $totalUsers ?>)
        </a>
        <a href="admin.php?tab=analytics" class="btn <?= $activeTab === 'analytics' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            📊 Category Analytics
        </a>
    </div>

    <!-- =====================================================================
         TAB 1: LISTINGS MODERATION
         ===================================================================== -->
    <?php if ($activeTab === 'listings'): ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <h2 style="font-size: 1.3rem; font-weight: 800;">All Campus Listings (<?= count($allListings) ?>)</h2>
            
            <!-- Admin Search Filter Form -->
            <form method="GET" action="admin.php" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="tab" value="listings">
                <input type="text" name="q" class="form-control" placeholder="Search by title, student ID, seller..." value="<?= htmlspecialchars($searchQuery) ?>" style="width: 280px; padding: 0.5rem 0.85rem;">
                <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="admin.php?tab=listings" class="btn btn-outline btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table responsive-card-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Listing Details</th>
                        <th>Seller / Student</th>
                        <th>Category</th>
                        <th>Resale Price</th>
                        <th>Status</th>
                        <th>Date Posted</th>
                        <th style="text-align: right;">Moderation Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allListings)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                No listings found matching your search.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allListings as $item): ?>
                            <tr>
                                <td data-label="Listing ID" style="font-weight: 700; color: var(--text-muted);">#<?= (int)$item['id'] ?></td>
                                <td data-label="Listing Details">
                                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                                        <span style="font-size: 1.4rem;"><?= htmlspecialchars($item['image_icon'] ?? '📦') ?></span>
                                        <div>
                                            <a href="item_details.php?id=<?= (int)$item['id'] ?>" target="_blank" style="font-weight: 700; color: var(--text-primary);">
                                                <?= htmlspecialchars($item['title']) ?> 🔗
                                            </a>
                                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                                Course: <?= htmlspecialchars($item['course_code'] ?: 'N/A') ?> &bull; Condition: <?= htmlspecialchars($item['item_condition']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Student Seller">
                                    <div style="font-weight: 600; font-size: 0.88rem;"><?= htmlspecialchars($item['seller_name']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">ID: <?= htmlspecialchars($item['seller_sid']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--accent);"><?= htmlspecialchars($item['seller_phone']) ?></div>
                                </td>
                                <td data-label="Category">
                                    <span class="card-badge-category" style="position: static; font-size: 0.72rem;"><?= htmlspecialchars($item['category']) ?></span>
                                </td>
                                <td data-label="Resale Price">
                                    <strong style="color: var(--accent);"><?= format_price($item['selling_price']) ?></strong>
                                    <div style="font-size: 0.72rem; color: var(--text-muted); text-decoration: line-through;">
                                        <?= format_price($item['original_price']) ?>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <form method="POST" action="admin.php" style="display: inline-block;">
                                        <input type="hidden" name="action" value="change_item_status">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" class="custom-select" style="font-size: 0.78rem; padding: 2px 6px;">
                                            <option value="Available" <?= $item['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                            <option value="Reserved" <?= $item['status'] === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                                            <option value="Sold" <?= $item['status'] === 'Sold' ? 'selected' : '' ?>>Sold</option>
                                        </select>
                                    </form>
                                </td>
                                <td data-label="Date Posted" style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= date('M d, Y', strtotime($item['created_at'])) ?>
                                </td>
                                <td data-label="Actions" style="text-align: right;">
                                    <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to permanently delete this listing from the marketplace as administrator?');" style="display: inline-block;">
                                        <input type="hidden" name="action" value="admin_delete_item">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Remove Prohibited/Spam Listing">
                                            🗑️ Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- =====================================================================
         TAB 2: USER MANAGEMENT
         ===================================================================== -->
    <?php elseif ($activeTab === 'users'): ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h2 style="font-size: 1.3rem; font-weight: 800;">Registered Students &amp; Administrators (<?= count($allUsers) ?>)</h2>
        </div>

        <div class="table-responsive">
            <table class="custom-table responsive-card-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name &amp; Dept</th>
                        <th>Email Address</th>
                        <th>Mobile / WhatsApp</th>
                        <th>Listings</th>
                        <th>Current Role</th>
                        <th>Joined Date</th>
                        <th style="text-align: right;">Admin Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td data-label="Student ID">
                                <strong><?= htmlspecialchars($u['student_id']) ?></strong>
                            </td>
                            <td data-label="Name &amp; Dept">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div class="user-avatar-sm" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700;"><?= htmlspecialchars($u['full_name']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($u['department']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Email">
                                <a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a>
                            </td>
                            <td data-label="Mobile / WhatsApp">
                                <strong><?= htmlspecialchars($u['phone']) ?></strong>
                            </td>
                            <td data-label="Listings">
                                <span style="background: var(--bg-subtle); padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.82rem; font-weight: 700;">
                                    <?= (int)$u['item_count'] ?> items
                                </span>
                            </td>
                            <td data-label="Current Role">
                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                    <span style="font-size: 0.8rem; background: var(--warning); color: #000; padding: 2px 8px; border-radius: var(--radius-sm); font-weight: 800;">
                                        ADMIN (You)
                                    </span>
                                <?php else: ?>
                                    <form method="POST" action="admin.php?tab=users" style="display: inline-block;">
                                        <input type="hidden" name="action" value="toggle_user_role">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <select name="role" onchange="this.form.submit()" class="custom-select" style="font-size: 0.78rem; padding: 2px 6px;">
                                            <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td data-label="Joined Date" style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= date('M d, Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td data-label="Actions" style="text-align: right;">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="admin.php?tab=users" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['full_name'])) ?>? This will delete all items posted by this student.');" style="display: inline-block;">
                                        <input type="hidden" name="action" value="admin_delete_user">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            🗑️ Remove User
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">Active Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <!-- =====================================================================
         TAB 3: ANALYTICS & CATEGORY BREAKDOWN
         ===================================================================== -->
    <?php elseif ($activeTab === 'analytics'): ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem;">Marketplace Category Distribution</h3>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($categoryCounts as $cat): ?>
                        <?php 
                            $pct = $totalListings > 0 ? round(($cat['count'] / $totalListings) * 100) : 0; 
                        ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.92rem; font-weight: 600; margin-bottom: 0.35rem;">
                                <span><?= htmlspecialchars($cat['category']) ?></span>
                                <span><?= (int)$cat['count'] ?> items (<?= $pct ?>%)</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--bg-subtle); border-radius: var(--radius-full); overflow: hidden;">
                                <div style="width: <?= $pct ?>%; height: 100%; background: var(--primary); border-radius: var(--radius-full);"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem;">Student Department Participation</h3>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($deptCounts as $dept): ?>
                        <?php 
                            $pct = $totalUsers > 0 ? round(($dept['count'] / $totalUsers) * 100) : 0; 
                        ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.92rem; font-weight: 600; margin-bottom: 0.35rem;">
                                <span>Department of <?= htmlspecialchars($dept['department']) ?></span>
                                <span><?= (int)$dept['count'] ?> students (<?= $pct ?>%)</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--bg-subtle); border-radius: var(--radius-full); overflow: hidden;">
                                <div style="width: <?= $pct ?>%; height: 100%; background: var(--accent); border-radius: var(--radius-full);"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
