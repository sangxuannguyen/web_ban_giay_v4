# Cập nhật v4 (đọc phần này trước)

> Vẫn **chưa chạy thật trên XAMPP** (môi trường tạo bản này không có PHP/MySQL). Hãy thử theo danh sách bên dưới và báo lỗi nếu có.
> CSDL tự nâng cấp lên phiên bản 4 (thêm cột `orders.tracking_code`, bảng `system_job`) ở lần truy cập đầu.

## Thay đổi
1. **Ảnh xem thêm (thư viện ảnh) có CRUD trong admin** – trang sửa sản phẩm: thêm nhiều ảnh cùng lúc (tối đa 6 ảnh/lần, 10 ảnh/sản phẩm),
   sửa thứ tự hiển thị, xóa ảnh (file trên đĩa cũng bị xóa nếu không còn nơi dùng), đặt một ảnh bất kỳ làm ảnh đại diện (ảnh đại diện cũ tự chuyển vào thư viện).
   Trang sản phẩm có ảnh nhỏ bấm để đổi ảnh lớn; ảnh lỗi (file không tồn tại) tự ẩn khỏi danh sách.
2. **Khách chưa đăng nhập xem được size/màu/tồn kho** (chỉ xem), cần đăng nhập để chọn và mua.
3. **Giao diện chọn size mới**: xếp theo màu, mỗi size là một ô bấm được (chọn = nền đen), ô hết hàng bị gạch; chọn size thì giá và số lượng còn lại cập nhật ngay.
4. **Quản trị đơn hàng được nâng cấp** (`pages/admin/orders.php`, `backend/orders.php`, `helpers/automation.php`):
   - Nút "Đã hủy" đổi thành **"Hủy đơn"**, bắt buộc nhập **lý do** (ghi vào lịch sử đơn). Các nút khác: "Xác nhận đơn", "Bắt đầu giao", "Đã giao xong".
   - **Tự động hóa** (chỉnh trong `app/config/app.php`):
     | Quy tắc | Hằng số | Mặc định |
     |---|---|---|
     | Đơn thanh toán online chưa trả tiền quá N giờ → tự hủy (hoàn kho, flash sale, voucher) | `AUTO_CANCEL_UNPAID_HOURS` | 24 (0 = tắt) |
     | Đơn vừa được ghi nhận đã thanh toán → tự xác nhận | `AUTO_CONFIRM_PAID` | true |
     | Đơn COD nhỏ → tự xác nhận sau 10 phút | `AUTO_CONFIRM_COD_UNDER` | 0 = tắt |
     | Cảnh báo đơn chờ xác nhận quá hạn | `STALE_PENDING_HOURS` | 2 |
     Tác vụ chạy tối đa mỗi `MAINT_INTERVAL_MIN` (5) phút khi có người truy cập web, hoặc bấm **"Chạy tự động ngay"** ở trang đơn hàng.
     Muốn chạy đều kể cả khi không ai truy cập: Windows Task Scheduler → chương trình `D:\xampp\php\php.exe`,
     tham số `"D:\xampp\htdocs\<thư-mục>\cron\maintenance.php"`, lặp mỗi 5-10 phút.
   - **Thao tác hàng loạt**: tích chọn nhiều đơn → Xác nhận / Bắt đầu giao / Đã giao xong / Hủy đơn (mỗi đơn xử lý trong transaction riêng, đơn nào lỗi chỉ báo riêng đơn đó).
   - **Bộ lọc & tìm kiếm**: theo trạng thái (có số lượng), trạng thái thanh toán, và ô tìm mã đơn / tên / SĐT / tài khoản / mã vận đơn.
   - **Cảnh báo**: đơn quá hạn xử lý (viền vàng), đếm ngược thời gian tự hủy của đơn online, số đơn chờ hoàn tiền.
   - **Mã vận đơn** khi chuyển sang "Đang giao" (hiển thị cho khách); **lịch sử xử lý** của từng đơn.
   - **Quy tắc thanh toán**: đơn online chưa thanh toán thì không thể "Đang giao". Đơn đã hủy mà khách đã trả tiền → "Chờ hoàn tiền" → nút **"Đã hoàn tiền"** (trước đây không có cách hoàn tất trạng thái này).

