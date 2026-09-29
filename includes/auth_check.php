<?php
// includes/auth_check.php
// Authentication, Session Security & General Helpers

require_once __DIR__ . '/../config/config.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'          => $_SESSION['user_id'],
        'student_id'  => $_SESSION['student_id'] ?? '',
        'full_name'   => $_SESSION['full_name'] ?? 'Student',
        'email'       => $_SESSION['email'] ?? '',
        'role'        => $_SESSION['role'] ?? 'student',
        'department'  => $_SESSION['department'] ?? 'CSE',
        'phone'       => $_SESSION['phone'] ?? ''
    ];
}

function require_login(string $redirect = 'auth.php'): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to access this page.');
        header("Location: $redirect");
        exit();
    }
}

function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function format_price($amount): string {
    return APP_CURRENCY . ' ' . number_format((float)$amount, 0);
}

function calc_discount_pct($original, $selling): int {
    $orig = (float)$original;
    $sell = (float)$selling;
    if ($orig <= 0 || $sell >= $orig) {
        return 0;
    }
    return (int)round((($orig - $sell) / $orig) * 100);
}

function get_item_image(?string $imageUrl): ?string {
    if (empty($imageUrl)) {
        return null;
    }
    if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
        return $imageUrl;
    }
    $cleanPath = ltrim($imageUrl, '/\\');
    if (file_exists(__DIR__ . '/../' . $cleanPath) || file_exists(__DIR__ . '/' . $cleanPath)) {
        return $imageUrl;
    }
    return null;
}

function save_item_image(?array $file): ?string {
    if (!$file || empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $fileInfo = @getimagesize($file['tmp_name']);
    if (!$fileInfo || !in_array($fileInfo['mime'], $allowedMimes)) {
        return null;
    }
    if ($file['size'] > 5 * 1024 * 1024) { // Max 5MB
        return null;
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext) ?: 'jpg';
    if ($ext === 'jpeg') $ext = 'jpg';

    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return 'assets/uploads/' . $filename;
    }
    return null;
}

// Generate or get CSRF token
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function is_admin(): bool {
    return is_logged_in() && (($_SESSION['role'] ?? '') === 'admin');
}

function require_admin(string $redirect = 'index.php'): void {
    require_login();
    if (!is_admin()) {
        set_flash('error', 'Access denied. Administrator privileges required.');
        header("Location: $redirect");
        exit();
    }
}
