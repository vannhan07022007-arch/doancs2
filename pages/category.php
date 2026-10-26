<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$settings = ['hotline' => '0984828392'];

// Tạo URL giữ lại các bộ lọc hiện tại, chỉ đổi/bỏ một tham số
function filterUrl($key, $value) {
    $params = $_GET;
    if (isset($params[$key]) && $params[$key] == $value) {
        unset($params[$key]);          // bấm lại đúng mục đang chọn = bỏ lọc
    } else {
        $params[$key] = $value;
    }
    unset($params['page']);            // đổi bộ lọc thì về trang 1
    return 'category.php?' . http_build_query($params);
}

// ==========================================
// 1. NHẬN CÁC THAM SỐ TỪ URL
// ==========================================
$category_id  = isset($_GET['id']) ? $_GET['id'] : null;
$size_filter  = isset($_GET['size']) ? $_GET['size'] : null;
$brand_filter = isset($_GET['brand']) ? $_GET['brand'] : null;   // TÊN hãng
$ground       = isset($_GET['ground']) ? $_GET['ground'] : null;
$price_filter = isset($_GET['price']) ? $_GET['price'] : null;
$keyword      = isset($_GET['keyword']) ? trim($_GET['keyword']) : null;

// ==========================================
// 2. XÂY DỰNG CÂU LỆNH SQL
// ==========================================
// JOIN bảng brands để lọc theo tên hãng (bảng products chỉ có brand_id)
$base_sql = " FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id";
$params = [];

if (!empty($size_filter)) {
    $base_sql .= " JOIN product_sizes ps ON p.product_id = ps.product_id";
}

$base_sql .= " WHERE 1=1";

if (!empty($category_id)) {
    $base_sql .= " AND p.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($brand_filter)) {
    $base_sql .= " AND b.brand_name = :brand_name";
    $params[':brand_name'] = $brand_filter;
}

if (!empty($ground)) {
    $base_sql .= " AND p.product_name LIKE :ground";
    $params[':ground'] = "%$ground%";
}

if (!empty($size_filter)) {
    $base_sql .= " AND ps.size_value = :size";
    $params[':size'] = $size_filter;
}

if (!empty($price_filter)) {
    switch ($price_filter) {
        case 'under_300k': $base_sql .= " AND p.price < 300000"; break;
        case '300k_400k':  $base_sql .= " AND p.price BETWEEN 300000 AND 400000"; break;
        case '400k_500k':  $base_sql .= " AND p.price BETWEEN 400000 AND 500000"; break;
        case '500k_600k':  $base_sql .= " AND p.price BETWEEN 500000 AND 600000"; break;
        case 'over_600k':  $base_sql .= " AND p.price > 600000"; break;
    }
}

if (!empty($keyword)) {
    $base_sql .= " AND (p.product_name LIKE :keyword OR p.product_code LIKE :keyword2)";
    $params[':keyword']  = "%$keyword%";
    $params[':keyword2'] = "%$keyword%";
}

// ==========================================
// 3. THỰC THI TRUY VẤN VÀ PHÂN TRANG
// ==========================================
$sql = "SELECT DISTINCT p.*, b.brand_name" . $base_sql . " ORDER BY p.product_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!is_array($products)) {
    $products = [];
}

$limit = 10;
$total_products = count($products);
$total_pages = $total_products > 0 ? ceil($total_products / $limit) : 1;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;

$display_products = array_slice($products, ($page - 1) * $limit, $limit);

// Tiêu đề trang theo danh mục / hãng đang xem
$page_title = 'TẤT CẢ SẢN PHẨM';
if (!empty($category_id)) {
    $cat = getCategoryById($db, $category_id);
    if ($cat) $page_title = mb_strtoupper($cat['name'], 'UTF-8');
}
if (!empty($brand_filter)) {
    $page_title .= ' - ' . mb_strtoupper($brand_filter, 'UTF-8');
}

// Danh sách hãng cho sidebar
$brands      = get_all_brands($db);
$accessories = get_all_accessories($db);

