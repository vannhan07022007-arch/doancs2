<?php
// Admin/products/edit.php
require_once dirname(dirname(__DIR__)) . '/config/auth.php';
requireAdmin();
require_once dirname(dirname(__DIR__)) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['product_csrf'])) {
    $_SESSION['product_csrf'] = bin2hex(random_bytes(32));
}

$root      = dirname(dirname(__DIR__));
$uploadDir = $root . '/images/products/';
$uploadRel = 'images/products/';

// Chuẩn hóa size: "38.0" -> "38", "38.50" -> "38.5"
function normSize($v) {
    $v = (string)$v;
    return strpos($v, '.') !== false ? rtrim(rtrim($v, '0'), '.') : $v;
}

// ==========================================
// LẤY SẢN PHẨM CẦN SỬA
// ==========================================
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    $_SESSION['flash'] = ['danger', 'Không tìm thấy sản phẩm #' . $id . '.'];
    header('Location: index.php');
    exit;
}

// Các size hiện có trong database
$sq = $db->prepare("SELECT size_id, size_value, stock FROM product_sizes WHERE product_id = ?");
$sq->execute([$id]);
$existing = [];   // [size chuẩn hóa => ['size_id'=>, 'stock'=>]]
foreach ($sq->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $existing[normSize($r['size_value'])] = ['size_id' => (int)$r['size_id'], 'stock' => (int)$r['stock']];
}

