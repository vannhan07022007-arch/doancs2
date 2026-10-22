<?php
require_once dirname(__DIR__) . '/config/auth.php';
requireAdmin();

$adminName = $_SESSION['user_name'] ?? 'Quản Trị Viên';
$initial   = mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Văn Nhân - Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 4.6 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/admin.css">
</head>
<body>

<div id="wrapper">

    <!-- SIDEBAR -->
    <?php include_once __DIR__ . '/includes/sidebar.php'; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div id="content-wrapper">

        <!-- TOPBAR -->
        <?php include __DIR__ . '/includes/header.php'; ?>

        <!-- MAIN CONTAINER -->
        <div class="page-content">

            <div class="crumb">Tables <span class="sep">/</span> <span class="current">Database login</span></div>
            <h1 class="page-title">Thống Kê</h1>

            <!-- STATS CARDS (2 hàng x 3 cột) -->
            <div class="row">

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-emerald h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-title tone-emerald">Doanh thu tháng này</div>
                                    <div class="stat-value">0 VNĐ</div>
                                </div>
                                <div class="col-auto">
                                    <div class="stat-icon icon-emerald"><i class="fas fa-dollar-sign"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-emerald h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-title tone-emerald">Doanh thu năm nay</div>
                                    <div class="stat-value">0 VNĐ</div>
                                </div>
                                <div class="col-auto">
                                    <div class="stat-icon icon-emerald"><i class="fas fa-dollar-sign"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-primary h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-title tone-blue">Khách hàng</div>
                                    <div class="stat-value">1</div>
                                </div>
                                <div class="col-auto">
                                    <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-info h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-title tone-sky">Giày bóng đá</div>
                                    <div class="stat-value">7</div>
                                </div>
                                <div class="col-auto">
                                    <div class="stat-icon icon-sky"><i class="fas fa-shoe-prints"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat border-left-indigo h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-title tone-indigo">Phụ kiện</div>
                                    <div class="stat-value">12</div>
                                </div>
                                <div class="col-auto">
                                    <div class="stat-icon icon-indigo"><i class="fas fa-clipboard-list"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-stat is-alert border-left-warning h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="stat-title tone-amber">Hóa đơn chưa duyệt</div>
                                    <div class="stat-value">4</div>
                                </div>
                                <div class="col-auto">
                                    <div class="stat-icon icon-amber"><i class="fas fa-exclamation-circle"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div><!-- End row -->

            <!-- FOOTER -->
            <footer class="admin-footer">
                Copyright &copy; Your Website 2026
            </footer>

        </div><!-- End page-content -->

    </div><!-- End Content Wrapper -->

</div>

<!-- Bootstrap JS & Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>