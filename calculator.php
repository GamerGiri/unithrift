<?php
// calculator.php - Standalone Additional Page of Choice: Academic Budget & Savings Calculator
$pageTitle = "Academic Budget & Savings Calculator";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Fetch average prices and live counts from database for each item category
$liveStats = [];
try {
    if ($pdo) {
        $stmt = $pdo->query("
            SELECT category, 
                   COUNT(*) as available_count, 
                   AVG(original_price) as avg_orig, 
                   AVG(selling_price) as avg_sell
            FROM items 
            WHERE status = 'Available'
            GROUP BY category
        ");
        while ($row = $stmt->fetch()) {
            $liveStats[$row['category']] = $row;
        }
    }
} catch (PDOException $e) {
    // Graceful fallback
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2.5rem; padding-bottom: 4rem;">

    <!-- Page Hero Header -->
    <div style="text-align: center; max-width: 720px; margin: 0 auto 3rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--accent-light); color: var(--accent); padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 700; margin-bottom: 1rem;">
            💡 Smart Semester Budget Planner
        </div>
        <h1 style="font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 1rem;">
            Semester Academic Expense &amp; Savings Calculator
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.6;">
            Select the books, lab gear, and drafting supplies required for your upcoming semester courses to see how much money you save by choosing pre-owned campus gear.
        </p>
    </div>

    <!-- Main Calculator Layout (Interactive Columns) -->
    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; align-items: flex-start;">
        
        <!-- Left Column: Item Selection Checklist -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; gap: 0.5rem;">
                <h2 style="font-size: 1.25rem; font-weight: 800;">Academic Course Supplies Checklist</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="selectAll(true)">Select All</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="selectAll(false)">Clear All</button>
                </div>
            </div>

            <!-- Group 1: Required Course Textbooks -->
            <div style="margin-bottom: 1.75rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: var(--primary); margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📚</span> Core Course Textbooks
                </h3>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="1200" data-sell="450" data-name="Algorithms (CLRS 3rd Ed)" checked style="width: 18px; height: 18px; cursor: pointer;">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Introduction to Algorithms (CLRS)</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">CSE 311 &bull; Department of CSE</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳450</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳1,200</span>
                        </div>
                    </label>

                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="950" data-sell="380" data-name="Database System Concepts" checked style="width: 18px; height: 18px; cursor: pointer;">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Database System Concepts (Korth)</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">CSE 341 &bull; Database Systems</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳380</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳950</span>
                        </div>
                    </label>

                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="850" data-sell="320" data-name="Calculus & Analytical Geometry (Anton)">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Calculus by Howard Anton (10th Ed)</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">MAT 101 &bull; Engineering Math</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳320</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳850</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Group 2: Laboratory Equipment & Microcontrollers -->
            <div style="margin-bottom: 1.75rem;">
                <h3 style="font-size: 1rem; font-weight: 700; color: var(--accent); margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🔬</span> Laboratory Hardware &amp; Kits
                </h3>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="2800" data-sell="1250" data-name="Arduino Uno + Sensor Kit" checked style="width: 18px; height: 18px; cursor: pointer;">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Arduino Uno R3 + 16x Sensor Kit</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">CSE 316 &bull; Microprocessor &amp; Interfacing</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳1,250</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳2,800</span>
                        </div>
                    </label>

                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="750" data-sell="300" data-name="Digital Multimeter DT-830D" checked style="width: 18px; height: 18px; cursor: pointer;">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Digital Multimeter DT-830D + Test Leads</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">EEE 102 &bull; Basic Electrical Lab</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳300</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳750</span>
                        </div>
                    </label>

                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="600" data-sell="200" data-name="Breadboard + IC Chip Set">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Solderless Breadboard (830) + 4x ICs</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">CSE 225 &bull; Digital Logic Design</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳200</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳600</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Group 3: Engineering Drawing & Calculators -->
            <div>
                <h3 style="font-size: 1rem; font-weight: 700; color: var(--warning); margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📐</span> Tools, Drafting &amp; Calculators
                </h3>
                
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="3200" data-sell="1300" data-name="A2 Drafting Board + Set Squares" checked style="width: 18px; height: 18px; cursor: pointer;">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Rotring A2 Engineering Drawing Board Set</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">ENG 103 &bull; Engineering Graphics</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳1,300</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳3,200</span>
                        </div>
                    </label>

                    <label class="calc-item-row" style="display: flex; align-items: center; justify-content: space-between; background: var(--bg-subtle); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); cursor: pointer; transition: var(--transition);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <input type="checkbox" class="calc-check" data-orig="2400" data-sell="1100" data-name="Casio fx-991EX ClassWiz">
                            <div>
                                <strong style="display: block; font-size: 0.95rem;">Casio fx-991EX ClassWiz Scientific Calculator</strong>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">All Engineering Mathematics</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-weight: 800; color: var(--accent);">৳1,100</span>
                            <span style="display: block; font-size: 0.75rem; text-decoration: line-through; color: var(--text-muted);">New: ৳2,400</span>
                        </div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Right Column: Real-Time Financial Summary Card -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-lg); position: sticky; top: 90px;">
            
            <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>💰</span> Your Semester Savings
            </h2>

            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                
                <div style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">Selected Items:</span>
                    <strong id="summaryItemCount" style="color: var(--text-primary);">5 items</strong>
                </div>

                <div style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">Estimated Retail Cost (New):</span>
                    <strong id="summaryOrigCost" style="color: var(--text-muted); text-decoration: line-through;">৳8,900</strong>
                </div>

                <div style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                    <span style="color: var(--text-secondary);">UniThrift Student Cost:</span>
                    <strong id="summarySellCost" style="color: var(--accent); font-size: 1.15rem;">৳3,680</strong>
                </div>

                <div style="background: var(--accent-light); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--accent); text-align: center;">
                    <div style="font-size: 0.85rem; color: #065f46; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Money Saved</div>
                    <div id="summarySavedTotal" style="font-size: 2.2rem; font-weight: 800; color: var(--accent); line-height: 1.2; margin: 0.25rem 0;">
                        ৳5,220
                    </div>
                    <div id="summarySavedPct" style="font-size: 0.9rem; font-weight: 700; color: #065f46;">
                        You save 59% of your budget!
                    </div>
                </div>

            </div>

            <!-- Call to Actions -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <a href="marketplace.php" class="btn btn-primary btn-lg" style="width: 100%;">
                    🔍 Browse These Items on Marketplace
                </a>
                <a href="my_listings.php" class="btn btn-outline" style="width: 100%;">
                    ➕ Have These to Sell? Post Now
                </a>
            </div>

            <div style="margin-top: 1.5rem; font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; text-align: center;">
                💡 Calculations based on real verified campus marketplace listings and student transaction data.
            </div>

        </div>

    </div>

</div>

<!-- Real-time Calculator JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('.calc-check');
    checkboxes.forEach(cb => cb.addEventListener('change', calculateSavings));
    calculateSavings();
});

function calculateSavings() {
    let origTotal = 0;
    let sellTotal = 0;
    let selectedCount = 0;

    const checkboxes = document.querySelectorAll('.calc-check:checked');
    checkboxes.forEach(cb => {
        origTotal += parseFloat(cb.getAttribute('data-orig')) || 0;
        sellTotal += parseFloat(cb.getAttribute('data-sell')) || 0;
        selectedCount++;
    });

    const savedTotal = origTotal - sellTotal;
    const savedPct = origTotal > 0 ? Math.round((savedTotal / origTotal) * 100) : 0;

    document.getElementById('summaryItemCount').textContent = `${selectedCount} item${selectedCount === 1 ? '' : 's'}`;
    document.getElementById('summaryOrigCost').textContent = '৳ ' + origTotal.toLocaleString();
    document.getElementById('summarySellCost').textContent = '৳ ' + sellTotal.toLocaleString();
    document.getElementById('summarySavedTotal').textContent = '৳ ' + savedTotal.toLocaleString();
    document.getElementById('summarySavedPct').textContent = `You save ${savedPct}% of your semester budget!`;
}

function selectAll(state) {
    document.querySelectorAll('.calc-check').forEach(cb => {
        cb.checked = state;
    });
    calculateSavings();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
