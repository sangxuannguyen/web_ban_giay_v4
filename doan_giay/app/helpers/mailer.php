<?php
// Gửi email. Mặc định (MAIL_MODE = 'outbox') KHÔNG gửi thật mà ghi thư vào storage/outbox/ để bạn mở xem khi demo
// (XAMPP thường chưa cấu hình SMTP). Khi có SMTP/hosting, đổi MAIL_MODE sang 'php'.
if (!defined('SECURE_CHECK')) {
    exit('Access denied');
}

function send_mail(string $to, string $subject, string $body): bool
{
    if (MAIL_MODE === 'php') {
        $headers = 'From: ' . MAIL_FROM . "\r\nContent-Type: text/plain; charset=UTF-8";
        return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
    }
    $dir = __DIR__ . '/../../storage/outbox';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return false;
    }
    $file = $dir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.txt';
    return file_put_contents($file, "TO: $to\nSUBJECT: $subject\nDATE: " . date('c') . "\n\n$body\n") !== false;
}
