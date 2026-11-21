<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once ROOT_DIR . '/includes/helpers.php';

const SESSION_IDLE = 1800; // 30 menit

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

// Sesi kedaluwarsa saat tidak aktif (kasus S5).
if (isset($_SESSION['user'])) {
    if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_IDLE) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['last_activity'] = time();
    }
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Token CSRF tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function require_login(): array
{
    $u = user();
    if (!$u) {
        redirect(BASE . '/auth/login.php');
    }
    return $u;
}

function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('Halaman ini hanya untuk peran: ' . implode(', ', $roles) . '.');
    }
    return $u;
}
