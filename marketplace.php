<?php
// marketplace.php - Page 3: Catalogue, Live Search & Multi-Filters
$pageTitle = "Marketplace Catalogue";
require_once __DIR__ . '/includes/header.php';

$selectedCategory = $_GET['category'] ?? 'All';

// Fetch all available items
$items = [];
try {
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT i.*, u.full_name as seller_name, u.phone as seller_phone, u.department as seller_dept
            FROM items i
            JOIN users u ON i.user_id = u.id
            WHERE i.status = 'Available'
            ORDER BY i.id DESC
        ");
        $items = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Graceful error handle
}
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 3.5rem;">
    
    <!-- Page Header & Action Bar -->
    <div class="section-header" style="margin-bottom: 1.5rem;">
        <div>
            <h1 class="section-title">Campus Gear Marketplace</h1>
            <p class="section-desc">Search and filter textbooks, lab equipment, and tools available from students on campus</p>
        </div>
        <div>
            <a href="my_listings.php" class="btn btn-accent">
                ➕ Post an Item for Sale
            </a>
        </div>
    </div>

    <!-- Search & Filter Controls Toolbar -->
    <div class="marketplace-toolbar">
        <!-- Keyword Search Input -->
        <div class="search-box-wrap">
            <span class="search-icon-svg">🔍</span>
            <input type="text" id="marketplaceSearch" class="search-input" placeholder="Search by item title, course code (e.g. CSE 311, EEE 102), or keywords...">
        </div>

        <!-- Filter Row -->
        <div class="filter-row">
            <!-- Category Pills -->
            <div class="filter-pills">
                <button type="button" class="filter-pill <?= $selectedCategory === 'All' ? 'active' : '' ?>" data-category="All">
                    All Items
                </button>
                <button type="button" class="filter-pill <?= $selectedCategory === 'Textbooks' ? 'active' : '' ?>" data-category="Textbooks">
                    📚 Textbooks
                </button>
                <button type="button" class="filter-pill <?= $selectedCategory === 'Lab Gear & Kits' ? 'active' : '' ?>" data-category="Lab Gear & Kits">
                    🔬 Lab Gear &amp; Kits
                </button>
                <button type="button" class="filter-pill <?= $selectedCategory === 'Drawing & Tools' ? 'active' : '' ?>" data-category="Drawing & Tools">
                    📐 Drawing &amp; Tools
                </button>
                <button type="button" class="filter-pill <?= $selectedCategory === 'Electronics & Calculators' ? 'active' : '' ?>" data-category="Electronics & Calculators">
                    🔢 Calculators &amp; Tech
                </button>
            </div>

            <!-- Dropdown Filters & Sorters -->
            <div class="filter-selects">
                <select id="conditionFilter" class="custom-select" aria-label="Filter by Condition">
                    <option value="All">All Conditions</option>
                    <option value="Like New">Like New</option>
                    <option value="Gently Used">Gently Used</option>
                    <option value="Fair">Fair</option>
                </select>

                <select id="sortFilter" class="custom-select" aria-label="Sort Items">
                    <option value="newest">Sort: Newest First</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="discount_desc">Biggest Discount %</option>
                </select>
            </div>
        </div>

        <!-- Results Counter Indicator -->
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
            <span id="resultsCount"><?= count($items) ?> items available</span>
            <span>💡 Click any card to view specs or contact the student seller</span>
        </div>
    </div>

    <!-- Items Grid -->
    <div class="items-grid" id="marketplaceGrid">
        <?php foreach ($items as $item): ?>
            <?php 
                $discount = calc_discount_pct($item['original_price'], $item['selling_price']);
            ?>
            <div class="item-card" 
                 data-id="<?= (int)$item['id'] ?>"
                 data-title="<?= htmlspecialchars($item['title']) ?>"
                 data-desc="<?= htmlspecialchars($item['description']) ?>"
                 data-course="<?= htmlspecialchars($item['course_code'] ?? '') ?>"
                 data-category="<?= htmlspecialchars($item['category']) ?>"
                 data-condition="<?= htmlspecialchars($item['item_condition']) ?>"
                 data-price="<?= (float)$item['selling_price'] ?>"
                 data-discount="<?= $discount ?>">
                 
                <div class="card-img-wrap">
                    <?php $imgSrc = get_item_image($item['image_url'] ?? null); ?>
                    <?php if ($imgSrc): ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="card-img-real">
                    <?php else: ?>
                        <span class="card-img-placeholder"><?= htmlspecialchars($item['image_icon'] ?? '📦') ?></span>
                    <?php endif; ?>
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
                        <a href="item_details.php?id=<?= (int)$item['id'] ?>" style="color: inherit;">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
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
                            View &amp; Contact &rarr;
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty State for Search/Filter -->
    <div id="noResultsMessage" style="display: none; text-align: center; padding: 4rem 1rem; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px dashed var(--border-color); margin-top: 1.5rem;">
        <div style="font-size: 3.5rem; margin-bottom: 1rem;">🔍</div>
        <h3 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.5rem;">No academic gear matches your filters</h3>
        <p style="color: var(--text-secondary); max-width: 480px; margin: 0 auto 1.5rem;">
            Try clearing the search keywords, switching categories, or looking for broader terms.
        </p>
        <button type="button" class="btn btn-primary" onclick="resetFilters()">
            Reset All Filters
        </button>
    </div>

</div>

<!-- Filter JavaScript -->
<script src="assets/js/filter.js"></script>
<script>
function resetFilters() {
    document.getElementById('marketplaceSearch').value = '';
    document.getElementById('conditionFilter').value = 'All';
    document.getElementById('sortFilter').value = 'newest';
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    document.querySelector('.filter-pill[data-category="All"]').classList.add('active');
    
    // Trigger custom event or filterItems
    const event = new Event('input');
    document.getElementById('marketplaceSearch').dispatchEvent(event);
}

// Auto-trigger category filter if query param provided
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const cat = urlParams.get('category');
    if (cat) {
        const targetPill = document.querySelector(`.filter-pill[data-category="${cat}"]`);
        if (targetPill) {
            targetPill.click();
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
