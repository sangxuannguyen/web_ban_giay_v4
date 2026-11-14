# Cấu hình VirtualHost cho XAMPP (Windows)

Mục tiêu: truy cập bằng `http://giaydep.local/` thay vì `http://localhost/doan_giay/`.

1. Mở `C:\xampp\apache\conf\extra\httpd-vhosts.conf`, thêm cuối file:

```apache
<VirtualHost *:80>
    ServerName giaydep.local
    DocumentRoot "C:/xampp/htdocs/doan_giay"
    <Directory "C:/xampp/htdocs/doan_giay">
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "logs/giaydep-error.log"
</VirtualHost>
```

2. Mở Notepad **bằng quyền Administrator**, sửa `C:\Windows\System32\drivers\etc\hosts`, thêm dòng:

```
127.0.0.1   giaydep.local
```

3. Trong `C:\xampp\apache\conf\httpd.conf` kiểm tra dòng `Include conf/extra/httpd-vhosts.conf` **không** bị comment (#).
4. Restart Apache trong XAMPP, mở `http://giaydep.local/`.

## Lưu ý bảo mật
- File `.htaccess` ở thư mục gốc đã chặn truy cập trực tiếp vào `app/`, `.git/`, `docs/` và các file `.sql`, `.md`.
- Cách an toàn hơn nữa (bước phát triển sau): chuyển `index.php` vào `public/` và đặt `DocumentRoot` trỏ vào `public/`,
  khi đó mã nguồn trong `app/` hoàn toàn nằm ngoài tầm truy cập của trình duyệt.
- "Ẩn/rút gọn URL" chỉ làm đẹp địa chỉ, **không** thay thế việc kiểm tra quyền ở phía server.
