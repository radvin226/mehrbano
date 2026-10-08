<?php
require 'includes/bootstrap.php';
$title = 'سبد خرید';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][$_POST['remove']]);
    } elseif (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $key => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) unset($_SESSION['cart'][$key]);
            elseif (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key] = min(10, $qty);
        }
    } elseif (isset($_POST['coupon'])) {
        $c = find_coupon($_POST['coupon']);
        if ($c) { $_SESSION['coupon'] = $c['code']; flash('کد تخفیف اعمال شد'); }
        else { unset($_SESSION['coupon']); flash('کد تخفیف معتبر نیست'); }
    }
    redirect('cart.php');
}

$sum = cart_summary();
include 'includes/header.php';
?>
<h1>سبد خرید</h1>
<?php if (!$sum['lines']): ?>
  <p>سبد خرید شما خالی است. <a href="index.php">ادامه خرید</a></p>
<?php else: ?>
<form method="post">
  <?= csrf_field() ?>
  <table class="cart">
    <tr><th>محصول</th><th>سایز / رنگ</th><th>قیمت واحد</th><th>تعداد</th><th>جمع</th><th></th></tr>
    <?php foreach ($sum['lines'] as $l): ?>
    <tr>
      <td><?= e($l['p']['title']) ?> <?php if ($l['up']['percent']): ?><span class="badge">٪<?= $l['up']['percent'] ?></span><?php endif; ?></td>
      <td><?= e($l['size']) ?> / <?= e($l['color']) ?></td>
      <td><?= money($l['up']['final']) ?></td>
      <td><input type="number" name="qty[<?= e($l['key']) ?>]" value="<?= $l['qty'] ?>" min="1" max="10" style="width:90px"></td>
      <td><?= money($l['line']) ?></td>
      <td><button class="link danger" name="remove" value="<?= e($l['key']) ?>">حذف</button></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <button class="btn" style="margin-top:14px">بروزرسانی سبد</button>
</form>

<form method="post" class="inline" style="margin-top:18px">
  <?= csrf_field() ?>
  <input name="coupon" placeholder="کد تخفیف" value="<?= e($_SESSION['coupon'] ?? '') ?>" style="width:220px">
  <button class="btn btn-sm">اعمال کد</button>
</form>

<div class="totals">
  <p>جمع کل: <?= money($sum['subtotal']) ?></p>
  <?php if ($sum['discount']): ?><p>تخفیف کد (<?= $sum['pct'] ?>٪): <?= money($sum['discount']) ?> -</p><?php endif; ?>
  <p class="total">قابل پرداخت: <?= money($sum['total']) ?></p>
  <a class="btn" href="checkout.php">ثبت سفارش</a>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
