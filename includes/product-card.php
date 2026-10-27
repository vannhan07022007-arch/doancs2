<?php
/** @var array $product */

// 1. Tính phần trăm giảm giá nếu có giá cũ
$discount_percent = 0;
if (!empty($product['old_price']) && $product['old_price'] > $product['price']) {
    $discount_percent = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100);
}

// 2. Lấy product_id của sản phẩm hiện tại trong vòng lặp
$current_product_id = isset($product['product_id']) ? intval($product['product_id']) : 0;

// 3. Sử dụng đúng biến kết nối $db của project bạn
$connection = null;
if (isset($db)) {
    $connection = $db;
} elseif (isset($pdo)) {
    $connection = $pdo;
}

// 4. Truy vấn lấy danh sách size duy nhất và tổng tồn kho của từng size
$sizes = [];
if ($current_product_id > 0 && $connection) {
    $stmt = $connection->prepare("SELECT size_value, SUM(stock) AS stock FROM product_sizes WHERE product_id = ? GROUP BY size_value ORDER BY size_value ASC");
    $stmt->execute([$current_product_id]);
    $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC); // Dùng FETCH_ASSOC để khớp với mảng $item['size_value'] và $item['stock'] ở dưới
}
?>

<div class="product-card">
    <!-- Huy hiệu giảm giá -->
    <?php if ($discount_percent > 0): ?>
    <div class="discount-badge">-<?php echo $discount_percent; ?>%</div>
    <?php endif; ?>

    <!-- Hình ảnh sản phẩm -->
    <a href="<?php echo BASE_URL; ?>/pages/product-detail.php?id=<?php echo $product['product_id']; ?>"
        class="product-img-wrap">
        <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($product['main_image']); ?>"
            alt="<?php echo htmlspecialchars($product['product_name']); ?>">
    </a>

    <!-- Phân khúc sản phẩm -->
    <div class="product-tier">
        <?php echo htmlspecialchars($product['tier_name'] ?? 'AUTHENTIC - PRO'); ?>
    </div>

    <!-- Tên sản phẩm -->
    <h3 class="product-title">
        <a href="<?php echo BASE_URL; ?>/pages/product-detail.php?id=<?php echo $product['product_id']; ?>">
            <?php echo htmlspecialchars($product['product_name']); ?>
        </a>
    </h3>

    <!-- Mã sản phẩm -->
    <div class="product-sku">
        Mã sản phẩm: <?php echo htmlspecialchars($product['product_code'] ?? 'N/A'); ?>
    </div>

    <!-- Khối giá -->
    <div class="product-price-box">
        <span class="current-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</span>
        <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
        <span class="old-price"><?php echo number_format($product['old_price'], 0, ',', '.'); ?>đ</span>
        <?php endif; ?>
    </div>

    <!-- Bảng chọn Size nhanh -->
    <div class="product-size-list">
        <?php if (!empty($sizes)): ?>
            <?php foreach ($sizes as $item): ?>
                <button type="button" 
                        class="size-btn <?php echo (intval($item['stock']) == 0) ? 'disabled' : ''; ?>" 
                        data-size="<?php echo htmlspecialchars($item['size_value']); ?>"
                        <?php echo (intval($item['stock']) == 0) ? 'disabled' : ''; ?>>
                    <?php echo htmlspecialchars($item['size_value']); ?>
                </button>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="font-size: 13px; color: #666;">Tạm hết size</p>
        <?php endif; ?>
    </div>
</div>