<?php 
 require_once dirname(__DIR__) . '/config/config.php';
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/register.css">
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

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 800;
        margin: 25px 0 15px 0;
        color: #111;
        text-transform: uppercase;
    }
    </style>
</head>

<body> 
     <?php include_once dirname(__DIR__) . '/includes/header.php'; ?> 
     <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php">Trang chủ</a>
            <span class="separator">&gt;</span>
            <span class="current">đăng kí tài khoản</span>
        </div>
    </div>
    <div class="container register-container">
    <h1 class="page-title">ĐĂNG KÝ TÀI KHOẢN</h1>
    <p class="page-subtitle">Nếu chưa có tài khoản vui lòng đăng ký tại đây</p>

    <form action="register.php" method="POST" class="register-form">
        <!-- Hàng 1: Họ và Tên -->
        <div class="form-row">
            <div class="form-group">
                <label for="last_name">Họ*</label>
                <input type="text" id="last_name" name="last_name" required class="form-control">
            </div>
            <div class="form-group">
                <label for="first_name">Tên *</label>
                <input type="text" id="first_name" name="first_name" required class="form-control">
            </div>
        </div>

        <!-- Hàng 2: Email và Mật khẩu -->
        <div class="form-row">
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu *</label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>
        </div>

        <!-- Nhóm nút hành động -->
        <div class="form-actions">
            <button type="submit" class="btn-register">Đăng ký</button>
            <a href="login.php" class="login-link">Đăng nhập</a>
        </div>
    </form>
</div>