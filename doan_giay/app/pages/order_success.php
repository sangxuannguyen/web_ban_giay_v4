<?php
if (!defined('SECURE_CHECK')) exit('Access denied');

$uid = (int)$_SESSION['user']['user_id'];
$s = db()->prepare('SELECT * FROM orders WHERE order_id = ? AND user_id = ?'); // chỉ xem được đơn của chính mình
$s->execute([get_int('id'), $uid]);
$order = $s->fetch();
?>
<main class="page-wrap">
  <?php if (!$order): ?>
    <div class="empty-state"><p>Không tìm thấy đơn hàng.</p><a class="btn btn-dark" href="index.php?page=orders">Xem đơn hàng của tôi</a></div>
  <?php else: ?>
    <div class="auth-card success-card">
      <h1>✓ Đặt hàng thành công!</h1>
      <p>Mã đơn hàng của bạn: <strong>#<?php echo (int)$order['order_id']; ?></strong></p>
      <p>Tổng thanh toán: <strong><?php echo e(format_vnd($order['final_amount'])); ?></strong> (<?php echo e($order['payment_method']); ?>)</p>
      <?php if ($order['payment_method'] === 'Chuyển khoản'): ?>
        <div class="flash flash-info">
          Vui lòng chuyển khoản <strong><?php echo e(format_vnd($order['final_amount'])); ?></strong> với nội dung <strong>DH<?php echo (int)$order['order_id']; ?></strong>.<br>
          <?php echo e(BANK_INFO); ?>
        </div>
      <?php endif; ?>
      <p>Giao đến: <?php echo e($order['recipient_name']); ?> · <?php echo e($order['phone']); ?><br><?php echo e($order['shipping_address']); ?></p>
      <p class="btn-row">
        <a class="btn btn-dark" href="index.php?page=orders">Xem đơn hàng của tôi</a>
        <a class="btn btn-light" href="index.php?page=catalog">Tiếp tục mua sắm</a>
      </p>
    </div>
  <?php endif; ?>
</main>
