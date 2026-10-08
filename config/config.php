<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/doancs2');
}
// Kết nối MySQL
$host   = "localhost";
$user   = "root";
$pass   = "";
$dbname = "shopbangiay";   // phải trùng tên database bạn đã import

mysqli_report(MYSQLI_REPORT_OFF); // để lỗi kết nối rơi vào die() bên dưới
$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Kết nối MySQL thất bại: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");