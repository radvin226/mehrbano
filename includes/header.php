<?php
$base ??= '';
$user = current_user();
$bf = bf_percent();
$endMs = (int)strtotime((string)setting('bf_ends')) * 1000;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e((isset($title) ? $title.' | ' : '').SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body<?= $base ? ' class="admin"' : '' ?>>
<header class="topbar">
  <a class="logo" href="<?= $base ?>index.php"><?= e(SITE_NAME) ?></a>
  <nav>
    <a href="<?= $base ?>index.php">خانه</a>
    <?php foreach (['زنانه', 'مردانه', 'بچگانه'] as $c): ?>
      <a href="<?= $base ?>index.php?cat=<?= urlencode($c) ?>"><?= $c ?></a>
    <?php endforeach; ?>
    <a class="cart" href="<?= $base ?>cart.php">سبد خرید <span><?= array_sum(cart()) ?></span></a>
    <?php if ($user): ?>
      <a href="<?= $base ?>account.php">حساب من</a>
      <?php if ($user['is_admin']): ?><a href="<?= $base ?>admin/index.php">پنل ادمین</a><?php endif; ?>
      <a href="<?= $base ?>auth.php?mode=logout">خروج</a>
    <?php else: ?>
      <a href="<?= $base ?>auth.php?mode=login">ورود</a>
      <a class="btn btn-sm" href="<?= $base ?>auth.php?mode=register">ثبت نام</a>
    <?php endif; ?>
  </nav>
</header>
<?php if ($bf): ?>
<div class="bf-banner">
  <span>بلک فرایدی: تخفیف <?= $bf ?>٪ روی همه محصولات</span>
  <?php if ($endMs): ?><span id="countdown" data-end="<?= $endMs ?>"></span><?php endif; ?>
</div>
<?php endif; ?>
<main class="container">
<?php if ($m = flash()): ?><div class="alert"><?= e($m) ?></div><?php endif; ?>
