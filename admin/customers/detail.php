<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lùi về 2 cấp để trỏ đúng file cấu hình kết nối CSDL ở thư mục gốc
require_once dirname(dirname(__DIR__)) . '/config/config.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: index.php");
    exit;
}

$success_msg = '';
$error_msg = '';

// 1. XỬ LÝ CẬP NHẬT THÔNG TIN (SỬA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_customer'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    try {
        $stmt = $db->prepare("UPDATE users SET fullname = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->execute([$fullname, $phone, $address, $user_id]);
        $success_msg = "Cập nhật thông tin khách hàng thành công!";
    } catch (PDOException $e) {
        $error_msg = "Lỗi cập nhật: " . $e->getMessage();
    }
}

// 2. XỬ LÝ XÓA TÀI KHOẢN
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $error_msg = "Không thể xóa tài khoản này vì có thể đã phát sinh dữ liệu liên quan (đơn hàng, giỏ hàng...).";
    }
}

// 3. LẤY THÔNG TIN KHÁCH HÀNG ĐỂ HIỂN THỊ
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết & Sửa Tài khoản - Văn Nhân Soccer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 4.6 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div id="wrapper">

    <!-- SIDEBAR -->
    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div id="content-wrapper" class="d-flex flex-column" style="flex: 1; background: #f8f9fc;">
        <div id="content">
            
            <!-- TOPBAR -->
            <?php include dirname(__DIR__) . '/includes/header.php'; ?>

            <!-- BEGIN PAGE CONTENT -->
            <div class="container-fluid px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 font-weight-bold">Chi tiết & Chỉnh sửa Tài khoản</h1>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Thông tin tài khoản #<?php echo $customer['user_id']; ?></h6>
                        <a href="detail.php?id=<?php echo $customer['user_id']; ?>&action=delete" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này không?');">
                            <i class="fas fa-trash"></i> Xóa tài khoản này
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label><strong>Email đăng ký (Không thể thay đổi):</strong></label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label><strong>Họ và tên:</strong></label>
                                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($customer['fullname'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label><strong>Số điện thoại:</strong></label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label><strong>Địa chỉ:</strong></label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" name="update_customer" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu thay đổi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->

        </div>
    </div>

</div>

<!-- Bootstrap JS & jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>