$price_options = [
    'under_300k' => 'Giá dưới 300.000đ',
    '300k_400k'  => '300.000đ - 400.000đ',
    '400k_500k'  => '400.000đ - 500.000đ',
    '500k_600k'  => '500.000đ - 600.000đ',
    'over_600k'  => 'Giá trên 600.000đ',
];
$sizes_list = [34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Văn Nhân Soccer - Giày Bóng Đá Chính Hãng</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

    body { background-color: #f8f9fa; }

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

    .price-item { text-decoration: none; color: #333; }
    .price-item.active-price { font-weight: bold; color: #e91e63; }
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
            <span class="current"><?php echo htmlspecialchars($page_title); ?></span>
        </div>

        <h2 class="main-page-title"><?php echo htmlspecialchars($page_title); ?></h2>
    </div>

    <div class="shop-layout">

        <!-- CỘT TRÁI: Sidebar -->
        <aside class="shop-sidebar">
            <div class="sidebar-box">
                <h3 class="sidebar-title">DANH MỤC SẢN PHẨM</h3>
                <ul class="sidebar-menu">
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php">TẤT CẢ SẢN PHẨM</a></li>

                    <!-- Giày bóng đá -->
                    <li class="has-submenu">
                        <div class="menu-link-wrapper" style="display: flex; align-items: center; justify-content: space-between;">
                            <a href="<?php echo BASE_URL; ?>/pages/category.php?id=1" class="category-main-link">GIÀY BÓNG ĐÁ</a>
                            <span class="submenu-toggle arrow" style="cursor: pointer; padding: 0 10px;">&gt;</span>
                        </div>

                        <ul class="sub-menu" style="display: none;">
                            <?php if (!empty($brands)): ?>
                                <?php foreach ($brands as $row): ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>/pages/category.php?id=1&brand=<?php echo urlencode($row['brand_name']); ?>">
                                        Giày <?php echo htmlspecialchars($row['brand_name']); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="#">Chưa có hãng nào</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>

                    <!-- Phụ kiện -->
                    <li class="has-submenu">
                        <div class="menu-link-wrapper" style="display: flex; align-items: center; justify-content: space-between;">
                            <a href="<?php echo BASE_URL; ?>/pages/category.php?id=2" class="category-main-link">PHỤ KIỆN</a>
                            <span class="submenu-toggle arrow" style="cursor: pointer; padding: 0 10px;">&gt;</span>
                        </div>

                        <ul class="sub-menu" style="display: none;">
                            <?php if (!empty($accessories)): ?>
                                <?php foreach ($accessories as $row): ?>
                                <li>
                                    <a href="<?php echo BASE_URL; ?>/pages/category.php?id=2&brand=<?php echo urlencode($row['brand_name']); ?>">
                                        <?php echo htmlspecialchars($row['brand_name']); ?>
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

            <!-- Theo kích cỡ (giữ lại id, brand, ground, price đang chọn) -->
            <div class="sidebar-box">
                <h3 class="sidebar-title">THEO KÍCH CỠ</h3>
                <div class="size-grid">
                    <?php foreach ($sizes_list as $s): ?>
                        <a href="<?php echo filterUrl('size', $s); ?>"
                           class="size-btn <?php echo (isset($_GET['size']) && $_GET['size'] == $s) ? 'active' : ''; ?>">
                            <?php echo $s; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Mức giá -->
            <div class="sidebar-box">
                <h3 class="sidebar-title">MỨC GIÁ</h3>
                <div class="price-filter-list" style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($price_options as $value => $label): ?>
                        <a href="<?php echo filterUrl('price', $value); ?>"
                           class="price-item <?php echo ($price_filter === $value) ? 'active-price' : ''; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- CỘT PHẢI: Sản phẩm -->
        <main class="shop-main">
            <div class="product-grid">
                <?php
                if (!empty($display_products)) {
                    foreach ($display_products as $product) {
                        include __DIR__ . '/../includes/product-card.php';
                    }
                } else {
                    echo '<p style="padding: 20px; text-align: center; width: 100%;">Không tìm thấy sản phẩm phù hợp.</p>';
                }
                ?>
            </div>
        </main>

    </div>

    <!-- Phân trang -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 6px; margin: 30px 0;">
        <?php
        $queryParams = $_GET;
        for ($i = 1; $i <= $total_pages; $i++):
            $queryParams['page'] = $i;
            $pageUrl  = 'category.php?' . http_build_query($queryParams);
            $isActive = ($page == $i) ? 'active' : '';
        ?>
            <a href="<?php echo $pageUrl; ?>" class="page-item <?php echo $isActive; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <?php
                $queryParams['page'] = $page + 1;
                $nextUrl = 'category.php?' . http_build_query($queryParams);
            ?>
            <a href="<?php echo $nextUrl; ?>" class="page-item next">
                <i class="fa-solid fa-angle-right"></i><i class="fa-solid fa-angle-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <script>
    document.querySelectorAll('.submenu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const subMenu = this.closest('li.has-submenu').querySelector('.sub-menu');
            if (subMenu) {
                subMenu.style.display = subMenu.style.display === 'block' ? 'none' : 'block';
            }
        });
    });
    </script>
</body>

</html>