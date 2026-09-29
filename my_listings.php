<?php
// my_listings.php - Page 5: Core CRUD Management Studio
$pageTitle = "My Listings (CRUD Studio)";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Route Guard
require_login();

$userId = (int)$_SESSION['user_id'];
$csrfToken = get_csrf_token();
$error = '';

// Handle POST Actions: Create, Update, Delete, Status Toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('error', 'Security token mismatch. Please try again.');
        header("Location: my_listings.php");
        exit();
    }

    // ==========================================
    // 1. CREATE ITEM (C)
    // ==========================================
    if ($action === 'create') {
        $title          = trim($_POST['title'] ?? '');
        $category       = trim($_POST['category'] ?? 'Textbooks');
        $courseCode     = strtoupper(trim($_POST['course_code'] ?? ''));
        $condition      = trim($_POST['item_condition'] ?? 'Gently Used');
        $origPrice      = (float)($_POST['original_price'] ?? 0);
        $sellPrice      = (float)($_POST['selling_price'] ?? 0);
        $description    = trim($_POST['description'] ?? '');
        $meetupLocation = trim($_POST['meetup_location'] ?? 'SEU Main Cafeteria');
        $icon           = trim($_POST['image_icon'] ?? '📦');

        if (empty($title) || empty($description) || empty($meetupLocation)) {
            set_flash('error', 'Title, description, and meetup location are required.');
        } elseif ($sellPrice <= 0) {
            set_flash('error', 'Selling price must be greater than zero.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO items 
                    (user_id, title, category, course_code, item_condition, original_price, selling_price, description, meetup_location, image_icon, status)
                    VALUES 
                    (:uid, :title, :category, :course, :condition, :orig, :sell, :desc, :meetup, :icon, 'Available')
                ");
                $stmt->execute([
                    ':uid'       => $userId,
                    ':title'     => $title,
                    ':category'  => $category,
                    ':course'    => $courseCode ?: null,
                    ':condition' => $condition,
                    ':orig'      => ($origPrice > 0 ? $origPrice : $sellPrice),
                    ':sell'      => $sellPrice,
                    ':desc'      => $description,
                    ':meetup'    => $meetupLocation,
                    ':icon'      => $icon
                ]);

                set_flash('success', 'Your academic item has been listed successfully!');
                header("Location: my_listings.php");
                exit();
            } catch (PDOException $e) {
                set_flash('error', 'Failed to create item: ' . $e->getMessage());
            }
        }
    }

    // ==========================================
    // 2. UPDATE ITEM (U)
    // ==========================================
    elseif ($action === 'update') {
        $itemId         = (int)($_POST['item_id'] ?? 0);
        $title          = trim($_POST['title'] ?? '');
        $category       = trim($_POST['category'] ?? 'Textbooks');
        $courseCode     = strtoupper(trim($_POST['course_code'] ?? ''));
        $condition      = trim($_POST['item_condition'] ?? 'Gently Used');
        $origPrice      = (float)($_POST['original_price'] ?? 0);
        $sellPrice      = (float)($_POST['selling_price'] ?? 0);
        $description    = trim($_POST['description'] ?? '');
        $meetupLocation = trim($_POST['meetup_location'] ?? 'SEU Main Cafeteria');
        $status         = trim($_POST['status'] ?? 'Available');
        $icon           = trim($_POST['image_icon'] ?? '📦');

        if (empty($title) || empty($description) || empty($meetupLocation) || $itemId <= 0) {
            set_flash('error', 'Invalid input data for update.');
        } elseif ($sellPrice <= 0) {
            set_flash('error', 'Selling price must be greater than zero.');
        } else {
            try {
                // Strict ownership check: user_id = :uid
                $stmt = $pdo->prepare("
                    UPDATE items SET 
                        title = :title,
                        category = :category,
                        course_code = :course,
                        item_condition = :condition,
                        original_price = :orig,
                        selling_price = :sell,
                        description = :desc,
                        meetup_location = :meetup,
                        status = :status,
                        image_icon = :icon
                    WHERE id = :id AND user_id = :uid
                ");
                $stmt->execute([
                    ':title'     => $title,
                    ':category'  => $category,
                    ':course'    => $courseCode ?: null,
                    ':condition' => $condition,
                    ':orig'      => ($origPrice > 0 ? $origPrice : $sellPrice),
                    ':sell'      => $sellPrice,
                    ':desc'      => $description,
                    ':meetup'    => $meetupLocation,
                    ':status'    => $status,
                    ':icon'      => $icon,
                    ':id'        => $itemId,
                    ':uid'       => $userId
                ]);

                if ($stmt->rowCount() > 0) {
                    set_flash('success', 'Listing updated successfully!');
                } else {
                    set_flash('info', 'No changes made or unauthorized action.');
                }
                header("Location: my_listings.php");
                exit();
            } catch (PDOException $e) {
                set_flash('error', 'Update failed: ' . $e->getMessage());
            }
        }
    }

    // ==========================================
    // 3. QUICK STATUS TOGGLE
    // ==========================================
    elseif ($action === 'toggle_status') {
        $itemId    = (int)($_POST['item_id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? 'Available');

        if (in_array($newStatus, ['Available', 'Reserved', 'Sold']) && $itemId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE items SET status = :status WHERE id = :id AND user_id = :uid");
                $stmt->execute([':status' => $newStatus, ':id' => $itemId, ':uid' => $userId]);
                set_flash('success', "Item status marked as {$newStatus}.");
            } catch (PDOException $e) {
                set_flash('error', 'Status update failed: ' . $e->getMessage());
            }
        }
        header("Location: my_listings.php");
        exit();
    }

    // ==========================================
    // 4. DELETE ITEM (D)
    // ==========================================
    elseif ($action === 'delete') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId > 0) {
            try {
                // Strict ownership check prevents unauthorized deletion
                $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id AND user_id = :uid");
                $stmt->execute([':id' => $itemId, ':uid' => $userId]);

                if ($stmt->rowCount() > 0) {
                    set_flash('success', 'Item permanently deleted from marketplace.');
                } else {
                    set_flash('error', 'Unauthorized deletion or item not found.');
                }
            } catch (PDOException $e) {
                set_flash('error', 'Delete error: ' . $e->getMessage());
            }
        }
        header("Location: my_listings.php");
        exit();
    }
}

