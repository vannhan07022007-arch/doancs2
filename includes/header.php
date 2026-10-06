<?php
// Lấy số lượng sản phẩm trong giỏ hàng (nếu chưa có thì bằng 0)
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
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

            <!-- Icon Tài khoản -->
            <div class="account-dropdown-wrapper">
                <a href="<?php echo isset($_SESSION['user_id']) ? BASE_URL . '/pages/account.php' : BASE_URL . '/pages/login.php'; ?>" class="icon-btn" title="Tài khoản">
                    <i class="fa-regular fa-user"></i>
                </a>
            </div>

            <!-- Icon Giỏ hàng -->
            <button type="button" class="icon-btn cart-btn" id="open-cart-drawer" title="Giỏ hàng">
                <i class="fa-solid fa-basket-shopping"></i>
                <span class="cart-badge" id="cart-badge-count"><?php echo $cart_count; ?></span>
            </button>

        </div>

    </div>

    <!-- Nhúng Mega Menu phía dưới Header -->
    <?php include_once __DIR__ . '/mega-menu.php'; ?>
</header>