<?php
require 'includes/bootstrap.php';
$title = 'حساب کاربری';
$user = require_login();
$st = db()->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC');
$st->execute([$user['id']]);
$orders = $st->fetchAll();
include 'includes/header.php';
?>
<h1>سلام، <?= e($user['name']) ?></h1>
<?php if ($orders): ?>
<table class="cart">
  <tr><th>شماره</th><th>تاریخ</th><th>مبلغ</th><th>وضعیت</th></tr>
  <?php foreach ($orders as $o): ?>
  <tr>
    <td>#<?= $o['id'] ?></td>
    <td><?= e($o['created_at']) ?></td>
    <td><?= money((int)$o['total']) ?></td>
    <td><?= ORDER_STATUSES[$o['status']] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?>
  <p>هنوز سفارشی ثبت نکرده‌اید. <a href="index.php">رفتن به فروشگاه</a></p>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
