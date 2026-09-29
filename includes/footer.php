<?php
// includes/footer.php
// Site footer with course metadata and script bindings
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="brand-logo" style="margin-bottom: 0.5rem;">
                    <span class="logo-icon">🔄</span>
                    <span><?= APP_NAME ?></span>
                </div>
                <p>
                    The student-to-student academic resource marketplace. Re-homing textbooks, microcontrollers, drawing boards, and academic equipment across semesters.
                </p>
            </div>

            <div class="footer-col">
                <h4>Quick Navigation</h4>
                <ul>
                    <li><a href="index.php">Home Overview</a></li>
                    <li><a href="marketplace.php">Browse Marketplace</a></li>
                    <li><a href="calculator.php">Savings Calculator</a></li>
                    <li><a href="my_listings.php">Post / Manage Listings</a></li>
                    <li><a href="profile.php">Student Profile</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Safety &amp; Community</h4>
                <ul>
                    <li><a href="index.php#how-it-works">Campus Handover Guide</a></li>
                    <li><a href="marketplace.php">Verified Student Deals</a></li>
                    <li><a href="profile.php">Account &amp; Security</a></li>
                    <li><a href="marketplace.php?category=Textbooks">Academic ReUse Hub</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Empowering students through sustainable campus academic reuse.</span>
            <span>Zero Waste &bull; Safe Peer Exchange</span>
        </div>
    </div>
</footer>

<!-- Core JavaScript -->
<script src="assets/js/main.js"></script>

</body>
</html>
