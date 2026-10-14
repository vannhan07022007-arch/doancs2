<?php
// Bật session và kết nối cấu hình
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Xử lý các thao tác trong giỏ hàng (Xóa sản phẩm hoặc cập nhật số lượng nếu cần)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Xóa 1 sản phẩm khỏi giỏ hàng
    if ($action === 'delete' && isset($_GET['key'])) {
        $key = $_GET['key'];
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
        }
        header("Location: cart.php");
        exit();
    }
    
    // Xóa toàn bộ giỏ hàng
    if ($action === 'clear') {
        unset($_SESSION['cart']);
        header("Location: cart.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Văn Nhân Soccer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f8f9fa; }
        .cart-container { max-width: 1000px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .cart-title { font-size: 24px; margin-bottom: 20px; color: #333; border-bottom: 2px solid #e91e63; padding-bottom: 10px; }
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .cart-table th, .cart-table td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
        .cart-table th { background: #f1f1f1; color: #333; }
        .cart-item-img { width: 70px; height: 70px; object-fit: contain; border-radius: 4px; border: 1px solid #eee; }
        .btn-remove { color: #ff4d4d; background: none; border: none; cursor: pointer; font-size: 16px; }
        .btn-remove:hover { color: #cc0000; }
        .cart-summary { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
        .total-amount { font-size: 22px; color: #e91e63; font-weight: bold; }
        .btn-action-group { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold; cursor: pointer; border: none; }
        .btn-continue { background: #6c757d; color: #fff; }
        .btn-checkout { background: #e91e63; color: #fff; }
        .empty-cart { text-align: center; padding: 40px; color: #666; font-size: 16px; }
    </style>
</head>
<body>

    <!-- Header chung của website -->
    <?php include_once dirname(__DIR__) . '/includes/header.php'; ?>

    <div class="cart-container">
        <h2 class="cart-title">Giỏ Hàng Của Bạn</h2>

        <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Size</th>
                        <th>Đơn giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    foreach ($_SESSION['cart'] as $key => $item):
                        $subtotal = $item['price'] * $item['quantity'];
                        $grand_total += $subtotal;
                    ?>
                        <tr>
                            <td>
                                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['main_image']); ?>" alt="" class="cart-item-img">
                            </td>
                            <td style="text-align: left; font-weight: bold; color: #333;">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </td>
                            <td>
                                <span style="background: #fff5f8; color: #e91e63; padding: 4px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #ffccd5;">
                                    <?php echo htmlspecialchars($item['size']); ?>
                                </span>
                            </td>
                            <td style="color: #e91e63; font-weight: bold;">
                                <?php echo number_format($item['price'], 0, ',', '.'); ?>đ
                            </td>
                            <td>
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td style="font-weight: bold; color: #333;">
                                <?php echo number_format($subtotal, 0, ',', '.'); ?>đ
                            </td>
                            <td>
                                <!-- Nút xóa sản phẩm này ra khỏi giỏ -->
                                <a href="cart.php?action=delete&key=<?php echo $key; ?>" class="btn-remove" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-summary">
                <div>
                    <a href="cart.php?action=clear" class="btn" style="background: #dc3545; color: #fff;" onclick="return confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?');">Xóa tất cả</a>
                    <a href="category.php" class="btn btn-continue">Tiếp tục mua sắm</a>
                </div>
                <div style="text-align: right;">
                    <div style="margin-bottom: 10px; font-size: 16px;">
                        Tổng tiền thanh toán: <span class="total-amount"><?php echo number_format($grand_total, 0, ',', '.'); ?>đ</span>
                    </div>
                    <a href="checkout.php" class="btn btn-checkout">Tiến hành thanh toán</a>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-cart">
                <p style="margin-bottom: 20px;"><i class="fa-solid fa-cart-shopping" style="font-size: 48px; color: #ccc;"></i></p>
                <p>Giỏ hàng của bạn đang trống!</p>
                <br>
                <a href="category.php" class="btn btn-checkout" style="display: inline-block;">Quay lại mua sắm ngay</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>