// Dữ liệu cho các ô chọn
$categories = $db->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);
$brands     = $db->query("SELECT MIN(brand_id) AS brand_id, brand_name FROM brands GROUP BY brand_name ORDER BY brand_name ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);

// Danh sách size: size chuẩn + mọi size sản phẩm đang có (để không làm mất size lạ)
$sizes_list = ['34', '35', '36', '37', '38', '39', '40',  '41', '42',  '43'];
$existingKeys = array_map('strval', array_keys($existing));
$sizes_all  = array_values(array_unique(array_merge($sizes_list, $existingKeys)));
usort($sizes_all, fn($a, $b) => (float)$a <=> (float)$b);

// ==========================================
// GIÁ GỐC + % GIẢM HIỆN TẠI (tính từ price và old_price)
// ==========================================
$curPrice = (float)$product['price'];
$curOld   = (float)$product['old_price'];
if ($curOld > $curPrice && $curOld > 0) {
    $prefillOrig = rtrim(rtrim(number_format($curOld, 2, '.', ''), '0'), '.');
    $prefillPct  = rtrim(rtrim(number_format(($curOld - $curPrice) / $curOld * 100, 2, '.', ''), '0'), '.');
} else {
    $prefillOrig = rtrim(rtrim(number_format($curPrice, 2, '.', ''), '0'), '.');
    $prefillPct  = '';
}

$errors = [];
$form = [
    'category_id'      => (int)$product['category_id'],
    'brand_id'         => (int)$product['brand_id'],
    'product_name'     => $product['product_name'],
    'product_code'     => $product['product_code'] ?? '',
    'original_price'   => $prefillOrig,
    'discount_percent' => $prefillPct,
    'sizes'            => $existingKeys,
    'stock'            => array_map(fn($e) => $e['stock'], $existing),
];

// ==========================================
// LƯU THAY ĐỔI
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['category_id']      = (int)($_POST['category_id'] ?? 0);
    $form['brand_id']         = (int)($_POST['brand_id'] ?? 0);
    $form['product_name']     = trim($_POST['product_name'] ?? '');
    $form['product_code']     = trim($_POST['product_code'] ?? '');
    $form['original_price']   = trim($_POST['original_price'] ?? '');
    $form['discount_percent'] = trim($_POST['discount_percent'] ?? '');
    $form['sizes']            = array_map('strval', $_POST['sizes'] ?? []);
    $form['stock']            = $_POST['stock'] ?? [];

    $validSizes = array_values(array_unique(array_filter($form['sizes'], fn($s) => in_array($s, $sizes_all, true))));

    // --- Kiểm tra dữ liệu ---
    if (!hash_equals($_SESSION['product_csrf'], $_POST['csrf'] ?? '')) {
        $errors[] = 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.';
    }
    if ($form['product_name'] === '') {
        $errors[] = 'Vui lòng nhập tên sản phẩm.';
    }
    if ($form['category_id'] <= 0) {
        $errors[] = 'Vui lòng chọn danh mục.';
    }
    if ($form['brand_id'] <= 0) {
        $errors[] = 'Vui lòng chọn hãng.';
    }
    if ($form['original_price'] === '' || !is_numeric($form['original_price']) || $form['original_price'] <= 0) {
        $errors[] = 'Giá gốc không hợp lệ.';
    }
    if ($form['discount_percent'] !== '' &&
        (!is_numeric($form['discount_percent']) || $form['discount_percent'] < 0 || $form['discount_percent'] >= 100)) {
        $errors[] = 'Phần trăm giảm giá phải từ 0 đến dưới 100.';
    }

    // Số lượng của từng size được tick
    $stockValues = [];
    foreach ($validSizes as $s) {
        $v = isset($form['stock'][$s]) ? trim((string)$form['stock'][$s]) : '';
        if ($v === '' || !ctype_digit($v)) {
            $errors[] = 'Số lượng của size ' . $s . ' phải là số nguyên từ 0 trở lên.';
        } else {
            $stockValues[$s] = (int)$v;
        }
    }

    // Mã sản phẩm không trùng với sản phẩm khác
    if (!$errors && $form['product_code'] !== '') {
        $chk = $db->prepare("SELECT COUNT(*) FROM products WHERE product_code = ? AND product_id <> ?");
        $chk->execute([$form['product_code'], $id]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'Mã sản phẩm này đã được sản phẩm khác sử dụng.';
        }
    }

    // --- Tính giá ---
    $salePrice = $curPrice;
    $oldPriceValue = $curOld;
    if (!$errors) {
        if ($form['original_price'] === $prefillOrig && $form['discount_percent'] === $prefillPct) {
            // Không đổi giá: giữ nguyên giá trong database, tránh sai lệch do làm tròn %
        } else {
            $originalPrice = (float)$form['original_price'];
            $discountPct   = $form['discount_percent'] === '' ? 0 : (float)$form['discount_percent'];
            if ($discountPct > 0) {
                $salePrice     = round($originalPrice * (100 - $discountPct) / 100);
                $oldPriceValue = $originalPrice;
            } else {
                $salePrice     = $originalPrice;
                $oldPriceValue = 0;
            }
        }
    }

    // --- Ảnh mới (không bắt buộc) ---
    $newImage = null;
    if (!$errors && isset($_FILES['main_image']) && $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['main_image'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Tải ảnh lên thất bại, vui lòng thử lại.';
        } elseif ($f['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Ảnh quá lớn (tối đa 3MB).';
        } else {
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
                    $newImage = $uploadRel . $fileName;
                } else {
                    $errors[] = 'Không lưu được ảnh vào thư mục images/products.';
                }
            }
        }
    }

    // --- Ghi vào database ---
    if (!$errors) {
        try {
            $db->beginTransaction();

            $sql = "UPDATE products
                    SET category_id = ?, brand_id = ?, product_name = ?, product_code = ?, price = ?, old_price = ?"
                 . ($newImage !== null ? ", main_image = ?" : "")
                 . " WHERE product_id = ?";
            $params = [
                $form['category_id'], $form['brand_id'], $form['product_name'], $form['product_code'],
                $salePrice, $oldPriceValue,
            ];
            if ($newImage !== null) $params[] = $newImage;
            $params[] = $id;
            $db->prepare($sql)->execute($params);

            // Đồng bộ size: sửa số lượng, thêm size mới, xóa size bị bỏ tick
            $upd = $db->prepare("UPDATE product_sizes SET stock = ? WHERE size_id = ?");
            $ins = $db->prepare("INSERT INTO product_sizes (product_id, size_value, stock) VALUES (?, ?, ?)");
            $del = $db->prepare("DELETE FROM product_sizes WHERE size_id = ?");

            foreach ($sizes_all as $s) {
                $ticked = in_array($s, $validSizes, true);
                $exist  = $existing[$s] ?? null;

                if ($ticked && $exist)       $upd->execute([$stockValues[$s], $exist['size_id']]);
                elseif ($ticked && !$exist)  $ins->execute([$id, $s, $stockValues[$s]]);
                elseif (!$ticked && $exist)  $del->execute([$exist['size_id']]);
            }

            $db->commit();

            // Đổi ảnh thành công thì xóa ảnh cũ, nhưng chỉ khi nó nằm trong images/products
            if ($newImage !== null && !empty($product['main_image'])) {
                $oldFull  = realpath($root . '/' . ltrim($product['main_image'], '/'));
                $prodRoot = realpath($uploadDir);
                if ($oldFull && $prodRoot && strpos($oldFull, $prodRoot . DIRECTORY_SEPARATOR) === 0 && is_file($oldFull)) {
                    unlink($oldFull);
                }
            }

            unset($_SESSION['product_csrf']);
            $_SESSION['flash'] = ['success', 'Đã cập nhật sản phẩm "' . $form['product_name'] . '".'];
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            // Lưu thất bại thì xóa ảnh mới vừa tải lên
            if ($newImage !== null && is_file($root . '/' . $newImage)) {
                unlink($root . '/' . $newImage);
            }
            $errors[] = 'Lỗi khi lưu sản phẩm: ' . $e->getMessage();
        }
    }
}