// ==========================================
// 5. READ (R): Fetch user's listings & metrics
// ==========================================
$userListings = [];
$statsTotalItems = 0;
$statsSoldItems = 0;
$statsActiveValue = 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = :uid ORDER BY id DESC");
    $stmt->execute([':uid' => $userId]);
    $userListings = $stmt->fetchAll();

    $statsTotalItems = count($userListings);
    foreach ($userListings as $li) {
        if ($li['status'] === 'Sold') {
            $statsSoldItems++;
        } else {
            $statsActiveValue += (float)$li['selling_price'];
        }
    }
} catch (PDOException $e) {
    $error = "Failed to load listings: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 3.5rem;">

    <!-- Studio Header -->
    <div class="section-header" style="margin-bottom: 1.5rem;">
        <div>
            <h1 class="section-title">My Seller Studio (CRUD Hub)</h1>
            <p class="section-desc">Manage your posted academic items, modify prices, update status, or post new gear</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-lg" onclick="openModal('createItemModal')">
                ➕ Post New Item
            </button>
        </div>
    </div>

    <!-- Personal Seller Metrics -->
    <div class="stats-grid" style="margin-bottom: 2.5rem;">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= $statsTotalItems ?></div>
            <div class="stat-label">Total Items Listed</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?= $statsSoldItems ?></div>
            <div class="stat-label">Items Sold / Re-Homed</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💵</div>
            <div class="stat-value"><?= format_price($statsActiveValue) ?></div>
            <div class="stat-label">Active Inventory Value</div>
        </div>
    </div>

    <!-- Listings Table / Grid View -->
    <div class="table-responsive">
        <?php if (empty($userListings)): ?>
            <div style="text-align: center; padding: 3.5rem 1rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">You haven't listed any items yet</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Have textbooks, microcontrollers, drawing boards, or calculators you no longer use? Pass them down to juniors!
                </p>
                <button type="button" class="btn btn-primary" onclick="openModal('createItemModal')">
                    ➕ Post Your First Item
                </button>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Item &amp; Category</th>
                        <th>Course</th>
                        <th>Condition</th>
                        <th>Resale Price</th>
                        <th>Status</th>
                        <th>Date Posted</th>
                        <th style="text-align: right;">CRUD Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userListings as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 1.7rem;"><?= htmlspecialchars($item['image_icon'] ?? '📦') ?></span>
                                    <div>
                                        <a href="item_details.php?id=<?= (int)$item['id'] ?>" style="font-weight: 700; color: var(--text-primary);">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </a>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($item['category']) ?> &bull; 📍 <?= htmlspecialchars($item['meetup_location']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($item['course_code'])): ?>
                                    <span class="tag-course"><?= htmlspecialchars($item['course_code']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="tag-condition"><?= htmlspecialchars($item['item_condition']) ?></span>
                            </td>
                            <td>
                                <strong style="color: var(--accent); font-size: 1.05rem;">
                                    <?= format_price($item['selling_price']) ?>
                                </strong>
                                <?php if ((float)$item['original_price'] > (float)$item['selling_price']): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); text-decoration: line-through;">
                                        <?= format_price($item['original_price']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="my_listings.php" style="display: inline-block;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()" class="custom-select" style="font-size: 0.78rem; padding: 3px 6px;">
                                        <option value="Available" <?= $item['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                        <option value="Reserved" <?= $item['status'] === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                                        <option value="Sold" <?= $item['status'] === 'Sold' ? 'selected' : '' ?>>Sold</option>
                                    </select>
                                </form>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= date('M d, Y', strtotime($item['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                    <button type="button" class="btn btn-outline btn-sm" onclick='openEditModal(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        ✏️ Edit
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= (int)$item['id'] ?>, '<?= htmlspecialchars(addslashes($item['title'])) ?>')">
                                        🗑️ Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

<!-- =====================================================================
     MODAL 1: CREATE NEW ITEM (CREATE)
     ===================================================================== -->
<div class="modal-overlay" id="createItemModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">➕ Post New Academic Gear</h2>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>

        <form method="POST" action="my_listings.php">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-group">
                <label class="form-label">Item Title *</label>
                <input type="text" name="title" class="form-control" placeholder="e.g., Introduction to Algorithms (CLRS 3rd Edition)" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="Textbooks">📚 Textbooks</option>
                        <option value="Lab Gear & Kits">🔬 Lab Gear &amp; Kits</option>
                        <option value="Drawing & Tools">📐 Drawing &amp; Tools</option>
                        <option value="Electronics & Calculators">🔢 Electronics &amp; Calculators</option>
                        <option value="Other">📦 Other Academic Supplies</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Course Code (Optional)</label>
                    <input type="text" name="course_code" class="form-control" placeholder="e.g., CSE 472, EEE 101">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Condition *</label>
                    <select name="item_condition" class="form-control" required>
                        <option value="Like New">Like New (Barely used)</option>
                        <option value="Gently Used" selected>Gently Used (Good condition)</option>
                        <option value="Fair">Fair (Noticeable wear, fully usable)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Display Icon</label>
                    <select name="image_icon" class="form-control">
                        <option value="📚">📚 Textbook / Notes</option>
                        <option value="🔬">🔬 Lab Equipment</option>
                        <option value="🔢">🔢 Calculator / Hardware</option>
                        <option value="📐">📐 Drawing / Architecture</option>
                        <option value="⚡">⚡ Circuit / Electronics</option>
                        <option value="💻">💻 Computer / Adapter</option>
                        <option value="💡">💡 IC / Components</option>
                        <option value="📦">📦 General Academic Gear</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Original Retail Price (BDT) *</label>
                    <input type="number" step="1" name="original_price" class="form-control calc-orig-price" placeholder="Original price new (e.g. 1000)" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Resale Price (BDT) *</label>
                    <input type="number" step="1" name="selling_price" class="form-control calc-sell-price" placeholder="Your low price (e.g. 400)" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <span class="calc-discount-badge" style="display: none; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700; background: var(--accent-light); color: var(--accent);"></span>
            </div>

            <div class="form-group">
                <label class="form-label">Item Description &amp; What's Included *</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Condition details, included cables/cases, textbook edition, marks or notes..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Preferred Campus Meetup Spot *</label>
                <input type="text" name="meetup_location" class="form-control" placeholder="e.g. SEU Main Cafeteria, CSE Lab 4, Library 2nd Floor" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                🚀 Publish Listing to Marketplace
            </button>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL 2: EDIT EXISTING ITEM (UPDATE)
     ===================================================================== -->
<div class="modal-overlay" id="editItemModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">✏️ Edit Academic Listing</h2>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>

        <form method="POST" action="my_listings.php" id="editItemForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="item_id" id="editItemId" value="">

            <div class="form-group">
                <label class="form-label">Item Title *</label>
                <input type="text" name="title" id="editTitle" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" id="editCategory" class="form-control" required>
                        <option value="Textbooks">📚 Textbooks</option>
                        <option value="Lab Gear & Kits">🔬 Lab Gear &amp; Kits</option>
                        <option value="Drawing & Tools">📐 Drawing &amp; Tools</option>
                        <option value="Electronics & Calculators">🔢 Electronics &amp; Calculators</option>
                        <option value="Other">📦 Other Academic Supplies</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Course Code</label>
                    <input type="text" name="course_code" id="editCourseCode" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Condition *</label>
                    <select name="item_condition" id="editCondition" class="form-control" required>
                        <option value="Like New">Like New</option>
                        <option value="Gently Used">Gently Used</option>
                        <option value="Fair">Fair</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="editStatus" class="form-control" required>
                        <option value="Available">Available</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Sold">Sold</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Display Icon</label>
                    <select name="image_icon" id="editIcon" class="form-control">
                        <option value="📚">📚 Textbook / Notes</option>
                        <option value="🔬">🔬 Lab Equipment</option>
                        <option value="🔢">🔢 Calculator / Hardware</option>
                        <option value="📐">📐 Drawing / Architecture</option>
                        <option value="⚡">⚡ Circuit / Electronics</option>
                        <option value="💻">💻 Computer / Adapter</option>
                        <option value="💡">💡 IC / Components</option>
                        <option value="📦">📦 General Academic Gear</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Campus Meetup Location *</label>
                    <input type="text" name="meetup_location" id="editMeetup" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Original Price (BDT)</label>
                    <input type="number" step="1" name="original_price" id="editOrigPrice" class="form-control calc-orig-price" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Resale Price (BDT) *</label>
                    <input type="number" step="1" name="selling_price" id="editSellPrice" class="form-control calc-sell-price" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <span class="calc-discount-badge" style="display: none; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700; background: var(--accent-light); color: var(--accent);"></span>
            </div>

            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea name="description" id="editDesc" class="form-control" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                💾 Save Changes
            </button>
        </form>
    </div>
</div>

<!-- =====================================================================
     MODAL 3: DELETE CONFIRMATION (DELETE)
     ===================================================================== -->
<div class="modal-overlay" id="deleteConfirmModal">
    <div class="modal-content" style="max-width: 440px; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 0.75rem;">🗑️</div>
        <h2 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 0.5rem;">Delete Listing?</h2>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;">
            Are you sure you want to permanently remove "<span id="deleteItemTitle" style="font-weight: 700; color: var(--text-primary);"></span>"? This action cannot be undone.
        </p>

        <form method="POST" action="my_listings.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="item_id" id="deleteItemId" value="">

            <div style="display: flex; gap: 1rem;">
                <button type="button" class="btn btn-outline" style="flex: 1;" data-close-modal>
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger" style="flex: 1;">
                    Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(item) {
    document.getElementById('editItemId').value = item.id;
    document.getElementById('editTitle').value = item.title;
    document.getElementById('editCategory').value = item.category;
    document.getElementById('editCourseCode').value = item.course_code || '';
    document.getElementById('editCondition').value = item.item_condition;
    document.getElementById('editStatus').value = item.status;
    document.getElementById('editIcon').value = item.image_icon || '📦';
    document.getElementById('editMeetup').value = item.meetup_location;
    document.getElementById('editOrigPrice').value = item.original_price;
    document.getElementById('editSellPrice').value = item.selling_price;
    document.getElementById('editDesc').value = item.description;

    // Trigger price calculator
    const editForm = document.getElementById('editItemForm');
    const orig = editForm.querySelector('.calc-orig-price');
    if (orig) {
        orig.dispatchEvent(new Event('input'));
    }

    openModal('editItemModal');
}

function confirmDelete(id, title) {
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemTitle').textContent = title;
    openModal('deleteConfirmModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
