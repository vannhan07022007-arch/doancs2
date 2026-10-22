<?php
$adminName = $_SESSION['user_name'] ?? 'Quản Trị Viên';
$initial   = mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8');
?>
<div class="topbar">
    <div class="dropdown ml-auto">
        <a href="#" class="d-flex align-items-center user-toggle" id="userDropdown"
           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="user-info d-none d-md-block">
                <span class="user-name"><?php echo htmlspecialchars($adminName); ?></span>
                <span class="user-status">Trực tuyến</span>
            </span>
            <span class="user-avatar"><?php echo htmlspecialchars($initial); ?></span>
        </a>

        <div class="dropdown-menu dropdown-menu-right user-menu" aria-labelledby="userDropdown">
            <a class="dropdown-item" href="profile.php">
                <i class="fas fa-user fa-sm fa-fw mr-2"></i> Profile
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?php echo BASE_URL; ?>/index.php">
                <i class="fas fa-store fa-sm fa-fw mr-2"></i> Trang chủ shop
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="logout.php">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i> Logout
            </a>
        </div>
    </div>
</div>