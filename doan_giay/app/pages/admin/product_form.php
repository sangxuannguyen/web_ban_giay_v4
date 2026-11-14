<?php
if (!defined('SECURE_CHECK')) exit('Access denied');

$pdo = db();
$id  = get_int('id');
$product = ['product_id' => 0, 'category_id' => 0, 'name' => '', 'description' => '', 'base_price' => '', 'sale_price' => '', 'badge' => 'none', 'gender' => 'unisex', 'status' => 'active', 'thumbnail' => ''];
$variants = [];
$moves = [];
$gallery = [];

if ($id > 0) {
    $s = $pdo->prepare('SELECT * FROM product WHERE product_id = ?');
    $s->execute([$id]);
    $found = $s->fetch();
    if (!$found) {
        echo '<main class="page-wrap"><div class="empty-state"><p>Không tìm thấy sản phẩm.</p><a class="btn btn-dark" href="index.php?page=admin_products">Quay lại</a></div></main>';
        return;
    }
    $product = $found;
    $s = $pdo->prepare('SELECT * FROM product_variant WHERE product_id = ? ORDER BY variant_id');
    $s->execute([$id]);
    $variants = $s->fetchAll();

    $s = $pdo->prepare('SELECT image_id, image_url, sort_order FROM product_image WHERE product_id = ? ORDER BY sort_order, image_id');
    $s->execute([$id]);
    $gallery = $s->fetchAll();

    $s = $pdo->prepare(
        'SELECT m.change_qty, m.reason, m.created_at, v.sku FROM stock_movement m
           JOIN product_variant v ON v.variant_id = m.variant_id
          WHERE v.product_id = ? ORDER BY m.movement_id DESC LIMIT 15'
    );
    $s->execute([$id]);
    $moves = $s->fetchAll();
}
$categories = $pdo->query('SELECT category_id, name FROM category ORDER BY name')->fetchAll();
$brands = $pdo->query('SELECT brand_id, name FROM brand ORDER BY name')->fetchAll();

