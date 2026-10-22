<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';

// Kiểm tra giỏ hàng có sản phẩm không
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đơn hàng - Định Lực Soccer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        .checkout-wrapper {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
        }
        .checkout-left, .checkout-right {
            background: #fff;
            padding: 20px;
        }
        .checkout-logo img {
            max-height: 60px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #222;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-control, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d9d9d9;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }
        .shipping-box, .payment-box {
            background: #fafafa;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .payment-method {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .payment-method:last-child {
            border-bottom: none;
        }
        .payment-method input {
            margin-right: 10px;
        }
        /* Cột phải: Đơn hàng */
        .checkout-right {
            background: #fafafa;
            border-left: 1px solid #e1e1e1;
            padding-left: 30px;
        }
        .checkout-product-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e5e5;
        }
        .product-info-mini {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .product-info-mini img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .discount-section {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .total-line.final {
            font-size: 20px;
            font-weight: bold;
            color: #e11d48;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
        }
        .btn-order {
            width: 100%;
            padding: 14px;
            background: #e11d48;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.2s;
        }
        .btn-order:hover {
            background: #be123c;
        }
        @media (max-width: 768px) {
            .checkout-wrapper {
                grid-template-columns: 1fr;
            }
            .checkout-right {
                border-left: none;
                padding-left: 20px;
            }
        }
    </style>
</head>
<body>

<div class="checkout-wrapper">
    <!-- CỘT TRÁI: THÔNG TIN KHÁCH HÀNG & THANH TOÁN -->
    <div class="checkout-left">
        <div class="checkout-logo">
            <a href="<?php echo BASE_URL; ?>/index.php">
                <img src="<?php echo BASE_URL; ?>/images/logo.png" alt="Định Lực Soccer">
            </a>
        </div>

        <form action="process-order.php" method="POST">
            <h3 class="section-title">Thông tin nhận hàng</h3>
            <?php if (!empty($_SESSION['checkout_error'])): ?>
    <div style="background:#fde8ee;color:#c0153f;padding:12px 16px;border-radius:4px;margin-bottom:15px;">
        <?php echo htmlspecialchars($_SESSION['checkout_error']); unset($_SESSION['checkout_error']); ?>
    </div>
<?php endif; ?>
            
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder="Email (tùy chọn)" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <input type="text" name="fullname" class="form-control" required placeholder="Họ và tên">
            </div>
            <div class="form-group">
                <input type="text" name="phone" class="form-control" required placeholder="Số điện thoại">
            </div>
            <div class="form-group">
                <input type="text" name="address" class="form-control" required placeholder="Địa chỉ cụ thể (Số nhà, tên đường...)">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <select name="province" class="form-select" required>
                        <option value="">Tỉnh thành ---</option>
                        <option value="Đà Nẵng">Đà Nẵng</option>
                        <option value="Quảng Nam">Quảng Nam</option>
                        <option value="Hồ Chí Minh">TP. Hồ Chí Minh</option>
                    </select>
                </div>
                <div class="form-group">
                    <select name="district" class="form-select" required>
                        <option value="">Quận huyện ---</option>
                        <option value="Hải Châu">Hải Châu</option>
                        <option value="Thanh Khê">Thanh Khê</option>
                        <option value="Ngũ Hành Sơn">Ngũ Hành Sơn</option>
                    </select>
                </div>
                <div class="form-group">
                    <select name="ward" class="form-select" required>
                        <option value="">Phường xã ---</option>
                        <option value="Thạc Gián">Thạc Gián</option>
                        <option value="Hòa Hải">Hòa Hải</option>
                    </select>
                </div>
            </div>

            <h3 class="section-title" style="margin-top: 30px;">Vận chuyển</h3>
            <div class="shipping-box">
                <p style="margin: 0; color: #666; font-size: 14px;"><i class="fa-solid fa-truck" style="margin-right: 8px; color: #e11d48;"></i> Vui lòng nhập thông tin giao hàng để tính phí vận chuyển.</p>
            </div>

            <h3 class="section-title">Thanh toán</h3>
<div class="payment-box">
    <label class="payment-method">
        <input type="radio" name="payment_method" value="cod" checked>
        <span>Thanh toán khi nhận hàng (COD)</span>
    </label>
</div>
    </div>

    <!-- CỘT PHẢI: TÓM TẮT ĐƠN HÀNG -->
    <div class="checkout-right">
        <h3 class="section-title">Đơn hàng (<?php echo count($_SESSION['cart']); ?> sản phẩm)</h3>
        
        <div class="checkout-products-list">
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="checkout-product-item">
                    <div class="product-info-mini">
                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['main_image']); ?>" alt="">
                        <div>
                            <div style="font-size: 14px; font-weight: 500; color: #333;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div style="font-size: 12px; color: #666;">Size: <?php echo htmlspecialchars($item['size']); ?> (SL: <?php echo $item['quantity']; ?>)</div>
                        </div>
                    </div>
                    <div style="font-size: 14px; font-weight: bold; color: #333;">
                        <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total-line">
            <span>Tạm tính</span>
            <span><?php echo number_format($total_price, 0, ',', '.'); ?>đ</span>
        </div>
        <div class="total-line">
            <span>Phí vận chuyển</span>
            <span>-</span>
        </div>
        <div class="total-line final">
            <span>Tổng cộng</span>
            <span><?php echo number_format($total_price, 0, ',', '.'); ?>đ</span>
        </div>

        <a href="cart.php" style="display: inline-block; margin-top: 15px; color: #2563eb; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-chevron-left"></i> Quay về giỏ hàng</a>

        <button type="submit" class="btn-order">ĐẶT HÀNG</button>
        </form>
    </div>
</div>

</body>
</html>