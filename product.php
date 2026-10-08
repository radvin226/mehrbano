<?php
require 'includes/bootstrap.php';
$st = db()->prepare('SELECT * FROM products WHERE id=? AND is_active=1');
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
if (!$p) { http_response_code(404); exit('محصول پیدا نشد'); }
$sizes = array_map('trim', explode(',', $p['sizes']));
$colors = array_map('trim', explode(',', $p['colors']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $size = $_POST['size'] ?? ''; $color = $_POST['color'] ?? '';
    $qty = max(1, min(10, (int)($_POST['qty'] ?? 1)));
    if (!in_array($size, $sizes, true) || !in_array($color, $colors, true)) {
        flash('سایز یا رنگ انتخاب‌شده معتبر نیست');
    } elseif ((int)$p['stock'] < 1) {
        flash('این محصول فعلا ناموجود است');
    } else {
        $key = "{$p['id']}|$size|$color";
        $_SESSION['cart'][$key] = min((int)$p['stock'], 10, (cart()[$key] ?? 0) + $qty);
        flash('به سبد خرید اضافه شد');
        redirect('cart.php');
    }
    redirect("product.php?id={$p['id']}");
}

$title = $p['title'];
$pr = unit_price($p);
include 'includes/header.php';
?>
<div class="product">
  <div class="thumb big">
    <?php if ($p['image']): ?><img src="<?= e($p['image']) ?>" alt=""><?php else: ?><?= e(mb_substr($p['title'], 0, 1)) ?><?php endif; ?>
  </div>
  <div>
    <p class="note"><?= e($p['category']) ?></p>
    <h1><?= e($p['title']) ?></h1>
    <?php if ($pr['percent']): ?><p><del><?= money((int)$p['price']) ?></del> <span class="badge">٪<?= $pr['percent'] ?> تخفیف</span></p><?php endif; ?>
    <p class="price"><?= money($pr['final']) ?></p>
    <p><?= nl2br(e($p['description'])) ?></p>
    <?php if ((int)$p['stock'] > 0): ?>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <label>سایز
        <select name="size"><?php foreach ($sizes as $s): ?><option><?= e($s) ?></option><?php endforeach; ?></select>
      </label>
      <label>رنگ / مدل
        <select name="color"><?php foreach ($colors as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select>
      </label>
      <label>تعداد <input type="number" name="qty" value="1" min="1" max="10"></label>
      <button class="btn">افزودن به سبد خرید</button>
    </form>
    <?php else: ?><p class="out">ناموجود</p><?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
