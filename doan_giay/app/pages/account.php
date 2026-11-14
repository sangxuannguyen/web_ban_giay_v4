<?php
if (!defined('SECURE_CHECK')) exit('Access denied');

$uid = (int)$_SESSION['user']['user_id'];
$s = db()->prepare('SELECT username, name, email, phone, points, created_at FROM users WHERE user_id = ?');
$s->execute([$uid]);
$u = $s->fetch();
$points = (int)$u['points'];
$tier = member_tier($points);
?>
<main class="page-wrap wide">
  <h1>Tài khoản của tôi</h1>

  <div class="member-card">
    <div>
      <div class="muted">Hạng thành viên</div>
      <div class="member-tier"><?php echo e($tier); ?></div>
    </div>
    <div>
      <div class="muted">Điểm tích lũy</div>
      <div class="member-tier"><?php echo number_format($points, 0, ',', '.'); ?></div>
    </div>
    <p class="hint">Mỗi <?php echo e(format_vnd(POINT_PER_VND)); ?> của đơn hàng đã giao được cộng 1 điểm. Bạc từ 500 điểm, Vàng từ 2.000 điểm.</p>
  </div>

  <div class="two-col">
    <form method="post" action="index.php" class="form-card form-stack">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="profile_update">
      <h2>Thông tin cá nhân</h2>
      <label>Tên đăng nhập <input type="text" value="<?php echo e($u['username']); ?>" disabled></label>
      <label>Email <input type="text" value="<?php echo e($u['email']); ?>" disabled></label>
      <label>Họ và tên <input type="text" name="name" required maxlength="100" value="<?php echo e($u['name']); ?>"></label>
      <label>Số điện thoại <input type="tel" name="phone" maxlength="11" pattern="0[0-9]{9,10}" value="<?php echo e($u['phone'] ?? ''); ?>"></label>
      <button type="submit" class="btn btn-dark">Lưu thay đổi</button>
    </form>

    <form method="post" action="index.php" class="form-card form-stack">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="change_password">
      <h2>Đổi mật khẩu</h2>
      <label>Mật khẩu hiện tại <input type="password" name="current_password" required autocomplete="current-password"></label>
      <label>Mật khẩu mới (tối thiểu 8 ký tự) <input type="password" name="new_password" required minlength="8" autocomplete="new-password"></label>
      <label>Nhập lại mật khẩu mới <input type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password"></label>
      <button type="submit" class="btn btn-dark">Đổi mật khẩu</button>
    </form>
  </div>

  <p><a class="btn btn-light" href="index.php?page=orders">Xem đơn hàng của tôi</a>
     <a class="btn btn-light" href="index.php?page=returns">Đổi/trả của tôi</a>
     <a class="btn btn-light" href="index.php?page=favorites">Sản phẩm yêu thích</a></p>
</main>