## Kiểm thử v4
1. Admin → Sửa sản phẩm → thêm 2-3 ảnh xem thêm, đổi thứ tự, đặt 1 ảnh làm đại diện, xóa 1 ảnh → mở trang sản phẩm kiểm tra.
2. Đăng xuất → mở trang sản phẩm → thấy size nhưng không có nút mua; đăng nhập → chọn size, giá cập nhật.
3. Đặt đơn bằng ví MoMo (mô phỏng) nhưng **không** thanh toán → trong trang đơn admin thấy "Tự hủy sau ...". Để thử nhanh: đổi `AUTO_CANCEL_UNPAID_HOURS` thành 1 và sửa `created_at` của đơn lùi 2 giờ trong phpMyAdmin, bấm "Chạy tự động ngay" → đơn tự hủy, kho được hoàn.
4. Đơn ví đã thanh toán ở trạng thái "Chờ xác nhận" → tự thành "Đã xác nhận" (xem trong "Lịch sử xử lý").
5. Chọn 3 đơn → "Xác nhận" hàng loạt; thử "Hủy đơn" hàng loạt không nhập lý do → bị từ chối.
6. Hủy một đơn đã thanh toán → "Chờ hoàn tiền" → bấm "Đã hoàn tiền".

## Giới hạn
- Tác vụ tự động chỉ chạy khi có người truy cập (hoặc có Task Scheduler). Kích thước tải lên phụ thuộc `upload_max_filesize` / `post_max_size` trong `php.ini` (XAMPP mặc định 2M / 8M); nếu tải nhiều ảnh bị bỏ qua hãy tăng các giá trị này.
- Ảnh trong dữ liệu mẫu có đường dẫn không tồn tại nên sẽ tự ẩn; hãy tải ảnh thật qua trang admin.

---

# Bản cập nhật v3 – Hướng dẫn cài đặt, kiểm thử và ghi chú thiết kế

> **Trạng thái kiểm thử:** mã được rà soát tĩnh (cân bằng ngoặc, hàm, whitelist action/page, đường dẫn include, số tham số SQL).
> **Chưa chạy thật trên XAMPP** vì môi trường tạo bản này không có PHP/MySQL. Hãy chạy theo mục 3 và báo lại mọi lỗi
> (kèm nội dung khung lỗi hiển thị) để sửa tiếp.

## 0. Vì sao bản trước báo "Unknown column 'points'" và không thanh toán được?
Các tính năng mới cần thêm cột/bảng vào CSDL (vd. `users.points`, `orders.recipient_name`). Bản trước yêu cầu bạn tự import file
`web_giaydep_migration_v2.sql`; CSDL của bạn chưa có các cột đó nên trang Tài khoản, Dashboard admin và bước Đặt hàng đều lỗi.
**Bản này tự kiểm tra và tự nâng cấp CSDL** (`app/helpers/migrate.php`) ngay lần truy cập đầu tiên của mỗi phiên, nên bạn không cần import tay nữa.
Nếu việc tự nâng cấp thất bại (vd. thiếu quyền), trang sẽ hiện **danh sách lỗi SQL cụ thể** thay vì lỗi mơ hồ.

