<?php
// Admin/products/delete.php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chỉ nhận yêu cầu POST; mở trực tiếp bằng đường link thì quay về danh sách
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$token = $_POST['csrf'] ?? '';
$id    = (int)($_POST['delete_id'] ?? 0);

// Quay lại đúng trang/tìm kiếm đang xem
$back = 'index.php';
if (!empty($_POST['back_query'])) {
    $back .= '?' . $_POST['back_query'];
}

if (empty($_SESSION['product_csrf']) || !hash_equals($_SESSION['product_csrf'], $token)) {
    $_SESSION['flash'] = ['danger', 'Phiên làm việc đã hết hạn, vui lòng thử lại.'];
} elseif ($id <= 0) {
    $_SESSION['flash'] = ['danger', 'Sản phẩm không hợp lệ.'];
} else {
    try {
        $db->beginTransaction();

        // Xóa dữ liệu phụ của sản phẩm trước, rồi xóa sản phẩm
        $db->prepare("DELETE FROM product_sizes  WHERE product_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
        $del = $db->prepare("DELETE FROM products WHERE product_id = ?");
        $del->execute([$id]);

        $db->commit();

        $_SESSION['flash'] = $del->rowCount() > 0
            ? ['success', 'Đã xóa sản phẩm #' . $id . '.']
            : ['danger', 'Không tìm thấy sản phẩm #' . $id . '.'];
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        // Thường do sản phẩm đã nằm trong đơn hàng (order_items)
        $_SESSION['flash'] = ['danger', 'Không thể xóa sản phẩm này vì đã phát sinh trong đơn hàng.'];
    }
}

header('Location: ' . $back);
exit;