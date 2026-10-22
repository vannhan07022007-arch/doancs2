<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';

// Xóa toàn bộ dữ liệu session
$_SESSION = [];

// Xóa cookie session trên trình duyệt
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Hủy session
session_destroy();

// Quay về trang chủ (hoặc trang đăng nhập)
header('Location: ' . BASE_URL . '/index.php');
exit;