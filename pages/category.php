<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';


$settings = ['hotline' => '0984828392'];
// Truy vấn lấy danh sách các hãng (brand) không bị trùng lặp từ bảng products



// Truy vấn lấy danh sách các loại phụ kiện động từ database

// ==========================================
// 1. NHẬN TẤT CẢ CÁC THAM SỐ TỪ URL (CHỈ NHẬN 1 LẦN DUY NHẤT)
// ==========================================
$category_id  = isset($_GET['id']) ? $_GET['id'] : null;
$size_filter  = isset($_GET['size']) ? $_GET['size'] : null;
$brand        = isset($_GET['brand']) ? $_GET['brand'] : null;
$ground       = isset($_GET['ground']) ? $_GET['ground'] : null;
$price_filter = isset($_GET['price']) ? $_GET['price'] : null;
$keyword      = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;

// ==========================================
// 2. XÂY DỰNG CÂU LỆNH SQL KẾT HỢP TẤT CẢ CÁC BỘ LỌC VÀ TÌM KIẾM
// ==========================================
$base_sql = " FROM products p";
$params = [];

// Nếu có lọc size thì mới JOIN bảng product_sizes
if (!empty($size_filter)) {
    $base_sql .= " JOIN product_sizes ps ON p.product_id = ps.product_id";
}

$base_sql .= " WHERE 1=1";

// Lọc theo danh mục
if (!empty($category_id)) {
    $base_sql .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

// Lọc theo thương hiệu
if (!empty($brand)) {
    $base_sql .= " AND p.brand = :brand";
    $params[':brand'] = $brand;
}

// Lọc theo loại sân (dựa vào tên sản phẩm)
if (!empty($ground)) {
    $base_sql .= " AND p.product_name LIKE :ground";
    $params[':ground'] = "%$ground%";
}

// Lọc theo size cụ thể
if (!empty($size_filter)) {
    $base_sql .= " AND ps.size_value = :size";
    $params[':size'] = $size_filter;
}

// Lọc theo khoảng giá
if (!empty($price_filter)) {
    switch ($price_filter) {
        case 'under_300k':
            $base_sql .= " AND p.price < 300000";
            break;
        case '300k_400k':
            $base_sql .= " AND p.price BETWEEN 300000 AND 400000";
            break;
        case '400k_500k':
            $base_sql .= " AND p.price BETWEEN 400000 AND 500000";
            break;
        case '500k_600k':
            $base_sql .= " AND p.price BETWEEN 500000 AND 600000";
            break;
        case 'over_600k':
            $base_sql .= " AND p.price > 600000";
            break;
    }
}

// Lọc theo từ khóa tìm kiếm (Tên hoặc mã sản phẩm)
if (!empty($keyword)) {
    $base_sql .= " AND (p.product_name LIKE :keyword OR p.product_code LIKE :keyword)";
    $params[':keyword'] = "%$keyword%";
}

// ==========================================
// 3. THỰC THI TRUY VẤN VÀ PHÂN TRANG
// ==========================================
$sql = "SELECT DISTINCT p.*" . $base_sql . " ORDER BY p.product_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!is_array($products)) {
    $products = [];
}

// Thiết lập phân trang
$limit = 10; // Số sản phẩm trên 1 trang
$total_products = count($products);
$total_pages = $total_products > 0 ? ceil($total_products / $limit) : 1;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;

// Cắt mảng hiển thị cho trang hiện tại (đã sửa chuẩn công thức phân trang)
$display_products = array_slice($products, ($page - 1) * $limit, $limit);

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

                </ul>
            </div>

            <div class="sidebar-box">
                <h3 class="sidebar-title">THEO KÍCH CỠ</h3>
                <div class="size-grid">
                   <?php 
