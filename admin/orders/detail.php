<?php
// Admin/orders/detail.php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once __DIR__ . '/helpers.php';



if (empty($_SESSION['orders_csrf'])) {
    $_SESSION['orders_csrf'] = bin2hex(random_bytes(32));
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$order = null;
$items = [];
$loi   = '';

try {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $it = $db->prepare("SELECT oi.*, p.product_name, p.main_image
                            FROM order_items oi
                            LEFT JOIN products p ON p.product_id = oi.product_id
                            WHERE oi.order_id = ?");
        $it->execute([$id]);
        $items = $it->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $loi = $e->getMessage();
}

if (!$order && $loi === '') {
    $_SESSION['flash'] = ['danger', 'Không tìm thấy đơn hàng #' . $id . '.'];
    header('Location: index.php');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$st = $order ? o_status($order) : '';

// Tổng tiền: ưu tiên cột trong bảng orders, không có thì cộng từ các dòng sản phẩm
$lineTotal = 0;
foreach ($items as $r) {
    $lineTotal += (float)o_pick($r, ['quantity', 'qty', 'so_luong'], 1) * (float)o_pick($r, ['price', 'unit_price'], 0);
}
$sum = $order ? o_pick($order, ['total', 'total_amount', 'total_price', 'grand_total'], $lineTotal) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng - Văn Nhân Soccer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .info-table th { width: 150px; color: #8a93a6; font-weight: 600; }
        .item-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 8px; border: 1px solid #eceef4; }
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
                    <h1 class="h3 text-gray-800 font-weight-bold mb-2 mb-md-0">
                        Đơn hàng #<?php echo $id; ?>
                        <?php if ($order): ?>
                            <span class="badge badge-<?php echo o_status_class($st); ?> ml-2" style="font-size: 14px;">
                                <?php echo htmlspecialchars(o_status_label($st)); ?>
                            </span>
                        <?php endif; ?>
                    </h1>

                    <div class="d-flex align-items-center flex-wrap">
                        <?php if ($order && o_can_confirm($st)): ?>
                            <form method="POST" action="update.php" class="mr-2 mb-0"
                                  onsubmit="return confirm('Xác nhận đơn hàng #<?php echo $id; ?>? Email thông báo sẽ được gửi cho khách.');">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['orders_csrf']); ?>">
                                <input type="hidden" name="order_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="confirm">
                                <input type="hidden" name="back" value="detail">
                                <button type="submit" class="btn btn-success btn-sm shadow-sm"><i class="fas fa-check mr-1"></i> Xác nhận đơn</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($order && o_can_cancel($st)): ?>
                            <form method="POST" action="update.php" class="mr-2 mb-0"
                                  onsubmit="return confirm('Hủy đơn hàng #<?php echo $id; ?>? Email thông báo sẽ được gửi cho khách. Thao tác này không hoàn tác được.');">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['orders_csrf']); ?>">
                                <input type="hidden" name="order_id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="back" value="detail">
                                <button type="submit" class="btn btn-danger btn-sm shadow-sm"><i class="fas fa-times mr-1"></i> Hủy đơn</button>
                            </form>
                        <?php endif; ?>

                        <a href="index.php" class="btn btn-secondary btn-sm shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($flash[0]); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($flash[1]); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Đóng"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <?php if ($loi !== ''): ?>
                    <div class="alert alert-danger">Lỗi truy vấn: <?php echo htmlspecialchars($loi); ?></div>
                <?php endif; ?>

                <?php if ($order): ?>
                <div class="row">

                    <!-- Thông tin đơn -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Thông tin người nhận</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless info-table mb-0">
                                    <tr>
                                        <th>Khách hàng</th>
                                        <td><?php echo htmlspecialchars(o_pick($order, ['fullname', 'customer_name', 'receiver_name', 'name'],
                                                isset($order['user_id']) ? 'Khách #' . $order['user_id'] : '—')); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Điện thoại</th>
                                        <td><?php echo htmlspecialchars(o_pick($order, ['phone', 'customer_phone', 'receiver_phone'], '—')); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo htmlspecialchars(o_pick($order, ['customer_email', 'email'], '—')); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Địa chỉ</th>
                                        <td><?php echo htmlspecialchars(o_pick($order, ['customer_address', 'address', 'shipping_address'], '—')); ?></td>
                                    </tr>
                                    <?php $note = o_pick($order, ['note', 'notes', 'ghi_chu'], ''); if ($note !== ''): ?>
                                    <tr>
                                        <th>Ghi chú</th>
                                        <td><?php echo nl2br(htmlspecialchars($note)); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php $pay = o_pick($order, ['payment_method', 'payment'], ''); if ($pay !== ''): ?>
                                    <tr>
                                        <th>Thanh toán</th>
                                        <td><?php echo htmlspecialchars($pay); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php $date = o_pick($order, ['created_at', 'order_date', 'date'], ''); if ($date !== ''): ?>
                                    <tr>
                                        <th>Ngày đặt</th>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($date))); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Sản phẩm trong đơn -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Sản phẩm trong đơn</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">Ảnh</th>
                                                <th>Sản phẩm</th>
                                                <th>Size</th>
                                                <th>SL</th>
                                                <th>Đơn giá</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if ($items): ?>
                                            <?php foreach ($items as $r):
                                                $qty   = (int)o_pick($r, ['quantity', 'qty', 'so_luong'], 1);
                                                $price = (float)o_pick($r, ['price', 'unit_price'], 0);
                                                $size  = o_pick($r, ['size', 'size_value'], '—');
                                            ?>
                                            <tr>
                                                <td class="align-middle text-center">
                                                    <?php if (!empty($r['main_image'])): ?>
                                                        <img class="item-thumb" src="<?php echo BASE_URL . '/' . ltrim(htmlspecialchars($r['main_image']), '/'); ?>" alt="">
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle font-weight-bold">
                                                    <?php echo htmlspecialchars($r['product_name'] ?? 'Sản phẩm đã bị xóa'); ?>
                                                </td>
                                                <td class="align-middle"><?php echo htmlspecialchars((string)$size); ?></td>
                                                <td class="align-middle"><?php echo $qty; ?></td>
                                                <td class="align-middle"><?php echo o_money($price); ?></td>
                                                <td class="align-middle font-weight-bold"><?php echo o_money($qty * $price); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="6" class="text-center text-muted py-3">Đơn hàng không có sản phẩm nào.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5" class="text-right font-weight-bold">Tổng tiền</td>
                                                <td class="text-danger font-weight-bold" style="font-size: 18px;"><?php echo o_money($sum); ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
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