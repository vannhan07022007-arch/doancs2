<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
$settings = ['hotline' => '0984828392'];

// Đã đăng nhập rồi thì chuyển đúng trang theo role
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: ' . BASE_URL . '/Admin/index.php');
    } else {
        header('Location: ' . BASE_URL . '/pages/account.php');
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $role = $dbUser['role'] ?? '';

    // Chỉ khách hàng mới được đăng nhập ở đây; admin phải dùng trang đăng nhập quản trị
    if ($dbUser && password_verify($password, $dbUser['password']) && $role !== 'admin') {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $dbUser['user_id'];
        $_SESSION['user_name']  = $dbUser['fullname'];
        $_SESSION['user_email'] = $dbUser['email'];
        $_SESSION['role']       = $role ?: 'customer';

        header('Location: ' . BASE_URL . '/pages/account.php');
        exit;
    }

    // Thông báo chung, không cho biết email có phải tài khoản admin hay không
    $error = 'Email hoặc mật khẩu không đúng!';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Văn Nhân Soccer</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/register-login.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body { background-color: #f8f9fa; }

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
            <span class="current">Đăng nhập</span>
        </div>
    </div>

    <div class="container register-container">
        <h1 class="page-title">ĐĂNG NHẬP</h1>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="register-form login-form">

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required class="form-control"
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu *</label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-register">Đăng nhập</button>
                <a href="register.php" class="login-link">Đăng ký</a>
            </div>
        </form>
    </div>
</body>

</html>