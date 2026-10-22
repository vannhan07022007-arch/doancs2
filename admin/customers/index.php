<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lùi về 2 cấp để trỏ đúng file cấu hình kết nối CSDL ở thư mục gốc
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Truy vấn lấy danh sách tài khoản khách hàng từ CSDL
try {
    $stmt = $db->prepare("SELECT * FROM users ORDER BY user_id DESC");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $customers = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tài khoản - Văn Nhân Soccer Admin</title>
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
                <h1 class="h3 mb-4 text-gray-800 font-weight-bold">Quản lý Tài khoản Khách hàng</h1>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Danh sách người dùng hệ thống</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email đăng ký</th>
                                        <th>Họ tên</th>
                                        <th>Số điện thoại</th>
                                        <th>Địa chỉ</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($customers)): ?>
                                        <?php foreach ($customers as $cus): ?>
                                            <tr>
                                                <td><?php echo $cus['user_id']; ?></td>
                                                <td><?php echo htmlspecialchars($cus['email'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($cus['fullname'] ?? 'Chưa cập nhật'); ?></td>
                                                <td><?php echo htmlspecialchars($cus['phone'] ?? 'Chưa cập nhật'); ?></td>
                                                <td><?php echo htmlspecialchars($cus['address'] ?? 'Chưa cập nhật'); ?></td>
                                                <td>
                                                    <a href="detail.php?id=<?php echo $cus['user_id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">Chưa có tài khoản khách hàng nào trong hệ thống.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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