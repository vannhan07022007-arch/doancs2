
<?php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();

require_once dirname(dirname(__DIR__)) . '/config/config.php';

$brandId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$brandId || $brandId <= 0) {
    header('Location: index.php');
    exit;
}

$projectRoot = dirname(dirname(__DIR__));
$uploadDir = $projectRoot . '/images/brands/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$error = '';

try {
    // Lấy thông tin hãng giày
    $stmt = $db->prepare(
        "SELECT brand_id, brand_name, brand_logo
         FROM brands
         WHERE brand_id = ?"
    );
    $stmt->execute([$brandId]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$brand) {
        header('Location: index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $brandName = trim($_POST['brand_name'] ?? '');
        $newLogo = $brand['brand_logo'];

        if ($brandName === '') {
            $error = 'Vui lòng nhập tên hãng giày.';
        } else {
            // Kiểm tra logo mới nếu người dùng chọn
            if (
                isset($_FILES['brand_logo']) &&
                $_FILES['brand_logo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $file = $_FILES['brand_logo'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $error = 'Tải logo lên thất bại.';
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $error = 'Logo không được vượt quá 2 MB.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);

                    $allowed = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                    ];

                    if (!isset($allowed[$mime])) {
                        $error = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.';
                    } else {
                        $newLogo = bin2hex(random_bytes(16))
                            . '.' . $allowed[$mime];

                        if (!move_uploaded_file(
                            $file['tmp_name'],
                            $uploadDir . $newLogo
                        )) {
                            $error = 'Không thể lưu logo lên máy chủ.';
                        }
                    }
                }
            }

            if ($error === '') {
                $stmt = $db->prepare(
                    "UPDATE brands
                     SET brand_name = ?, brand_logo = ?
                     WHERE brand_id = ?"
                );

                $stmt->execute([
                    $brandName,
                    $newLogo,
                    $brandId
                ]);

                // Xóa logo cũ sau khi cập nhật thành công
                if (
                    $newLogo !== $brand['brand_logo'] &&
                    !empty($brand['brand_logo'])
                ) {
                    $oldLogoPath = $uploadDir . basename($brand['brand_logo']);

                    if (is_file($oldLogoPath)) {
                        unlink($oldLogoPath);
                    }
                }

                header('Location: index.php');
                exit;
            }

            // Giữ lại dữ liệu đã nhập khi có lỗi
            $brand['brand_name'] = $brandName;
            $brand['brand_logo'] = $newLogo;
        }
    }
} catch (PDOException $e) {
    $error = 'Có lỗi cơ sở dữ liệu. Vui lòng thử lại.';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa hãng giày</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div id="wrapper">
    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column"
         style="flex: 1; background: #f8f9fc;">
        <div id="content">
            <?php include dirname(__DIR__) . '/includes/header.php'; ?>

            <div class="container-fluid px-4 py-4">
                <h1 class="h3 mb-4 text-gray-800 font-weight-bold">
                    Sửa hãng giày
                </h1>

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="brand_name">Tên hãng giày</label>
                                <input type="text" id="brand_name"
                                       name="brand_name"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($brand['brand_name']); ?>"
                                       required>
                            </div>

                            <div class="form-group">
                                <label>Logo hiện tại</label><br>

                                <?php if (!empty($brand['brand_logo'])): ?>
                                    <img
                                        src="<?php echo BASE_URL; ?>/images/brands/<?php echo rawurlencode(basename($brand['brand_logo'])); ?>"
                                        alt="Logo hãng giày"
                                        style="width:100px;height:100px;object-fit:contain;">
                                <?php else: ?>
                                    <p class="text-muted">Chưa có logo.</p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="brand_logo">Chọn logo mới (nếu muốn)</label>
                                <input type="file" id="brand_logo"
                                       name="brand_logo"
                                       class="form-control-file"
                                       accept=".jpg,.jpeg,.png,.webp">
                                <small class="form-text text-muted">
                                    JPG, PNG hoặc WEBP; tối đa 2 MB.
                                </small>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Lưu thay đổi
                            </button>

                            <a href="index.php" class="btn btn-secondary">
                                Quay lại
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
