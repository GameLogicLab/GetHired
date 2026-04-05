<?php
require_once __DIR__ . '/includes/auth_check.php';

// Clear session
session_unset();
session_destroy();

// Clear remember-me cookie
if (isset($_COOKIE['remember_email'])) {
    setcookie('remember_email', '', time() - 3600, '/', '', false, true);
}

// Restart session to set flash message
session_start();
flash('success', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . '/login.php');
exit;
