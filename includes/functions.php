<?php
    //hàm lấy danh sách brand
    function get_all_brands($db) {
    $brands = [];
    if ($db) {
        // Câu lệnh truy vấn bằng PDO
        $stmt = $db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != ''");
        // Lúc này bạn có thể dùng fetchAll thoải mái vì đang dùng PDO
        $brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $brands;
}
//hàm lấy danh sách phụ kiện
function get_all_accessories($db) {
    $accessories = [];
    if ($db) {
        $stmt = $db->query("SELECT DISTINCT brand FROM products WHERE category_id = 2 AND brand IS NOT NULL ");
        $accessories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    return $accessories;
}

// Hàm lấy thông tin danh mục theo ID
function getCategoryById($db, $category_id) {
    // Dùng đúng cột category_id và lấy category_name đổi bí danh (alias) thành name cho dễ gọi
    $stmt = $db->prepare("SELECT category_name AS name FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm lấy danh sách sản phẩm theo danh mục và hãng (nếu có)
function getProductsByCategory($db, $category_id, $brand = '') {
    // Đảm bảo có điều kiện WHERE category_id = :category_id chính xác
    $sql = "SELECT * FROM products WHERE category_id = :category_id";
    $params = [':category_id' => $category_id];

    if (!empty($brand)) {
        $sql .= " AND brand = :brand";
        $params[':brand'] = $brand;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getAllOrCategoryProducts($db, $category_id = null, $brand = null) {
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    // Chỉ lọc theo category_id nếu có truyền vào và khác rỗng / khác 'all'
    if (!empty($category_id) && $category_id !== 'all') {
        $sql .= " AND category_id = :category_id";
        $params[':category_id'] = $category_id;
    }

    // Nếu có lọc theo hãng
    if (!empty($brand)) {
        $sql .= " AND brand = :brand";
        $params[':brand'] = $brand;
    }

    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    ?>