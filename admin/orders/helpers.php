<?php
// Admin/orders/helpers.php - dùng chung cho index.php, detail.php, update.php

// Giá trị trong cột `order_status` của bảng orders (đơn khách đặt mặc định là 'Pending').
// Nếu muốn dùng giá trị khác thì chỉ cần sửa 3 dòng này.
const ORDER_PENDING   = 'Pending';
const ORDER_CONFIRMED = 'Confirmed';
const ORDER_CANCELLED = 'Cancelled';

// Lấy giá trị đầu tiên có thật trong $row theo danh sách tên cột có thể có
function o_pick(array $row, array $keys, $default = '') {
    foreach ($keys as $k) {
        if (isset($row[$k]) && $row[$k] !== '') {
            return $row[$k];
        }
    }
    return $default;
}

// Chuẩn hóa chuỗi trạng thái về đúng 3 hằng số (không phân biệt hoa thường)
function o_normalize($s) {
    $s = strtolower(trim((string)$s));
    if ($s === '' || $s === 'pending')   return ORDER_PENDING;
    if ($s === 'confirmed')              return ORDER_CONFIRMED;
    if ($s === 'cancelled' || $s === 'canceled') return ORDER_CANCELLED;
    return $s;   // giá trị lạ thì giữ nguyên để còn thấy
}

// Trạng thái hiện tại của đơn (rỗng / NULL coi như chờ xác nhận)
function o_status(array $row) {
    return o_normalize($row['order_status'] ?? '');
}

function o_status_label($s) {
    switch ($s) {
        case ORDER_PENDING:   return 'Chờ xác nhận';
        case ORDER_CONFIRMED: return 'Đã xác nhận';
        case ORDER_CANCELLED: return 'Đã hủy';
        default:              return $s;
    }
}

function o_status_class($s) {
    switch ($s) {
        case ORDER_PENDING:   return 'warning';
        case ORDER_CONFIRMED: return 'success';
        case ORDER_CANCELLED: return 'danger';
        default:              return 'secondary';
    }
}

function o_money($n) {
    return number_format((float)$n, 0, ',', '.') . ' đ';
}

// Đơn đang ở trạng thái nào thì được phép làm gì
function o_can_confirm($status) { return $status === ORDER_PENDING; }
function o_can_cancel($status)  { return $status === ORDER_PENDING || $status === ORDER_CONFIRMED; }