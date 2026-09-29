<?php
// includes/header.php
// Reusable navigation bar, theme switch, session state & alerts

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_check.php';

$currentUser = current_user();
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE ?></title>
    
    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Prevent theme flicker -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('unithrift_theme');
                if (savedTheme) {
                    document.documentElement.setAttribute('data-theme', savedTheme);
                } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>

<header class="site-header">
    <div class="container navbar">
        <a href="index.php" class="brand-logo">
            <span class="logo-icon">🔄</span>
            <span><?= APP_NAME ?></span>
            <span class="brand-badge">SEU</span>
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
            <li><a href="marketplace.php" class="<?= $currentPage === 'marketplace.php' ? 'active' : '' ?>">Marketplace</a></li>
            <?php if ($currentUser): ?>
                <li>
                    <a href="my_listings.php" class="<?= $currentPage === 'my_listings.php' ? 'active' : '' ?>">
                        My Listings &amp; Sell
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                        Profile
                    </a>
                </li>
                <?php if (is_admin()): ?>
                    <li>
                        <a href="admin.php" class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>" style="color: var(--warning); font-weight: 700;">
                            🛡️ Admin Panel
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <!-- Dark / Light Theme Toggle -->
            <button id="themeToggle" class="theme-toggle-btn" title="Toggle Theme" aria-label="Toggle Dark/Light Mode">
                🌙
            </button>

            <?php if ($currentUser): ?>
                <a href="profile.php" class="user-menu-pill" title="View Profile &amp; Update Mobile Number" style="text-decoration: none; color: inherit;">
                    <span class="user-avatar-sm">
                        <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                    </span>
                    <span><?= htmlspecialchars(explode(' ', $currentUser['full_name'])[0]) ?></span>
                    <?php if (is_admin()): ?>
                        <span style="font-size: 0.65rem; background: var(--warning); color: #000; padding: 1px 5px; border-radius: var(--radius-full); font-weight: 800;">ADMIN</span>
                    <?php endif; ?>
                </a>
                <a href="logout.php" class="btn btn-outline btn-sm" title="Log Out">
                    Log Out
                </a>
            <?php else: ?>
                <a href="auth.php" class="btn btn-primary btn-sm">
                    Sign In
                </a>
            <?php endif; ?>

            <button id="mobileNavToggle" class="mobile-nav-toggle" aria-label="Open Navigation Menu">
                ☰
            </button>
        </div>
    </div>
</header>

<?php if ($flash): ?>
    <div class="container flash-container">
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
            <span><?= ($flash['type'] === 'success' ? '✅ ' : ($flash['type'] === 'error' ? '⚠️ ' : 'ℹ️ ')) . htmlspecialchars($flash['message']) ?></span>
            <span style="cursor: pointer; margin-left: 10px;" onclick="this.parentElement.remove();">&times;</span>
        </div>
    </div>
<?php endif; ?>
