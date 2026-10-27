<?php
// Chỉ admin mới được vào (sửa đường dẫn auth.php nếu thư mục của bạn khác)
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Lấy hãng từ bảng brands (kèm logo) và đếm số sản phẩm của từng hãng
try {
    $sql = "SELECT DISTINCT b.brand_id, b.brand_name, b.brand_logo, COUNT(p.product_id) AS total_products
            FROM brands b
            LEFT JOIN products p ON p.brand_id = b.brand_id";
    $params = [];
    if ($search !== '') {
        $sql .= " WHERE b.brand_name LIKE ?";
        $params[] = "%" . $search . "%";
    }
    $sql .= " GROUP BY b.brand_id, b.brand_name, b.brand_logo ORDER BY b.brand_name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
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
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
    .brand-logo-thumb {
        width: 56px;
        height: 56px;
        object-fit: contain;
        background: #fff;
        border: 1px solid #eceef4;
        border-radius: 10px;
        padding: 4px;
    }

    .brand-logo-empty {
        width: 56px;
        height: 56px;
        display: inline-grid;
        place-items: center;
        background: #f3f4f8;
        color: #b7b9cc;
        border-radius: 10px;
        font-size: 18px;
    }
    </style>
</head>

<body>

    <div id="wrapper">

        <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column" style="flex: 1; background: #f8f9fc;">
            <div id="content">

                <?php include dirname(__DIR__) . '/includes/header.php'; ?>

                <div class="container-fluid px-4 py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 text-gray-800 font-weight-bold">Quản lý Hãng giày</h1>
                        <a href="create.php" class="btn btn-primary btn-sm shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Thêm nhãn hàng
                        </a>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
                            <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">Danh sách các hãng giày trong hệ
                                thống</h6>

                            <form action="" method="GET" class="form-inline">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Tìm tên hãng giày..."
                                        value="<?php echo htmlspecialchars($search); ?>" style="width: 250px;">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit" title="Tìm kiếm"><i
                                                class="fas fa-search"></i></button>
                                        <?php if ($search !== ''): ?>
                                        <a href="index.php" class="btn btn-outline-secondary" title="Hủy tìm kiếm"><i
                                                class="fas fa-times"></i></a>
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
                                            <th style="width: 70px;">STT</th>
                                            <th style="width: 100px;">Logo</th>
                                            <th>Tên hãng giày (Brand)</th>
                                            <th style="width: 180px;">Tổng số sản phẩm</th>
                                            <th style="width: 160px;">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($brands)): ?>
                                        <?php $stt = 1; foreach ($brands as $item): ?>
                                        <tr>
                                            <td class="align-middle text-center"><?php echo $stt++; ?></td>
                                            <td class="align-middle text-center">
                                                <?php if (!empty($item['brand_logo'])): ?>
                                                <img class="brand-logo-thumb"
                                                    src="<?php echo BASE_URL; ?>/images/brands/<?php echo htmlspecialchars($item['brand_logo']); ?>"
                                                    alt="<?php echo htmlspecialchars($item['brand_name']); ?>">
                                                <?php else: ?>
                                                <span class="brand-logo-empty"><i class="far fa-image"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="align-middle font-weight-bold text-dark">
                                                <?php echo htmlspecialchars($item['brand_name']); ?>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="badge badge-success px-3 py-2" style="font-size: 14px;">
                                                    <?php echo (int)$item['total_products']; ?> sản phẩm
                                                </span>
                                            </td>

                                            
<td class="align-middle text-center">
    <!-- Nút xem sản phẩm -->
    <a href="showproduct.php?brand=<?php echo urlencode($item['brand_name']); ?>"
       class="btn btn-sm btn-info text-white mb-1">
        <i class="fas fa-eye"></i> Xem
    </a>

    <!-- Nút sửa -->
    <a href="edit.php?id=<?php echo (int)$item['brand_id']; ?>"
       class="btn btn-sm btn-warning mb-1">
        <i class="fas fa-edit"></i> Sửa
    </a>

    <!-- Nút xóa -->
    <form action="delete.php" method="POST"
          style="display: inline-block;"
          onsubmit="return confirm('Bạn có chắc chắn muốn xóa hãng giày này không?');">
        <input type="hidden" name="brand_id"
               value="<?php echo (int)$item['brand_id']; ?>">

        <button type="submit" class="btn btn-sm btn-danger mb-1">
            <i class="fas fa-trash"></i> Xóa
        </button>
    </form>
</td>


                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Không tìm thấy hãng giày
                                                nào phù hợp.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>