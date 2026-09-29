<?php
// calculator.php - Standalone Additional Page of Choice: Academic Budget & Savings Calculator
$pageTitle = "Academic Budget & Savings Calculator";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Fetch live available items from database
$catalogItems = [];
$itemsByCategory = [];

try {
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT id, title, category, course_code, item_condition, original_price, selling_price, image_icon, image_url 
            FROM items 
            WHERE status = 'Available'
            ORDER BY category ASC, id ASC
        ");
        $catalogItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($catalogItems as $it) {
            $cat = $it['category'];
            if (!isset($itemsByCategory[$cat])) {
                $itemsByCategory[$cat] = [];
            }
            $itemsByCategory[$cat][] = $it;
        }
    }
} catch (PDOException $e) {
    // Graceful fallback
}

// Fallback presets if catalog is completely empty
if (empty($catalogItems)) {
    $fallbackPresets = [
        'Textbooks' => [
            ['id' => 1, 'title' => 'Introduction to Algorithms (CLRS 3rd Edition)', 'course_code' => 'CSE 311', 'category' => 'Textbooks', 'item_condition' => 'Gently Used', 'original_price' => 1200, 'selling_price' => 450],
            ['id' => 6, 'title' => 'Database System Concepts (Silberschatz, Korth 7th Ed)', 'course_code' => 'CSE 341', 'category' => 'Textbooks', 'item_condition' => 'Gently Used', 'original_price' => 950, 'selling_price' => 380]
        ],
        'Lab Gear & Kits' => [
            ['id' => 2, 'title' => 'Arduino Uno R3 + 16x Sensor Starter Kit', 'course_code' => 'CSE 316', 'category' => 'Lab Gear & Kits', 'item_condition' => 'Like New', 'original_price' => 2800, 'selling_price' => 1250],
            ['id' => 5, 'title' => 'Digital Multimeter DT-830D + Test Leads', 'course_code' => 'EEE 102', 'category' => 'Lab Gear & Kits', 'item_condition' => 'Like New', 'original_price' => 750, 'selling_price' => 300]
        ],
        'Drawing & Tools' => [
            ['id' => 4, 'title' => 'Rotring Engineering Drawing Board (A2 Size) + T-Square', 'course_code' => 'ENG 103', 'category' => 'Drawing & Tools', 'item_condition' => 'Gently Used', 'original_price' => 3200, 'selling_price' => 1300]
        ],
        'Electronics & Calculators' => [
            ['id' => 3, 'title' => 'Casio fx-991EX ClassWiz Scientific Calculator', 'course_code' => 'MAT 101', 'category' => 'Electronics & Calculators', 'item_condition' => 'Like New', 'original_price' => 2400, 'selling_price' => 1100]
        ]
    ];
    $itemsByCategory = $fallbackPresets;
}

