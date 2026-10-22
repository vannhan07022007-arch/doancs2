<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? LIMIT 1");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt hàng thành công</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f8f9fa;margin:0}
        .box{max-width:560px;margin:80px auto;background:#fff;padding:40px;border-radius:8px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,.08)}
        h1{color:#e11d48;font-size:26px}
        p{color:#555;line-height:1.7}
        a{display:inline-block;margin-top:20px;background:#e11d48;color:#fff;padding:12px 28px;border-radius:4px;text-decoration:none}
    </style>
</head>
<body>
    <div class="box">
        <h1>Đặt hàng thành công!</h1>
        <p>Mã đơn hàng: <strong>#<?php echo $order['order_id']; ?></strong></p>
        <p>Tổng tiền: <strong><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</strong></p>
<p>Cảm ơn <?php echo htmlspecialchars($order['customer_name']); ?>, chúng tôi sẽ liên hệ xác nhận đơn sớm nhất.</p>
        <a href="<?php echo BASE_URL; ?>/index.php">Tiếp tục mua sắm</a>
    </div>
</body>
</html>