<?php
// Admin/products/detail.php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$product = null;
$sizes   = [];
$loi     = '';

try {
    $stmt = $db->prepare("SELECT p.*, b.brand_name, c.category_name
                          FROM products p
                          LEFT JOIN brands b     ON p.brand_id = b.brand_id
                          LEFT JOIN categories c ON p.category_id = c.category_id
                          WHERE p.product_id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $sz = $db->prepare("SELECT size_value, stock FROM product_sizes
                            WHERE product_id = ?
                            ORDER BY CAST(size_value AS DECIMAL(4,1)) ASC");
        $sz->execute([$id]);
        $sizes = $sz->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $loi = $e->getMessage();
}

// Không tìm thấy sản phẩm thì quay về danh sách
if (!$product && $loi === '') {
    $_SESSION['flash'] = ['danger', 'Không tìm thấy sản phẩm #' . $id . '.'];
    header('Location: index.php');
    exit;
}

// Tính phần trăm giảm và tổng tồn kho
$price    = (float)($product['price'] ?? 0);
$oldPrice = (float)($product['old_price'] ?? 0);
$discount = ($oldPrice > $price && $oldPrice > 0) ? round(($oldPrice - $price) / $oldPrice * 100) : 0;
$totalStock = array_sum(array_map(fn($r) => (int)$r['stock'], $sizes));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết sản phẩm - Văn Nhân Soccer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .detail-img {
            width: 100%;
            max-height: 380px;
            object-fit: contain;
            background: #fff;
            border: 1px solid #eceef4;
            border-radius: 12px;
        }
        .detail-img-empty {
            height: 260px;
            display: grid;
            place-items: center;
            background: #f3f4f8;
            color: #b7b9cc;
            border-radius: 12px;
            font-size: 14px;
        }
        .info-table th { width: 160px; color: #8a93a6; font-weight: 600; }
        .price-now { font-size: 26px; font-weight: 800; color: #dc3545; }
        .price-old { color: #98a1b3; text-decoration: line-through; margin-left: 8px; }
        .stock-low { color: #dc3545; font-weight: 700; }
    </style>
</head>
<body>

<div id="wrapper">

    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column" style="flex: 1; background: #f8f9fc;">
        <div id="content">

            <?php include dirname(__DIR__) . '/includes/header.php'; ?>

            <div class="container-fluid px-4 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h1 class="h3 text-gray-800 font-weight-bold mb-2 mb-md-0">Chi tiết sản phẩm</h1>
                    <div>
                        <?php if ($product): ?>
                            <a href="edit.php?id=<?php echo (int)$product['product_id']; ?>" class="btn btn-warning btn-sm text-white shadow-sm">
                                <i class="fas fa-edit mr-1"></i> Sửa
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary btn-sm shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>

                <?php if ($loi !== ''): ?>
                    <div class="alert alert-danger">Lỗi truy vấn: <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <?php if ($product): ?>
                <div class="row">

                    <!-- Ảnh -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-body">
                                <?php if (!empty($product['main_image'])): ?>
                                    <img class="detail-img"
                                         src="<?php echo BASE_URL . '/' . ltrim(htmlspecialchars($product['main_image']), '/'); ?>"
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php else: ?>
                                    <div class="detail-img-empty">Chưa có ảnh</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    #<?php echo (int)$product['product_id']; ?> -
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <span class="price-now"><?php echo number_format($price, 0, ',', '.'); ?> đ</span>
                                    <?php if ($discount > 0): ?>
                                        <span class="price-old"><?php echo number_format($oldPrice, 0, ',', '.'); ?> đ</span>
                                        <span class="badge badge-danger ml-1">-<?php echo $discount; ?>%</span>
                                    <?php endif; ?>
                                </div>

                                <table class="table table-borderless info-table mb-0">
                                    <tr>
                                        <th>Mã sản phẩm</th>
                                        <td><?php echo htmlspecialchars($product['product_code'] ?? '') ?: '—'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Hãng</th>
                                        <td><?php echo htmlspecialchars($product['brand_name'] ?? '—'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Danh mục</th>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? '—'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tổng tồn kho</th>
                                        <td><strong><?php echo $totalStock; ?></strong> đôi</td>
                                    </tr>
                                    <?php if (!empty($product['created_at'])): ?>
                                    <tr>
                                        <th>Ngày thêm</th>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($product['created_at']))); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($product['description'])): ?>
                                    <tr>
                                        <th>Mô tả</th>
                                        <td><?php echo nl2br(htmlspecialchars($product['description'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <!-- Tồn kho theo size -->
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Kích cỡ và tồn kho</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($sizes): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0 text-center">
                                            <thead>
                                                <tr>
                                                    <th>Size</th>
                                                    <?php foreach ($sizes as $r): ?>
                                                        <th><?php $v = (string)$r['size_value']; echo htmlspecialchars(strpos($v, '.') !== false ? rtrim(rtrim($v, '0'), '.') : $v); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="font-weight-bold">Số lượng</td>
                                                    <?php foreach ($sizes as $r): ?>
                                                        <td class="<?php echo (int)$r['stock'] <= 5 ? 'stock-low' : ''; ?>">
                                                            <?php echo (int)$r['stock']; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted d-block mt-2">Số màu đỏ là size sắp hết hàng (còn 5 đôi trở xuống).</small>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Sản phẩm này chưa có size nào.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>