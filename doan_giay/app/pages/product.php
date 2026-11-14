<?php
if (!defined('SECURE_CHECK')) exit('Access denied');

$pdo = db();
$id  = get_int('id');

$s = $pdo->prepare("SELECT p.*, c.name AS category_name, b.name AS brand_name FROM product p
                      JOIN category c ON c.category_id = p.category_id
                      LEFT JOIN brand b ON b.brand_id = p.brand_id
                     WHERE p.product_id = ? AND p.status = 'active'");
$s->execute([$id]);
$product = $s->fetch();

if (!$product) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    return;
}

// Các biến thể (size + màu) còn bán
$s = $pdo->prepare("SELECT variant_id, size, color, sku, price, stock_quantity FROM product_variant
                     WHERE product_id = ? AND status = 'active'
                     ORDER BY color, CAST(size AS UNSIGNED), size");
$s->execute([$id]);
$variants = $s->fetchAll();

$basePrice = (float)$product['base_price'];
$salePrice = !empty($product['sale_price']) ? (float)$product['sale_price'] : 0.0;
$isProductSale = ($salePrice > 0 && $salePrice < $basePrice);
$productDiscountPercent = $isProductSale ? (int)round((1 - $salePrice / $basePrice) * 100) : 0;

foreach ($variants as &$v) {                 // gắn giá hiển thị (flash sale nếu có)
    $flash = active_flash_item((int)$v['variant_id']);
    if ($flash) {
        $v['display_price'] = (float)$flash['sale_price'];
        $v['is_flash']      = true;
    } elseif ($isProductSale) {
        $vPrice = (float)$v['price'];
        $v['display_price'] = ($vPrice == $basePrice) ? $salePrice : round($vPrice * ($salePrice / $basePrice));
        $v['is_flash']      = false;
    } else {
        $v['display_price'] = (float)$v['price'];
        $v['is_flash']      = false;
    }
}
unset($v);

// Nhóm theo màu để hiển thị: mỗi màu một hàng các size
$byColor = [];
foreach ($variants as $v) {
    $byColor[$v['color']][] = $v;
}

// Ảnh: ảnh đại diện trước, rồi ảnh xem thêm theo thứ tự admin sắp xếp
$s = $pdo->prepare('SELECT image_url FROM product_image WHERE product_id = ? ORDER BY sort_order, image_id');
$s->execute([$id]);
$images = $s->fetchAll(PDO::FETCH_COLUMN);
if (!empty($product['thumbnail'])) {
    array_unshift($images, $product['thumbnail']);
}
$images = array_values(array_unique($images));

// Đánh giá
$s = $pdo->prepare("SELECT r.rating, r.comment, r.created_at, u.name FROM review r JOIN users u ON u.user_id = r.user_id
                     WHERE r.product_id = ? AND r.status = 'visible' ORDER BY r.created_at DESC LIMIT 20");
$s->execute([$id]);
$reviews = $s->fetchAll();
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;

$in_stock = array_filter($variants, fn($v) => (int)$v['stock_quantity'] > 0);
$fav = isset(favorite_ids()[$id]);
$self = 'index.php?page=product&id=' . $id;
$can_review = is_logged_in() && reviewable_detail_id((int)$_SESSION['user']['user_id'], $id) !== null;
$rec = recommend_products(4, $id);

// Vẽ một ô size. $interactive = true: có nút chọn (người đã đăng nhập); false: chỉ để xem (khách)
if (!function_exists('pd_size_pill')) {
    function pd_size_pill(array $v, bool $interactive): void
    {
        $stock = (int)$v['stock_quantity'];
        $out   = ($stock <= 0);
        if ($out)               $sub = 'Hết hàng';
        elseif ($stock <= 5)    $sub = 'Còn ' . $stock;
        elseif ($v['is_flash']) $sub = '⚡ Sale';
        else                    $sub = '';
        $label = 'Size ' . $v['size'] . ' · ' . $v['color'];

        echo '<label class="size-pill' . ($out ? ' is-out' : '') . ($interactive ? '' : ' is-static') . '">';
        if ($interactive) {
            echo '<input type="radio" name="variant_id" value="' . (int)$v['variant_id'] . '"'
               . ' data-price="' . e((string)(int)$v['display_price']) . '" data-stock="' . $stock . '" data-label="' . e($label) . '"'
               . ($out ? ' disabled' : ' required') . '>';
        }
        echo '<span class="size-face"><b>' . e($v['size']) . '</b><small>' . e($sub) . '</small></span></label>';
    }
}
?>
<main class="page-wrap wide">
  <div class="breadcrumbs"><a href="index.php?page=home">Trang chủ</a> / <a href="index.php?page=catalog">Sản phẩm</a> / <?php echo e($product['name']); ?></div>

  <div class="pd-layout">
    <div class="pd-gallery">
      <img id="pdMain" class="pd-main" src="<?php echo e(img_url($images[0] ?? '')); ?>"
           alt="<?php echo e($product['name']); ?>" onerror="this.onerror=null; this.src='public/assets/imgs/placeholder.svg';">
      <?php if (count($images) > 1): ?>
        <div class="pd-thumbs">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" class="pd-thumb-btn <?php echo $i === 0 ? 'is-active' : ''; ?>" data-src="<?php echo e(img_url($img)); ?>" aria-label="Xem ảnh <?php echo $i + 1; ?>">
              <img class="pd-thumb" src="<?php echo e(img_url($img)); ?>" alt="" onerror="this.parentNode.remove()">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="pd-info">
      <small class="muted"><?php echo e($product['category_name']); ?> · <?php echo e(GENDERS[$product['gender']] ?? ''); ?><?php echo !empty($product['brand_name']) ? ' · ' . e($product['brand_name']) : ''; ?></small>
      <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:6px;">
        <h1 style="margin:0;"><?php echo e($product['name']); ?></h1>
        <?php if ($isProductSale || ($product['badge'] ?? '') === 'sale'): ?>
          <span class="badge-tag badge-tag-sale">Sale <?php echo $productDiscountPercent > 0 ? "-{$productDiscountPercent}%" : ''; ?></span>
        <?php endif; ?>
        <?php if (($product['badge'] ?? '') === 'new'): ?>
          <span class="badge-tag badge-tag-new">MỚI</span>
        <?php elseif (($product['badge'] ?? '') === 'hot'): ?>
          <span class="badge-tag badge-tag-hot">HOT</span>
        <?php endif; ?>
      </div>

      <div class="pd-price-row" style="display:flex;align-items:baseline;gap:12px;margin:12px 0;">
        <span class="pd-price" id="pdPrice" style="color:<?php echo $isProductSale ? '#e53935' : 'inherit'; ?>;font-size:26px;font-weight:800;margin:0;">
          <?php echo e(format_vnd($isProductSale ? $salePrice : $basePrice)); ?>
        </span>
        <?php if ($isProductSale): ?>
          <del class="pd-price-original" id="pdPriceOriginal" style="font-size:17px;opacity:0.6;color:#888;">
            <?php echo e(format_vnd($basePrice)); ?>
          </del>
          <span class="badge-tag badge-tag-sale" style="font-size:12px;padding:2px 8px;">-<?php echo $productDiscountPercent; ?>%</span>
        <?php endif; ?>
      </div>
      <div class="muted">
        <?php echo $avg !== null ? '★ ' . number_format($avg, 1) . ' (' . count($reviews) . ' đánh giá)' : 'Chưa có đánh giá'; ?>
      </div>
      <p class="pd-desc"><?php echo nl2br(e($product['description'] ?? '')); ?></p>

      <?php if (!$variants): ?>
        <div class="flash flash-error">Sản phẩm chưa có size để bán.</div>

      <?php elseif (!is_logged_in()): ?>
        <!-- Khách chưa đăng nhập: xem được size/màu/tồn kho, cần đăng nhập để mua -->
        <div class="size-picker">
          <div class="size-title">Size &amp; màu có sẵn</div>
          <?php foreach ($byColor as $color => $list): ?>
            <div class="color-group">
              <div class="color-name">Màu: <?php echo e($color); ?></div>
              <div class="size-grid">
                <?php foreach ($list as $v) pd_size_pill($v, false); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <a class="btn btn-dark" href="index.php?page=login&return=<?php echo urlencode($self); ?>">Đăng nhập để chọn size và mua hàng</a>

      <?php elseif (!$in_stock): ?>
        <div class="size-picker">
          <?php foreach ($byColor as $color => $list): ?>
            <div class="color-group"><div class="color-name">Màu: <?php echo e($color); ?></div>
              <div class="size-grid"><?php foreach ($list as $v) pd_size_pill($v, false); ?></div></div>
          <?php endforeach; ?>
        </div>
        <div class="flash flash-error">Sản phẩm tạm hết hàng.</div>

      <?php else: ?>
        <form method="post" action="index.php" class="form-stack" id="buyForm">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="cart_add">
          <input type="hidden" name="return" value="<?php echo e($self); ?>">

          <div class="size-picker">
            <div class="size-title">Chọn size</div>
            <?php foreach ($byColor as $color => $list): ?>
              <div class="color-group">
                <div class="color-name">Màu: <?php echo e($color); ?></div>
                <div class="size-grid">
                  <?php foreach ($list as $v) pd_size_pill($v, true); ?>
                </div>
              </div>
            <?php endforeach; ?>
            <p id="pickInfo" class="pick-info">Hãy chọn một size để xem giá và số lượng còn lại.</p>
          </div>

          <label>Số lượng
            <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?php echo (int)MAX_QTY_PER_ITEM; ?>" required class="qty-input">
          </label>

          <div class="btn-row">
            <button type="submit" class="btn btn-dark">Thêm vào giỏ</button>
            <button type="submit" name="buy_now" value="1" class="btn btn-light">Mua ngay</button>
          </div>
        </form>
      <?php endif; ?>

      <form method="post" action="index.php" class="fav-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="favorite_toggle">
        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
        <input type="hidden" name="return" value="<?php echo e($self); ?>">
        <button type="submit" class="fav-btn <?php echo $fav ? 'is-fav' : ''; ?>">
          <i class="<?php echo $fav ? 'fa-solid' : 'fa-regular'; ?> fa-heart"></i> <?php echo $fav ? 'Đã yêu thích' : 'Thêm vào yêu thích'; ?>
        </button>
      </form>
    </div>
  </div>

  <section class="pd-reviews">
    <h2>Đánh giá từ khách hàng</h2>
    <?php if ($can_review): ?>
      <form method="post" action="index.php" class="form-card form-stack">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="review_save">
        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
        <strong>Bạn đã mua sản phẩm này - hãy chia sẻ cảm nhận</strong>
        <label>Số sao
          <select name="rating" required>
            <option value="5">★★★★★ (5)</option><option value="4">★★★★☆ (4)</option><option value="3">★★★☆☆ (3)</option>
            <option value="2">★★☆☆☆ (2)</option><option value="1">★☆☆☆☆ (1)</option>
          </select>
        </label>
        <label>Nhận xét (không bắt buộc) <textarea name="comment" rows="3" maxlength="1000"></textarea></label>
        <button type="submit" class="btn btn-dark">Gửi đánh giá</button>
      </form>
    <?php endif; ?>
    <?php if (!$reviews): ?>
      <p class="muted">Chưa có đánh giá nào.</p>
    <?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <article class="review-item">
        <strong><?php echo e($r['name']); ?></strong>
        <span class="stars"><?php echo str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']); ?></span>
        <span class="muted"><?php echo e(date('d/m/Y', strtotime($r['created_at']))); ?></span>
        <p><?php echo e($r['comment'] ?? ''); ?></p>
      </article>
    <?php endforeach; ?>
  </section>

  <?php render_recommend_section('Có thể bạn cũng thích', $rec); ?>
</main>
<script>
  (function () {
    // Bấm ảnh nhỏ để xem ảnh lớn
    var main = document.getElementById('pdMain');
    document.querySelectorAll('.pd-thumb-btn').forEach(function (b) {
      b.addEventListener('click', function () {
        main.src = b.getAttribute('data-src');
        document.querySelectorAll('.pd-thumb-btn').forEach(function (x) { x.classList.remove('is-active'); });
        b.classList.add('is-active');
      });
    });

    // Chọn size: cập nhật giá, thông tin tồn kho, giới hạn số lượng
    var form = document.getElementById('buyForm');
    if (!form) return;
    var price = document.getElementById('pdPrice'), info = document.getElementById('pickInfo'), qty = document.getElementById('qtyInput');
    var maxQty = <?php echo (int)MAX_QTY_PER_ITEM; ?>;
    form.querySelectorAll('input[name="variant_id"]').forEach(function (r) {
      r.addEventListener('change', function () {
        var p = parseInt(r.getAttribute('data-price'), 10), st = parseInt(r.getAttribute('data-stock'), 10);
        price.textContent = p.toLocaleString('vi-VN') + ' ₫';
        info.textContent = r.getAttribute('data-label') + ' — còn ' + st + ' sản phẩm';
        qty.max = Math.max(1, Math.min(maxQty, st));
        if (parseInt(qty.value, 10) > parseInt(qty.max, 10)) qty.value = qty.max;
      });
    });
  })();
</script>
