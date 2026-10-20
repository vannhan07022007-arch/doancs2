<?php 
require_once dirname(__DIR__) . '/config/config.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_name  = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $phone      = trim($_POST['phone'] ?? '');

    // Ghép họ + tên thành fullname
    $fullname = $last_name . ' ' . $first_name;

    if ($last_name === '' || $first_name === '' || $email === '' || $password === '' || $phone === '') {
        $error_message = "Vui lòng điền đầy đủ tất cả các thông tin bắt buộc!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Địa chỉ email không hợp lệ!";
    } elseif (!preg_match('/^(0|\+84)[0-9]{9}$/', $phone)) {
        $error_message = "Số điện thoại không hợp lệ!";
    } elseif (strlen($password) < 6) {
        $error_message = "Mật khẩu phải có ít nhất 6 ký tự!";
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetchColumn() > 0) {
            $error_message = "Email này đã được sử dụng. Vui lòng chọn email khác.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user'; // mặc định là người dùng

            $stmt = $db->prepare(
                "INSERT INTO users (fullname, email, password, phone, role) VALUES (?, ?, ?, ?, ?)"
            );

            if ($stmt->execute([$fullname, $email, $hashed_password, $phone, $role])) {
                $success_message = "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
            } else {
                $error_message = "Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại sau.";
            }
        }
    }
}

$settings = ['hotline' => '0984828392'];
?>
<?php if (!empty($error_message)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/register-login.css">
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
                <!-- Hàng 3: Số điện thoại -->
        <div class="form-row single-col">
    <div class="form-group">
        <label for="phone">Số điện thoại *</label>
        <input type="tel" id="phone" name="phone" required class="form-control"
               pattern="(0|\+84)[0-9]{9}" placeholder="Ví dụ: 0984828392">
    </div>
</div>

        <!-- Nhóm nút hành động -->
        <div class="form-actions">
            <button type="submit" class="btn-register">Đăng ký</button>
            <a href="login.php" class="login-link">Đăng nhập</a>
        </div>
    </form>
</div>