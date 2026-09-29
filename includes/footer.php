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
                    <li><a href="my_listings.php">Post / Manage Listings</a></li>
                    <li><a href="auth.php">Student Portal</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Academic Project</h4>
                <ul>
                    <li><strong>Course:</strong> CSE 472</li>
                    <li><strong>Title:</strong> Web &amp; Internet Programming</li>
                    <li><strong>Institution:</strong> Southeast University</li>
                    <li><strong>Stack:</strong> PHP 8, MySQL, HTML5, CSS3, JS</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= APP_NAME ?> - Southeast University Department of CSE.</span>
            <span>Secure Student ReUse Platform</span>
        </div>
    </div>
</footer>

<!-- Core JavaScript -->
<script src="assets/js/main.js"></script>

</body>
</html>
