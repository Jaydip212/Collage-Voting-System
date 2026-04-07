<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    $uid  = $_SESSION['user_id'];
    logActivity($pdo, $role, $uid, 'LOGOUT', 'User logged out');
}

logoutUser();
header('Location: ' . BASE_URL . '/login.php?msg=logged_out');
exit;
