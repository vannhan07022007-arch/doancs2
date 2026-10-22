<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
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
    header('Location: ' . BASE_URL . '/pages/checkout.php');
    exit;
}
if (!preg_match('/^[0-9]{9,11}$/', $phone)) {
    $_SESSION['checkout_error'] = 'Số điện thoại không hợp lệ (9-11 chữ số).';
    header('Location: ' . BASE_URL . '/pages/checkout.php');
    exit;
}

// Địa chỉ đầy đủ (kèm ghi chú nếu có)
$fullAddress = implode(', ', array_filter([$street, $ward, $district, $province]));
if ($note !== '') {
    $fullAddress .= ' | Ghi chú: ' . $note;
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
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
    foreach ($_SESSION['cart'] as $item) {
        $itemStmt->execute([
            $orderId,
            $item['product_id'] ?? $item['id'] ?? null,
            $item['size'],
            $item['quantity'],
            $item['price']
        ]);
    }

    $db->commit();
    unset($_SESSION['cart']);

    header('Location: ' . BASE_URL . '/pages/order-success.php?id=' . $orderId);
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['checkout_error'] = 'Không thể đặt hàng: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/pages/checkout.php');
    exit;
}