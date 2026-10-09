<?php
// Bật lại kết nối cấu hình để load BASE_URL cho header
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$settings = ['hotline' => '0984828392'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Văn Nhân Soccer - Giày Bóng Đá Chính Hãng</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }
    body {
        background-color: #f8f9fa;
    }
    </style>
</head>

<body>

    <!-- 1. Header & Mega Menu -->
    <?php include_once dirname(__DIR__) . '/includes/header.php'; ?>

</body>
</html>