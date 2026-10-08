<?php
/** @var array $product */
// Tính phần trăm giảm giá nếu có giá cũ
$discount_percent = 0;
if (!empty($product['old_price']) && $product['old_price'] > $product['price']) {
    $discount_percent = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100);
}

// Chuyển danh sách size từ chuỗi thành mảng
$sizes = !empty($product['sizes']) ? explode(',', $product['sizes']) : [38, 39, 40, '40.5', 41, 42, '42.5', 43, 44];
?>

<div class="product-card">
    <!-- Huy hiệu giảm giá -->
    <?php if ($discount_percent > 0): ?>
    <div class="discount-badge">-<?php echo $discount_percent; ?>%</div>
    <?php endif; ?>

    <!-- Hình ảnh sản phẩm -->
    <a href="<?php echo BASE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>"
        class="product-img-wrap">
        <img src="<?php echo BASE_URL; ?>/<?php echo $product['image']; ?>"
            alt="<?php echo htmlspecialchars($product['name']); ?>">
    </a>

    <!-- Phân khúc sản phẩm -->
    <div class="product-tier">
        <?php echo htmlspecialchars($product['tier_name'] ?? 'AUTHENTIC - PRO'); ?>
    </div>

    <!-- Tên sản phẩm -->
    <h3 class="product-title">
        <a href="<?php echo BASE_URL; ?>/pages/product-detail.php?id=<?php echo $product['id']; ?>">
            <?php echo htmlspecialchars($product['name']); ?>
        </a>
    </h3>

    <!-- Mã sản phẩm -->
    <div class="product-sku">
        Mã sản phẩm: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?>
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
        <?php foreach ($sizes as $size): ?>
        <button type="button" class="size-btn" data-size="<?php echo trim($size); ?>">
            <?php echo trim($size); ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>