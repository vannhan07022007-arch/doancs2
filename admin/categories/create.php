<?php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';

$uploadDir = dirname(dirname(__DIR__)) . '/images/brands/';   // thư mục lưu logo
$error = '';
$name  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['brand_name'] ?? '');
    $logoFile = null;

    if ($name === '') {
        $error = 'Vui lòng nhập tên hãng.';
    } else {
        // Không cho thêm hãng trùng tên
        $chk = $db->prepare("SELECT COUNT(*) FROM brands WHERE brand_name = ?");
        $chk->execute([$name]);
        if ($chk->fetchColumn() > 0) {
            $error = 'Hãng này đã tồn tại.';
        }
    }

    // Xử lý ảnh logo (không bắt buộc)
    if ($error === '' && isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['logo'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $error = 'Tải ảnh lên thất bại, vui lòng thử lại.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $error = 'Ảnh quá lớn (tối đa 2MB).';
        } else {
            // Kiểm tra loại file thật bằng nội dung, không tin tên file
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

            if (!isset($allowed[$mime])) {
                $error = 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.';
            } else {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $logoFile = 'brand_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                if (!move_uploaded_file($f['tmp_name'], $uploadDir . $logoFile)) {
                    $error = 'Không lưu được ảnh vào thư mục images/brands.';
                    $logoFile = null;
                }
            }
        }
    }

    if ($error === '') {
        $ins = $db->prepare("INSERT INTO brands (brand_name, brand_logo) VALUES (?, ?)");
        $ins->execute([$name, $logoFile]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm nhãn hàng - Văn Nhân Soccer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .logo-preview {
            width: 120px;
            height: 120px;
            object-fit: contain;
            background: #fff;
            border: 1px dashed #c9cde0;
            border-radius: 12px;
            padding: 8px;
            display: none;
        }
    </style>
</head>
<body>

<div id="wrapper">

    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column" style="flex: 1; background: #f8f9fc;">
        <div id="content">

            <?php include dirname(__DIR__) . '/includes/header.php'; ?>

            <div class="container-fluid px-4 py-4">
                <h1 class="h3 text-gray-800 font-weight-bold mb-4">Thêm nhãn hàng</h1>

                <div class="card shadow mb-4" style="max-width: 560px;">
                    <div class="card-body">

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="brand_name" class="font-weight-bold">Tên hãng *</label>
                                <input type="text" id="brand_name" name="brand_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($name); ?>">
                            </div>

                            <div class="form-group">
                                <label for="logo" class="font-weight-bold">Logo hãng</label>
                                <input type="file" id="logo" name="logo" class="form-control-file"
                                       accept="image/png,image/jpeg,image/webp,image/gif">
                                <small class="form-text text-muted">JPG, PNG, WEBP hoặc GIF, tối đa 2MB. Ảnh vuông, nền trong suốt sẽ đẹp nhất.</small>
                                <img id="preview" class="logo-preview mt-3" alt="Xem trước logo">
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                            <a href="index.php" class="btn btn-outline-secondary">Hủy</a>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Xem trước ảnh ngay khi chọn file
    document.getElementById('logo').addEventListener('change', function () {
        const img = document.getElementById('preview');
        const file = this.files[0];
        if (file) {
            img.src = URL.createObjectURL(file);
            img.style.display = 'block';
        } else {
            img.style.display = 'none';
        }
    });
</script>
</body>
</html>