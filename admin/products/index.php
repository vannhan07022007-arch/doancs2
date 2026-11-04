<?php
// Admin/products/index.php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Token chống CSRF cho nút xóa (delete.php sẽ kiểm tra lại token này)
if (empty($_SESSION['product_csrf'])) {
    $_SESSION['product_csrf'] = bin2hex(random_bytes(32));
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ==========================================
// TÌM KIẾM + PHÂN TRANG
// ==========================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = '';
$params = [];
if ($search !== '') {
    $where = " WHERE (p.product_name LIKE :kw1 OR p.product_code LIKE :kw2)";
    $params[':kw1'] = "%$search%";
    $params[':kw2'] = "%$search%";
}

try {
    // Tổng số sản phẩm để chia trang
    $countStmt = $db->prepare("SELECT COUNT(*) FROM products p" . $where);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $total_pages = max(1, (int)ceil($total / $limit));
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT p.product_id, p.product_name, p.product_code, p.price, p.old_price, p.main_image,
                   b.brand_name, c.category_name
            FROM products p
            LEFT JOIN brands b     ON p.brand_id = b.brand_id
            LEFT JOIN categories c ON p.category_id = c.category_id"
            . $where . " ORDER BY p.product_id DESC LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $total = 0;
    $total_pages = 1;
    $loi = $e->getMessage();
}

// Giữ bộ lọc khi chuyển trang / sau khi xóa
function pageUrl($p, $search) {
    $q = ['page' => $p];
    if ($search !== '') $q['search'] = $search;
    return 'index.php?' . http_build_query($q);
}
$backQuery = http_build_query(array_filter(['search' => $search, 'page' => $page > 1 ? $page : null]));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - Văn Nhân Soccer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .p-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eceef4;
        }
        .action-cell { display: inline-block; }
        .action-row { display: flex; gap: 6px; }
        .action-row .btn { flex: 1; white-space: nowrap; }
        .action-delete { margin: 6px 0 0; }
        .p-old { color: #98a1b3; text-decoration: line-through; font-size: 12px; }
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
                    <h1 class="h3 text-gray-800 font-weight-bold">Quản lý Sản phẩm</h1>
                    <a href="create.php" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-plus fa-sm text-white-50 mr-1"></i> Thêm sản phẩm
                    </a>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash[0]; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($flash[1]); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($loi)): ?>
                    <div class="alert alert-danger">Lỗi truy vấn: <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">
                            Danh sách sản phẩm <span class="text-muted font-weight-normal">(<?php echo $total; ?>)</span>
                        </h6>

                        <form action="" method="GET" class="form-inline">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control"
                                       placeholder="Tìm theo tên hoặc mã sản phẩm..."
                                       value="<?php echo htmlspecialchars($search); ?>" style="width: 280px;">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit" title="Tìm kiếm"><i class="fas fa-search"></i></button>
                                    <?php if ($search !== ''): ?>
                                        <a href="index.php" class="btn btn-outline-secondary" title="Hủy tìm kiếm"><i class="fas fa-times"></i></a>
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
                                        <th style="width: 60px;">ID</th>
                                        <th style="width: 90px;">Hình ảnh</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Mã SP</th>
                                        <th>Hãng</th>
                                        <th>Danh mục</th>
                                        <th>Giá bán</th>
                                        <th style="width: 190px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($products)): ?>
                                    <?php foreach ($products as $item): ?>
                                    <tr>
                                        <td class="align-middle text-center"><?php echo (int)$item['product_id']; ?></td>
                                        <td class="align-middle text-center">
                                            <?php if (!empty($item['main_image'])): ?>
                                                <img class="p-thumb"
                                                     src="<?php echo BASE_URL . '/' . ltrim(htmlspecialchars($item['main_image']), '/'); ?>"
                                                     alt="Ảnh sản phẩm">
                                            <?php else: ?>
                                                <span class="text-muted small">Không có ảnh</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle font-weight-bold text-dark">
                                            <?php echo htmlspecialchars($item['product_name'] ?? ''); ?>
                                        </td>
                                        <td class="align-middle"><?php echo htmlspecialchars($item['product_code'] ?? ''); ?></td>
                                        <td class="align-middle"><?php echo htmlspecialchars($item['brand_name'] ?? '—'); ?></td>
                                        <td class="align-middle"><?php echo htmlspecialchars($item['category_name'] ?? '—'); ?></td>
                                        <td class="align-middle">
                                            <span class="text-danger font-weight-bold">
                                                <?php echo number_format((float)$item['price'], 0, ',', '.'); ?> đ
                                            </span>
                                            <?php if (!empty($item['old_price']) && $item['old_price'] > $item['price']): ?>
                                                <div class="p-old"><?php echo number_format((float)$item['old_price'], 0, ',', '.'); ?> đ</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="action-cell">
                                                <div class="action-row">
                                                    <a href="detail.php?id=<?php echo (int)$item['product_id']; ?>"
                                                       class="btn btn-sm btn-info text-white" title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i> Chi tiết
                                                    </a>
                                                    <a href="edit.php?id=<?php echo (int)$item['product_id']; ?>"
                                                       class="btn btn-sm btn-warning text-white" title="Sửa">
                                                        <i class="fas fa-edit"></i> Sửa
                                                    </a>
                                                </div>

                                            <form method="POST" action="delete.php" class="action-delete"
                                                  onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');">
                                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['product_csrf']); ?>">
                                                <input type="hidden" name="delete_id" value="<?php echo (int)$item['product_id']; ?>">
                                                <input type="hidden" name="back_query" value="<?php echo htmlspecialchars($backQuery); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger " title="Xóa">
                                                    <i class="fas fa-trash"></i> Xóa
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <?php echo $search !== '' ? 'Không tìm thấy sản phẩm phù hợp.' : 'Chưa có sản phẩm nào.'; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Phân trang">
                            <ul class="pagination justify-content-center mb-0 mt-3">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo pageUrl($page - 1, $search); ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo pageUrl($i, $search); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo pageUrl($page + 1, $search); ?>">&raquo;</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
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