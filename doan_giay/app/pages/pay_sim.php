<?php
if (!defined('SECURE_CHECK')) exit('Access denied');
$s = db()->prepare("SELECT order_id, final_amount FROM orders WHERE order_id = ? AND user_id = ? AND payment_method = ? AND payment_status = 'unpaid' AND order_status <> 'cancelled'");
$s->execute([get_int('id'), (int)$_SESSION['user']['user_id'], WALLET_METHOD]);
$o = $s->fetch();
?>
<main class="page-wrap">
  <?php if (!$o): ?>
    <div class="empty-state"><p>Không có đơn nào đang chờ thanh toán ví.</p><a class="btn btn-dark" href="index.php?page=orders">Đơn hàng của tôi</a></div>
  <?php else: ?>
  <div class="auth-card success-card">
    <h1>Thanh toán ví MoMo</h1>
    <div class="flash flash-info">Đây là <strong>trang mô phỏng</strong> để demo luồng thanh toán online. Không có giao dịch thật và không trừ tiền.</div>
    <p>Đơn hàng <strong>#<?php echo (int)$o['order_id']; ?></strong></p>
    <p class="pd-price"><?php echo e(format_vnd($o['final_amount'])); ?></p>
    <div class="btn-row" style="justify-content:center">
      <form method="post" action="index.php" class="inline-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="pay_simulate">
        <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
        <button type="submit" name="result" value="success" class="btn btn-dark">Xác nhận thanh toán</button>
        <button type="submit" name="result" value="fail" class="btn btn-light">Hủy / thanh toán sau</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</main>
