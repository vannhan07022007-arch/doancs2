<?php
// Admin/orders/mailer.php - gửi email thông báo đơn hàng cho khách
require_once __DIR__ . '/helpers.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// Nạp PHPMailer: ưu tiên Composer, nếu không có thì dùng bản tải tay trong includes/PHPMailer/src
function order_mail_load_phpmailer() {
    $root = dirname(dirname(__DIR__));

    if (is_file($root . '/vendor/autoload.php')) {
        require_once $root . '/vendor/autoload.php';
        return true;
    }

    $manual = $root . '/includes/PHPMailer/src/';
    if (is_file($manual . 'PHPMailer.php')) {
        require_once $manual . 'Exception.php';
        require_once $manual . 'PHPMailer.php';
        require_once $manual . 'SMTP.php';
        return true;
    }
    return false;
}

/**
 * Gửi email cho khách khi admin xác nhận hoặc hủy đơn.
 * @param array  $order  một dòng của bảng orders
 * @param array  $items  các dòng order_items (kèm product_name)
 * @param string $type   'confirm' hoặc 'cancel'
 * @return array [bool đã gửi, string thông báo]
 */
function send_order_mail(array $order, array $items, $type) {
    $root = dirname(dirname(__DIR__));

    $email = trim((string)o_pick($order, ['customer_email', 'email'], ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'đơn hàng này không có email hợp lệ của khách.'];
    }

    $cfgFile = $root . '/config/mail.php';
    if (!is_file($cfgFile)) {
        return [false, 'chưa có file config/mail.php.'];
    }
    $cfg = require $cfgFile;

    if (!order_mail_load_phpmailer()) {
        return [false, 'chưa cài PHPMailer (xem hướng dẫn cài đặt).'];
    }

    $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    $orderId  = (int)$order['order_id'];
    $name     = o_pick($order, ['customer_name', 'fullname', 'name'], 'quý khách');
    $address  = o_pick($order, ['customer_address', 'address'], '');
    $total    = o_pick($order, ['total_amount', 'total', 'total_price'], 0);
    $shop     = $cfg['from_name'] ?? 'Văn Nhân Soccer';
    $hotline  = $cfg['hotline'] ?? '';
    $isConfirm = ($type === 'confirm');

    if ($isConfirm) {
        $subject  = "[$shop] Đơn hàng #$orderId của bạn đã được xác nhận";
        $headline = 'Đơn hàng của bạn đã được xác nhận';
        $intro    = 'Cảm ơn bạn đã mua hàng. Đơn hàng của bạn đã được xác nhận và chúng tôi sẽ sớm chuẩn bị giao đến bạn.';
        $color    = '#16a34a';
    } else {
        $subject  = "[$shop] Đơn hàng #$orderId của bạn đã bị hủy";
        $headline = 'Đơn hàng của bạn đã bị hủy';
        $intro    = 'Rất tiếc, đơn hàng của bạn đã bị hủy. Nếu bạn có thắc mắc hoặc muốn đặt lại, vui lòng liên hệ với chúng tôi.';
        $color    = '#dc2626';
    }

    // Bảng sản phẩm trong đơn
    $rows = '';
    $text = '';
    foreach ($items as $r) {
        $qty   = (int)o_pick($r, ['quantity', 'qty'], 1);
        $price = (float)o_pick($r, ['price', 'unit_price'], 0);
        $size  = o_pick($r, ['size_value', 'size'], '—');
        $pname = $r['product_name'] ?? 'Sản phẩm';

        $rows .= '<tr>'
              . '<td style="padding:8px;border:1px solid #e5e7eb;">' . $h($pname) . '</td>'
              . '<td style="padding:8px;border:1px solid #e5e7eb;text-align:center;">' . $h($size) . '</td>'
              . '<td style="padding:8px;border:1px solid #e5e7eb;text-align:center;">' . $qty . '</td>'
              . '<td style="padding:8px;border:1px solid #e5e7eb;text-align:right;">' . o_money($qty * $price) . '</td>'
              . '</tr>';
        $text .= "- $pname (size $size) x$qty: " . o_money($qty * $price) . "\n";
    }

    $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#111827;">'
          . '<h2 style="color:' . $color . ';margin-bottom:4px;">' . $h($headline) . '</h2>'
          . '<p>Xin chào <strong>' . $h($name) . '</strong>,</p>'
          . '<p>' . $h($intro) . '</p>'
          . '<p><strong>Mã đơn hàng:</strong> #' . $orderId . '</p>'
          . '<table style="border-collapse:collapse;width:100%;font-size:14px;">'
          . '<thead><tr style="background:#f3f4f6;">'
          . '<th style="padding:8px;border:1px solid #e5e7eb;text-align:left;">Sản phẩm</th>'
          . '<th style="padding:8px;border:1px solid #e5e7eb;">Size</th>'
          . '<th style="padding:8px;border:1px solid #e5e7eb;">SL</th>'
          . '<th style="padding:8px;border:1px solid #e5e7eb;text-align:right;">Thành tiền</th>'
          . '</tr></thead><tbody>' . $rows . '</tbody>'
          . '<tfoot><tr><td colspan="3" style="padding:8px;text-align:right;font-weight:bold;">Tổng tiền</td>'
          . '<td style="padding:8px;text-align:right;font-weight:bold;color:#dc2626;">' . o_money($total) . '</td></tr></tfoot>'
          . '</table>'
          . ($address !== '' ? '<p><strong>Địa chỉ nhận hàng:</strong> ' . $h($address) . '</p>' : '')
          . ($hotline !== '' ? '<p>Cần hỗ trợ? Gọi hotline <strong>' . $h($hotline) . '</strong>.</p>' : '')
          . '<p style="color:#6b7280;font-size:13px;">Trân trọng,<br>' . $h($shop) . '</p>'
          . '</div>';

    $alt = "$headline\n\nXin chào $name,\n$intro\n\nMã đơn hàng: #$orderId\n$text"
         . "Tổng tiền: " . o_money($total) . "\n"
         . ($address !== '' ? "Địa chỉ nhận hàng: $address\n" : '')
         . ($hotline !== '' ? "Hotline: $hotline\n" : '')
         . "\nTrân trọng,\n$shop";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->Port       = (int)$cfg['port'];
        $mail->SMTPSecure = (($cfg['encryption'] ?? 'tls') === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout    = 15;
        $mail->CharSet    = 'UTF-8';

        if (isset($cfg['verify_peer']) && $cfg['verify_peer'] === false) {
            $mail->SMTPOptions = ['ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]];
        }

        $mail->setFrom($cfg['from_email'], $shop);
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $alt;

        $mail->send();
        return [true, 'đã gửi email cho khách.'];
    } catch (MailException $e) {
        return [false, $mail->ErrorInfo ?: $e->getMessage()];
    } catch (\Throwable $e) {
        return [false, $e->getMessage()];
    }
}