$categoryIcons = [
    'Textbooks' => '📚',
    'Lab Gear & Kits' => '🔬',
    'Drawing & Tools' => '📐',
    'Electronics & Calculators' => '🔢',
    'Other' => '📦'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">

    <!-- Page Hero Header -->
    <div style="text-align: center; max-width: 760px; margin: 0 auto 2.5rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--accent-light); color: var(--accent); padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 700; margin-bottom: 1rem;">
            💡 Smart Semester Budget Planner
        </div>
        <h1 style="font-size: 2.3rem; font-weight: 800; line-height: 1.25; margin-bottom: 0.75rem;">
            Semester Academic Expense &amp; Savings Calculator
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.02rem; line-height: 1.6;">
            Calculate your personalized savings on course textbooks, lab equipment, and drawing tools compared to brand new retail costs.
        </p>
    </div>

    <!-- Main Calculator Layout (Interactive Columns) -->
    <div class="calc-layout-grid">
        
        <!-- Left Column: Item Selection Checklist & Search Bar -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem 2rem; box-shadow: var(--shadow-sm);">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h2 style="font-size: 1.25rem; font-weight: 800; margin: 0;">Academic Supplies Checklist</h2>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0 0;">Check supplies needed for your upcoming semester</p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="selectAll(true)">Select All</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="selectAll(false)">Clear All</button>
                </div>
            </div>

            <!-- Interactive Search & Filter Bar with Search Button -->
            <div style="background: var(--bg-surface); padding: 1rem 1.15rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                <div class="calc-search-bar">
                    <div>
                        <input type="text" id="calcSearchInput" class="form-control" placeholder="Search title, course code (e.g. CSE 311, MAT 101)..." onkeydown="if(event.key==='Enter') filterCalculatorItems();">
                    </div>
                    <div>
                        <select id="calcCategoryFilter" class="form-control" onchange="filterCalculatorItems()">
                            <option value="">All Categories</option>
                            <option value="Textbooks">📚 Textbooks</option>
                            <option value="Lab Gear & Kits">🔬 Lab Gear &amp; Kits</option>
                            <option value="Drawing & Tools">📐 Drawing &amp; Tools</option>
                            <option value="Electronics & Calculators">🔢 Electronics</option>
                            <option value="Other">📦 Other Supplies</option>
                        </select>
                    </div>
                    <button type="button" id="calcSearchBtn" class="btn btn-primary" onclick="filterCalculatorItems()" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; white-space: nowrap;">
                        🔍 Search
                    </button>
                    <button type="button" id="calcResetBtn" class="btn btn-outline" onclick="resetCalculatorSearch()" style="white-space: nowrap; justify-content: center;">
                        Reset
                    </button>
                </div>
                <div id="calcSearchResultCount" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem; display: none;"></div>
            </div>

            <!-- Dynamic Category Groups -->
            <div id="calcSuppliesList">
                <?php 
                    $itemIndex = 0;
                    foreach ($itemsByCategory as $catName => $items): 
                        $icon = $categoryIcons[$catName] ?? '📦';
                ?>
                    <div class="calc-category-group" data-group-category="<?= htmlspecialchars($catName) ?>" style="margin-bottom: 1.75rem;">
                        <h3 style="font-size: 0.98rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span><?= $icon ?></span> <?= htmlspecialchars($catName) ?>
                        </h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                            <?php foreach ($items as $item): ?>
                                <?php 
                                    $itemIndex++;
                                    // Default check first 4 items so calculator starts with active savings
                                    $defaultChecked = ($itemIndex <= 4);
                                    $itemUrl = "item_details.php?id=" . (int)$item['id'];
                                ?>
                                <label class="calc-item-row" 
                                       data-name="<?= htmlspecialchars(strtolower($item['title'])) ?>" 
                                       data-course="<?= htmlspecialchars(strtolower($item['course_code'] ?? '')) ?>" 
                                       data-category="<?= htmlspecialchars($item['category']) ?>" 
                                       style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 0;">
                                        <input type="checkbox" 
                                               class="calc-check" 
                                               id="calcItem_<?= (int)$item['id'] ?>" 
                                               data-id="<?= (int)$item['id'] ?>" 
                                               data-orig="<?= (float)$item['original_price'] ?>" 
                                               data-sell="<?= (float)$item['selling_price'] ?>" 
                                               data-name="<?= htmlspecialchars($item['title']) ?>" 
                                               data-course="<?= htmlspecialchars($item['course_code'] ?: 'Academic Gear') ?>" 
                                               data-url="<?= htmlspecialchars($itemUrl) ?>" 
                                               <?= $defaultChecked ? 'checked' : '' ?> 
                                               style="width: 18px; height: 18px; cursor: pointer; flex-shrink: 0;">
                                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 0.5rem;">
                                            <div style="display: flex; align-items: center; gap: 0.45rem;">
                                                <strong style="font-size: 0.94rem; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($item['title']) ?></strong>
                                                <a href="<?= htmlspecialchars($itemUrl) ?>" target="_blank" onclick="event.stopPropagation();" title="Open item page in new tab" style="color: var(--primary); font-size: 0.78rem; text-decoration: none; flex-shrink: 0;">
                                                    🔗
                                                </a>
                                            </div>
                                            <span style="font-size: 0.78rem; color: var(--text-muted); display: block;">
                                                <?= htmlspecialchars($item['course_code'] ?: 'General') ?> &bull; Condition: <?= htmlspecialchars($item['item_condition'] ?? 'Used') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="text-align: right; flex-shrink: 0; padding-left: 0.5rem;">
                                        <span style="font-weight: 800; color: var(--accent); font-size: 0.98rem; display: block;"><?= format_price($item['selling_price']) ?></span>
                                        <span style="font-size: 0.74rem; text-decoration: line-through; color: var(--text-muted);">New: <?= format_price($item['original_price']) ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="calcNoResults" style="display: none; text-align: center; padding: 2rem; color: var(--text-muted);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔍</div>
                <strong>No academic supplies match your search criteria.</strong>
                <div style="font-size: 0.85rem; margin-top: 4px;">Try searching for a course code like "CSE", "MAT", or general term.</div>
            </div>

        </div>

        <!-- Right Column: Real-Time Financial Summary Card -->
        <div class="calc-sticky-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem 2rem; box-shadow: var(--shadow-lg); position: sticky; top: 90px;">
            
            <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>💰</span> Your Semester Savings
            </h2>

            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                
                <div style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">Selected Items:</span>
                    <strong id="summaryItemCount" style="color: var(--text-primary);">0 items</strong>
                </div>

                <!-- Below Selected Items: Show Items Selected with Clickable Link -->
                <div style="margin-top: 0.25rem; margin-bottom: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem;">
                        <span style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Selected Items Breakdown:</span>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">🔗 Click title to inspect</span>
                    </div>
                    <div id="selectedItemsContainer" class="calc-selected-box">
                        <!-- Filled dynamically by JavaScript -->
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">Estimated Retail Cost (New):</span>
                    <strong id="summaryOrigCost" style="color: var(--text-muted); text-decoration: line-through;">৳ 0</strong>
                </div>

                <div style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">UniThrift Student Cost:</span>
                    <strong id="summarySellCost" style="color: var(--accent); font-size: 1.15rem;">৳ 0</strong>
                </div>

                <div style="background: var(--accent-light); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--accent); text-align: center;">
                    <div style="font-size: 0.85rem; color: #065f46; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Money Saved</div>
                    <div id="summarySavedTotal" style="font-size: 2.2rem; font-weight: 800; color: var(--accent); line-height: 1.2; margin: 0.25rem 0;">
                        ৳ 0
                    </div>
                    <div id="summarySavedPct" style="font-size: 0.9rem; font-weight: 700; color: #065f46;">
                        You save 0% of your budget!
                    </div>
                </div>

            </div>

            <!-- Call to Actions -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <a href="marketplace.php" class="btn btn-primary btn-lg" style="width: 100%;">
                    🔍 Browse Full Marketplace
                </a>
                <a href="my_listings.php" class="btn btn-outline" style="width: 100%;">
                    ➕ Have Academic Gear to Sell?
                </a>
            </div>

            <div style="margin-top: 1.25rem; font-size: 0.78rem; color: var(--text-muted); line-height: 1.4; text-align: center;">
                💡 Calculations based on real verified campus marketplace listings and student transaction data.
            </div>

        </div>

    </div>

</div>

<!-- Real-time Calculator & Interactive Search JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.calc-check');
    checkboxes.forEach(cb => cb.addEventListener('change', calculateSavings));

    // Live search on typing
    const searchInput = document.getElementById('calcSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', filterCalculatorItems);
    }

    calculateSavings();
});

