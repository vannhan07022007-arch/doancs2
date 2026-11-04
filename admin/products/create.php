<?php
// Admin/products/create.php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['product_csrf'])) {
    $_SESSION['product_csrf'] = bin2hex(random_bytes(32));
}

$uploadDir = dirname(dirname(__DIR__)) . '/images/products/';   // thư mục lưu ảnh
$uploadRel = 'images/products/';                                // đường dẫn lưu trong database

// Dữ liệu cho các ô chọn
$categories = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);
$brands     = $db->query("SELECT MIN(brand_id) AS brand_id, brand_name FROM brands GROUP BY brand_name ORDER BY brand_name ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);
$sizes_list = ['34', '35', '36', '37', '38', '39', '40', '41', '42', '43',];

$errors = [];
$old = [
    'category_id'  => '',
    'brand_id'     => '',
    'product_name' => '',
    'product_code' => '',
    'original_price'   => '',
    'discount_percent' => '',
    'sizes'        => [],
    'quantity'     => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['category_id']  = (int)($_POST['category_id'] ?? 0);
    $old['brand_id']     = (int)($_POST['brand_id'] ?? 0);
    $old['product_name'] = trim($_POST['product_name'] ?? '');
    $old['product_code'] = trim($_POST['product_code'] ?? '');
    $old['original_price']   = trim($_POST['original_price'] ?? '');
    $old['discount_percent'] = trim($_POST['discount_percent'] ?? '');
    $old['sizes']        = array_map('strval', $_POST['sizes'] ?? []);
    $old['quantity']     = trim($_POST['quantity'] ?? '');

    // Chỉ giữ các size hợp lệ, bỏ trùng
    $validSizes = array_values(array_unique(array_filter($old['sizes'], fn($s) => in_array($s, $sizes_list, true))));

    // --- Kiểm tra dữ liệu ---
    if (!hash_equals($_SESSION['product_csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.';
    }
    if ($old['product_name'] === '') {
        $errors[] = 'Vui lòng nhập tên sản phẩm.';
    }
    if ($old['category_id'] <= 0) {
        $errors[] = 'Vui lòng chọn danh mục.';
    }
    if ($old['brand_id'] <= 0) {
        $errors[] = 'Vui lòng chọn hãng.';
    }
    if ($old['original_price'] === '' || !is_numeric($old['original_price']) || $old['original_price'] <= 0) {
        $errors[] = 'Giá gốc không hợp lệ.';
    }
    if ($old['discount_percent'] !== '' &&
        (!is_numeric($old['discount_percent']) || $old['discount_percent'] < 0 || $old['discount_percent'] >= 100)) {
        $errors[] = 'Phần trăm giảm giá phải từ 0 đến dưới 100.';
    }

    // Tính giá: có giảm giá thì price = giá sau giảm, old_price = giá gốc; không giảm thì old_price = 0
    $originalPrice = (float)$old['original_price'];
    $discountPct   = $old['discount_percent'] === '' ? 0 : (float)$old['discount_percent'];
    if ($discountPct > 0) {
        $salePrice     = round($originalPrice * (100 - $discountPct) / 100);
        $oldPriceValue = $originalPrice;
    } else {
        $salePrice     = $originalPrice;
        $oldPriceValue = 0;
    }

    // Đã chọn size thì phải nhập số lượng (mỗi size nhận cùng số lượng này)
    if ($validSizes && ($old['quantity'] === '' || !ctype_digit($old['quantity']) || (int)$old['quantity'] < 1)) {
        $errors[] = 'Vui lòng nhập số lượng mỗi size (số nguyên từ 1 trở lên).';
    }

    // Mã sản phẩm không được trùng
    if (!$errors && $old['product_code'] !== '') {
        $chk = $db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ?");
        $chk->execute([$old['product_code']]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'Mã sản phẩm này đã tồn tại.';
        }
    }

    // --- Xử lý ảnh chính (không bắt buộc) ---
    $imagePath = null;
    if (!$errors && isset($_FILES['main_image']) && $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['main_image'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Tải ảnh lên thất bại, vui lòng thử lại.';
        } elseif ($f['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Ảnh quá lớn (tối đa 3MB).';
        } else {
            // Kiểm tra loại file thật bằng nội dung
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

            if (!isset($allowed[$mime])) {
                $errors[] = 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.';
            } else {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $fileName = 'product_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                if (move_uploaded_file($f['tmp_name'], $uploadDir . $fileName)) {
                    $imagePath = $uploadRel . $fileName;
                } else {
                    $errors[] = 'Không lưu được ảnh vào thư mục images/products.';
                }
            }
        }
    }

    // --- Lưu vào database ---
    if (!$errors) {
        try {
            $db->beginTransaction();

            $ins = $db->prepare("INSERT INTO products
                    (category_id, brand_id, product_name, product_code, price, old_price, main_image)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([
                $old['category_id'],
                $old['brand_id'],
                $old['product_name'],
                $old['product_code'],
                $salePrice,
                $oldPriceValue,
                $imagePath,
            ]);
            $newId = (int)$db->lastInsertId();

            // Mỗi size được tick nhận cùng một số lượng
            if ($validSizes) {
                $qty = (int)$old['quantity'];
                $sz = $db->prepare("INSERT INTO product_sizes (product_id, size_value, stock) VALUES (?, ?, ?)");
                foreach ($validSizes as $sizeValue) {
                    $sz->execute([$newId, $sizeValue, $qty]);
                }
            }

            $db->commit();

            unset($_SESSION['product_csrf']);
            $_SESSION['flash'] = ['success', 'Đã thêm sản phẩm "' . $old['product_name'] . '".'];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // Lưu thất bại thì xóa luôn ảnh vừa tải lên
            if ($imagePath !== null && is_file(dirname(dirname(__DIR__)) . '/' . $imagePath)) {
                unlink(dirname(dirname(__DIR__)) . '/' . $imagePath);
            }
            $errors[] = 'Lỗi khi lưu sản phẩm: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm - Văn Nhân Soccer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .img-preview {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border: 1px dashed #c9cde0;
            border-radius: 12px;
            display: none;
        }
        .size-check { min-width: 64px; }
    </style>
</head>
<body>

<div id="wrapper">

    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column" style="flex: 1; background: #f8f9fc;">
        <div id="content">

            <?php include dirname(__DIR__) . '/includes/header.php'; ?>

            <div class="container-fluid px-4 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 text-gray-800 font-weight-bold">Thêm sản phẩm</h1>
                    <a href="index.php" class="btn btn-secondary btn-sm shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Quay lại danh sách
                    </a>
                </div>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            <?php foreach ($errors as $e): ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['product_csrf']); ?>">

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Thông tin sản phẩm</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">

                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="product_name" class="font-weight-bold">Tên sản phẩm *</label>
                                        <input type="text" id="product_name" name="product_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($old['product_name']); ?>">
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="category_id" class="font-weight-bold">Danh mục *</label>
                                            <select id="category_id" name="category_id" class="form-control" required>
                                                <option value="">-- Chọn danh mục --</option>
                                                <?php foreach ($categories as $c): ?>
                                                    <option value="<?php echo (int)$c['category_id']; ?>"
                                                        <?php echo $old['category_id'] == $c['category_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($c['category_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="brand_id" class="font-weight-bold">Hãng *</label>
                                            <select id="brand_id" name="brand_id" class="form-control" required>
                                                <option value="">-- Chọn hãng --</option>
                                                <?php foreach ($brands as $b): ?>
                                                    <option value="<?php echo (int)$b['brand_id']; ?>"
                                                        <?php echo $old['brand_id'] == $b['brand_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($b['brand_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="product_code" class="font-weight-bold">Mã sản phẩm</label>
                                        <input type="text" id="product_code" name="product_code" class="form-control"
                                               value="<?php echo htmlspecialchars($old['product_code']); ?>">
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="original_price" class="font-weight-bold">Giá gốc (đ) *</label>
                                            <input type="number" id="original_price" name="original_price" class="form-control"
                                                   min="0" step="1000" required
                                                   value="<?php echo htmlspecialchars($old['original_price']); ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="discount_percent" class="font-weight-bold">Giảm giá (%)</label>
                                            <input type="number" id="discount_percent" name="discount_percent" class="form-control"
                                                   min="0" max="99" step="1" placeholder="Ví dụ: 20"
                                                   value="<?php echo htmlspecialchars($old['discount_percent']); ?>">
                                            <small class="form-text text-muted">Để trống nếu không giảm giá.</small>
                                        </div>
                                    </div>
                                    <div class="alert alert-light border py-2 mb-0" id="pricePreview">
                                        Giá bán: <strong id="salePriceText">—</strong>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="main_image" class="font-weight-bold">Ảnh chính</label>
                                        <input type="file" id="main_image" name="main_image" class="form-control-file"
                                               accept="image/png,image/jpeg,image/webp,image/gif">
                                        <small class="form-text text-muted">JPG, PNG, WEBP hoặc GIF, tối đa 3MB.</small>
                                        <img id="preview" class="img-preview mt-3" alt="Xem trước ảnh">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold d-block">Kích cỡ có sẵn</label>
                                <?php foreach ($sizes_list as $s): ?>
                                    <div class="custom-control custom-checkbox custom-control-inline size-check">
                                        <input type="checkbox" class="custom-control-input" id="size<?php echo str_replace('.', '_', $s); ?>"
                                               name="sizes[]" value="<?php echo $s; ?>"
                                               <?php echo in_array($s, $old['sizes'], true) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="size<?php echo str_replace('.', '_', $s); ?>"><?php echo $s; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-group mb-0" style="max-width: 280px;">
                                <label for="quantity" class="font-weight-bold">Số lượng mỗi size (đôi)</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" min="1" step="1"
                                       placeholder="Ví dụ: 100"
                                       value="<?php echo htmlspecialchars($old['quantity']); ?>">
                                <small class="form-text text-muted" id="qtyHint">
                                    Mỗi size được tick sẽ nhận số lượng này.
                                </small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu sản phẩm</button>
                    <a href="index.php" class="btn btn-outline-secondary">Hủy</a>
                </form>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Xem trước ảnh ngay khi chọn file
    document.getElementById('main_image').addEventListener('change', function () {
        const img = document.getElementById('preview');
        const file = this.files[0];
        if (file) {
            img.src = URL.createObjectURL(file);
            img.style.display = 'block';
        } else {
            img.style.display = 'none';
        }
    });

    // Xem trước giá bán sau khi giảm
    const origInput = document.getElementById('original_price');
    const pctInput  = document.getElementById('discount_percent');
    const saleText  = document.getElementById('salePriceText');

    function updatePrice() {
        const orig = parseFloat(origInput.value) || 0;
        const pct  = parseFloat(pctInput.value) || 0;
        if (orig <= 0) { saleText.textContent = '—'; return; }
        const sale = pct > 0 ? Math.round(orig * (100 - pct) / 100) : orig;
        let text = sale.toLocaleString('vi-VN') + ' đ';
        if (pct > 0) text += '  (giá gốc ' + orig.toLocaleString('vi-VN') + ' đ, giảm ' + pct + '%)';
        saleText.textContent = text;
    }
    origInput.addEventListener('input', updatePrice);
    pctInput.addEventListener('input', updatePrice);
    updatePrice();

    // Bắt buộc nhập số lượng khi đã tick size, và hiện tổng số đôi
    const sizeBoxes = document.querySelectorAll('input[name="sizes[]"]');
    const qtyInput  = document.getElementById('quantity');
    const qtyHint   = document.getElementById('qtyHint');

    function updateQty() {
        const n   = [...sizeBoxes].filter(c => c.checked).length;
        const qty = parseInt(qtyInput.value, 10) || 0;
        qtyInput.required = n > 0;
        qtyHint.textContent = (n > 0 && qty > 0)
            ? n + ' size x ' + qty + ' đôi = ' + (n * qty) + ' đôi'
            : 'Mỗi size được tick sẽ nhận số lượng này.';
    }
    sizeBoxes.forEach(c => c.addEventListener('change', updateQty));
    qtyInput.addEventListener('input', updateQty);
    updateQty();
</script>
</body>
</html>