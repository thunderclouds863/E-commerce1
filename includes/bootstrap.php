<?php
// Centralized bootstrap: secure session, DB connection, CSRF, auth guards, flash messages, helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    $name = getenv('DB_NAME') ?: 'ecommerce_db';

    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        http_response_code(500);
        die("Database connection failed.");
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_idr($amount): string {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_role($roles): void {
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed, true)) {
        header("Location: index.php");
        exit();
    }
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function status_badge_class(string $status): string {
    $s = strtolower(trim($status));
    if ($s === 'selesai' || $s === 'paid') return 'badge-success';
    if ($s === 'pending' || $s === 'awaiting payment' || $s === 'dikemas') return 'badge-warning';
    if ($s === 'dikonfirmasi' || $s === 'siap dikirim' || $s === 'dikirim' || $s === 'sampai tujuan') return 'badge-info';
    if ($s === 'rejected') return 'badge-danger';
    return 'badge-neutral';
}

function cart_total_units(): int {
    $count = 0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $count += max(0, intval($item['quantity']));
            }
        }
    }
    return $count;
}