## 1. Cài đặt
1. Sao lưu dự án. Giải nén bản này đè lên thư mục dự án trong `htdocs`.
2. Bật Apache + MySQL. Nếu CSDL `web_giaydep` chưa có: import `web_giaydep_schema.sql` rồi `web_giaydep_sample_data.sql`.
3. Mở `http://localhost/<thư-mục>/index.php`. Lần đầu có thể chậm hơn 1-2 giây do tự nâng cấp CSDL.
4. Đăng nhập: `admin` / `password` (admin, tự vào Dashboard), `an.nguyen` / `password` (khách). **Đổi mật khẩu khi nộp bài/triển khai.**
5. Sửa `BANK_INFO` (thông tin chuyển khoản) và `APP_DEBUG` (đặt `false` khi nộp) trong `app/config/app.php`.
6. (Tùy chọn) Bật AI cho chatbot: sao chép `app/config/secrets.example.php` thành `secrets.php`, điền `ANTHROPIC_API_KEY`.
   Không có khóa thì chatbot vẫn chạy ở chế độ theo luật + RAG. Nếu gọi API lỗi SSL trên Windows, cấu hình `curl.cainfo` trong `php.ini`.
7. Bật flash sale mẫu: vào Quản trị → Flash sale, sửa đợt "Flash sale cuối tháng" sang thời gian hiện tại.

## 2. Những gì đã làm
| Hạng mục | Trạng thái | File chính |
|---|---|---|
| Tự nâng cấp CSDL, trang lỗi thân thiện | Xong | `helpers/migrate.php`, `index.php` |
| Admin/nhân viên đăng nhập → vào thẳng Dashboard | Xong | `backend/auth.php` |
| Phân quyền: **staff** (đơn, sản phẩm, đổi trả, đánh giá) / **admin** (thêm: danh mục, thương hiệu, flash sale, voucher, người dùng) | Xong | `app.php` (`auth`), `functions.php` |
| Đổi trả / hoàn tiền (khách gửi → duyệt → nhận hàng → hoàn tiền/hoàn tất, cộng lại kho) | Xong | `backend/returns.php`, `pages/return_new.php`, `pages/admin/returns.php` |
| Viết đánh giá (chỉ khi đã nhận hàng, 1 lần/dòng đơn) + ẩn/hiện đánh giá | Xong | `backend/reviews.php`, `pages/admin/reviews.php` |
| Quên / đặt lại mật khẩu (token băm, hạn 30 phút) | Xong (email ghi vào `storage/outbox/`) | `pages/forgot.php`, `pages/reset.php`, `helpers/mailer.php` |
| Quản lý danh mục, thương hiệu (+ lọc theo thương hiệu) | Xong | `pages/admin/catalog.php` |
| Quản lý flash sale | Xong | `pages/admin/flash.php` |
| Chatbot tư vấn size + RAG (BM25) + tra cứu đơn; tùy chọn AI sinh câu trả lời | Xong | `helpers/rag.php`, `backend/chatbot.php`, `components/chatbot.php` |
| Gợi ý sản phẩm (mua chung, cùng danh mục, theo sở thích, phổ biến) | Xong (thuật toán, không dùng LLM) | `helpers/recommend.php` |
| Thanh toán ví điện tử | **Mô phỏng** (demo luồng, không có tiền thật) | `pages/pay_sim.php`, `backend/orders.php` |

## 3. Danh sách kiểm thử thủ công
1. **Tự nâng cấp:** vào trang chủ → không còn lỗi `Unknown column`. Vào Tài khoản → thấy hạng/điểm.
2. **Admin:** đăng xuất, đăng nhập `admin` → tự chuyển tới Dashboard. Đăng nhập `an.nguyen` rồi mở `?page=admin` → 403.
3. **Đặt hàng:** thêm vào giỏ → thanh toán COD → đơn hiện ở "Đơn hàng của tôi", tồn kho giảm. Thử "Ví MoMo (mô phỏng)": sau đặt hàng sang trang mô phỏng → Xác nhận → đơn "Đã thanh toán".
4. **Đổi trả:** admin chuyển đơn qua Xác nhận → Đang giao → Đã giao; khách vào đơn → "Đổi/Trả hàng" → gửi; admin vào Đổi trả: Duyệt → Đã nhận hàng trả (kho +) → Đã hoàn tiền (trạng thái thanh toán đổi).
5. **Đánh giá:** khách có đơn "Đã giao" mở trang sản phẩm đó → thấy form đánh giá; admin ẩn/hiện ở trang Đánh giá.
6. **Quên mật khẩu:** nhập email → mở file mới nhất trong `storage/outbox/` → mở liên kết → đặt mật khẩu mới.
7. **Chatbot:** hỏi "chân mình dài 26 cm nên chọn size nào" (→ EU 42), "chính sách đổi trả", "đơn #1" (khi đã đăng nhập).
8. **Flash sale:** Quản trị → Flash sale → thêm biến thể với giá sale → trang sản phẩm hiện "⚡ Flash sale", giỏ hàng tính giá sale.
9. **Xung đột kho:** đặt tồn kho một biến thể = 1, hai trình duyệt cùng đặt → người thứ hai bị báo hết hàng, tồn không âm.

