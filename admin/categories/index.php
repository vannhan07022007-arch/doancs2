<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lùi về 2 cấp để trỏ đúng file cấu hình kết nối CSDL ở thư mục gốc
require_once dirname(dirname(__DIR__)) . '/config/config.php';

// Khởi tạo biến tìm kiếm an toàn
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Truy vấn gom nhóm các hãng giày có hỗ trợ tìm kiếm theo tên hãng
try {
    if ($search !== '') {
        $stmt = $db->prepare("SELECT brand, COUNT(product_id) as total_products FROM products WHERE brand IS NOT NULL AND brand != '' AND brand LIKE ? GROUP BY brand ORDER BY brand ASC");
        $searchTerm = "%" . $search . "%";
        $stmt->execute([$searchTerm]);
    } else {
        $stmt = $db->prepare("SELECT brand, COUNT(product_id) as total_products FROM products WHERE brand IS NOT NULL AND brand != '' GROUP BY brand ORDER BY brand ASC");
        $stmt->execute();
    }
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $brands = [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Hãng giày - Văn Nhân Soccer Admin</title>
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
                    <h1 class="h3 text-gray-800 font-weight-bold">Quản lý Hãng giày</h1>
                    <!-- Nút chuyển sang trang create.php -->
                    <a href="create.php" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Thêm nhãn hàng
                    </a>
                </div>

                <div class="card shadow mb-4">
                    <!-- CARD HEADER TÍCH HỢP Ô TÌM KIẾM -->
                    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">Danh sách các hãng giày trong hệ thống</h6>
                        
                        <!-- Form tìm kiếm hãng giày -->
                        <form action="" method="GET" class="form-inline">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control" placeholder="Tìm tên hãng giày..." value="<?php echo htmlspecialchars($search); ?>" style="width: 250px;">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit" title="Tìm kiếm">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search !== ''): ?>
                                        <a href="index.php" class="btn btn-outline-secondary" title="Hủy tìm kiếm">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">STT</th>
                                        <th>Tên hãng giày (Brand)</th>
                                        <th style="width: 200px;">Tổng số sản phẩm</th>
                                        <th style="width: 150px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($brands)): ?>
                                        <?php $stt = 1; foreach ($brands as $item): ?>
                                            <tr>
                                                <td class="align-middle text-center"><?php echo $stt++; ?></td>
                                                <td class="align-middle font-weight-bold text-dark">
                                                    <?php echo htmlspecialchars($item['brand']); ?>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <span class="badge badge-success px-3 py-2" style="font-size: 14px;">
                                                        <?php echo $item['total_products']; ?> sản phẩm
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center">
                                                <a href="showproduct.php?brand=<?php echo urlencode($item['brand']); ?>" class="btn btn-sm btn-info text-white" title="Xem danh sách sản phẩm">
                                                 <i class="fas fa-eye"></i> Xem sản phẩm
                                                </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">Không tìm thấy hãng giày nào phù hợp.</td>
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