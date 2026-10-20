<?php
 if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy thông tin hotline từ DB hoặc cài đặt mặc định
$hotline = $settings['hotline'] ?? '0984828392';
?>

<header class="site-header">
    <div class="container header-container">
        
        <!-- 1. Logo -->
        <a href="<?php echo BASE_URL; ?>/index.php" class="header-logo">
            <img src="<?php echo BASE_URL; ?>./images/logo.png" alt="Định Lực Soccer Logo">
        </a>

        <!-- 2. Thanh tìm kiếm -->
        <div class="header-search">
            <form action="<?php echo BASE_URL; ?>/pages/category.php" method="GET" class="search-form">
                <input 
                    type="text" 
                    name="keyword" 
                    id="search-input" 
                    placeholder="Tìm kiếm..." 
                    autocomplete="off"
                    value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>"
                >
                <button type="submit" aria-label="Tìm kiếm">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </form>
            <!-- Khung chứa kết quả gợi ý AJAX -->
            <!-- <div id="search-suggestions" class="search-suggestions-box"></div> -->
        </div>

        <!-- 3. Hotline & Hành động (Tài khoản, Giỏ hàng) -->
        <div class="header-actions">
            
            <!-- Hotline tư vấn -->
            <a href="tel:<?php echo str_replace(' ', '', $hotline); ?>" class="hotline-btn">
                <div class="hotline-text">
                    <span class="sub-text">Tư vấn bán hàng</span>
                    <span class="main-text">Gọi ngay <?php echo $hotline; ?></span>
                </div>
                <div class="hotline-icon">
                    <i class="fa-solid fa-phone"></i>
                </div>
            </a>

            <div class="account-dropdown-wrapper" style="position: relative;">
    <a href="<?php echo isset($_SESSION['user_id']) ? BASE_URL . '/pages/account.php' : BASE_URL . '/pages/login.php'; ?>" class="icon-btn" id="open-account-dropdown" title="Tài khoản">
        <i class="fa-regular fa-user"></i>
    </a>

    <!-- Menu Dropdown thay đổi linh hoạt theo trạng thái đăng nhập -->
    <div class="account-menu" id="accountDropdownMenu">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- ĐÃ ĐĂNG NHẬP: Hiển thị Tài khoản và Đăng xuất -->
           <a href="<?php echo BASE_URL; ?>/pages/account.php" class="account-menu-btn" style="color: #fff; background: #f43f5e; display: block; padding: 10px; text-align: center; border-radius: 20px; margin-bottom: 8px; text-decoration: none; font-weight: bold;">Tài khoản</a>
        <a href="<?php echo BASE_URL; ?>/pages/logout.php" class="account-menu-btn" style="color: #fff; background: #f43f5e; display: block; padding: 10px; text-align: center; border-radius: 20px; text-decoration: none; font-weight: bold;">Đăng xuất</a>
        <?php else: ?>
            <!-- CHƯA ĐĂNG NHẬP: Hiển thị Đăng nhập và Đăng ký -->
            <a href="<?php echo BASE_URL; ?>/pages/login.php" class="account-menu-btn">Đăng nhập</a>
            <a href="<?php echo BASE_URL; ?>/pages/register.php" class="account-menu-btn">Đăng ký</a>
        <?php endif; ?>
    </div>
</div>
            <!-- Icon Giỏ hàng -->
           <div class="cart-dropdown-wrapper" style="position: relative;">
    <!-- Nút icon giỏ hàng -->
    <button type="button" class="icon-btn cart-btn" id="open-cart-drawer" title="Giỏ hàng">
        <i class="fa-solid fa-basket-shopping"></i>
    </button>

    <!-- Khung dropdown danh sách sản phẩm (Sẽ tự ẩn đi và chỉ hiện khi hover) -->
    <div class="cart-dropdown-box" id="cartDropdownMenu">
        <div class="cart-items-list">
            <?php 
            $total_price = 0;
            if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): 
                foreach ($_SESSION['cart'] as $key => $item):
                    $item_total = $item['price'] * $item['quantity'];
                    $total_price += $item_total;
            ?>
                <div class="cart-item-dropdown">
                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['main_image']); ?>" alt="" class="item-img">
                    <div class="item-info">
                        <h4 class="item-name">
                            <?php echo htmlspecialchars($item['product_name']); ?> 
                            <span style="font-size: 12px; color: #e91e63;">(Size: <?php echo htmlspecialchars($item['size']); ?>)</span>
                        </h4>
                        <div class="item-price-qty">
                            <span class="item-price"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</span>
                            <span class="item-qty-box">SL: <?php echo $item['quantity']; ?></span>
                        </div>
                    </div>
                    <a href="cart.php?action=delete&key=<?php echo $key; ?>" class="item-remove-btn">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                </div>
            <?php 
                endforeach;
            else: 
            ?>
                <p style="text-align: center; padding: 20px; color: #666; font-size: 14px;">Giỏ hàng của bạn đang trống</p>
            <?php endif; ?>
        </div>

        <div class="cart-dropdown-footer">
            <div class="cart-total-section">
                <span class="total-label">Tổng tiền:</span>
                <span class="total-price"><?php echo number_format($total_price, 0, ',', '.'); ?>đ</span>
            </div>
            <a href="cart.php" class="btn-checkout">Xem giỏ hàng & Thanh toán</a>
        </div>
    </div>
</div>

        </div>

    </div>

    <!-- Nhúng Mega Menu phía dưới Header -->
    <?php include_once __DIR__ . '/mega-menu.php'; ?>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- 1. XỬ LÝ DROPDOWN GIỎ HÀNG ---
    const cartBtn = document.getElementById('open-cart-drawer');
    const cartDropdown = document.getElementById('cartDropdownMenu');

    if (cartBtn && cartDropdown) {
        // Bấm vào nút giỏ hàng -> bật/tắt dropdown
        cartBtn.addEventListener('click', function (e) {
            e.stopPropagation(); // Ngăn sự kiện lan ra document, tránh đóng ngay lập tức
            cartDropdown.classList.toggle('active');
        });

        // Bấm ra ngoài vùng giỏ hàng -> tự động đóng lại
        document.addEventListener('click', function (e) {
            const isClickInside = cartDropdown.contains(e.target) || cartBtn.contains(e.target);
            if (!isClickInside) {
                cartDropdown.classList.remove('active');
            }
        });
    }


    // --- 2. XỬ LÝ DROPDOWN TÀI KHOẢN (ĐĂNG NHẬP / ĐĂNG KÝ) ---
    const accountWrapper = document.querySelector('.account-dropdown-wrapper');
    const accountDropdown = document.getElementById('accountDropdownMenu');

    // Chỉ chạy hiệu ứng hover khi chưa đăng nhập (tức là có tồn tại menu dropdown)
    if (accountWrapper && accountDropdown) {
        accountWrapper.addEventListener('mouseenter', function () {
            accountDropdown.classList.add('active');
        });

        accountWrapper.addEventListener('mouseleave', function () {
            accountDropdown.classList.remove('active');
        });
    }
});
</script>