$currentImage = !empty($product['main_image'])
    ? BASE_URL . '/' . ltrim($product['main_image'], '/')
    : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm - Văn Nhân Soccer Admin</title>
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
            display: block;
        }
        .size-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .size-box {
            width: 96px;
            padding: 8px 10px 10px;
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            background: #fff;
        }
        .size-box.is-on { border-color: #4e73df; background: #f5f8ff; }
        .size-box input[type=number] { height: 32px; padding: 2px 8px; }
    </style>
</head>
<body>

<div id="wrapper">

    <?php include_once dirname(__DIR__) . '/includes/sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column" style="flex: 1; background: #f8f9fc;">
        <div id="content">

            <?php include dirname(__DIR__) . '/includes/header.php'; ?>

            <div class="container-fluid px-4 py-4">

                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h1 class="h3 text-gray-800 font-weight-bold mb-2 mb-md-0">Sửa sản phẩm #<?php echo (int)$product['product_id']; ?></h1>
                    <div>
                        <a href="detail.php?id=<?php echo (int)$product['product_id']; ?>" class="btn btn-info btn-sm text-white shadow-sm">
                            <i class="fas fa-eye mr-1"></i> Chi tiết
                        </a>
                        <a href="index.php" class="btn btn-secondary btn-sm shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Quay lại danh sách
                        </a>
                    </div>
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
                                               value="<?php echo htmlspecialchars($form['product_name']); ?>">
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="category_id" class="font-weight-bold">Danh mục *</label>
                                            <select id="category_id" name="category_id" class="form-control" required>
                                                <option value="">-- Chọn danh mục --</option>
                                                <?php foreach ($categories as $c): ?>
                                                    <option value="<?php echo (int)$c['category_id']; ?>"
                                                        <?php echo $form['category_id'] == $c['category_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($c['category_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="brand_id" class="font-weight-bold">Hãng *</label>
                                            <select id="brand_id" name="brand_id" class="form-control" required>
                                                <option value="">-- Chọn hãng --</option>
                                                <?php
                                                // Nếu hãng hiện tại là một dòng trùng tên, chọn dòng đại diện cùng tên
                                                $curBrandName = null;
                                                foreach ($brands as $b) {
                                                    if ($b['brand_id'] == $form['brand_id']) $curBrandName = $b['brand_name'];
                                                }
                                                if ($curBrandName === null) {
                                                    $bn = $db->prepare("SELECT brand_name FROM brands WHERE brand_id = ?");
                                                    $bn->execute([$form['brand_id']]);
                                                    $curBrandName = $bn->fetchColumn();
                                                }
                                                foreach ($brands as $b):
                                                    $sel = ($b['brand_id'] == $form['brand_id'] || ($curBrandName !== false && $b['brand_name'] === $curBrandName));
                                                ?>
                                                    <option value="<?php echo (int)$b['brand_id']; ?>" <?php echo $sel ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($b['brand_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="product_code" class="font-weight-bold">Mã sản phẩm</label>
                                        <input type="text" id="product_code" name="product_code" class="form-control"
                                               value="<?php echo htmlspecialchars($form['product_code']); ?>">
                                    </div>

                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="original_price" class="font-weight-bold">Giá gốc (đ) *</label>
                                            <input type="number" id="original_price" name="original_price" class="form-control"
                                                   min="0" step="any" required
                                                   value="<?php echo htmlspecialchars($form['original_price']); ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="discount_percent" class="font-weight-bold">Giảm giá (%)</label>
                                            <input type="number" id="discount_percent" name="discount_percent" class="form-control"
                                                   min="0" max="99.99" step="any" placeholder="Ví dụ: 20"
                                                   value="<?php echo htmlspecialchars($form['discount_percent']); ?>">
                                            <small class="form-text text-muted">Để trống nếu không giảm giá.</small>
                                        </div>
                                    </div>
                                    <div class="alert alert-light border py-2 mb-0">
                                        Giá bán: <strong id="salePriceText">—</strong>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="main_image" class="font-weight-bold">Ảnh chính</label>
                                        <?php if ($currentImage !== ''): ?>
                                            <img id="preview" class="img-preview mb-2" src="<?php echo htmlspecialchars($currentImage); ?>" alt="Ảnh hiện tại">
                                        <?php else: ?>
                                            <img id="preview" class="img-preview mb-2" style="display:none;" alt="Xem trước ảnh">
                                        <?php endif; ?>
                                        <input type="file" id="main_image" name="main_image" class="form-control-file"
                                               accept="image/png,image/jpeg,image/webp,image/gif">
                                        <small class="form-text text-muted">Chọn ảnh mới để thay; bỏ trống để giữ ảnh hiện tại. JPG, PNG, WEBP, GIF, tối đa 3MB.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Kích cỡ và số lượng từng size</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">Tick size đang bán và nhập số lượng của size đó. Bỏ tick một size sẽ xóa size khỏi sản phẩm.</p>
                            <div class="size-grid">
                                <?php foreach ($sizes_all as $s):
                                    $on  = in_array($s, $form['sizes'], true);
                                    $qty = $form['stock'][$s] ?? '';
                                    $sid = str_replace('.', '_', $s);
                                ?>
                                    <div class="size-box <?php echo $on ? 'is-on' : ''; ?>">
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input type="checkbox" class="custom-control-input size-check" id="size<?php echo $sid; ?>"
                                                   name="sizes[]" value="<?php echo htmlspecialchars($s); ?>" <?php echo $on ? 'checked' : ''; ?>>
                                            <label class="custom-control-label font-weight-bold" for="size<?php echo $sid; ?>"><?php echo htmlspecialchars($s); ?></label>
                                        </div>
                                        <input type="number" class="form-control form-control-sm size-qty"
                                               name="stock[<?php echo htmlspecialchars($s); ?>]" min="0" step="1" placeholder="SL"
                                               value="<?php echo htmlspecialchars((string)$qty); ?>" <?php echo $on ? '' : 'disabled'; ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 text-muted" id="stockTotal"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button>
                    <a href="index.php" class="btn btn-outline-secondary">Hủy</a>
                </form>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Xem trước ảnh mới
    document.getElementById('main_image').addEventListener('change', function () {
        const img = document.getElementById('preview');
        const file = this.files[0];
        if (file) {
            img.src = URL.createObjectURL(file);
            img.style.display = 'block';
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

    // Tick size thì bật ô số lượng; hiện tổng số đôi
    const boxes = document.querySelectorAll('.size-box');
    const totalText = document.getElementById('stockTotal');

    function updateStock() {
        let total = 0, n = 0;
        boxes.forEach(box => {
            const chk = box.querySelector('.size-check');
            const qty = box.querySelector('.size-qty');
            qty.disabled = !chk.checked;
            box.classList.toggle('is-on', chk.checked);
            if (chk.checked) { n++; total += parseInt(qty.value, 10) || 0; }
        });
        totalText.textContent = n > 0 ? n + ' size, tổng ' + total + ' đôi' : 'Chưa chọn size nào.';
    }
    boxes.forEach(box => {
        box.querySelector('.size-check').addEventListener('change', function () {
            const qty = box.querySelector('.size-qty');
            if (this.checked && qty.value === '') qty.value = 0;
            updateStock();
            if (this.checked) qty.focus();
        });
        box.querySelector('.size-qty').addEventListener('input', updateStock);
    });
    updateStock();
</script>
</body>
</html>