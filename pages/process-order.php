<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';

// "Mua ngay": đơn chỉ gồm sản phẩm trong $_SESSION['buy_now'], giỏ hàng giữ nguyên.
// Thanh toán bình thường: đơn gồm toàn bộ giỏ hàng.
$isBuyNow    = (($_POST['mode'] ?? '') === 'buy_now') && !empty($_SESSION['buy_now']);
$items       = $isBuyNow ? $_SESSION['buy_now'] : ($_SESSION['cart'] ?? []);
$checkoutUrl = BASE_URL . '/pages/checkout.php' . ($isBuyNow ? '?mode=buy_now' : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($items)) {
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$email    = trim($_POST['email'] ?? '');
$note     = trim($_POST['note'] ?? '');
$street   = trim($_POST['address'] ?? '');
$ward     = trim($_POST['ward'] ?? '');
$district = trim($_POST['district'] ?? '');
$province = trim($_POST['province'] ?? '');
$payment  = ($_POST['payment_method'] ?? 'cod') === 'vietqr' ? 'vietqr' : 'cod';

if ($fullname === '' || $phone === '' || $street === '') {
    $_SESSION['checkout_error'] = 'Vui lòng nhập Họ tên, Số điện thoại và Địa chỉ.';
    header('Location: ' . $checkoutUrl);
    exit;
}
if (!preg_match('/^[0-9]{9,11}$/', $phone)) {
    $_SESSION['checkout_error'] = 'Số điện thoại không hợp lệ (9-11 chữ số).';
    header('Location: ' . $checkoutUrl);
    exit;
}

// Địa chỉ đầy đủ (kèm ghi chú nếu có)
$fullAddress = implode(', ', array_filter([$street, $ward, $district, $province]));
if ($note !== '') {
    $fullAddress .= ' | Ghi chú: ' . $note;
}

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO orders
        (user_id, customer_name, customer_phone, customer_email, customer_address, total_amount, payment_method, order_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $fullname, $phone, $email, $fullAddress, $total, $payment
    ]);
    $orderId = (int)$db->lastInsertId();

    $itemStmt = $db->prepare("INSERT INTO order_items
        (order_id, product_id, size_value, quantity, price)
        VALUES (?, ?, ?, ?, ?)");

    // Trừ kho: chỉ trừ khi còn đủ hàng (kiểm tra và trừ diễn ra cùng một câu lệnh,
    // nên hai khách đặt cùng lúc không thể làm kho bị âm)
    $deduct = $db->prepare("UPDATE product_sizes
                            SET stock = stock - ?
                            WHERE product_id = ? AND size_value = ? AND stock >= ?");

    foreach ($items as $item) {
        $productId = $item['product_id'] ?? $item['id'] ?? null;
        $qty       = (int)$item['quantity'];

        if ($productId === null || $qty < 1) {
            throw new Exception('Giỏ hàng có sản phẩm không hợp lệ.');
        }

        $itemStmt->execute([
            $orderId,
            $productId,
            $item['size'],
            $qty,
            $item['price']
        ]);

        $deduct->execute([$qty, $productId, $item['size'], $qty]);
        if ($deduct->rowCount() === 0) {
            $tenSp = $item['product_name'] ?? ('Sản phẩm #' . $productId);
            throw new Exception('"' . $tenSp . '" size ' . $item['size'] . ' không đủ hàng, vui lòng giảm số lượng hoặc chọn size khác.');
        }
    }

    // Đánh dấu đơn này đã trừ kho (để khi admin hủy đơn thì cộng trả lại đúng một lần)
    $db->prepare("UPDATE orders SET stock_deducted = 1 WHERE order_id = ?")->execute([$orderId]);

    $db->commit();

    // Mua ngay thì chỉ xóa sản phẩm mua ngay; thanh toán giỏ thì xóa giỏ
    if ($isBuyNow) {
        unset($_SESSION['buy_now']);
    } else {
        unset($_SESSION['cart']);
    }

    header('Location: ' . BASE_URL . '/pages/order-success.php?id=' . $orderId);
    exit;

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['checkout_error'] = 'Không thể đặt hàng: ' . $e->getMessage();
    header('Location: ' . $checkoutUrl);
    exit;
}