function calculateSavings() {
    let origTotal = 0;
    let sellTotal = 0;
    let selectedCount = 0;

    const checkedBoxes = document.querySelectorAll('.calc-check:checked');
    checkedBoxes.forEach(cb => {
        origTotal += parseFloat(cb.getAttribute('data-orig')) || 0;
        sellTotal += parseFloat(cb.getAttribute('data-sell')) || 0;
        selectedCount++;
    });

    const savedTotal = Math.max(0, origTotal - sellTotal);
    const savedPct = origTotal > 0 ? Math.round((savedTotal / origTotal) * 100) : 0;

    document.getElementById('summaryItemCount').textContent = `${selectedCount} item${selectedCount === 1 ? '' : 's'}`;
    document.getElementById('summaryOrigCost').textContent = '৳ ' + Math.round(origTotal).toLocaleString();
    document.getElementById('summarySellCost').textContent = '৳ ' + Math.round(sellTotal).toLocaleString();
    document.getElementById('summarySavedTotal').textContent = '৳ ' + Math.round(savedTotal).toLocaleString();
    document.getElementById('summarySavedPct').textContent = `You save ${savedPct}% of your semester budget!`;

    // Render Clickable Links Breakdown
    renderSelectedItemsList(checkedBoxes);
}

function renderSelectedItemsList(checkedBoxes) {
    const container = document.getElementById('selectedItemsContainer');
    if (!container) return;

    if (checkedBoxes.length === 0) {
        container.innerHTML = `
            <div style="font-size: 0.82rem; color: var(--text-muted); font-style: italic; text-align: center; padding: 1.25rem 0.75rem; background: var(--bg-card); border-radius: var(--radius-sm); border: 1px dashed var(--border-color);">
                No items selected yet. Check items on the left to calculate your savings.
            </div>`;
        return;
    }

    let html = '';
    checkedBoxes.forEach(cb => {
        const id = cb.id;
        const name = cb.getAttribute('data-name');
        const course = cb.getAttribute('data-course');
        const url = cb.getAttribute('data-url');
        const sell = Math.round(parseFloat(cb.getAttribute('data-sell')) || 0).toLocaleString();

        html += `
            <div class="calc-selected-row">
                <div style="flex: 1; min-width: 0;">
                    <a href="${url}" target="_blank" class="calc-selected-link" title="Open '${name}' in new tab">
                        <span style="font-size: 0.8rem; margin-top: 1px;">🔗</span>
                        <span>${name}</span>
                    </a>
                    <span style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 2px;">${course}</span>
                </div>
                <div class="calc-selected-row-meta">
                    <span style="font-weight: 800; color: var(--accent); font-size: 0.95rem;">৳ ${sell}</span>
                    <button type="button" class="calc-selected-remove-btn" onclick="uncheckCalcItem('${id}')" aria-label="Remove ${name}" title="Remove this item from calculation">&times;</button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function uncheckCalcItem(id) {
    const el = document.getElementById(id);
    if (el) {
        el.checked = false;
        calculateSavings();
    }
}

function selectAll(state) {
    document.querySelectorAll('.calc-check').forEach(cb => {
        // Only toggle visible items if search is active
        const parentRow = cb.closest('.calc-item-row');
        if (parentRow && parentRow.style.display !== 'none') {
            cb.checked = state;
        } else if (!parentRow) {
            cb.checked = state;
        }
    });
    calculateSavings();
}

function filterCalculatorItems() {
    const q = (document.getElementById('calcSearchInput').value || '').trim().toLowerCase();
    const cat = document.getElementById('calcCategoryFilter').value;
    const rows = document.querySelectorAll('.calc-item-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = (row.getAttribute('data-name') || '').toLowerCase();
        const course = (row.getAttribute('data-course') || '').toLowerCase();
        const category = row.getAttribute('data-category') || '';

        const matchesQ = !q || name.includes(q) || course.includes(q);
        const matchesCat = !cat || category === cat;

        if (matchesQ && matchesCat) {
            row.style.display = 'flex';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Check each category group to show or hide section title
    document.querySelectorAll('.calc-category-group').forEach(group => {
        const hasVisible = group.querySelectorAll('.calc-item-row:not([style*="display: none"])').length > 0;
        group.style.display = hasVisible ? 'block' : 'none';
    });

    const noResults = document.getElementById('calcNoResults');
    const countBadge = document.getElementById('calcSearchResultCount');

    if (visibleCount === 0) {
        if (noResults) noResults.style.display = 'block';
    } else {
        if (noResults) noResults.style.display = 'none';
    }

    if (q || cat) {
        countBadge.style.display = 'block';
        countBadge.textContent = `Found ${visibleCount} supply item${visibleCount === 1 ? '' : 's'} matching "${q || cat}".`;
    } else {
        countBadge.style.display = 'none';
    }
}

function resetCalculatorSearch() {
    document.getElementById('calcSearchInput').value = '';
    document.getElementById('calcCategoryFilter').value = '';
    document.querySelectorAll('.calc-item-row').forEach(row => row.style.display = 'flex');
    document.querySelectorAll('.calc-category-group').forEach(group => group.style.display = 'block');
    document.getElementById('calcSearchResultCount').style.display = 'none';
    const noResults = document.getElementById('calcNoResults');
    if (noResults) noResults.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
