<?php
if (!defined('SECURE_CHECK')) exit('Access denied');

$uid = (int)$_SESSION['user']['user_id'];
$s = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY order_id DESC LIMIT 50');
$s->execute([$uid]);
$orders = $s->fetchAll();

// Lấy chi tiết của tất cả đơn bằng 1 truy vấn (id đều là số nguyên lấy từ CSDL)
$details = [];
if ($orders) {
    $ids = array_map('intval', array_column($orders, 'order_id'));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $d = db()->prepare("SELECT * FROM order_detail WHERE order_id IN ($in) ORDER BY order_detail_id");
    $d->execute($ids);
    foreach ($d->fetchAll() as $row) {
        $details[$row['order_id']][] = $row;
    }
}
?>
<main class="page-wrap wide">
  <h1>Đơn hàng của tôi</h1>
  <p><a class="btn btn-light btn-sm" href="index.php?page=returns">Yêu cầu đổi/trả của tôi</a></p>
  <?php if (!$orders): ?>
    <div class="empty-state"><p>Bạn chưa có đơn hàng nào.</p><a class="btn btn-dark" href="index.php?page=catalog">Mua sắm ngay</a></div>
  <?php endif; ?>

  <?php foreach ($orders as $o): ?>
    <section class="order-card">
      <header class="order-head">
        <div>
          <strong>Đơn #<?php echo (int)$o['order_id']; ?></strong>
          <span class="muted"> · <?php echo e(date('d/m/Y H:i', strtotime($o['created_at']))); ?></span>
        </div>
        <div>
          <span class="badge badge-<?php echo e($o['order_status']); ?>"><?php echo e(ORDER_STATUS[$o['order_status']] ?? $o['order_status']); ?></span>
          <span class="badge badge-pay-<?php echo e($o['payment_status']); ?>"><?php echo e(PAYMENT_STATUS[$o['payment_status']] ?? $o['payment_status']); ?></span>
        </div>
      </header>

      <table class="table">
        <thead><tr><th>Sản phẩm</th><th>Size/Màu</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
        <tbody>
        <?php foreach ($details[$o['order_id']] ?? [] as $row): ?>
          <tr>
            <td><?php echo e($row['product_name']); ?></td>
            <td><?php echo e($row['size']); ?> / <?php echo e($row['color']); ?></td>
            <td><?php echo (int)$row['quantity']; ?></td>
            <td><?php echo e(format_vnd($row['unit_price'])); ?></td>
            <td><?php echo e(format_vnd($row['subtotal'])); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <div class="order-foot">
        <div class="muted">
          <?php if (!empty($o['tracking_code'])): ?>Mã vận đơn: <strong><?php echo e($o['tracking_code']); ?></strong><br><?php endif; ?>
          <?php if ($o['order_status'] === 'pending' && $o['payment_status'] === 'unpaid' && in_array($o['payment_method'], [WALLET_METHOD, 'Chuyển khoản'], true) && AUTO_CANCEL_UNPAID_HOURS > 0): ?>
            <strong>Vui lòng thanh toán trước <?php echo e(date('H:i d/m/Y', strtotime($o['created_at']) + AUTO_CANCEL_UNPAID_HOURS * 3600)); ?></strong>, quá hạn đơn sẽ tự hủy.<br>
          <?php endif; ?>
          Giao đến: <?php echo e($o['recipient_name'] ?: '—'); ?> · <?php echo e($o['phone'] ?: ''); ?> · <?php echo e($o['shipping_address'] ?? ''); ?><br>
          Phí vận chuyển <?php echo e(format_vnd($o['shipping_fee'])); ?> · Giảm giá −<?php echo e(format_vnd($o['discount_amount'])); ?> · Thanh toán: <?php echo e($o['payment_method']); ?>
        </div>
        <div class="order-total">
          <strong><?php echo e(format_vnd($o['final_amount'])); ?></strong>
          <?php if ($o['payment_method'] === WALLET_METHOD && $o['payment_status'] === 'unpaid' && $o['order_status'] !== 'cancelled'): ?>
            <a class="btn btn-dark btn-sm" href="index.php?page=pay_sim&id=<?php echo (int)$o['order_id']; ?>">Thanh toán ví</a>
          <?php endif; ?>
          <?php if ($o['order_status'] === 'delivered'): ?>
            <a class="btn btn-light btn-sm" href="index.php?page=return_new&order_id=<?php echo (int)$o['order_id']; ?>">Đổi/Trả hàng</a>
          <?php endif; ?>
          <?php if ($o['order_status'] === 'pending'): ?>
            <form method="post" action="index.php" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn này?');">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="cancel_order">
              <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
              <button type="submit" class="btn btn-light btn-sm">Hủy đơn</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endforeach; ?>
</main>
