<?php
// $error được truyền từ config.php
echo "<h3 style='color:red'>Đã xảy ra lỗi</h3>";
echo "<p>" . htmlspecialchars($error ?? 'Lỗi không xác định') . "</p>";