## 4. Ghi chú thiết kế
- **RAG:** (1) Retrieve: BM25 trên kho tri thức (`knowledge_base`) + danh sách sản phẩm, bỏ dấu tiếng Việt khi so khớp;
  (2) Augment: ghép đoạn tìm được + kết quả tính size/đơn hàng đã được hệ thống xác thực vào prompt;
  (3) Generate: có API key thì AI diễn đạt (prompt chỉ cho dùng ngữ cảnh, coi câu hỏi của khách là dữ liệu chứ không phải mệnh lệnh);
  không có key thì trả lời bằng nội dung truy xuất. Có giới hạn 15 tin/10 phút/phiên, CSRF, ghi `chat_log`.
- **Tư vấn size:** chọn size EU nhỏ nhất có chiều dài lòng giày ≥ chiều dài chân + 0.5 cm (+0.5 nếu chân bè). Bảng `size_chart` là **số liệu tham khảo**; hãy thay bằng bảng size thật của hãng.
- **Đổi trả:** khóa đơn bằng `FOR UPDATE` khi gửi yêu cầu để không đổi/trả vượt số lượng đã mua; hạn `RETURN_DAYS` = 7 ngày từ khi "Đã giao".
- **Quên mật khẩu:** chỉ lưu `sha256(token)`; phản hồi luôn giống nhau dù email có tồn tại; tối đa 3 yêu cầu/giờ/tài khoản; đặt lại xong vô hiệu mọi token cũ.
- Các nguyên tắc bảo mật/transaction của bản trước vẫn giữ nguyên (PDO prepared statement, CSRF, XSS escape, trừ kho nguyên tử, v.v.).

## 5. Hạn chế đã biết
1. **Chưa chạy thực tế.** Có thể còn lỗi nhỏ khi chạy lần đầu.
2. **MoMo/ngân hàng thật chưa tích hợp**: cần tài khoản merchant, public URL cho IPN và kiểm tra chữ ký HMAC. Trang mô phỏng đã tách sẵn để thay thế (xem chú thích trong `action_pay_simulate`).
3. Email quên mật khẩu mặc định chỉ ghi file (`MAIL_MODE = 'outbox'`); muốn gửi thật cần SMTP hoặc `MAIL_MODE = 'php'` + cấu hình `sendmail`.
4. Hoàn tiền chỉ **ghi nhận** trạng thái; việc chuyển tiền thật do cửa hàng thực hiện. Điểm thưởng không bị trừ khi hoàn tiền.
5. Gợi ý sản phẩm là thuật toán chấm điểm, không phải mô hình học máy; dữ liệu càng nhiều (đơn, yêu thích) gợi ý càng tốt.
6. Giỏ hàng vẫn yêu cầu đăng nhập; `index.php` vẫn nằm ngoài `public/`; chưa "thiết kế lại toàn bộ" giao diện.
7. Chưa có: ảnh phụ nhiều ảnh trong admin (chỉ ảnh đại diện), phân quyền chi tiết hơn cho staff, tìm kiếm có gợi ý khi gõ, thông báo (notification), chat với nhân viên thật.
