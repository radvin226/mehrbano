<?php
require 'includes/bootstrap.php';
$title = 'ثبت سفارش';
$user = require_login();
$sum = cart_summary();
if (!$sum['lines']) redirect('cart.php');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if (mb_strlen($name) < 3) $errors[] = 'نام و نام خانوادگی را وارد کنید';
    if (!preg_match('/^09\d{9}$/', $phone)) $errors[] = 'شماره موبایل معتبر نیست (مثال: 09123456789)';
    if (mb_strlen($address) < 10) $errors[] = 'آدرس را کامل وارد کنید';
    foreach ($sum['lines'] as $l) {
        if ($l['qty'] > (int)$l['p']['stock']) $errors[] = "موجودی «{$l['p']['title']}» کافی نیست";
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO orders (user_id,full_name,phone,address,subtotal,discount,total,coupon) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$user['id'], $name, $phone, $address, $sum['subtotal'], $sum['discount'], $sum['total'], $sum['coupon']]);
            $orderId = (int)$pdo->lastInsertId();
            $item = $pdo->prepare('INSERT INTO order_items (order_id,product_id,title,size,color,unit_price,qty) VALUES (?,?,?,?,?,?,?)');
            $dec = $pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
            foreach ($sum['lines'] as $l) {
                $dec->execute([$l['qty'], $l['p']['id'], $l['qty']]);
                if ($dec->rowCount() === 0) throw new RuntimeException('stock');
                $item->execute([$orderId, $l['p']['id'], $l['p']['title'], $l['size'], $l['color'], $l['up']['final'], $l['qty']]);
            }
            $pdo->commit();
            $_SESSION['cart'] = [];
            unset($_SESSION['coupon']);
            flash("سفارش #$orderId ثبت شد. به زودی با شما تماس می‌گیریم.");
            redirect('account.php');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errors[] = 'موجودی بعضی محصولات تغییر کرده است؛ لطفا سبد خرید را بررسی کنید';
        }
    }
}

include 'includes/header.php';
?>
<h1>ثبت سفارش</h1>
<?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>
<div class="checkout">
  <form method="post" class="form box">
    <?= csrf_field() ?>
    <label>نام و نام خانوادگی <input name="full_name" required value="<?= e($_POST['full_name'] ?? $user['name']) ?>"></label>
    <label>شماره موبایل <input name="phone" required placeholder="09123456789" value="<?= e($_POST['phone'] ?? '') ?>"></label>
    <label>آدرس کامل <textarea name="address" rows="3" required><?= e($_POST['address'] ?? '') ?></textarea></label>
    <p class="note">پرداخت آنلاین در این نسخه فعال نیست؛ پرداخت هنگام تحویل یا از طریق هماهنگی با فروشگاه انجام می‌شود.</p>
    <button class="btn">ثبت سفارش</button>
  </form>
  <aside class="totals">
    <p>جمع کل: <?= money($sum['subtotal']) ?></p>
    <?php if ($sum['discount']): ?><p>تخفیف کد: <?= money($sum['discount']) ?> -</p><?php endif; ?>
    <p class="total">قابل پرداخت: <?= money($sum['total']) ?></p>
  </aside>
</div>
<?php include 'includes/footer.php'; ?>
