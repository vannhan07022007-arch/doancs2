<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lùi về 2 cấp để trỏ đúng file cấu hình kết nối CSDL ở thư mục gốc
require_once dirname(dirname(__DIR__)) . '/config/config.php';

$error = '';

// Xử lý khi người dùng submit form thêm nhãn hàng mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = trim($_POST['brand'] ?? '');

    if ($brand === '') {
        $error = 'Vui lòng nhập tên nhãn hàng.';
    } else {
        try {
            // Lấy tạm một category_id đầu tiên đang có sẵn trong bảng categories để tránh lỗi khóa ngoại
            $stmtCat = $db->query("SELECT category_id FROM categories LIMIT 1");
            $defaultCategory = $stmtCat->fetch(PDO::FETCH_ASSOC);
            $category_id = $defaultCategory ? $defaultCategory['category_id'] : 1;

            // Tạo mã sản phẩm ngẫu nhiên độc nhất để không bị lỗi trùng lặp (Duplicate entry)
            $product_code = 'BRAND-' . strtoupper(substr(uniqid(), -6));
            $product_name = 'Sản phẩm mẫu ' . $brand;

            // Thực hiện thêm vào bảng products
            $stmt = $db->prepare("INSERT INTO products (product_name, product_code, brand, price, category_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product_name, $product_code, $brand, 0, $category_id]);
            
            // Thêm thành công thì chuyển hướng về trang danh sách hãng
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error = 'Lỗi thêm dữ liệu: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Nhãn hàng - Văn Nhân Soccer Admin</title>
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
                    <h1 class="h3 text-gray-800 font-weight-bold">Thêm Nhãn hàng mới</h1>
                    <a href="index.php" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Quay lại danh sách
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Nhập tên nhãn hàng</h6>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="form-group">
                                <label for="brand" class="font-weight-bold">Tên nhãn hàng (Brand) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="brand" name="brand" placeholder="Ví dụ: Nike, Adidas, Puma..." required autofocus>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Lưu nhãn hàng
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary ml-2">Hủy bỏ</a>
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