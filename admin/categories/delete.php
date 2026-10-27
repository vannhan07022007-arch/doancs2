
<?php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();

require_once dirname(dirname(__DIR__)) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$brandId = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);

if (!$brandId || $brandId <= 0) {
    header('Location: index.php');
    exit;
}

$projectRoot = dirname(dirname(__DIR__));
$uploadDir = $projectRoot . '/images/brands/';

try {
    $db->beginTransaction();

    // Lấy thông tin hãng giày
    $stmt = $db->prepare(
        "SELECT brand_id, brand_logo
         FROM brands
         WHERE brand_id = ?"
    );
    $stmt->execute([$brandId]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$brand) {
        $db->rollBack();
        header('Location: index.php?error=not_found');
        exit;
    }

    // Kiểm tra sản phẩm thuộc hãng
    $stmt = $db->prepare(
        "SELECT COUNT(*)
         FROM products
         WHERE brand_id = ?"
    );
    $stmt->execute([$brandId]);
    $totalProducts = (int) $stmt->fetchColumn();

    if ($totalProducts > 0) {
        $db->rollBack();
        header('Location: index.php?error=has_products');
        exit;
    }

    // Xóa hãng giày
    $stmt = $db->prepare(
        "DELETE FROM brands WHERE brand_id = ?"
    );
    $stmt->execute([$brandId]);

    $db->commit();

    // Xóa file logo sau khi xóa hãng thành công
    if (!empty($brand['brand_logo'])) {
        $logoPath = $uploadDir . basename($brand['brand_logo']);

        if (is_file($logoPath)) {
            unlink($logoPath);
        }
    }

    header('Location: index.php?success=deleted');
    exit;

} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    header('Location: index.php?error=delete_failed');
    exit;
}
