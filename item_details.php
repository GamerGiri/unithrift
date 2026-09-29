<?php
// item_details.php - Page 4: Full Specifications, Seller Info & Direct Contact Modal
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($itemId <= 0) {
    set_flash('error', 'Invalid item requested.');
    header("Location: marketplace.php");
    exit();
}

$item = null;
$relatedItems = [];

try {
    $stmt = $pdo->prepare("
        SELECT i.*, u.full_name as seller_name, u.email as seller_email, 
               u.phone as seller_phone, u.department as seller_dept, u.student_id as seller_sid
        FROM items i
        JOIN users u ON i.user_id = u.id
        WHERE i.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        set_flash('error', 'Item not found or has been removed.');
        header("Location: marketplace.php");
        exit();
    }

    // Fetch up to 3 related items in same category
    $relStmt = $pdo->prepare("
        SELECT * FROM items 
        WHERE category = :cat AND id != :id AND status = 'Available'
        ORDER BY id DESC LIMIT 3
    ");
    $relStmt->execute([':cat' => $item['category'], ':id' => $itemId]);
    $relatedItems = $relStmt->fetchAll();

} catch (PDOException $e) {
    set_flash('error', 'Database error: ' . $e->getMessage());
    header("Location: marketplace.php");
    exit();
}

$csrfToken = get_csrf_token();

// Handle Report Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'report_item') {
    require_login();
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Security verification failed.');
        header("Location: item_details.php?id={$itemId}");
        exit();
    }
    $reason = trim($_POST['reason'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $reporterId = (int)$_SESSION['user_id'];

    if (empty($reason) || empty($details)) {
        set_flash('error', 'Please select a reason and provide details.');
    } else {
        try {
            $repStmt = $pdo->prepare("
                INSERT INTO reports (item_id, reporter_id, reason, details, status)
                VALUES (:item_id, :reporter_id, :reason, :details, 'Pending')
            ");
            $repStmt->execute([
                ':item_id'     => $itemId,
                ':reporter_id' => $reporterId,
                ':reason'      => $reason,
                ':details'     => $details
            ]);
            set_flash('success', 'Thank you! Your safety report has been submitted to campus administrators for review.');
        } catch (PDOException $e) {
            set_flash('error', 'Failed to submit report: ' . $e->getMessage());
        }
    }
    header("Location: item_details.php?id={$itemId}");
    exit();
}

$pageTitle = $item['title'];
require_once __DIR__ . '/includes/header.php';

$discount = calc_discount_pct($item['original_price'], $item['selling_price']);
$savingsAmount = (float)$item['original_price'] - (float)$item['selling_price'];

// Clean phone for WhatsApp Link (Ensure BD format)
$rawPhone = preg_replace('/[^0-9]/', '', $item['seller_phone']);
if (strlen($rawPhone) === 11 && str_starts_with($rawPhone, '01')) {
    $waPhone = '88' . $rawPhone;
} else {
    $waPhone = $rawPhone;
}

$waMessage = rawurlencode("Hi {$item['seller_name']}, I found your listing \"{$item['title']}\" on UniThrift. Is it still available for campus meetup?");
$waLink = "https://wa.me/{$waPhone}?text={$waMessage}";
$isOwner = ($currentUser && $currentUser['id'] == $item['user_id']);
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 3.5rem;">

    <!-- Breadcrumb -->
    <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        <a href="index.php">Home</a> &gt; 
        <a href="marketplace.php">Marketplace</a> &gt; 
        <a href="marketplace.php?category=<?= urlencode($item['category']) ?>"><?= htmlspecialchars($item['category']) ?></a> &gt; 
        <span style="color: var(--text-primary); font-weight: 600;"><?= htmlspecialchars($item['title']) ?></span>
    </div>

    <!-- Main Detail Layout -->
    <div class="detail-layout">
        
        <!-- Left: Image Preview Box -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div class="detail-img-box" style="overflow: hidden; padding: 0;">
                <?php $imgSrc = get_item_image($item['image_url'] ?? null); ?>
                <?php if ($imgSrc): ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['title']) ?>" style="width: 100%; height: 100%; min-height: 380px; max-height: 480px; object-fit: contain; background: var(--bg-subtle);">
                <?php else: ?>
                    <span><?= htmlspecialchars($item['image_icon'] ?? '📦') ?></span>
                <?php endif; ?>
            </div>

            <!-- Handover Safety Checklist Card -->
            <div style="background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                    <span>🛡️</span> Campus Handover Tips
                </h4>
                <ul style="font-size: 0.85rem; color: var(--text-secondary); padding-left: 1.25rem; line-height: 1.5; margin-bottom: 0.75rem;">
                    <li>Meet inside the university campus (Cafeteria, Library, Lab).</li>
                    <li>Inspect book pages, circuits, or calculator keys before paying.</li>
                    <li>Support safe peer-to-peer student transactions.</li>
                </ul>
                <div style="border-top: 1px dashed var(--border-color); padding-top: 0.75rem; text-align: center;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="openModal('reportModal')" style="font-size: 0.8rem; color: var(--danger); border-color: var(--border-color); width: 100%;">
                        🚩 Report this Listing
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: Specifications & Direct Contact -->
        <div class="detail-info">
            
            <div class="card-tags" style="margin-bottom: 0.75rem;">
                <span class="card-badge-category" style="position: static;"><?= htmlspecialchars($item['category']) ?></span>
                <?php if (!empty($item['course_code'])): ?>
                    <span class="tag-course" style="font-size: 0.82rem; padding: 4px 10px;"><?= htmlspecialchars($item['course_code']) ?></span>
                <?php endif; ?>
                <span class="tag-condition" style="font-size: 0.82rem; padding: 4px 10px;"><?= htmlspecialchars($item['item_condition']) ?></span>
                
                <?php if ($item['status'] === 'Available'): ?>
                    <span class="status-available" style="font-size: 0.82rem; padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 700;">Available</span>
                <?php elseif ($item['status'] === 'Reserved'): ?>
                    <span class="status-reserved" style="font-size: 0.82rem; padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 700;">Reserved</span>
                <?php else: ?>
                    <span class="status-sold" style="font-size: 0.82rem; padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 700;">Sold</span>
                <?php endif; ?>
            </div>

            <h1 style="font-size: 2rem; font-weight: 800; line-height: 1.25; margin-bottom: 1rem;">
                <?= htmlspecialchars($item['title']) ?>
            </h1>

            <!-- Pricing Box -->
            <div class="detail-price-box">
                <div>
                    <span style="font-size: 0.85rem; color: var(--text-muted); display: block;">Student Resale Price</span>
                    <span style="font-size: 2.2rem; font-weight: 800; color: var(--accent);">
                        <?= format_price($item['selling_price']) ?>
                    </span>
                    <?php if ($savingsAmount > 0): ?>
                        <span style="text-decoration: line-through; color: var(--text-muted); font-size: 1.1rem; margin-left: 0.5rem;">
                            <?= format_price($item['original_price']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($discount > 0): ?>
                    <div style="text-align: right;">
                        <span class="card-badge-discount" style="position: static; font-size: 0.95rem; padding: 6px 12px;">
                            <?= $discount ?>% OFF
                        </span>
                        <div style="font-size: 0.82rem; color: var(--accent); font-weight: 600; margin-top: 4px;">
                            Save <?= format_price($savingsAmount) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.5rem;">Item Description &amp; Details</h3>
                <p style="color: var(--text-secondary); line-height: 1.7; white-space: pre-line;">
                    <?= htmlspecialchars($item['description']) ?>
                </p>
            </div>

            <!-- Campus Meetup Location -->
            <div style="display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1rem; background: var(--bg-subtle); border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid var(--border-color);">
                <span style="font-size: 1.3rem;">📍</span>
                <div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PREFERRED CAMPUS MEETUP LOCATION</div>
                    <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($item['meetup_location']) ?></div>
                </div>
            </div>

            <!-- Seller Profile & Contact Box -->
            <div class="seller-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.85rem;">
                        <div class="user-avatar-sm" style="width: 44px; height: 44px; font-size: 1.1rem;">
                            <?= strtoupper(substr($item['seller_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight: 800; font-size: 1.05rem;"><?= htmlspecialchars($item['seller_name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">
                                Dept. of <?= htmlspecialchars($item['seller_dept']) ?> &bull; Student ID: <?= htmlspecialchars($item['seller_sid']) ?>
                            </div>
                        </div>
                    </div>
                    <span style="font-size: 0.82rem; background: var(--primary-light); color: var(--primary); padding: 4px 10px; border-radius: var(--radius-full); font-weight: 700;">
                        Verified Student
                    </span>
                </div>

                <!-- Action Buttons -->
                <?php if ($isOwner): ?>
                    <div style="background: var(--primary-light); padding: 1rem; border-radius: var(--radius-md); text-align: center; border: 1px solid var(--primary-border);">
                        <p style="font-size: 0.9rem; font-weight: 600; color: var(--primary); margin-bottom: 0.75rem;">
                            ℹ️ This is your listing.
                        </p>
                        <a href="my_listings.php" class="btn btn-primary" style="width: 100%;">
                            ✏️ Edit or Change Status in My Listings
                        </a>
                    </div>
                <?php elseif ($item['status'] !== 'Available'): ?>
                    <div style="padding: 1rem; background: var(--bg-subtle); border-radius: var(--radius-md); text-align: center; border: 1px solid var(--border-color);">
                        <span style="font-weight: 700; color: var(--text-muted);">
                            This item is currently marked as <?= strtoupper($item['status']) ?>.
                        </span>
                    </div>
                <?php else: ?>
                    <div style="display: flex; gap: 0.85rem; flex-wrap: wrap;">
                        <a href="<?= htmlspecialchars($waLink) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-accent btn-lg" style="flex: 1; min-width: 180px;">
                            💬 WhatsApp Seller
                        </a>
                        <a href="tel:<?= htmlspecialchars($item['seller_phone']) ?>" class="btn btn-outline btn-lg" style="flex: 1; min-width: 180px;">
                            📞 Call <?= htmlspecialchars($item['seller_phone']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- Related Listings in Same Category -->
    <?php if (!empty($relatedItems)): ?>
        <div style="margin-top: 3.5rem;">
            <div class="section-header" style="margin-bottom: 1.5rem;">
                <div>
                    <h3 class="section-title" style="font-size: 1.4rem;">More from <?= htmlspecialchars($item['category']) ?></h3>
                    <p class="section-desc">Other students selling in this category</p>
                </div>
            </div>

            <div class="items-grid">
                <?php foreach ($relatedItems as $rel): ?>
                    <?php $relDiscount = calc_discount_pct($rel['original_price'], $rel['selling_price']); ?>
                    <div class="item-card">
                        <div class="card-img-wrap" style="height: 150px;">
                            <span class="card-img-placeholder" style="font-size: 2.5rem;"><?= htmlspecialchars($rel['image_icon'] ?? '📦') ?></span>
                            <?php if ($relDiscount > 0): ?>
                                <span class="card-badge-discount"><?= $relDiscount ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title" style="font-size: 0.98rem;">
                                <a href="item_details.php?id=<?= (int)$rel['id'] ?>" style="color: inherit;">
                                    <?= htmlspecialchars($rel['title']) ?>
                                </a>
                            </h4>
                            <div class="card-price-row">
                                <span class="price-current" style="font-size: 1.15rem;"><?= format_price($rel['selling_price']) ?></span>
                            </div>
                            <div class="card-footer">
                                <span>📍 <?= htmlspecialchars($rel['meetup_location']) ?></span>
                                <a href="item_details.php?id=<?= (int)$rel['id'] ?>" class="btn btn-outline btn-sm">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Report Listing Modal -->
<div class="modal-overlay" id="reportModal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 class="modal-title" style="color: var(--danger); font-size: 1.25rem;">🚩 Report this Listing</h2>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>

        <?php if (!is_logged_in()): ?>
            <div style="text-align: center; padding: 1.5rem 0;">
                <p style="color: var(--text-secondary); margin-bottom: 1.25rem;">
                    Please log in with your university student account to submit a report to campus administrators.
                </p>
                <a href="auth.php" class="btn btn-primary">Sign In to Report</a>
            </div>
        <?php else: ?>
            <form method="POST" action="item_details.php?id=<?= (int)$item['id'] ?>">
                <input type="hidden" name="action" value="report_item">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <p style="font-size: 0.88rem; color: var(--text-secondary); margin-bottom: 1.25rem;">
                    Listing: <strong style="color: var(--text-primary);"><?= htmlspecialchars($item['title']) ?></strong><br>
                    Seller: <span style="color: var(--accent);"><?= htmlspecialchars($item['seller_name']) ?></span>
                </p>

                <div class="form-group">
                    <label class="form-label">Report Category *</label>
                    <select name="reason" class="form-control" required>
                        <option value="">-- Select issue reason --</option>
                        <option value="Misleading / Incorrect Info">Misleading or false description / price</option>
                        <option value="Damaged / Broken Gear">Damaged or broken gear claimed as working</option>
                        <option value="Inappropriate / Prohibited Item">Prohibited or non-academic item</option>
                        <option value="Suspicious / Scam Activity">Suspicious activity or suspected scam</option>
                        <option value="Duplicate / Spam Listing">Duplicate or spam listing</option>
                        <option value="Other">Other issue</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Detailed Explanation *</label>
                    <textarea name="details" class="form-control" rows="4" placeholder="Describe the issue clearly for the administrator (e.g. wrong edition, broken parts, fake specs)..." required></textarea>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Report</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
