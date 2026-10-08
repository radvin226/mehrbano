<?php
require 'includes/bootstrap.php';
$title = 'خانه';
$cat = $_GET['cat'] ?? '';
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM products WHERE is_active=1'; $args = [];
if ($cat !== '') { $sql .= ' AND category=?'; $args[] = $cat; }
if ($q !== '') { $sql .= ' AND title LIKE ?'; $args[] = "%$q%"; }
$st = db()->prepare($sql.' ORDER BY id DESC'); $st->execute($args);
$products = $st->fetchAll();
include 'includes/header.php';
?>
<section class="hero">
  <h1>لباسی برای هر روز، با مهربانی</h1>
  <p>مجموعه زنانه، مردانه و بچگانه؛ با سایز و رنگ دلخواه خودتان.</p>
  <a class="btn btn-sm" href="#shop">دیدن محصولات</a>
</section>

<form class="search" method="get" id="shop">
  <input name="q" value="<?= e($q) ?>" placeholder="جستجوی محصول">
  <select name="cat">
    <option value="">همه دسته‌ها</option>
    <?php foreach (['زنانه', 'مردانه', 'بچگانه'] as $c): ?>
      <option <?= $cat === $c ? 'selected' : '' ?>><?= $c ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn">جستجو</button>
</form>

<div class="grid">
<?php foreach ($products as $p): $pr = unit_price($p); ?>
  <a class="card" href="product.php?id=<?= $p['id'] ?>">
    <div class="thumb">
      <?php if ($p['image']): ?><img src="<?= e($p['image']) ?>" alt=""><?php else: ?><?= e(mb_substr($p['title'], 0, 1)) ?><?php endif; ?>
    </div>
    <div class="info">
      <h3><?= e($p['title']) ?></h3>
      <?php if ($pr['percent']): ?><span class="badge">٪<?= $pr['percent'] ?></span> <del><?= money((int)$p['price']) ?></del><br><?php endif; ?>
      <span class="price"><?= money($pr['final']) ?></span>
      <?php if ($p['stock'] < 1): ?><div class="out">ناموجود</div><?php endif; ?>
    </div>
  </a>
<?php endforeach; ?>
</div>
<?php if (!$products): ?><p>محصولی پیدا نشد.</p><?php endif; ?>
<?php include 'includes/footer.php'; ?>
