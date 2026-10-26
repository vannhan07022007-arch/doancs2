<?php
// Bật lại kết nối cấu hình để load BASE_URL cho header
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Bật session để lưu giỏ hàng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xử lý khi bấm nút Thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    
    // PHẢI DÙNG $_GET['id'] VÌ URL CỦA BẠN LÀ ?id=...
    $p_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 
    $size = isset($_POST['selected_size']) ? trim($_POST['selected_size']) : '';
    $qty  = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($p_id > 0 && !empty($size)) {
        $cart_key = $p_id . '_' . $size;

        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['quantity'] += $qty;
        } else {
            // Lấy thông tin sản phẩm kết hợp tên hãng từ bảng brands
            $stmt_cart = $db->prepare("SELECT p.*, b.brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_id = ?");
            $stmt_cart->execute([$p_id]);
            $prod_info = $stmt_cart->fetch(PDO::FETCH_ASSOC);

            if ($prod_info) {
                $_SESSION['cart'][$cart_key] = [
                    'product_id'   => $prod_info['product_id'],
                    'product_name' => $prod_info['product_name'],
                    'price'        => $prod_info['price'],
                    'main_image'   => $prod_info['main_image'],
                    'brand_name'   => $prod_info['brand_name'] ?? '',
                    'size'         => $size,
                    'quantity'     => $qty
                ];
            }
        }

        echo "<script>alert('Đã thêm vào giỏ hàng thành công!'); window.location.href='cart.php';</script>";
        exit();
    } else {
        echo "<script>alert('Vui lòng chọn kích thước giày!');</script>";
    }
}

$settings = ['hotline' => '0984828392'];

// ==========================================
// KIỂM TRA XEM CÓ ĐANG XEM CHI TIẾT SẢN PHẨM KHÔNG?
// ==========================================
$product_id = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : 0;
$is_detail = ($product_id > 0);

if ($is_detail) {
    // 1. Lấy thông tin chi tiết sản phẩm kết hợp bảng brands để lấy tên hãng (`brand_name`)
    $stmt = $db->prepare("SELECT p.*, b.brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "Không tìm thấy sản phẩm!";
        exit();
    }

    // 2. Lấy danh sách size của sản phẩm này
    $size_stmt = $db->prepare("SELECT DISTINCT size_value FROM product_sizes WHERE product_id = ? ORDER BY size_value ASC");
    $size_stmt->execute([$product_id]);
    $sizes = $size_stmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Lấy sản phẩm cùng loại (cùng category_id) kết hợp bảng brands
    $related_stmt = $db->prepare("SELECT p.*, b.brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE p.category_id = ? AND p.product_id != ? LIMIT 5");
    $related_stmt->execute([$product['category_id'], $product_id]);
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // ĐÂY LÀ PHẦN CODE LỌC / HIỂN THỊ DANH SÁCH CỦA BẠN (nếu không truyền id sản phẩm)
    // ...
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_detail ? htmlspecialchars($product['product_name']) : "Văn Nhân Soccer - Giày Bóng Đá Chính Hãng"; ?></title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/product-detail.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }
    body {
        background-color: #f8f9fa;
    }
    /* Thêm CSS tùy chỉnh cho trang chi tiết nếu cần */
    </style>
</head>

