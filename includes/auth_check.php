<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        flash('warning', 'Your session has expired. Please log in again.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
$_SESSION['last_activity'] = time();

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flash(): string
{
    if (empty($_SESSION['flash'])) return '';

    $map = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];

    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $cls  = $map[$f['type']] ?? 'alert-info';
        $msg  = htmlspecialchars($f['message'], ENT_QUOTES, 'UTF-8');
        $html .= <<<HTML
<div class="alert {$cls} alert-dismissible fade show mb-3" role="alert">
  {$msg}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
HTML;
    }
    unset($_SESSION['flash']);
    return $html;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function current_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    require_login();
    if (current_role() !== $role) {
        flash('error', 'You do not have permission to access that page.');
        $redirect = current_role() === 'freelancer'
            ? BASE_URL . '/freelancer/dashboard.php'
            : BASE_URL . '/client/dashboard.php';
        header('Location: ' . $redirect);
        exit;
    }
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function truncate(string $s, int $len = 100): string
{
    return mb_strlen($s) > $len ? mb_substr($s, 0, $len) . '…' : $s;
}

function money(float $n): string
{
    return '$' . number_format($n, 2);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'just now';
    if ($diff < 3600)    return floor($diff / 60) . ' min ago';
    if ($diff < 86400)   return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800)  return floor($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}