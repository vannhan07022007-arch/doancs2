<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/functions.php';


?>
<nav class="main-navigation">
    <div class="container nav-container">
        <ul class="nav-menu">

            <!-- 1. Tất cả sản phẩm -->
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/pages/category.php" class="nav-link">TẤT CẢ SẢN PHẨM</a>
            </li>

            <!-- 2. Giày bóng đá (Mega Menu) -->
            <li class="nav-item has-dropdown">
                <a href="<?php echo BASE_URL; ?>/pages/category.php?cat=giay-bong-da" class="nav-link">
                    GIÀY BÓNG ĐÁ <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="dropdown-menu mega-dropdown">
                    <div class="mega-col">
                        <?php $brands = get_all_brands($db); ?>
                        <h4>THƯƠNG HIỆU</h4>
                        <ul>
                            <?php if (!empty($brands)): ?>
                            <?php foreach ($brands as $brand): ?>
                            <li>
                                <a
                                    href="<?php echo BASE_URL; ?>/pages/category.php?brand=<?php echo urlencode($brand); ?>">
                                    Giày <?php echo htmlspecialchars($brand); ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <li><a href="#">Chưa có hãng nào</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="mega-col">
                        <h4>LOẠI ĐINH SÂN</h4>
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?ground=tf">Sân cỏ nhân tạo (TF)</a>
                            </li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?ground=fg">Sân cỏ tự nhiên
                                    (FG/AG)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?ground=ic">Sân Futsal (IC)</a></li>
                        </ul>
                    </div>
                </div>
            </li>

            <!-- 3. Phụ kiện -->
            <li class="nav-item has-dropdown">
            <?php $accessories = get_all_accessories($db); ?>
                <a href="<?php echo BASE_URL; ?>/pages/category.php?cat=phu-kien" class="nav-link">
                PHỤ KIỆN <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </a>
                    <ul class="dropdown-menu simple-dropdown">
                     <?php if (!empty($accessories)): ?>
                        <?php foreach ($accessories as $item): ?>
                        <li>
                         <a href="<?php echo BASE_URL; ?>/pages/category.php?cat=<?php echo urlencode($item); ?>">
                    <?php echo htmlspecialchars($item); ?>
                </a>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li><a href="#">Chưa có phụ kiện nào</a></li>
    <?php endif; ?>
</ul>
            </li>

            <!-- 5. Tìm giày nhanh -->
            <li class="nav-item has-dropdown">
                <a href="#" class="nav-link">
                    TÌM GIÀY NHANH <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </a>
                <ul class="dropdown-menu simple-dropdown">
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?sole=tf">Giày đinh thấp (Sân cỏ nhân tạo
                            TF)</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?sole=ag_fg">Giày đinh cao (AG / FG)</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?fit=wide">Giày chuyên cho chân bè</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?feature=collared">Giày lưỡi gà liền (Cổ
                            thun)</a></li>
                </ul>
            </li>

        </ul>
    </div>
</nav>