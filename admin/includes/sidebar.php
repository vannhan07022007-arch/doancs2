<div id="sidebar">
    <a href="<?php echo BASE_URL; ?>/admin/index.php" class="sidebar-brand">
        <span class="brand-logo">VNS</span>
        <span>
            <span class="brand-title">Văn Nhân Soccer</span>
            <span class="brand-sub">Hệ thống quản trị</span>
        </span>
    </a>

    <div class="sidebar-heading">Chức năng chính</div>

    <ul class="nav">
        <?php 
        // Sử dụng BASE_URL để đường dẫn luôn chuẩn xác từ bất kỳ trang nào trong admin
        $menu = [
            [BASE_URL . '/admin/index.php', 'fas fa-home', 'Trang chủ', 'admin/index.php'],
            [BASE_URL . '/admin/customers/index.php', 'far fa-user', 'Quản lý Tài khoản', 'customers'],
            [BASE_URL . '/admin/products/index.php', 'fas fa-cube', 'Quản lý Sản phẩm', 'products'],
            [BASE_URL . '/admin/categories/index.php', 'fas fa-box-open', 'Quản lý hãng giày', 'categories'],
            [BASE_URL . '/admin/orders/index.php', 'far fa-file-alt', 'Xét duyệt hóa đơn', 'orders'],
            [BASE_URL . '/admin/brands/index.php', 'fas fa-bars', 'Quản lý danh mục sản phẩm', 'brands'],
        ];

        $currentUri = $_SERVER['REQUEST_URI'];

        foreach ($menu as [$url, $icon, $label, $keyword]): 
            // Kiểm tra xem URL hiện tại có chứa từ khóa của mục đó không để bật active
            $isActive = strpos($currentUri, $keyword) !== false;
        ?>
            <li class="nav-item <?php echo $isActive ? 'active' : ''; ?>">
                <a class="nav-link" href="<?php echo $url; ?>">
                    <i class="<?php echo $icon; ?>"></i> <?php echo $label; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>