<body>

    <!-- 1. Header & Mega Menu (Giữ nguyên không đổi) -->
    <?php include_once dirname(__DIR__) . '/includes/header.php'; ?>

    <!-- 2. NỘI DUNG THAY ĐỔI TÙY THEO TRANG -->
    <?php if ($is_detail): ?>
        <!-- ================= GIAO DIỆN CHI TIẾT SẢN PHẨM ================= -->
        <form action="" method="POST" style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
            <div style="display: flex; gap: 40px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                
                <!-- Ảnh sản phẩm lớn -->
                <div style="flex: 1;">
                    <img src="<?php echo BASE_URL . '/' . $product['main_image']; ?>" alt="" style="width: 100%; height: auto; object-fit: contain; border-radius: 6px;">
                </div>

                <!-- Thông tin & Chọn size -->
                <div style="flex: 1.2;">
                    <h1 style="font-size: 24px; color: #333; margin-bottom: 10px;"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                    <p style="font-size: 14px; color: #666; margin-bottom: 8px;">Thương hiệu: <span style="color: #e91e63; font-weight: bold;"><?php echo htmlspecialchars($product['brand_name']); ?></span></p>
                    <p style="font-size: 14px; color: #666; margin-bottom: 15px;">Mã SP: <?php echo htmlspecialchars($product['product_code']); ?></p>

                    <!-- Giá tiền -->
                    <div style="margin-bottom: 20px;">
                        <span style="font-size: 26px; color: #e91e63; font-weight: bold;"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
                    </div>

                    <!-- Danh sách size (Lưu ý thêm input ẩn để lưu size người dùng chọn) -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px;">Kích Thước:</label>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php if (!empty($sizes)): ?>
                                <?php foreach ($sizes as $index => $sz): ?>
                                    <button type="button" onclick="selectSize(this, '<?php echo $sz; ?>')" 
                                            class="size-btn <?php echo $index === 0 ? 'active' : ''; ?>"
                                            style="padding: 8px 16px; border: 1px solid #ddd; background: #fff; cursor: pointer; border-radius: 4px; font-weight: bold; <?php echo $index === 0 ? 'border-color: #e91e63; color: #e91e63; background: #fff5f8;' : ''; ?>">
                                        <?php echo htmlspecialchars($sz); ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: red; font-style: italic;">Tạm hết size</span>
                            <?php endif; ?>
                        </div>
                        <!-- Input ẩn chứa size được chọn để gửi lên PHP -->
                        <input type="hidden" name="selected_size" id="selected_size" value="<?php echo !empty($sizes) ? $sizes[0] : ''; ?>">
                    </div>

                    <!-- Phần chọn số lượng -->
                    <div class="quantity-section" style="margin-bottom: 20px;">
                        <span class="quantity-title" style="font-weight: bold; display: block; margin-bottom: 8px;">Số lượng:</span>
                        <div class="quantity-box" style="display: flex; align-items: center; width: max-content; border: 1px solid #ddd; border-radius: 4px;">
                            <button type="button" class="qty-btn" id="decrease-btn" onclick="changeQty(-1)" style="padding: 6px 12px; background: #f5f5f5; border: none; cursor: pointer;">-</button>
                            <input type="text" id="quantity" name="quantity" class="qty-input" value="1" readonly style="width: 40px; text-align: center; border: none; font-weight: bold;">
                            <button type="button" class="qty-btn" id="increase-btn" onclick="changeQty(1)" style="padding: 6px 12px; background: #f5f5f5; border: none; cursor: pointer;">+</button>
                        </div>
                    </div>

                    <!-- Nút mua hàng / Thêm vào giỏ -->
                    <div class="product-action-buttons" style="display: flex; gap: 10px;">
                        <button type="submit" name="add_to_cart" class="btn-action btn-add-cart" style="flex: 1; background: #e91e63; color: #fff; border: none; padding: 14px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                            THÊM VÀO GIỎ HÀNG
                        </button>
                        <button type="submit" name="buy_now" class="btn-action btn-buy-now" style="flex: 1; background: #333; color: #fff; border: none; padding: 14px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                            MUA NGAY
                        </button>
                    </div>
                </div>
            </div>

            <!-- Phần mô tả sản phẩm -->
            <div style="background: #fff; margin-top: 30px; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3 style="border-bottom: 2px solid #e91e63; padding-bottom: 10px; margin-bottom: 15px; color: #333;">Mô tả sản phẩm</h3>
                <div style="line-height: 1.6; color: #444;">
                    <?php echo !empty($product['description']) ? $product['description'] : 'Đang cập nhật thông tin mô tả cho sản phẩm này.'; ?>
                </div>
            </div>
        </form>

    <?php else: ?>
        <!-- ================= GIAO DIỆN DANH SÁCH / LỌC ================= -->
        <div style="max-width: 1200px; margin: 30px auto; padding: 0 15px;">
            <h2>Danh sách sản phẩm</h2>
        </div>
    <?php endif; ?>

    <script>
        function selectSize(btn, sizeVal) {
            let buttons = btn.parentNode.querySelectorAll('button');
            buttons.forEach(b => {
                b.style.borderColor = '#ddd';
                b.style.color = '#000';
                b.style.background = '#fff';
            });
            btn.style.borderColor = '#e91e63';
            btn.style.color = '#e91e63';
            btn.style.background = '#fff5f8';
        }
        document.addEventListener("DOMContentLoaded", function () {
    const decreaseBtn = document.getElementById("decrease-btn");
    const increaseBtn = document.getElementById("increase-btn");
    const quantityInput = document.getElementById("quantity");

    if (decreaseBtn && increaseBtn && quantityInput) {
        decreaseBtn.addEventListener("click", function () {
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        increaseBtn.addEventListener("click", function () {
            let currentValue = parseInt(quantityInput.value);
            quantityInput.value = currentValue + 1;
        });

        // Đảm bảo người dùng chỉ nhập số
        quantityInput.addEventListener("input", function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value === "" || parseInt(this.value) < 1) {
                this.value = 1;
            }
        });
    }
});
    </script>
</body>
</html>