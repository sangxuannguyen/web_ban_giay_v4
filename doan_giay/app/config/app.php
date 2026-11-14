<?php
// Ngăn truy cập trực tiếp
if (!defined('SECURE_CHECK')) {
    exit('Access denied');
}

// ---------- Cấu hình nghiệp vụ (đổi ở đây, không cần sửa code nơi khác) ----------
const SHIPPING_FEE      = 30000;    // phí vận chuyển mặc định
const FREE_SHIP_FROM    = 2000000;  // đơn từ mức này trở lên được miễn phí vận chuyển
const PRODUCTS_PER_PAGE = 12;
const MAX_QTY_PER_ITEM  = 10;
const MAX_LOGIN_FAILS   = 5;        // sai quá số lần này sẽ bị khóa tạm
const LOGIN_LOCK_MINUTES = 15;
const POINT_PER_VND     = 10000;    // mỗi 10.000đ = 1 điểm (cộng khi đơn "Đã giao")

const SCHEMA_VERSION    = 5;        // tăng số này khi thêm cột/bảng mới trong app/helpers/migrate.php
const APP_DEBUG         = true;     // true: hiện chi tiết lỗi để dễ sửa. ĐẶT false khi nộp/triển khai thật
const RETURN_DAYS       = 7;

// ---------- Tự động hóa quản lý đơn hàng / thanh toán (xem app/helpers/automation.php) ----------
const AUTO_CANCEL_UNPAID_HOURS = 24;  // đơn thanh toán ONLINE (ví/chuyển khoản) chưa trả tiền sau N giờ -> tự hủy, hoàn kho + voucher. 0 = tắt
const AUTO_CONFIRM_PAID        = true; // đơn vừa được ghi nhận "đã thanh toán" -> tự chuyển "Đã xác nhận"
const AUTO_CONFIRM_COD_UNDER   = 0;    // đơn COD có giá trị <= số này (và đã chờ >= 10 phút) -> tự xác nhận. 0 = tắt (xác nhận thủ công)
const STALE_PENDING_HOURS      = 2;    // đơn "Chờ xác nhận" quá N giờ sẽ được cảnh báo "quá hạn xử lý"
const MAINT_INTERVAL_MIN       = 5;    // tác vụ tự động chạy tối đa mỗi N phút (kích hoạt khi có người truy cập web)        // số ngày được đổi/trả kể từ khi đơn "Đã giao"
const CHAT_MAX_PER_10MIN = 15;      // giới hạn số tin nhắn chatbot / 10 phút / phiên
const WALLET_METHOD     = 'Ví MoMo (mô phỏng)';
const AI_MODEL          = 'claude-sonnet-5-5';           // chỉ dùng khi có ANTHROPIC_API_KEY (xem docs)
const AI_ENDPOINT       = 'https://api.anthropic.com/v1/messages';
const MAIL_MODE         = 'outbox'; // 'outbox': ghi email ra thư mục storage/outbox (dùng khi chưa có SMTP) | 'php': dùng hàm mail()
const MAIL_FROM         = 'no-reply@giaydep.local';

const PAYMENT_METHODS = [
    'COD'          => 'Thanh toán khi nhận hàng (COD)',
    'Chuyển khoản' => 'Chuyển khoản ngân hàng',
    WALLET_METHOD  => 'Ví điện tử MoMo (mô phỏng để demo, không trừ tiền thật)',
];
const ORDER_STATUS = [
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao',
    'delivered' => 'Đã giao',
    'cancelled' => 'Đã hủy',
];
const PAYMENT_STATUS = [
    'unpaid'         => 'Chưa thanh toán',
    'paid'           => 'Đã thanh toán',
    'refund_pending' => 'Chờ hoàn tiền',
    'partial_refund' => 'Hoàn tiền một phần',
    'refunded'       => 'Đã hoàn tiền',
];
const RETURN_STATUS = [
    'requested' => 'Chờ duyệt',
    'approved'  => 'Đã duyệt - chờ nhận hàng trả',
    'rejected'  => 'Từ chối',
    'received'  => 'Đã nhận hàng trả',
    'refunded'  => 'Đã hoàn tiền',
    'completed' => 'Hoàn tất',
];
const RETURN_TYPES = ['return' => 'Trả hàng - hoàn tiền', 'exchange' => 'Đổi hàng'];
// Thông tin chuyển khoản hiển thị cho khách chọn "Chuyển khoản" (HÃY SỬA thành thông tin thật của nhóm)
const BANK_INFO = 'Ngân hàng: [Tên ngân hàng] - STK: [Số tài khoản] - Chủ TK: [Tên chủ tài khoản]';
const GENDERS = ['nam' => 'Nam', 'nu' => 'Nữ', 'tre' => 'Trẻ em', 'unisex' => 'Unisex'];

// ---------- Danh sách hành động (POST) hợp lệ: tên hành động => file trong app/backend ----------
$actions = [
    'register'        => 'auth',
    'login'           => 'auth',
    'logout'          => 'auth',
    'profile_update'  => 'auth',
    'change_password' => 'auth',
    'cart_add'        => 'cart',
    'cart_update'     => 'cart',
    'cart_remove'     => 'cart',
    'apply_voucher'   => 'cart',
    'remove_voucher'  => 'cart',
    'favorite_toggle' => 'favorites',
    'place_order'     => 'orders',
    'cancel_order'    => 'orders',
    'password_forgot' => 'auth',
    'password_reset'  => 'auth',
    'review_save'     => 'reviews',
    'return_request'  => 'returns',
    'pay_simulate'    => 'orders',
    'admin_product_save'   => 'admin',
    'admin_return_update'  => 'admin',
    'admin_orders_bulk'    => 'admin',
    'admin_order_refund'   => 'admin',
    'admin_run_maintenance' => 'admin',
    'admin_review_toggle'  => 'admin',
    'admin_category_save'  => 'admin',
    'admin_brand_save'     => 'admin',
    'admin_flash_save'     => 'admin',
    'admin_flash_item_save'   => 'admin',
    'admin_flash_item_delete' => 'admin',
    'admin_product_toggle' => 'admin',
    'admin_order_status'   => 'admin',
    'admin_order_paid'     => 'admin',
    'admin_voucher_save'   => 'admin',
    'admin_voucher_toggle' => 'admin',
    'admin_user_update'    => 'admin',
];

