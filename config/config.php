<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/doancs2');
}

// Kết nối MySQL bằng PDO
$host   = "localhost";
$user   = "root";      // Hoặc giữ nguyên tên biến của bạn
$pass   = "";
$dbname = "shopbangiay"; 

try {
    // Sửa lại $user và $pass cho khớp với khai báo bên trên
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Thiết lập chế độ báo lỗi của PDO để dễ debug
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error = 'Connection error: ' . $e->getMessage();
    include('error.php');
    exit();
}