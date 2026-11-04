<?php
// config/mail.php - cấu hình gửi email bằng PHPMailer
// Điền thông tin của bạn. KHÔNG đưa file này lên GitHub (thêm vào .gitignore).
return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'encryption' => 'tls',                        // 'tls' (cổng 587) hoặc 'ssl' (cổng 465)
    'username'   => 'nhanvv.25it@vku.udn.vn',    // tài khoản gửi mail
    'password'   => 'ryra vmvt oxym zhau', // Gmail: dùng "Mật khẩu ứng dụng", không dùng mật khẩu đăng nhập
    'from_email' => 'nhanvv.25it@vku.udn.vn',
    'from_name'  => 'Văn Nhân Soccer',
    'hotline'    => '0984828392',

    // Chỉ đặt false khi chạy thử trên XAMPP bị lỗi chứng chỉ SSL. Khi chạy thật phải để true.
    'verify_peer' => true,
];