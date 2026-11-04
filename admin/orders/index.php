<?php
// Admin/orders/index.php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once __DIR__ . '/helpers.php';


if (empty($_SESSION['orders_csrf'])) {
    $_SESSION['orders_csrf'] = bin2hex(random_bytes(32));
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ==========================================
// BỘ LỌC + TÌM KIẾM + PHÂN TRANG
// ==========================================
$allowedStatus = ['all', ORDER_PENDING, ORDER_CONFIRMED, ORDER_CANCELLED];
$status = $_GET['status'] ?? 'all';
if (!in_array($status, $allowedStatus, true)) $status = 'all';

$search = trim($_GET['search'] ?? '');
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));

$statusExpr = "COALESCE(NULLIF(o.`order_status`, ''), '" . ORDER_PENDING . "')";
$where  = [];
$params = [];
if ($status !== 'all') {
    $where[] = "$statusExpr = :st";
    $params[':st'] = $status;
}
$searchId = ltrim($search, '#');
if ($search !== '' && ctype_digit($searchId)) {
    $where[] = "o.order_id = :oid";
    $params[':oid'] = (int)$searchId;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$orders = [];
$counts = [ORDER_PENDING => 0, ORDER_CONFIRMED => 0, ORDER_CANCELLED => 0];
$total = 0;
$total_pages = 1;
$loi = '';

try {
    // Số đơn theo từng trạng thái (hiện trên các tab)
    $cs = $db->query("SELECT COALESCE(NULLIF(`order_status`, ''), '" . ORDER_PENDING . "') AS st, COUNT(*) AS n FROM orders GROUP BY st");
    foreach ($cs->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $key = o_normalize($r['st']);
        $counts[$key] = ($counts[$key] ?? 0) + (int)$r['n'];
    }

    $cnt = $db->prepare("SELECT COUNT(*) FROM orders o" . $whereSql);
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $total_pages = max(1, (int)ceil($total / $limit));
    if ($page > $total_pages) $page = $total_pages;
    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare("SELECT o.* FROM orders o" . $whereSql . " ORDER BY o.order_id DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $loi = $e->getMessage();
}

function listUrl($status, $search, $page = 1) {
    $q = [];
    if ($status !== 'all') $q['status'] = $status;
    if ($search !== '')    $q['search'] = $search;
    if ($page > 1)         $q['page']   = $page;
    return 'index.php' . ($q ? '?' . http_build_query($q) : '');
}
$backQuery = http_build_query(array_filter([
    'status' => $status !== 'all' ? $status : null,
    'search' => $search !== '' ? $search : null,
    'page'   => $page > 1 ? $page : null,
]));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - Văn Nhân Soccer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .status-tabs .nav-link { color: #5a5c69; font-weight: 600; }
        .status-tabs .nav-link.active { color: #c8143f; border-color: #e3e6f0 #e3e6f0 #fff; }
        .status-tabs .badge { font-size: 11px; }
        .action-cell { display: inline-block; min-width: 170px; }
        .action-row { display: flex; gap: 6px; margin-top: 6px; }
        .action-row form { flex: 1; margin: 0; }
        .action-row .btn { white-space: nowrap; }
    </style>
</head>
<body>

<div id="wrapper">

    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column" style="flex: 1; background: #f8f9fc;">
        <div id="content">

            <?php include dirname(__DIR__) . '/includes/header.php'; ?>

            <div class="container-fluid px-4 py-4">

                <h1 class="h3 text-gray-800 font-weight-bold mb-4">Quản lý Đơn hàng</h1>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash[0]); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($flash[1]); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if ($loi !== ''): ?>
                    <div class="alert alert-danger">Lỗi truy vấn: <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header pt-3 pb-0">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <ul class="nav nav-tabs card-header-tabs status-tabs mb-2">
                                <?php
                                $tabs = [
                                    'all'           => ['Tất cả', array_sum($counts)],
                                    ORDER_PENDING   => ['Chờ xác nhận', $counts[ORDER_PENDING]],
                                    ORDER_CONFIRMED => ['Đã xác nhận', $counts[ORDER_CONFIRMED]],
                                    ORDER_CANCELLED => ['Đã hủy', $counts[ORDER_CANCELLED]],
                                ];
                                foreach ($tabs as $key => [$label, $n]): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $status === $key ? 'active' : ''; ?>"
                                           href="<?php echo listUrl($key, $search); ?>">
                                            <?php echo $label; ?>
                                            <span class="badge badge-light border"><?php echo (int)$n; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <form action="" method="GET" class="form-inline mb-2">
                                <?php if ($status !== 'all'): ?>
                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                                <?php endif; ?>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" placeholder="Tìm theo mã đơn..."
                                           value="<?php echo htmlspecialchars($search); ?>" style="width: 200px;">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit" title="Tìm kiếm"><i class="fas fa-search"></i></button>
                                        <?php if ($search !== ''): ?>
                                            <a href="<?php echo listUrl($status, ''); ?>" class="btn btn-outline-secondary" title="Hủy tìm kiếm"><i class="fas fa-times"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th style="width: 90px;">Mã đơn</th>
                                        <th>Khách hàng</th>
                                        <th>Số điện thoại</th>
                                        <!-- <th>Email</th> -->
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th style="width: 130px;">Trạng thái</th>
                                        <th style="width: 200px;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($orders): ?>
                                    <?php foreach ($orders as $o):
                                        $st    = o_status($o);
                                        $oid   = (int)$o['order_id'];
                                        $name  = o_pick($o, ['fullname', 'customer_name', 'receiver_name', 'name'],
                                                        isset($o['user_id']) ? 'Khách #' . $o['user_id'] : '—');
                                        $phone = o_pick($o, ['phone', 'customer_phone', 'receiver_phone'], '—');
                                        $email = o_pick($o, ['customer_email', 'email'], '');
                                        $date  = o_pick($o, ['created_at', 'order_date', 'date'], '');
                                        $sum   = o_pick($o, ['total', 'total_amount', 'total_price', 'grand_total'], 0);
                                    ?>
                                    <tr>
                                        <td class="align-middle font-weight-bold">#<?php echo $oid; ?></td>
                                        <td class="align-middle"><?php echo htmlspecialchars($name); ?></td>
                                        <td class="align-middle"><?php echo htmlspecialchars($phone); ?></td>
                
                                        <td class="align-middle">
                                            <?php echo $date !== '' ? htmlspecialchars(date('d/m/Y H:i', strtotime($date))) : '—'; ?>
                                        </td>
                                        <td class="align-middle text-danger font-weight-bold"><?php echo o_money($sum); ?></td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-<?php echo o_status_class($st); ?> px-2 py-2">
                                                <?php echo htmlspecialchars(o_status_label($st)); ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <div class="action-cell">
                                                <a href="detail.php?id=<?php echo $oid; ?>" class="btn btn-sm btn-info text-white btn-block">
                                                    <i class="fas fa-eye"></i> Chi tiết
                                                </a>

                                                <?php if (o_can_confirm($st) || o_can_cancel($st)): ?>
                                                <div class="action-row">
                                                    <?php if (o_can_confirm($st)): ?>
                                                    <form method="POST" action="update.php"
                                                          onsubmit="return confirm('Xác nhận đơn hàng #<?php echo $oid; ?>? Email thông báo sẽ được gửi cho khách.');">
                                                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['orders_csrf']); ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                                        <input type="hidden" name="action" value="confirm">
                                                        <input type="hidden" name="back_query" value="<?php echo htmlspecialchars($backQuery); ?>">
                                                        <button type="submit" class="btn btn-sm btn-success btn-block">
                                                            <i class="fas fa-check"></i> Xác nhận
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>

                                                    <?php if (o_can_cancel($st)): ?>
                                                    <form method="POST" action="update.php"
                                                          onsubmit="return confirm('Hủy đơn hàng #<?php echo $oid; ?>? Email thông báo sẽ được gửi cho khách. Thao tác này không hoàn tác được.');">
                                                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['orders_csrf']); ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="back_query" value="<?php echo htmlspecialchars($backQuery); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger btn-block">
                                                            <i class="fas fa-times"></i> Hủy đơn
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Không có đơn hàng nào.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Phân trang">
                            <ul class="pagination justify-content-center mb-0 mt-3">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo listUrl($status, $search, $page - 1); ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo listUrl($status, $search, $i); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo listUrl($status, $search, $page + 1); ?>">&raquo;</a>
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