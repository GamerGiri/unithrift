<?php
// index.php - Page 1: Home / Landing Page
$pageTitle = "Home";
require_once __DIR__ . '/includes/header.php';

// Fetch Live Statistics & Featured Listings from Database
$totalItems = 0;
$totalSavings = 0;
$totalUsers = 0;
$featuredItems = [];

try {
    if ($pdo) {
        // Total items available
        $stmt = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Available'");
        $totalItems = (int)$stmt->fetchColumn();

        // Estimated student savings
        $stmt = $pdo->query("SELECT SUM(original_price - selling_price) FROM items WHERE original_price > selling_price");
        $totalSavings = (float)$stmt->fetchColumn();

        // Total registered students
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $totalUsers = (int)$stmt->fetchColumn();

        // Fetch 6 recent available items
        $stmt = $pdo->query("
            SELECT i.*, u.full_name, u.phone 
            FROM items i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status = 'Available' 
            ORDER BY i.id DESC 
            LIMIT 6
        ");
        $featuredItems = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Graceful fallback if database error
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-pill">
            <span>🎓 Southeast University Academic ReUse Hub</span>
        </div>
        <h1 class="hero-title">
            Pass down your academic gear.<br>
            <span class="hero-gradient">Save money every semester.</span>
        </h1>
        <p class="hero-subtitle">
            Don't let expensive textbooks, microcontrollers, drawing boards, and calculators gather dust after final exams. Connect with juniors and exchange gear at student-friendly prices.
        </p>

        <div class="hero-actions">
            <a href="marketplace.php" class="btn btn-primary btn-lg">
                🔍 Explore Marketplace
            </a>
            <a href="my_listings.php" class="btn btn-accent btn-lg">
                ➕ Post an Item for Sale
            </a>
        </div>

        <!-- Live Impact Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= number_format($totalItems) ?>+</div>
                <div class="stat-label">Active Campus Listings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-value"><?= APP_CURRENCY ?> <?= number_format($totalSavings) ?></div>
                <div class="stat-label">Estimated Student Savings</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?= number_format($totalUsers) ?>+</div>
                <div class="stat-label">Registered Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">♻️</div>
                <div class="stat-value">100%</div>
                <div class="stat-label">ReUse &amp; Zero Waste</div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Showcase Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Popular Academic Categories</h2>
                <p class="section-desc">Find equipment and books specific to your semester courses</p>
            </div>
            <a href="marketplace.php" class="btn btn-outline btn-sm">View All Categories &rarr;</a>
        </div>

        <div class="categories-grid">
            <a href="marketplace.php?category=Textbooks" class="category-card">
                <div class="cat-icon">📚</div>
                <div class="cat-name">Course Textbooks</div>
                <div class="cat-desc" style="font-size: 0.85rem; color: var(--text-secondary);">CLRS Algorithms, Database, Calculus, Discrete Math.</div>
                <div class="cat-count">Save up to 70% vs New</div>
            </a>

            <a href="marketplace.php?category=Lab+Gear+%26+Kits" class="category-card">
                <div class="cat-icon">🔬</div>
                <div class="cat-name">Lab Gear &amp; Kits</div>
                <div class="cat-desc" style="font-size: 0.85rem; color: var(--text-secondary);">Arduinos, sensors, breadboards, IC chips, multimeters.</div>
                <div class="cat-count">For DLD &amp; Microprocessor Labs</div>
            </a>

            <a href="marketplace.php?category=Drawing+%26+Tools" class="category-card">
                <div class="cat-icon">📐</div>
                <div class="cat-name">Drawing &amp; Drafting</div>
                <div class="cat-desc" style="font-size: 0.85rem; color: var(--text-secondary);">Drafting boards, T-squares, set squares, engineering compasses.</div>
                <div class="cat-count">For Engineering Graphics</div>
            </a>

            <a href="marketplace.php?category=Electronics+%26+Calculators" class="category-card">
                <div class="cat-icon">🔢</div>
                <div class="cat-name">Calculators &amp; Tech</div>
                <div class="cat-desc" style="font-size: 0.85rem; color: var(--text-secondary);">Scientific calculators (Casio 991EX), adapters, networking cables.</div>
                <div class="cat-count">Original &amp; Working Units</div>
            </a>
        </div>
    </div>
</section>

<!-- Featured Listings Section -->
<section class="section" style="background-color: var(--bg-surface); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Recently Listed on Campus</h2>
                <p class="section-desc">Freshly posted by students finishing their exams</p>
            </div>
            <a href="marketplace.php" class="btn btn-primary btn-sm">Browse Entire Catalogue</a>
        </div>

        <div class="items-grid">
            <?php if (empty($featuredItems)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">No items listed yet</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Be the first student to post an item for sale!</p>
                    <a href="my_listings.php" class="btn btn-primary">Post an Item</a>
                </div>
            <?php else: ?>
                <?php foreach ($featuredItems as $item): ?>
                    <?php 
                        $discount = calc_discount_pct($item['original_price'], $item['selling_price']);
                    ?>
                    <div class="item-card">
                        <div class="card-img-wrap">
                            <span class="card-img-placeholder"><?= htmlspecialchars($item['image_icon'] ?? '📦') ?></span>
                            <span class="card-badge-category"><?= htmlspecialchars($item['category']) ?></span>
                            <?php if ($discount > 0): ?>
                                <span class="card-badge-discount"><?= $discount ?>% OFF</span>
                            <?php endif; ?>
                            <span class="card-badge-status status-available">Available</span>
                        </div>

                        <div class="card-body">
                            <div class="card-tags">
                                <?php if (!empty($item['course_code'])): ?>
                                    <span class="tag-course"><?= htmlspecialchars($item['course_code']) ?></span>
                                <?php endif; ?>
                                <span class="tag-condition"><?= htmlspecialchars($item['item_condition']) ?></span>
                            </div>

                            <h3 class="card-title" title="<?= htmlspecialchars($item['title']) ?>">
                                <?= htmlspecialchars($item['title']) ?>
                            </h3>

                            <p class="card-desc">
                                <?= htmlspecialchars($item['description']) ?>
                            </p>

                            <div class="card-price-row">
                                <span class="price-current"><?= format_price($item['selling_price']) ?></span>
                                <?php if ((float)$item['original_price'] > (float)$item['selling_price']): ?>
                                    <span class="price-original"><?= format_price($item['original_price']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer">
                                <span class="card-location" title="<?= htmlspecialchars($item['meetup_location']) ?>">
                                    📍 <?= htmlspecialchars($item['meetup_location']) ?>
                                </span>
                                <a href="item_details.php?id=<?= (int)$item['id'] ?>" class="btn btn-outline btn-sm">
                                    View Details &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="section">
    <div class="container" style="text-align: center;">
        <h2 class="section-title" style="margin-bottom: 0.5rem;">How UniThrift Works</h2>
        <p class="section-desc" style="margin-bottom: 3rem;">Three easy steps to exchange academic supplies safely on campus</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 2rem;">
            <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">1️⃣</div>
                <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem;">List Your Gear</h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem;">
                    Snap a note of your used textbook, lab kit, or drawing tool. Set your discounted price and preferred campus meetup spot.
                </p>
            </div>

            <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">2️⃣</div>
                <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem;">Connect on WhatsApp / Call</h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem;">
                    Interested students can click to contact you directly via WhatsApp or call to ask questions and confirm reservation.
                </p>
            </div>

            <div style="background: var(--bg-surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">3️⃣</div>
                <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem;">Exchange at Campus</h3>
                <p style="color: var(--text-secondary); font-size: 0.92rem;">
                    Meet up safely at the SEU Cafeteria, Library, or Department Lab. Inspect the item, make payment, and mark the listing as Sold!
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
