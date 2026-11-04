<?php
// Admin/orders/update.php - xác nhận hoặc hủy đơn hàng
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chỉ nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token  = $_POST['csrf'] ?? '';
$id     = (int)($_POST['order_id'] ?? 0);
$action = $_POST['action'] ?? '';

// Quay lại đúng nơi vừa bấm (danh sách có bộ lọc, hoặc trang chi tiết)
$back = 'index.php';
if (($_POST['back'] ?? '') === 'detail' && $id > 0) {
    $back = 'detail.php?id=' . $id;
} elseif (!empty($_POST['back_query'])) {
    $back .= '?' . $_POST['back_query'];
}

if (empty($_SESSION['orders_csrf']) || !hash_equals($_SESSION['orders_csrf'], $token)) {
    $_SESSION['flash'] = ['danger', 'Phiên làm việc đã hết hạn, vui lòng thử lại.'];
} elseif ($id <= 0 || !in_array($action, ['confirm', 'cancel'], true)) {
    $_SESSION['flash'] = ['danger', 'Yêu cầu không hợp lệ.'];
} else {
    try {
        $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            $_SESSION['flash'] = ['danger', 'Không tìm thấy đơn hàng #' . $id . '.'];
        } else {
            $current = o_status($order);

            if ($action === 'confirm') {
                $allowed = o_can_confirm($current);
                $new     = ORDER_CONFIRMED;
                $okMsg   = 'Đã xác nhận đơn hàng #' . $id . '.';
            } else {
                $allowed = o_can_cancel($current);
                $new     = ORDER_CANCELLED;
                $okMsg   = 'Đã hủy đơn hàng #' . $id . '.';
            }

            if (!$allowed) {
                $_SESSION['flash'] = ['warning', 'Đơn hàng #' . $id . ' đang ở trạng thái "' . o_status_label($current) . '", không thể thực hiện thao tác này.'];
            } else {
                // Đổi trạng thái và (nếu cần) trả hàng về kho trong cùng một giao dịch
                $db->beginTransaction();

                // Chỉ cập nhật nếu trạng thái chưa bị ai đổi trong lúc này
                $upd = $db->prepare("UPDATE orders SET `order_status` = ?
                                     WHERE order_id = ? AND COALESCE(NULLIF(`order_status`, ''), ?) = ?");
                $upd->execute([$new, $id, ORDER_PENDING, $current]);

                if ($upd->rowCount() > 0) {
                    // Hủy đơn đã bị trừ kho lúc đặt hàng -> cộng số lượng trả lại cho từng size.
                    // Chỉ làm khi cột orders.stock_deducted = 1, để đơn cũ (chưa từng trừ kho) không bị cộng nhầm.
                    if ($action === 'cancel' && (int)($order['stock_deducted'] ?? 0) === 1) {
                        $its = $db->prepare("SELECT product_id, size_value, quantity FROM order_items WHERE order_id = ?");
                        $its->execute([$id]);
                        $restore = $db->prepare("UPDATE product_sizes SET stock = stock + ? WHERE product_id = ? AND size_value = ?");
                        foreach ($its->fetchAll(PDO::FETCH_ASSOC) as $r) {
                            $restore->execute([(int)$r['quantity'], $r['product_id'], $r['size_value']]);
                        }
                        $db->prepare("UPDATE orders SET stock_deducted = 0 WHERE order_id = ?")->execute([$id]);
                    }
                    $db->commit();

                    // Trạng thái đã đổi xong -> gửi email báo cho khách.
                    // Gửi mail lỗi thì chỉ cảnh báo, không hoàn tác việc xác nhận/hủy.
                    require_once __DIR__ . '/mailer.php';

                    $it = $db->prepare("SELECT oi.*, p.product_name
                                        FROM order_items oi
                                        LEFT JOIN products p ON p.product_id = oi.product_id
                                        WHERE oi.order_id = ?");
                    $it->execute([$id]);
                    $items = $it->fetchAll(PDO::FETCH_ASSOC);

                    [$sent, $mailMsg] = send_order_mail($order, $items, $action);

                    $_SESSION['flash'] = $sent
                        ? ['success', $okMsg . ' Đã gửi email thông báo cho khách.']
                        : ['warning', $okMsg . ' Nhưng chưa gửi được email: ' . $mailMsg];
                } else {
                    $db->rollBack();
                    $_SESSION['flash'] = ['warning', 'Đơn hàng #' . $id . ' vừa được cập nhật bởi người khác, vui lòng kiểm tra lại.'];
                }
            }
        }
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['flash'] = ['danger', 'Lỗi cập nhật đơn hàng: ' . $e->getMessage()];
    }
}

header('Location: ' . $back);
exit;