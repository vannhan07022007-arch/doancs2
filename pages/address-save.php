<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$listUrl = BASE_URL . '/pages/account.php?tab=address';
$formUrl = BASE_URL . '/pages/address.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $listUrl);
    exit;
}

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $db->prepare("UPDATE users SET address = NULL WHERE user_id = ?")->execute([$userId]);
    header('Location: ' . $listUrl);
    exit;
}

$fullname = trim($_POST['fullname'] ?? '');
$phone    = trim($_POST['phone'] ?? '');
$address  = trim($_POST['address'] ?? '');

if ($fullname === '' || $phone === '' || $address === '') {
    $_SESSION['address_error'] = 'Vui lòng nhập Họ tên, Số điện thoại và Địa chỉ.';
    header('Location: ' . $formUrl);
    exit;
}

$db->prepare("UPDATE users SET fullname = ?, phone = ?, address = ? WHERE user_id = ?")
   ->execute([$fullname, $phone, $address, $userId]);
$_SESSION['user_name'] = $fullname;

header('Location: ' . $listUrl);
exit;