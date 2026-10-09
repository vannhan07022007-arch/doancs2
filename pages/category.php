<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';


$settings = ['hotline' => '0984828392'];
// Truy vấn lấy danh sách các hãng (brand) không bị trùng lặp từ bảng products



// Truy vấn lấy danh sách các loại phụ kiện động từ database
$accessories = [];
if (isset($conn) && $conn) {
    // Giả sử cột phân loại phụ kiện của bạn lưu trong cột 'brand' hoặc 'category' khi sản phẩm thuộc nhóm phụ kiện
    $acc_sql = "SELECT DISTINCT brand FROM products WHERE type = 'phu_kien' AND brand IS NOT NULL AND brand != ''";
    $acc_result = mysqli_query($conn, $acc_sql);
    if ($acc_result) {
        while ($acc_row = mysqli_fetch_assoc($acc_result)) {
            $accessories[] = $acc_row['brand'];
        }
    }
}
$display_products = [];
if (isset($db) && $db) {
    // Truy vấn bằng PDO để lấy danh sách sản phẩm
    $sql = "SELECT product_id AS id, product_name AS name, product_code AS sku, brand AS tier_name, price, old_price, main_image AS image FROM products ORDER BY product_id DESC LIMIT 18";
    $stmt = $db->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($products) {
        foreach ($products as $row) {
            $p_id = $row['id'];
            // Lấy size của sản phẩm bằng PDO
            $size_stmt = $db->prepare("SELECT GROUP_CONCAT(size_value ORDER BY size_value ASC) AS sizes FROM product_sizes WHERE product_id = ?");
            $size_stmt->execute([$p_id]);
            $size_row = $size_stmt->fetch(PDO::FETCH_ASSOC);
            
            $row['sizes'] = $size_row['sizes'] ?? '';
            $display_products[] = $row;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Văn Nhân Soccer - Giày Bóng Đá Chính Hãng</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/product-card.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/category.css">
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

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .section-title {
        font-size: 20px;
        font-weight: 800;
        margin: 25px 0 15px 0;
        color: #111;
        text-transform: uppercase;
    }
    </style>
</head>

<body>

    <!-- 1. Header & Mega Menu -->
    <?php include_once dirname(__DIR__) . '/includes/header.php'; ?>
    <!-- Breadcrumb & Tiêu đề trang -->
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>/index.php">Trang chủ</a>
            <span class="separator">&gt;</span>
            <span class="current">TẤT CẢ SẢN PHẨM</span>
        </div>

        <h2 class="main-page-title">TẤT CẢ SẢN PHẨM</h2>
    </div>
    <div class="shop-layout">

        <!-- CỘT TRÁI: Sidebar danh mục & kích cỡ -->
        <aside class="shop-sidebar">
            <div class="sidebar-box">
                <h3 class="sidebar-title">DANH MỤC SẢN PHẨM</h3>
                <ul class="sidebar-menu">
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php">TẤT CẢ SẢN PHẨM</a></li>

                    <!-- Mục Giày Bóng Đá có menu con -->
                    <li class="has-submenu">
    <div class="menu-link-wrapper" style="display: flex; align-items: center; justify-content: space-between;">
        <!-- 1. Bấm vào chữ: Chuyển đến trang danh mục giày (id = 1), chỉ hiện giày -->
        <a href="<?php echo BASE_URL; ?>/pages/category.php?id=1" class="category-main-link">
            GIÀY BÓNG ĐÁ
        </a>
        
        <!-- 2. Bấm vào mũi tên: Chỉ làm nhiệm vụ xổ menu con xuống -->
        <span class="submenu-toggle arrow" style="cursor: pointer; padding: 0 10px;">&gt;</span>
    </div>

    <?php
    $brands = get_all_brands($db);
    ?>
    <ul class="sub-menu" style="display: none;"> <!-- Mặc định ẩn menu con -->
        <?php if (!empty($brands)): ?>
        <?php foreach ($brands as $brand): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/category.php?id=1&brand=<?php echo urlencode($brand); ?>">
                Giày <?php echo htmlspecialchars($brand); ?>
            </a>
        </li>
        <?php endforeach; ?>
        <?php else: ?>
        <li><a href="#">Chưa có hãng nào</a></li>
        <?php endif; ?>
    </ul>
</li>
                        <li class="has-submenu">
    <div class="menu-link-wrapper" style="display: flex; align-items: center; justify-content: space-between;">
        <!-- 1. Bấm vào chữ: Chuyển đến trang danh mục phụ kiện (id = 2), chỉ hiện phụ kiện -->
        <a href="<?php echo BASE_URL; ?>/pages/category.php?id=2" class="category-main-link">
            PHỤ KIỆN
        </a>
        
        <!-- 2. Bấm vào mũi tên: Chỉ làm nhiệm vụ xổ menu con xuống -->
        <span class="submenu-toggle arrow" style="cursor: pointer; padding: 0 10px;">&gt;</span>
    </div>

    <?php 
    $accessories = get_all_accessories($db); 
    ?>
    <ul class="sub-menu" style="display: none;"> <!-- Mặc định ẩn menu con -->
        <?php if (!empty($accessories)): ?>
        <?php foreach ($accessories as $item): ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/pages/category.php?id=2&brand=<?php echo urlencode($item); ?>">
                <?php echo htmlspecialchars($item); ?>
            </a>
        </li>
        <?php endforeach; ?>
        <?php else: ?>
        <li><a href="#">Chưa có phụ kiện nào</a></li>
        <?php endif; ?>
    </ul>
</li>

                    <li class="has-submenu">
                        <a href="javascript:void(0);" class="submenu-toggle">
                            TÌM GIÀY NHANH <span class="arrow">&gt;</span>
                        </a>
                        <ul class="sub-menu">
                            <!-- Đổi dropdown-menu thành sub-menu -->
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?sole=tf">Giày đinh thấp (Sân cỏ nhân
                                    tạo TF)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?sole=ag_fg">Giày đinh cao (AG /
                                    FG)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?fit=wide">Giày chuyên cho chân
                                    bè</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?feature=collared">Giày lưỡi gà liền
                                    (Cổ thun)</a></li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="sidebar-box">
                <h3 class="sidebar-title">THEO KÍCH CỠ</h3>
                <div class="size-grid">
                    <button class="size-btn">34</button>
                    <button class="size-btn">35</button>
                    <button class="size-btn">36</button>
                    <button class="size-btn">37</button>
                    <button class="size-btn">38</button>
                    <button class="size-btn">39</button>
                    <button class="size-btn">40</button>
                    <button class="size-btn">41</button>
                    <button class="size-btn">42</button>
                    <button class="size-btn">43</button>
                    <button class="size-btn">44</button>
                    <button class="size-btn">45</button>

                </div>
            </div>
            <!-- Khung bộ lọc Mức Giá -->
            <div class="sidebar-box">
                <h3 class="sidebar-title">MỨC GIÁ</h3>
                <div class="price-filter-list">
                    <label class="price-item">
                        <input type="checkbox" name="price[]" value="under_300k">
                        <span>Giá dưới 300.000đ</span>
                    </label>
                    <label class="price-item">
                        <input type="checkbox" name="price[]" value="300k_400k">
                        <span>300.000đ - 400.000đ</span>
                    </label>
                    <label class="price-item">
                        <input type="checkbox" name="price[]" value="400k_500k">
                        <span>400.000đ - 500.000đ</span>
                    </label>
                    <label class="price-item">
                        <input type="checkbox" name="price[]" value="500k_600k">
                        <span>500.000đ - 600.000đ</span>
                    </label>
                    <label class="price-item">
                        <input type="checkbox" name="price[]" value="over_600k">
                        <span>Giá trên 600.000đ</span>
                    </label>
                </div>
            </div>
        </aside>

        <!-- CỘT PHẢI: Nội dung sản phẩm -->
        <main class="shop-main">
            <div class="product-grid">
    <?php 
        if (!empty($display_products)) {
                foreach ($display_products as $product) {
                    include __DIR__ . '/../includes/product-card.php';
                }
            } else {
                echo '<p style="padding: 20px; text-align: center; width: 100%;">Chưa có sản phẩm nào trong cơ sở dữ liệu shopbangiay.</p>';
            }
?>
</div>
        </main>
    </div>
    </div>
    <script>
    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        // Tìm thẻ ul.sub-menu ngay bên cạnh để ẩn/hiện
        const subMenu = this.closest('li.has-submenu').querySelector('.sub-menu');
        if (subMenu) {
            subMenu.style.display = subMenu.style.display === 'block' ? 'none' : 'block';
        }
    });
});
    </script>
</body>

</html>