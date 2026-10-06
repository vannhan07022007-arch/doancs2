<?php
session_start();
define('BASE_URL', 'http://localhost/doancs2');

$settings = ['hotline' => '0984828392'];

// Giả lập danh sách 30 sản phẩm
$products = [];
for ($i = 1; $i <= 30; $i++) {
    $products[] = [
        'id' => $i,
        'name' => 'GIÀY BÓNG ĐÁ NIKE MERCURIAL VAPOR 16 PRO TF',
        'sku' => 'IU2727-900',
        'tier_name' => 'AUTHENTIC - PRO',
        'price' => 3600000,
        'old_price' => 4400000,
        'image' => '',
        'sizes' => '38,39,40,40.5,41,42,43,44'
    ];
}

// Cắt mảng: Chỉ lấy tối đa 18 sản phẩm đầu tiên
$display_products = array_slice($products, 0, 18);
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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { background-color: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .section-title { font-size: 20px; font-weight: 800; margin: 25px 0 15px 0; color: #111; text-transform: uppercase; }
    </style>
</head>
<body>

    <!-- 1. Header & Mega Menu -->
    <?php include_once __DIR__ . '/includes/header.php'; ?>

    <!-- 2. Danh sách sản phẩm -->
    <main class="container">
        
        <div class="product-grid">
            <?php 
            // Duyệt qua mảng $display_products (đã giới hạn 18 sản phẩm)
            foreach ($display_products as $product) {
                include __DIR__ . '/includes/product-card.php';
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