$sizes_list = [34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
$current_id = $_GET['id'] ?? 1;
$current_brand = isset($_GET['brand']) ? $_GET['brand'] : '';
$current_ground = isset($_GET['ground']) ? $_GET['ground'] : '';

foreach ($sizes_list as $s): 
    // Giữ lại các bộ lọc cũ (id, brand, ground) nếu người dùng đã chọn trước đó
    $url = "category.php?id=" . $current_id;
    if (!empty($current_brand)) $url .= "&brand=" . urlencode($current_brand);
    if (!empty($current_ground)) $url .= "&ground=" . urlencode($current_ground);
    $url .= "&size=" . $s;
?>
    <a href="<?php echo $url; ?>" class="size-btn <?php echo (isset($_GET['size']) && $_GET['size'] == $s) ? 'active' : ''; ?>">
        <?php echo $s; ?>
    </a>
<?php endforeach; ?>

                </div>
            </div>
            <!-- Khung bộ lọc Mức Giá -->
            <div class="sidebar-box">
    <h3 class="sidebar-title">MỨC GIÁ</h3>
    <div class="price-filter-list" style="display: flex; flex-direction: column; gap: 8px;">
        <?php 
            // Hàm phụ trợ để giữ lại các bộ lọc khác (như danh mục, size, brand...) khi chuyển giá
            function getPriceUrl($priceValue) {
                $params = $_GET;
                // Nếu bấm lại đúng mức giá đang chọn thì coi như bỏ lọc giá
                if (isset($params['price']) && $params['price'] == $priceValue) {
                    unset($params['price']);
                } else {
                    $params['price'] = $priceValue;
                }
                unset($params['page']); // Reset về trang 1 khi đổi bộ lọc
                return "category.php?" . http_build_query($params);
            }
            
            $current_price = $_GET['price'] ?? '';
        ?>

        <a href="<?php echo getPriceUrl('under_300k'); ?>" class="price-item <?php echo ($current_price == 'under_300k') ? 'active-price' : ''; ?>" style="text-decoration: none; color: #333; <?php echo ($current_price == 'under_300k') ? 'font-weight: bold; color: #e91e63;' : ''; ?>">
            Giá dưới 300.000đ
        </a>
        <a href="<?php echo getPriceUrl('300k_400k'); ?>" class="price-item <?php echo ($current_price == '300k_400k') ? 'active-price' : ''; ?>" style="text-decoration: none; color: #333; <?php echo ($current_price == '300k_400k') ? 'font-weight: bold; color: #e91e63;' : ''; ?>">
            300.000đ - 400.000đ
        </a>
        <a href="<?php echo getPriceUrl('400k_500k'); ?>" class="price-item <?php echo ($current_price == '400k_500k') ? 'active-price' : ''; ?>" style="text-decoration: none; color: #333; <?php echo ($current_price == '400k_500k') ? 'font-weight: bold; color: #e91e63;' : ''; ?>">
            400.000đ - 500.000đ
        </a>
        <a href="<?php echo getPriceUrl('500k_600k'); ?>" class="price-item <?php echo ($current_price == '500k_600k') ? 'active-price' : ''; ?>" style="text-decoration: none; color: #333; <?php echo ($current_price == '500k_600k') ? 'font-weight: bold; color: #e91e63;' : ''; ?>">
            500.000đ - 600.000đ
        </a>
        <a href="<?php echo getPriceUrl('over_600k'); ?>" class="price-item <?php echo ($current_price == 'over_600k') ? 'active-price' : ''; ?>" style="text-decoration: none; color: #333; <?php echo ($current_price == 'over_600k') ? 'font-weight: bold; color: #e91e63;' : ''; ?>">
            Giá trên 600.000đ
        </a>
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
    <?php if ($total_pages > 1): ?>
    <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 6px; margin: 30px 0;">
        <?php 
        // Lấy tất cả tham số GET hiện tại (như id, brand, size, ground...) để khi bấm chuyển trang không bị mất bộ lọc
        $queryParams = $_GET;
        
        // Vòng lặp hiển thị các số trang từ 1 đến tổng số trang
        for ($i = 1; $i <= $total_pages; $i++): 
            $queryParams['page'] = $i;
            $pageUrl = "category.php?" . http_build_query($queryParams);
            // Kiểm tra nếu là trang hiện tại thì thêm class 'active'
            $isActive = ($page == $i) ? 'active' : '';
        ?>
            <a href="<?php echo $pageUrl; ?>" class="page-item <?php echo $isActive; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <!-- Nút chuyển trang tiếp theo (Next) -->
        <?php if ($page < $total_pages): ?>
            <?php 
                $queryParams['page'] = $page + 1;
                $nextUrl = "category.php?" . http_build_query($queryParams);
            ?>
            <a href="<?php echo $nextUrl; ?>" class="page-item next">
                <i class="fa-solid fa-angle-right"></i><i class="fa-solid fa-angle-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>
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