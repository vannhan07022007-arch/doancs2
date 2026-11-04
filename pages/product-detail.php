<?php
// Bật lại kết nối cấu hình để load BASE_URL cho header
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Bật session để lưu giỏ hàng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hiển thị size gọn: "38.0" -> "38", "38.5" -> "38.5"
function fmt_size($v) {
    $v = (string)$v;
    return strpos($v, '.') !== false ? rtrim(rtrim($v, '0'), '.') : $v;
}

// Xử lý khi bấm "Thêm vào giỏ hàng" hoặc "Mua ngay"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {

    $is_buy_now = isset($_POST['buy_now']);

    // PHẢI DÙNG $_GET['id'] VÌ URL CỦA BẠN LÀ ?id=...
    $p_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $size = isset($_POST['selected_size']) ? trim($_POST['selected_size']) : '';
    $qty  = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($qty < 1) $qty = 1;

    if ($p_id > 0 && $size !== '') {
        $cart_key = $p_id . '_' . $size;

        // Kiểm tra tồn kho của đúng size này (không tin số lượng gửi từ trình duyệt)
        $stock_stmt = $db->prepare("SELECT COALESCE(SUM(stock), 0) FROM product_sizes WHERE product_id = ? AND size_value = ?");
        $stock_stmt->execute([$p_id, $size]);
        $available = (int)$stock_stmt->fetchColumn();

        // Mua ngay không liên quan tới giỏ hàng nên không cộng số đã có trong giỏ
        $in_cart = (!$is_buy_now && isset($_SESSION['cart'][$cart_key])) ? (int)$_SESSION['cart'][$cart_key]['quantity'] : 0;

        if ($available <= 0) {
            echo "<script>alert('Size này đã hết hàng!');</script>";
        } elseif ($in_cart + $qty > $available) {
            $con_them = max(0, $available - $in_cart);
            $msg = "Kho chỉ còn $available sản phẩm size này (trong giỏ bạn đã có $in_cart). Bạn chỉ có thể thêm tối đa $con_them.";
            echo "<script>alert(" . json_encode($msg) . ");</script>";
        } else {
            // Lấy thông tin sản phẩm kết hợp tên hãng từ bảng brands
            $stmt_cart = $db->prepare("SELECT p.*, b.brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_id = ?");
            $stmt_cart->execute([$p_id]);
            $prod_info = $stmt_cart->fetch(PDO::FETCH_ASSOC);

            if ($prod_info) {
                $line = [
                    'product_id'   => $prod_info['product_id'],
                    'product_name' => $prod_info['product_name'],
                    'price'        => $prod_info['price'],
                    'main_image'   => $prod_info['main_image'],
                    'brand_name'   => $prod_info['brand_name'] ?? '',
                    'size'         => $size,
                    'quantity'     => $qty
                ];

                if ($is_buy_now) {
                    // MUA NGAY: lưu riêng 1 sản phẩm này rồi sang thanh toán, giỏ hàng không bị đụng tới
                    $_SESSION['buy_now'] = [$cart_key => $line];
                    header('Location: ' . BASE_URL . '/pages/checkout.php?mode=buy_now');
                    exit();
                }

                if (isset($_SESSION['cart'][$cart_key])) {
                    $_SESSION['cart'][$cart_key]['quantity'] += $qty;
                } else {
                    $_SESSION['cart'][$cart_key] = $line;
                }
            }

            echo "<script>alert('Đã thêm vào giỏ hàng thành công!'); window.location.href='cart.php';</script>";
            exit();
        }
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

    // 2. Lấy danh sách size kèm số lượng tồn kho, sắp xếp từ nhỏ đến lớn
    $size_stmt = $db->prepare("SELECT size_value, SUM(stock) AS stock
                               FROM product_sizes
                               WHERE product_id = ?
                               GROUP BY size_value
                               ORDER BY CAST(size_value AS DECIMAL(4,1)) ASC");
    $size_stmt->execute([$product_id]);
    $size_rows = $size_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Size được chọn sẵn: size đầu tiên còn hàng, nếu hết cả thì lấy size đầu tiên
    $default_index = 0;
    foreach ($size_rows as $i => $r) {
        if ((int)$r['stock'] > 0) { $default_index = $i; break; }
    }

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
    .size-btn.sold-out { color: #aaa !important; text-decoration: line-through; background: #f5f5f5 !important; }
    #stock-info { margin-top: 12px; font-size: 14px; min-height: 20px; }
    .btn-action:disabled { opacity: .5; cursor: not-allowed !important; }
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

                    <!-- Danh sách size + số lượng trong kho -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: bold; margin-bottom: 8px;">Kích Thước:</label>
                        <div id="size-list" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <?php if (!empty($size_rows)): ?>
                                <?php foreach ($size_rows as $index => $sz):
                                    $stock = (int)$sz['stock'];
                                    $isDefault = ($index === $default_index);
                                ?>
                                    <button type="button"
                                            class="size-btn <?php echo $isDefault ? 'active' : ''; ?> <?php echo $stock <= 0 ? 'sold-out' : ''; ?>"
                                            data-size="<?php echo htmlspecialchars($sz['size_value']); ?>"
                                            data-stock="<?php echo $stock; ?>"
                                            style="padding: 8px 16px; border: 1px solid #ddd; background: #fff; cursor: pointer; border-radius: 4px; font-weight: bold; <?php echo $isDefault ? 'border-color: #e91e63; color: #e91e63; background: #fff5f8;' : ''; ?>">
                                        <?php echo htmlspecialchars(fmt_size($sz['size_value'])); ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: red; font-style: italic;">Tạm hết size</span>
                            <?php endif; ?>
                        </div>

                        <!-- Số lượng còn trong kho của size đang chọn -->
                        <div id="stock-info"></div>

                        <!-- Input ẩn chứa size được chọn để gửi lên PHP -->
                        <input type="hidden" name="selected_size" id="selected_size"
                               value="<?php echo !empty($size_rows) ? htmlspecialchars($size_rows[$default_index]['size_value']) : ''; ?>">
                    </div>

                    <!-- Phần chọn số lượng -->
                    <div class="quantity-section" style="margin-bottom: 20px;">
                        <span class="quantity-title" style="font-weight: bold; display: block; margin-bottom: 8px;">Số lượng:</span>
                        <div class="quantity-box" style="display: flex; align-items: center; width: max-content; border: 1px solid #ddd; border-radius: 4px;">
                            <button type="button" class="qty-btn" id="decrease-btn" style="padding: 6px 12px; background: #f5f5f5; border: none; cursor: pointer;">-</button>
                            <input type="text" id="quantity" name="quantity" class="qty-input" value="1" readonly style="width: 40px; text-align: center; border: none; font-weight: bold;">
                            <button type="button" class="qty-btn" id="increase-btn" style="padding: 6px 12px; background: #f5f5f5; border: none; cursor: pointer;">+</button>
                        </div>
                    </div>

                    <!-- Nút mua hàng / Thêm vào giỏ -->
                    <div class="product-action-buttons" style="display: flex; gap: 10px;">
                        <button type="submit" name="add_to_cart" id="btn-add-cart" class="btn-action btn-add-cart" style="flex: 1; background: #e91e63; color: #fff; border: none; padding: 14px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer;">
                            THÊM VÀO GIỎ HÀNG
                        </button>
                        <button type="submit" name="buy_now" id="btn-buy-now" class="btn-action btn-buy-now" style="flex: 1; background: #333; color: #fff; border: none; padding: 14px; font-size: 15px; font-weight: bold; border-radius: 4px; cursor: pointer;">
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
    document.addEventListener("DOMContentLoaded", function () {
        const sizeButtons   = document.querySelectorAll("#size-list .size-btn");
        const selectedInput = document.getElementById("selected_size");
        const stockInfo     = document.getElementById("stock-info");
        const quantityInput = document.getElementById("quantity");
        const decreaseBtn   = document.getElementById("decrease-btn");
        const increaseBtn   = document.getElementById("increase-btn");
        const addCartBtn    = document.getElementById("btn-add-cart");
        const buyNowBtn     = document.getElementById("btn-buy-now");

        if (!quantityInput) return;

        // Số lượng tối đa được mua = tồn kho của size đang chọn
        let maxQty = 0;

        function setActive(btn) {
            sizeButtons.forEach(function (b) {
                b.style.borderColor = "#ddd";
                b.style.color = "#000";
                b.style.background = "#fff";
                b.classList.remove("active");
            });
            btn.classList.add("active");
            btn.style.borderColor = "#e91e63";
            btn.style.color = "#e91e63";
            btn.style.background = "#fff5f8";
        }

        function showStock(stock) {
            maxQty = stock;

            if (stock <= 0) {
                stockInfo.innerHTML = '<span style="color:#dc2626;font-weight:bold;">Hết hàng</span>';
            } else if (stock <= 5) {
                stockInfo.innerHTML = '<span style="color:#d97706;">Chỉ còn <b>' + stock + '</b> sản phẩm trong kho</span>';
            } else {
                stockInfo.innerHTML = '<span style="color:#16a34a;">Còn <b>' + stock + '</b> sản phẩm trong kho</span>';
            }

            // Không cho đặt vượt tồn kho
            let q = parseInt(quantityInput.value, 10) || 1;
            if (stock > 0 && q > stock) q = stock;
            if (q < 1) q = 1;
            quantityInput.value = q;

            // Hết hàng thì khóa nút mua
            [addCartBtn, buyNowBtn].forEach(function (b) { if (b) b.disabled = (stock <= 0); });
        }

        function selectBtn(btn) {
            setActive(btn);
            selectedInput.value = btn.dataset.size;
            showStock(parseInt(btn.dataset.stock, 10) || 0);
        }

        sizeButtons.forEach(function (btn) {
            btn.addEventListener("click", function () { selectBtn(btn); });
        });

        // Hiện tồn kho của size được chọn sẵn khi mở trang
        const activeBtn = document.querySelector("#size-list .size-btn.active");
        if (activeBtn) {
            selectBtn(activeBtn);
        } else {
            stockInfo.innerHTML = '<span style="color:#dc2626;font-weight:bold;">Hết hàng</span>';
            [addCartBtn, buyNowBtn].forEach(function (b) { if (b) b.disabled = true; });
        }

        // Nút giảm / tăng số lượng (tăng tối đa bằng tồn kho)
        decreaseBtn.addEventListener("click", function () {
            let q = parseInt(quantityInput.value, 10) || 1;
            if (q > 1) quantityInput.value = q - 1;
        });

        increaseBtn.addEventListener("click", function () {
            let q = parseInt(quantityInput.value, 10) || 1;
            if (q < maxQty) {
                quantityInput.value = q + 1;
            } else {
                stockInfo.innerHTML += ' <span style="color:#dc2626;">(đã đạt tối đa)</span>';
                setTimeout(function () {
                    const cur = document.querySelector("#size-list .size-btn.active");
                    if (cur) showStock(parseInt(cur.dataset.stock, 10) || 0);
                }, 1200);
            }
        });
    });
    </script>
</body>
</html>