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
                        <h4>THƯƠNG HIỆU</h4>
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?brand=nike">Giày Nike</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?brand=adidas">Giày Adidas</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?brand=mizuno">Giày Mizuno</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?brand=kamito">Giày Kamito</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?brand=puma">Giày Puma</a></li>
                        </ul>
                    </div>
                    <div class="mega-col">
                        <h4>LOẠI ĐINH SÂN</h4>
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?ground=tf">Sân cỏ nhân tạo (TF)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?ground=fg">Sân cỏ tự nhiên (FG/AG)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?ground=ic">Sân Futsal (IC)</a></li>
                        </ul>
                    </div>
                    <div class="mega-col">
                        <h4>PHÂN KHÚC</h4>
                        <ul>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?tier=topend">Cấp độ Pro / Elite (Top-end)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?tier=midrange">Cấp độ Academy (Tầm trung)</a></li>
                            <li><a href="<?php echo BASE_URL; ?>/pages/category.php?tier=budget">Giày bóng đá giá rẻ</a></li>
                        </ul>
                    </div>
                </div>
            </li>

            <!-- 3. Phụ kiện -->
            <li class="nav-item has-dropdown">
                <a href="<?php echo BASE_URL; ?>/pages/category.php?cat=phu-kien" class="nav-link">
                    PHỤ KIỆN <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </a>
                <ul class="dropdown-menu simple-dropdown">
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?cat=tat-dap-cau">Tất chống trượt / Tất dệt</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?cat=boc-ong-dong">Bọc ống đồng (Khay vế)</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?cat=gang-tay-mon">Găng tay thủ môn</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?cat=tui-dung-giay">Túi đựng giày</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?cat=qua-bong-da">Quả bóng đá</a></li>
                </ul>
            </li>

            <!-- 5. Tìm giày nhanh -->
            <li class="nav-item has-dropdown">
                <a href="#" class="nav-link">
                    TÌM GIÀY NHANH <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </a>
                <ul class="dropdown-menu simple-dropdown">
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?pos=striker">Giày cho Tiền đạo (Sút bóng, dứt điểm)</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?pos=midfielder">Giày cho Tiền vệ (Kiểm soát, chuyền bóng)</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?pos=winger">Giày cho Tốc độ & Chạy cánh</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/pages/category.php?pos=defender">Giày cho Hậu vệ (Chắc chắn, êm ái)</a></li>
                </ul>
            </li>

        </ul>
    </div>
</nav>