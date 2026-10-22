<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
$settings = ['hotline' => '0984828392'];

// Đăng nhập dựa trên user_id
$isLoggedIn = isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null;

$email    = $_SESSION['user_email'] ?? '';
$fullName = $_SESSION['user_name']  ?? '';

// Thông tin lấy từ bảng users
$userRow      = ['fullname' => '', 'phone' => '', 'address' => ''];
$addressCount = 0;

if ($isLoggedIn) {
    $stmt = $db->prepare("SELECT fullname, phone, address FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $userRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: $userRow;

    if ($fullName === '' && !empty($userRow['fullname'])) {
        $fullName = $userRow['fullname'];
        $_SESSION['user_name'] = $fullName;
    }

    $addressCount = !empty($userRow['address']) ? 1 : 0;
}

// Tab đang chọn: info | orders | password | address
$tab = $_GET['tab'] ?? 'info';
$allowedTabs = ['info', 'orders', 'password', 'address'];
if (!in_array($tab, $allowedTabs)) {
    $tab = 'info';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang khách hàng</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/register-login.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/account.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
    </style>
</head>
<body>
    <?php include_once dirname(__DIR__) . '/includes/header.php'; ?>

    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php">Trang chủ</a>
            <span class="separator">&gt;</span>
            <span class="current">Trang khách hàng</span>
        </div>

        <h1 class="account-title">Trang khách hàng</h1>

        <?php if ($isLoggedIn): ?>

        <div class="account-wrapper">

            <!-- CỘT TRÁI: MENU -->
            <aside class="account-sidebar">
                <h2>TRANG TÀI KHOẢN</h2>
                <p class="greeting">
                    Xin chào, <span><?php echo htmlspecialchars($fullName); ?></span> !
                </p>

                <ul class="account-nav">
                    <li>
                        <a href="?tab=info" class="<?php echo $tab === 'info' ? 'active' : ''; ?>">Thông tin tài khoản</a>
                    </li>
                    <li>
                        <a href="?tab=orders" class="<?php echo $tab === 'orders' ? 'active' : ''; ?>">Đơn hàng của bạn</a>
                    </li>
                    <li>
                        <a href="?tab=address" class="<?php echo $tab === 'address' ? 'active' : ''; ?>">Sổ địa chỉ (<?php echo $addressCount; ?>)</a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/pages/logout.php">Đăng xuất</a>
                    </li>
                </ul>
            </aside>

            <!-- CỘT PHẢI: NỘI DUNG -->
            <section class="account-content">

                <?php if ($tab === 'info'): ?>
                    <h2>THÔNG TIN TÀI KHOẢN</h2>
                    <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($fullName); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($userRow['phone'] ?? ''); ?></p>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars(!empty($userRow['address']) ? $userRow['address'] : 'Chưa có'); ?></p>

                <?php elseif ($tab === 'orders'): ?>
                    <h2>ĐƠN HÀNG CỦA BẠN</h2>
                    <p>Bạn chưa có đơn hàng nào.</p>

                <?php elseif ($tab === 'password'): ?>
                    <h2>ĐỔI MẬT KHẨU</h2>
                    <form action="<?php echo BASE_URL; ?>/pages/change-password.php" method="POST" class="account-form">
                        <div class="form-group">
                            <label>Mật khẩu cũ *</label>
                            <input type="password" name="old_password" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu mới *</label>
                            <input type="password" name="new_password" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Nhập lại mật khẩu mới *</label>
                            <input type="password" name="confirm_password" required class="form-control">
                        </div>
                        <button type="submit" class="btn-register">Cập nhật</button>
                    </form>

                <?php elseif ($tab === 'address'): ?>
                    <h2>ĐỊA CHỈ CỦA BẠN</h2>

                    <a href="<?php echo BASE_URL; ?>/pages/address.php" class="btn-add-address">
                        <?php echo $addressCount ? 'Sửa địa chỉ' : 'Thêm địa chỉ'; ?>
                    </a>

                    <?php if ($addressCount): ?>
                        <div class="address-card">
                            <div class="address-info">
                                <strong><?php echo htmlspecialchars($userRow['fullname']); ?></strong>
                                <p>SĐT: <?php echo htmlspecialchars($userRow['phone']); ?></p>
                                <p>Địa chỉ: <?php echo htmlspecialchars($userRow['address']); ?></p>
                            </div>
                            <div class="address-actions">
                                <form action="<?php echo BASE_URL; ?>/pages/address-save.php" method="POST"
                                      onsubmit="return confirm('Xóa địa chỉ này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="link-btn danger">Xóa</button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Bạn chưa có địa chỉ nào.</p>
                    <?php endif; ?>

                <?php endif; ?>

            </section>
        </div>

        <?php else: ?>

        <!-- KHÁCH CHƯA ĐĂNG NHẬP -->
        <div class="account-guest">
            <p>Bạn chưa đăng nhập.</p>
            <div class="account-guest-actions">
                <a href="<?php echo BASE_URL; ?>/pages/login.php" class="btn-register">Đăng nhập</a>
                <a href="<?php echo BASE_URL; ?>/pages/register.php" class="login-link">Đăng ký</a>
            </div>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>