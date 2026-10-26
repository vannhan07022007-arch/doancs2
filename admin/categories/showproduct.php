<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lùi về 2 cấp để trỏ đúng file cấu hình kết nối CSDL ở thư mục gốc
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Lấy tên hãng từ URL
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';

if ($brand === '') {
    header("Location: index.php");
    exit;
}

// Truy vấn lấy danh sách sản phẩm thuộc đúng hãng được chọn
try {
    $stmt = $db->prepare("SELECT * FROM products WHERE brand = ? ORDER BY product_id DESC");
    $stmt->execute([$brand]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm hãng <?php echo htmlspecialchars($brand); ?> - Văn Nhân Soccer Admin</title>
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
                    <h1 class="h3 text-gray-800 font-weight-bold">Sản phẩm của hãng: <span class="text-primary"><?php echo htmlspecialchars($brand); ?></span></h1>
                    <a href="index.php" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Quay lại danh sách hãng
                    </a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Danh sách giày thuộc hãng <?php echo htmlspecialchars($brand); ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Hình ảnh</th>
                                        <th>Tên giày</th>
                                        <th>Mã sản phẩm</th>
                                        <th>Giá bán</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($products)): ?>
                                        <?php foreach ($products as $item): ?>
                                            <tr>
                                                <td class="align-middle"><?php echo $item['product_id']; ?></td>
                                                <td class="align-middle text-center" style="width: 100px;">
                                                    <?php if (!empty($item['main_image'])): ?>
                                                        <img src="../../<?php echo htmlspecialchars($item['main_image']); ?>" alt="Ảnh giày" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px;">
                                                    <?php else: ?>
                                                        <span class="text-muted small">Không có ảnh</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle font-weight-bold text-dark"><?php echo htmlspecialchars($item['product_name'] ?? ''); ?></td>
                                                <td class="align-middle"><?php echo htmlspecialchars($item['product_code'] ?? 'Chưa có'); ?></td>
                                                <td class="align-middle text-danger font-weight-bold">
                                                    <?php echo number_format($item['price'] ?? 0, 0, ',', '.'); ?> đ
                                                </td>
                                                <td class="align-middle">
                                                    <a href="../products/index.php" class="btn btn-sm btn-warning text-white" title="Quản lý sản phẩm">
                                                        <i class="fas fa-edit"></i> Quản lý chung
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">Chưa có sản phẩm nào thuộc hãng này.</td>
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