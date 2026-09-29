<?php
// contact.php - Campus Support & Administrative Contact Page
$pageTitle = "Contact Us";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentUser = current_user();
$csrfToken = get_csrf_token();
$errors = [];
$successMessage = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token   = $_POST['csrf_token'] ?? '';
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');

    if (!verify_csrf_token($token)) {
        $errors[] = "CSRF verification failed. Please try again.";
    }

    if (empty($name)) {
        $errors[] = "Please provide your full name.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    if (empty($subject)) {
        $errors[] = "Please enter an inquiry subject.";
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = "Message content must be at least 10 characters long.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, status)
                VALUES (:name, :email, :phone, :subject, :message, 'unread')
            ");
            $stmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':phone'   => $phone ?: null,
                ':subject' => $subject,
                ':message' => $message
            ]);

            set_flash('success', 'Thank you! Your message has been safely delivered to the UniThrift administrative team. We will review your inquiry shortly.');
            header("Location: contact.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to transmit message: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">

    <!-- Breadcrumb -->
    <nav class="breadcrumb" style="margin-bottom: 1.5rem;">
        <a href="index.php">Home</a>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-current">Contact &amp; Support</span>
    </nav>

    <!-- Header Section -->
    <div style="text-align: center; max-width: 680px; margin: 0 auto 2.5rem;">
        <span style="display: inline-block; background: var(--primary-light); color: var(--primary); font-size: 0.8rem; font-weight: 800; padding: 4px 12px; border-radius: var(--radius-full); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem;">
            📬 Get in Touch
        </span>
        <h1 style="font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 0.75rem;">
            Campus Support &amp; Administrative Helpdesk
        </h1>
        <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.5;">
            Have a question regarding student listings, campus meetups, security verification, or suggestions? Reach out directly to the UniThrift team.
        </p>
    </div>

    <!-- Error Messages Display -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="max-width: 960px; margin: 0 auto 1.5rem;">
            <strong>⚠️ Please correct the following errors:</strong>
            <ul style="margin: 0.5rem 0 0 1.25rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Two-Column Grid: Form & Campus Information -->
    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; max-width: 1040px; margin: 0 auto; align-items: flex-start;">

        <!-- Left Column: Contact Form -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
            
            <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>✉️</span> Send a Message to Administration
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem;">
                Your inquiry will be logged directly into the administrator command center.
            </p>

            <form method="POST" action="contact.php" id="contactForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contactName">Your Full Name *</label>
                        <input type="text" 
                               id="contactName" 
                               name="name" 
                               class="form-control" 
                               placeholder="e.g. Tanvir Hasan" 
                               value="<?= htmlspecialchars($_POST['name'] ?? ($currentUser['full_name'] ?? '')) ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactEmail">Email Address *</label>
                        <input type="email" 
                               id="contactEmail" 
                               name="email" 
                               class="form-control" 
                               placeholder="e.g. tanvir@seu.edu.bd" 
                               value="<?= htmlspecialchars($_POST['email'] ?? ($currentUser['email'] ?? '')) ?>" 
                               required>
                        <small style="color: var(--text-muted); font-size: 0.74rem;">SEU email or personal email address.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="contactPhone">Phone / WhatsApp Number (Optional)</label>
                        <input type="tel" 
                               id="contactPhone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="01700000000" 
                               value="<?= htmlspecialchars($_POST['phone'] ?? ($currentUser['phone'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactSubject">Inquiry Subject *</label>
                        <select id="contactSubject" name="subject" class="form-control" required>
                            <option value="General Inquiry" <?= (($_POST['subject'] ?? '') === 'General Inquiry') ? 'selected' : '' ?>>💬 General Inquiry / Feedback</option>
                            <option value="Account & Login Help" <?= (($_POST['subject'] ?? '') === 'Account & Login Help') ? 'selected' : '' ?>>🔑 Account / Registration Assistance</option>
                            <option value="Listing & Item Question" <?= (($_POST['subject'] ?? '') === 'Listing & Item Question') ? 'selected' : '' ?>>📦 Listing or Marketplace Question</option>
                            <option value="Safety & Moderation" <?= (($_POST['subject'] ?? '') === 'Safety & Moderation') ? 'selected' : '' ?>>🛡️ Campus Safety / Dispute Resolution</option>
                            <option value="Partnership / Club Support" <?= (($_POST['subject'] ?? '') === 'Partnership / Club Support') ? 'selected' : '' ?>>🤝 Department / Club Collaboration</option>
                            <option value="Other" <?= (($_POST['subject'] ?? '') === 'Other') ? 'selected' : '' ?>>📝 Other Inquiry</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="contactMessage">Your Message *</label>
                    <textarea id="contactMessage" 
                              name="message" 
                              class="form-control" 
                              rows="6" 
                              placeholder="Please describe your question or issue in detail..." 
                              required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    <small style="color: var(--text-muted); font-size: 0.76rem;">Minimum 10 characters.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.5rem;">
                    <span>📨</span> Send Inquiry to Administration
                </button>
            </form>

        </div>

        <!-- Right Column: Campus Details & Support Channels -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Campus Helpdesk Information Card -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.15rem; font-weight: 800; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🏛️</span> University Campus Helpdesk
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1.15rem; font-size: 0.9rem;">
                    
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <span style="font-size: 1.25rem; line-height: 1;">📍</span>
                        <div>
                            <strong>Permanent Campus</strong>
                            <div style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.4; margin-top: 2px;">
                                Southeast University (SEU)<br>
                                251/A &amp; 252, Tejgaon I/A<br>
                                Dhaka-1208, Bangladesh
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <span style="font-size: 1.25rem; line-height: 1;">📧</span>
                        <div>
                            <strong>Administrative Email</strong>
                            <div style="margin-top: 2px;">
                                <a href="mailto:admin@seu.edu.bd" style="color: var(--primary); text-decoration: none; font-weight: 700;">
                                    admin@seu.edu.bd
                                </a>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                Direct inbox of campus administrators.
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <span style="font-size: 1.25rem; line-height: 1;">⏱️</span>
                        <div>
                            <strong>Support Hours</strong>
                            <div style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 2px;">
                                Sunday &ndash; Thursday: 9:00 AM &ndash; 5:00 PM
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                Closed on University Holidays &amp; Fridays
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Instant Listing Reporting Notice Card -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.2rem;">🚩</span>
                    <strong style="font-size: 0.95rem;">Reporting an Inappropriate Item?</strong>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.45; margin: 0 0 0.85rem 0;">
                    If you noticed a counterfeit, overpriced, or prohibited listing, you do not need to fill this form &mdash; simply click the <strong>&ldquo;Report Listing&rdquo;</strong> button directly on that item&rsquo;s detail page for faster action!
                </p>
                <a href="marketplace.php" class="btn btn-outline btn-sm" style="width: 100%; text-align: center; justify-content: center;">
                    🔍 Browse Marketplace
                </a>
            </div>

        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