// ---------- Cấu hình danh sách trang hợp lệ (Whitelist) ----------
// 'auth' => 'login' : phải đăng nhập;  'auth' => 'admin' : phải là admin;  'css' => null : không cần css riêng
$pages = [
    'home' => [
        'title' => 'Tuyển Chọn | Nike Style',
        'css'   => 'home.css',
        'file'  => 'pages/home.php'
    ],
    'catalog_loi' => [
        'title' => 'Danh sách giày | Tuyển Chọn',
        'css'   => 'catalog_loi.css',
        'file'  => 'pages/catalog_loi.php'
    ],
    'giaodien' => [
        'title' => 'TEST GIAO DIỆN | TEST',
        'css'   => 'giaodien.css',
        'file'  => 'pages/giaodien.php'
    ],
    'catalog' => [
        'title' => 'Sản phẩm | Tuyển Chọn',
        'css'   => 'catalog.css',
        'file'  => 'pages/catalog.php'
    ],
    'bosuutap' => [
        'title' => 'Bộ sưu tập | Tuyển Chọn',
        'css'   => 'bosuutap.css',
        'file'  => 'pages/bosuutap.php'
    ],
    'product' => [
        'title' => 'Chi tiết sản phẩm | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/product.php'
    ],
    'cart' => [
        'title' => 'Giỏ hàng | Tuyển Chọn',
        'css'   => 'cart.css',
        'file'  => 'pages/cart.php',
        'auth'  => 'login'
    ],
    'checkout' => [
        'title' => 'Thanh toán | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/checkout.php',
        'auth'  => 'login'
    ],
    'order_success' => [
        'title' => 'Đặt hàng thành công | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/order_success.php',
        'auth'  => 'login'
    ],
    'orders' => [
        'title' => 'Đơn hàng của tôi | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/orders.php',
        'auth'  => 'login'
    ],
    'account' => [
        'title' => 'Tài khoản | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/account.php',
        'auth'  => 'login'
    ],
    'favorites' => [
        'title' => 'Yêu thích | Tuyển Chọn',
        'css'   => 'catalog.css',
        'file'  => 'pages/favorites.php',
        'auth'  => 'login'
    ],
    'login' => [
        'title' => 'Đăng nhập | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/login.php'
    ],
    'register' => [
        'title' => 'Đăng ký | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/register.php'
    ],
    'forgot' => [
        'title' => 'Quên mật khẩu | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/forgot.php'
    ],
    'reset' => [
        'title' => 'Đặt lại mật khẩu | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/reset.php'
    ],
    'returns' => [
        'title' => 'Đổi trả | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/returns.php',
        'auth'  => 'login'
    ],
    'return_new' => [
        'title' => 'Yêu cầu đổi trả | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/return_new.php',
        'auth'  => 'login'
    ],
    'pay_sim' => [
        'title' => 'Thanh toán MoMo (mô phỏng) | Tuyển Chọn',
        'css'   => null,
        'file'  => 'pages/pay_sim.php',
        'auth'  => 'login'
    ],
    // ----- Khu vực quản trị -----
    'admin' => [
        'title' => 'Quản trị | Dashboard',
        'css'   => null,
        'file'  => 'pages/admin/dashboard.php',
        'auth'  => 'staff'
    ],
    'admin_products' => [
        'title' => 'Quản trị | Sản phẩm',
        'css'   => null,
        'file'  => 'pages/admin/products.php',
        'auth'  => 'staff'
    ],
    'admin_product_form' => [
        'title' => 'Quản trị | Sửa sản phẩm',
        'css'   => null,
        'file'  => 'pages/admin/product_form.php',
        'auth'  => 'staff'
    ],
    'admin_orders' => [
        'title' => 'Quản trị | Đơn hàng',
        'css'   => null,
        'file'  => 'pages/admin/orders.php',
        'auth'  => 'staff'
    ],
    'admin_vouchers' => [
        'title' => 'Quản trị | Voucher',
        'css'   => null,
        'file'  => 'pages/admin/vouchers.php',
        'auth'  => 'admin'
    ],
    'admin_returns' => [
        'title' => 'Quản trị | Đổi trả',
        'css'   => null,
        'file'  => 'pages/admin/returns.php',
        'auth'  => 'staff'
    ],
    'admin_reviews' => [
        'title' => 'Quản trị | Đánh giá',
        'css'   => null,
        'file'  => 'pages/admin/reviews.php',
        'auth'  => 'staff'
    ],
    'admin_catalog' => [
        'title' => 'Quản trị | Danh mục & thương hiệu',
        'css'   => null,
        'file'  => 'pages/admin/catalog.php',
        'auth'  => 'admin'
    ],
    'admin_flash' => [
        'title' => 'Quản trị | Flash sale',
        'css'   => null,
        'file'  => 'pages/admin/flash.php',
        'auth'  => 'admin'
    ],
    'admin_users' => [
        'title' => 'Quản trị | Người dùng',
        'css'   => null,
        'file'  => 'pages/admin/users.php',
        'auth'  => 'admin'
    ],
];
