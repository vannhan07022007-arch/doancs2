<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
$settings = ['hotline' => '0984828392'];

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

$stmt = $db->prepare("SELECT fullname, phone, address FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([(int)$_SESSION['user_id']]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['fullname' => '', 'phone' => '', 'address' => ''];

$isEdit = !empty($userRow['address']);

$addressError = $_SESSION['address_error'] ?? '';
unset($_SESSION['address_error']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới'; ?></title>

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
            <a href="<?php echo BASE_URL; ?>/pages/account.php?tab=address">Tài khoản</a>
            <span class="separator">&gt;</span>
            <span class="current"><?php echo $isEdit ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới'; ?></span>
        </div>

        <div class="address-page">
            <h2 class="address-page-title">
                <?php echo $isEdit ? 'SỬA ĐỊA CHỈ' : 'THÊM ĐỊA CHỈ MỚI'; ?>
            </h2>

            <?php if ($addressError): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($addressError); ?></div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>/pages/address-save.php" method="POST" class="address-form">
                <input type="hidden" name="action" value="save">

                <input type="text" name="fullname" placeholder="Họ tên *" required
                       value="<?php echo htmlspecialchars($userRow['fullname'] ?? ''); ?>">

                <input type="text" name="phone" placeholder="Số điện thoại *" required
                       pattern="[0-9]{9,11}" title="Nhập 9-11 chữ số"
                       value="<?php echo htmlspecialchars($userRow['phone'] ?? ''); ?>">

                <input type="text" name="address" required
                       placeholder="Địa chỉ (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành) *"
                       value="<?php echo htmlspecialchars($userRow['address'] ?? ''); ?>">

                <div class="address-form-actions">
                    <a href="<?php echo BASE_URL; ?>/pages/account.php?tab=address" class="btn-cancel">Hủy</a>
                    <button type="submit" class="btn-submit">
                        <?php echo $isEdit ? 'Lưu địa chỉ' : 'Thêm địa chỉ'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>