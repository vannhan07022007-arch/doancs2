<?php
// Hàm lấy danh sách tất cả các brand từ bảng brands
function get_all_brands($db) {
    $brands = [];
    if ($db) {
        $stmt = $db->query("SELECT MIN(brand_id) AS brand_id, brand_name
                            FROM brands
                            GROUP BY brand_name
                            ORDER BY brand_name ASC");
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $brands;
}

// Hàm lấy danh sách hãng thuộc danh mục phụ kiện (category_id = 2) thông qua JOIN bảng brands
function get_all_accessories($db) {
    $accessories = [];
    if ($db) {
        $stmt = $db->query("SELECT MIN(b.brand_id) AS brand_id, b.brand_name
                            FROM products p
                            JOIN brands b ON p.brand_id = b.brand_id
                            WHERE p.category_id = 2
                            GROUP BY b.brand_name
                            ORDER BY b.brand_name ASC");
        $accessories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return $accessories;
}

// Hàm lấy thông tin danh mục theo ID (Không đổi)
function getCategoryById($db, $category_id) {
    $stmt = $db->prepare("SELECT category_name AS name FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Hàm lấy danh sách sản phẩm theo danh mục và brand_id (nếu có)
function getProductsByCategory($db, $category_id, $brand_id = null) {
    $sql = "SELECT p.*, b.brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE p.category_id = :category_id";
    $params = [':category_id' => $category_id];

    if (!empty($brand_id)) {
        $sql .= $sql .= " AND p.brand_id IN (
            SELECT b2.brand_id FROM brands b2
            WHERE b2.brand_name = (SELECT b3.brand_name FROM brands b3 WHERE b3.brand_id = :brand_id)
          )";
        $params[':brand_id'] = $brand_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm lấy tất cả sản phẩm hoặc theo danh mục, brand_id kết hợp JOIN bảng brands
function getAllOrCategoryProducts($db, $category_id = null, $brand_id = null) {
    $sql = "SELECT p.*, b.brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.brand_id WHERE 1=1";
    $params = [];

    // Chỉ lọc theo category_id nếu có truyền vào và khác rỗng / khác 'all'
    if (!empty($category_id) && $category_id !== 'all') {
        $sql .= $sql .= " AND p.brand_id IN (
            SELECT b2.brand_id FROM brands b2
            WHERE b2.brand_name = (SELECT b3.brand_name FROM brands b3 WHERE b3.brand_id = :brand_id)
          )";
        $params[':category_id'] = $category_id;
    }

    // Nếu có lọc theo brand_id
    if (!empty($brand_id)) {
        $sql .= " AND p.brand_id = :brand_id";
        $params[':brand_id'] = $brand_id;
    }

    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>