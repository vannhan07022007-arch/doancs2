<?php
require_once dirname(__DIR__) . '/config/config.php';

// Đã đăng nhập bằng tài khoản admin thì vào thẳng trang quản trị
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . '/Admin/index.php');
    exit;
}

// Token chống CSRF
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token    = $_POST['csrf'] ?? '';

    if (!hash_equals($_SESSION['admin_csrf'], $token)) {
        $error = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.';
    } else {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

        $ok = false;

        if ($dbUser) {
            $stored = $dbUser['password'];

            if (password_verify($password, $stored)) {
                // Mật khẩu bcrypt bình thường
                $ok = true;
            } elseif (strlen($stored) === 32 && hash_equals($stored, md5($password))) {
                // Mật khẩu cũ dạng MD5: chấp nhận rồi đổi sang bcrypt
                $ok = true;
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $up->execute([$newHash, $dbUser['user_id']]);
            }
        }

        // Chỉ cho phép tài khoản có role = admin
        if ($ok && $dbUser['role'] === 'admin') {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $dbUser['user_id'];
            $_SESSION['user_name']  = $dbUser['fullname'];
            $_SESSION['user_email'] = $dbUser['email'];
            $_SESSION['role']       = 'admin';
            unset($_SESSION['admin_csrf']);

            header('Location: ' . BASE_URL . '/Admin/index.php');
            exit;
        }

        // Một thông báo chung, không tiết lộ email có tồn tại hay không
        $error = 'Email hoặc mật khẩu không đúng, hoặc tài khoản không có quyền quản trị.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị - Văn Nhân Soccer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/admin.css">
</head>
<body class="login-page">

    <header class="login-topbar">
        <a class="login-brand" href="<?php echo BASE_URL; ?>/index.php">
            <span class="login-brand-logo">VNS</span>
            <span class="login-brand-name">Văn Nhân Soccer</span>
        </a>
        <a class="login-avatar" href="<?php echo BASE_URL; ?>/index.php" aria-label="Quay về trang bán hàng">
            <i class="fa-solid fa-user"></i>
        </a>
    </header>

    <main class="login-main">
        <div class="login-card">
            <div class="login-badge-wrap"><span class="login-badge">Chào mừng quay trở lại</span></div>

            <h1 class="login-title">Đăng nhập vào Trang quản lý</h1>
            <p class="login-sub">Nhập thông tin xác thực để truy cập bảng điều khiển độc quyền của bạn</p>

            <?php if ($error !== ''): ?>
                <div class="login-alert" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="on">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['admin_csrf']); ?>">

                <div class="login-field">
                    <div class="login-label-row"><label for="email">Địa chỉ Email</label></div>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-at login-icon"></i>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($email); ?>"
                               placeholder="name@domain.com" autofocus>
                    </div>
                </div>

                <div class="login-field">
                    <div class="login-label-row">
                        <label for="password">Mật khẩu</label>
                        <span class="login-hint">Tối thiểu 8 ký tự</span>
                    </div>
                    <div class="login-input-wrap">
                        <i class="fa-solid fa-lock login-icon"></i>
                        <input type="password" id="password" name="password" required
                               placeholder="Nhập mật khẩu">
                        <button type="button" class="login-toggle-pass" id="togglePass" aria-label="Hiện mật khẩu">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">Đăng nhập <i class="fa-solid fa-arrow-right"></i></button>
            </form>
        </div>
    </main>

    <footer class="login-footer">
        <span>&copy; <?php echo date('Y'); ?> Văn Nhân Soccer. Bảo lưu mọi quyền.</span>
        <nav>
            <a href="#">Chính sách bảo mật</a>
            <a href="#">Điều khoản dịch vụ</a>
            <a href="<?php echo BASE_URL; ?>/index.php">Về trang bán hàng</a>
        </nav>
    </footer>

    <script>
        const pass = document.getElementById('password');
        const btn  = document.getElementById('togglePass');
        btn.addEventListener('click', function () {
            const show = pass.type === 'password';
            pass.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            btn.innerHTML = show
                ? '<i class="fa-regular fa-eye-slash"></i>'
                : '<i class="fa-regular fa-eye"></i>';
        });
    </script>
</body>
</html>