// Thêm 3 dòng trống để nhập biến thể mới
for ($i = 0; $i < 3; $i++) {
    $variants[] = ['variant_id' => 0, 'size' => '', 'color' => '', 'sku' => '', 'price' => $product['base_price'], 'stock_quantity' => 0, 'status' => 'active'];
}
$reasons = ['order' => 'Khách đặt hàng', 'cancel' => 'Hủy đơn (hoàn kho)', 'admin_adjust' => 'Admin chỉnh kho', 'admin_init' => 'Nhập kho ban đầu'];
?>
<main class="page-wrap wide">
  <h1>Quản trị</h1>
  <?php include __DIR__ . '/../../components/admin_nav.php'; ?>
  <h2><?php echo $id > 0 ? 'Sửa sản phẩm #' . $id : 'Thêm sản phẩm mới'; ?></h2>

  <form method="post" action="index.php" enctype="multipart/form-data" class="form-card form-stack">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="admin_product_save">
    <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">

    <label>Tên sản phẩm
      <input type="text" name="name" required maxlength="200" value="<?php echo e($product['name']); ?>">
    </label>
    <div class="two-col">
      <label>Danh mục
        <select name="category_id" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo (int)$c['category_id']; ?>" <?php echo ((int)$product['category_id'] === (int)$c['category_id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Thương hiệu
        <select name="brand_id">
          <option value="0">-- chưa chọn --</option>
          <?php foreach ($brands as $b): ?>
            <option value="<?php echo (int)$b['brand_id']; ?>" <?php echo ((int)($product['brand_id'] ?? 0) === (int)$b['brand_id']) ? 'selected' : ''; ?>><?php echo e($b['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Dành cho
        <select name="gender">
          <?php foreach (GENDERS as $k => $label): ?>
            <option value="<?php echo e($k); ?>" <?php echo ($product['gender'] === $k) ? 'selected' : ''; ?>><?php echo e($label); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div class="two-col">
      <label>Giá cơ bản (₫)
        <input type="number" name="base_price" id="basePriceInput" required min="0" step="1000" value="<?php echo e($product['base_price'] !== '' ? (int)$product['base_price'] : ''); ?>" oninput="calcDiscountPreview()">
      </label>
      <label>Trạng thái hiển thị
        <select name="status">
          <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Đang bán</option>
          <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Ẩn</option>
        </select>
      </label>
    </div>
    <div class="two-col">
      <label>Nhãn / Trạng thái sản phẩm (Tag)
        <select name="badge" id="productBadgeSelect" onchange="toggleSalePriceInput()">
          <option value="none" <?php echo ($product['badge'] ?? 'none') === 'none' ? 'selected' : ''; ?>>-- Bình thường (Không nhãn) --</option>
          <option value="new" <?php echo ($product['badge'] ?? '') === 'new' ? 'selected' : ''; ?>>✨ Sản phẩm mới (MỚI)</option>
          <option value="sale" <?php echo ($product['badge'] ?? '') === 'sale' ? 'selected' : ''; ?>>⚡ Đang giảm giá (SALE)</option>
          <option value="hot" <?php echo ($product['badge'] ?? '') === 'hot' ? 'selected' : ''; ?>>🔥 Bán chạy / Nổi bật (HOT)</option>
        </select>
      </label>
      <label id="salePriceGroup">Giá khuyến mãi sau giảm (₫)
        <input type="number" name="sale_price" id="salePriceInput" min="0" step="1000"
               value="<?php echo (!empty($product['sale_price']) && (float)$product['sale_price'] > 0) ? (int)$product['sale_price'] : ''; ?>"
               placeholder="Nhập giá sale (để trống nếu không giảm)" oninput="calcDiscountPreview()">
        <small id="discountPreviewText" class="hint" style="display:block;margin-top:4px;font-weight:600;"></small>
      </label>
    </div>
    <label>Mô tả
      <textarea name="description" rows="4"><?php echo e($product['description'] ?? ''); ?></textarea>
    </label>
    <label>Ảnh đại diện (JPG/PNG/WEBP, tối đa 2MB)
      <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp">
    </label>
    <?php if (!empty($product['thumbnail'])): ?>
      <img class="thumb-lg" src="<?php echo e(img_url($product['thumbnail'])); ?>" alt="" onerror="this.onerror=null; this.src='public/assets/imgs/placeholder.svg';">
    <?php endif; ?>

    <h3>Ảnh xem thêm (thư viện ảnh)</h3>
    <p class="hint">Tối đa 10 ảnh, mỗi ảnh ≤ 2MB (JPG/PNG/WEBP). Số thứ tự nhỏ hơn hiển thị trước. Ảnh đại diện hiện tại luôn hiển thị đầu tiên.</p>
    <?php if ($gallery): ?>
    <div class="gallery-admin">
      <label class="radio-row"><input type="radio" name="make_cover" value="0" checked> Giữ nguyên ảnh đại diện</label>
      <?php foreach ($gallery as $g): ?>
        <div class="gallery-row">
          <img src="<?php echo e(img_url($g['image_url'])); ?>" alt="" onerror="this.onerror=null; this.src='public/assets/imgs/placeholder.svg';">
          <div>
            <small class="muted"><?php echo e(basename($g['image_url'])); ?></small>
            <?php if (($product['thumbnail'] ?? '') === $g['image_url']): ?> <span class="badge badge-confirmed">Đang là ảnh đại diện</span><?php endif; ?>
          </div>
          <label>Thứ tự <input type="number" name="img_sort[<?php echo (int)$g['image_id']; ?>]" min="0" max="999" value="<?php echo (int)$g['sort_order']; ?>"></label>
          <label><input type="radio" name="make_cover" value="<?php echo (int)$g['image_id']; ?>"> Đặt làm ảnh đại diện</label>
          <label><input type="checkbox" name="img_delete[]" value="<?php echo (int)$g['image_id']; ?>"> Xóa</label>
        </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p class="muted">Chưa có ảnh xem thêm.</p>
    <?php endif; ?>
    <label>Thêm ảnh mới (có thể chọn nhiều ảnh cùng lúc, tối đa 6 ảnh mỗi lần)
      <input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp">
    </label>

    <h3>Biến thể (size / màu / kho)</h3>
    <div class="table-scroll">
    <table class="table variant-table">
      <thead><tr><th>Size</th><th>Màu</th><th>SKU</th><th>Giá bán</th><th>Tồn kho</th><th>Trạng thái</th></tr></thead>
      <tbody>
      <?php foreach ($variants as $i => $v): ?>
        <tr>
          <td>
            <input type="hidden" name="variants[<?php echo $i; ?>][variant_id]" value="<?php echo (int)$v['variant_id']; ?>">
            <input type="text" name="variants[<?php echo $i; ?>][size]" maxlength="20" value="<?php echo e($v['size']); ?>" class="w-sm">
          </td>
          <td><input type="text" name="variants[<?php echo $i; ?>][color]" maxlength="50" value="<?php echo e($v['color']); ?>"></td>
          <td><input type="text" name="variants[<?php echo $i; ?>][sku]" maxlength="50" value="<?php echo e($v['sku']); ?>"></td>
          <td><input type="number" name="variants[<?php echo $i; ?>][price]" min="0" step="1000" value="<?php echo e($v['price'] !== '' ? (int)$v['price'] : ''); ?>"></td>
          <td><input type="number" name="variants[<?php echo $i; ?>][stock_quantity]" min="0" value="<?php echo (int)$v['stock_quantity']; ?>" class="w-sm"></td>
          <td>
            <select name="variants[<?php echo $i; ?>][status]">
              <option value="active" <?php echo $v['status'] === 'active' ? 'selected' : ''; ?>>Bán</option>
              <option value="inactive" <?php echo $v['status'] === 'inactive' ? 'selected' : ''; ?>>Ngừng</option>
            </select>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <p class="hint">Dòng trống sẽ được bỏ qua. SKU phải duy nhất. Mỗi lần đổi tồn kho đều được ghi vào nhật ký bên dưới.</p>

    <div class="btn-row">
      <button type="submit" class="btn btn-dark">Lưu sản phẩm</button>
      <a class="btn btn-light" href="index.php?page=admin_products">Quay lại</a>
    </div>
  </form>

  <?php if ($moves): ?>
  <section class="form-card">
    <h3>Nhật ký kho gần đây</h3>
    <table class="table">
      <thead><tr><th>Thời gian</th><th>SKU</th><th>Thay đổi</th><th>Lý do</th></tr></thead>
      <tbody>
      <?php foreach ($moves as $m): ?>
        <tr>
          <td><?php echo e(date('d/m/Y H:i', strtotime($m['created_at']))); ?></td>
          <td><?php echo e($m['sku']); ?></td>
          <td><?php echo ((int)$m['change_qty'] > 0 ? '+' : '') . (int)$m['change_qty']; ?></td>
          <td><?php echo e($reasons[$m['reason']] ?? $m['reason']); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
  <?php endif; ?>

  <script>
  function toggleSalePriceInput() {
    var b = document.getElementById('productBadgeSelect');
    var sp = document.getElementById('salePriceInput');
    if (b && b.value === 'sale') {
      if (sp && !sp.value) sp.focus();
    }
    calcDiscountPreview();
  }

  function calcDiscountPreview() {
    var baseEl = document.getElementById('basePriceInput');
    var saleEl = document.getElementById('salePriceInput');
    var badgeEl = document.getElementById('productBadgeSelect');
    var hint = document.getElementById('discountPreviewText');
    if (!baseEl || !saleEl || !hint) return;

    var base = parseFloat(baseEl.value) || 0;
    var sale = parseFloat(saleEl.value) || 0;

    if (sale > 0 && base > 0) {
      if (sale >= base) {
        hint.style.color = '#c62828';
        hint.textContent = '⚠ Giá khuyến mãi phải nhỏ hơn giá cơ bản!';
      } else {
        var pct = Math.round((1 - sale / base) * 100);
        var diff = base - sale;
        hint.style.color = '#2e7d32';
        hint.textContent = '⚡ Giảm ' + pct + '% (tiết kiệm ' + diff.toLocaleString('vi-VN') + ' ₫). Tag hiển thị: Sale -' + pct + '%';
        if (badgeEl && badgeEl.value === 'none') {
          badgeEl.value = 'sale';
        }
      }
    } else {
      if (badgeEl && badgeEl.value === 'sale') {
        hint.style.color = '#e65100';
        hint.textContent = 'Hãy nhập giá khuyến mãi để hiển thị % giảm giá trên thẻ sản phẩm.';
      } else {
        hint.textContent = '';
      }
    }
  }
  document.addEventListener('DOMContentLoaded', calcDiscountPreview);
  </script>
</main>
