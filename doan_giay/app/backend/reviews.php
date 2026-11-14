<?php
// Viết đánh giá sản phẩm: chỉ người đã NHẬN HÀNG (đơn "Đã giao") mới được đánh giá, mỗi dòng đơn hàng đánh giá đúng 1 lần.
if (!defined('SECURE_CHECK')) {
    exit('Access denied');
}

function action_review_save(): void
{
    require_login();
    $uid       = (int)$_SESSION['user']['user_id'];
    $productId = post_int('product_id');
    $rating    = post_int('rating');
    $comment   = post_str('comment');
    $back      = 'index.php?page=product&id=' . $productId;

    if ($rating < 1 || $rating > 5) {
        flash_set('error', 'Vui lòng chọn số sao từ 1 đến 5.');
        redirect($back);
    }
    if (mb_strlen($comment) > 1000) {
        flash_set('error', 'Nhận xét tối đa 1000 ký tự.');
        redirect($back);
    }
    $detailId = reviewable_detail_id($uid, $productId);
    if ($detailId === null) {
        flash_set('error', 'Bạn chỉ có thể đánh giá sản phẩm đã mua và nhận hàng (và mỗi lần mua chỉ đánh giá một lần).');
        redirect($back);
    }
    try {
        db()->prepare("INSERT INTO review (user_id, product_id, order_detail_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, 'visible')")
            ->execute([$uid, $productId, $detailId, $rating, $comment === '' ? null : $comment]);
        flash_set('success', 'Cảm ơn bạn đã đánh giá!');
    } catch (PDOException $e) {
        flash_set('error', 'Bạn đã đánh giá lần mua này rồi.');
    }
    redirect($back);
}
