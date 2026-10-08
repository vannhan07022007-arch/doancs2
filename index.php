<?php
// Gọi file cấu hình chung (chứa cả session_start, BASE_URL và $conn)
require_once __DIR__ . '/config/config.php'; // Hoặc đường dẫn trỏ đúng tới file config.php của bạn

$settings = ['hotline' => '0984828392'];

// Truy vấn lấy tối đa 18 sản phẩm từ bảng products
$display_products = [];
if (isset($conn) && $conn) {
    $sql = "SELECT product_id AS id, product_name AS name, product_code AS sku, brand AS tier_name, price, old_price, main_image AS image FROM products ORDER BY product_id DESC LIMIT 18";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Lấy thêm các size của sản phẩm này
            $p_id = $row['id'];
            $size_sql = "SELECT GROUP_CONCAT(size_value ORDER BY size_value ASC) AS sizes FROM product_sizes WHERE product_id = $p_id";
            $size_result = mysqli_query($conn, $size_sql);
            if ($size_row = mysqli_fetch_assoc($size_result)) {
                $row['sizes'] = $size_row['sizes'];
            } else {
                $row['sizes'] = '';
            }
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
    <?php include_once __DIR__ . '/includes/header.php'; ?>

    <!-- 2. Danh sách sản phẩm -->
    <main class="container">

        <div class="product-grid">
            <?php 
            if (!empty($display_products)) {
                foreach ($display_products as $product) {
                    include __DIR__ . '/includes/product-card.php';
                }
            } else {
                echo '<p style="padding: 20px; text-align: center; width: 100%;">Chưa có sản phẩm nào trong cơ sở dữ liệu shopbangiay.</p>';
            }
            ?>
        </div>

        <!-- Nút Xem Tất Cả căn giữa -->
        <div class="view-more-wrapper">
            <a href="<?php echo BASE_URL; ?>/pages/category.php" class="btn-view-more">
                Xem tất cả <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </main>

</body>

</html>