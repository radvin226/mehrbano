<?php
require '../includes/bootstrap.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    switch ($_POST['act'] ?? '') {
        case 'save_product':
            $vals = [
                trim($_POST['title'] ?? ''), $_POST['category'] ?? 'زنانه', trim($_POST['description'] ?? ''),
                (int)($_POST['price'] ?? 0), min(90, max(0, (int)($_POST['discount_percent'] ?? 0))),
                trim($_POST['sizes'] ?? ''), trim($_POST['colors'] ?? ''), max(0, (int)($_POST['stock'] ?? 0)),
                trim($_POST['image'] ?? '') ?: null, isset($_POST['is_active']) ? 1 : 0,
            ];
            if ($id) {
                $pdo->prepare('UPDATE products SET title=?,category=?,description=?,price=?,discount_percent=?,sizes=?,colors=?,stock=?,image=?,is_active=? WHERE id=?')
                    ->execute([...$vals, $id]);
            } else {
                $pdo->prepare('INSERT INTO products (title,category,description,price,discount_percent,sizes,colors,stock,image,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute($vals);
            }
            flash('محصول ذخیره شد'); break;
        case 'delete_product':
            $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
            flash('محصول حذف شد'); break;
        case 'order_status':
            $status = $_POST['status'] ?? '';
            if (array_key_exists($status, ORDER_STATUSES)) {
                $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $id]);
            }
            flash('وضعیت سفارش بروزرسانی شد'); break;
        case 'settings':
            $set = $pdo->prepare('REPLACE INTO settings (k,v) VALUES (?,?)');
            $set->execute(['bf_active', isset($_POST['bf_active']) ? '1' : '0']);
            $set->execute(['bf_percent', (string)min(90, max(0, (int)($_POST['bf_percent'] ?? 0)))]);
            $set->execute(['bf_ends', ($_POST['bf_ends'] ?? '') ?: null]);
            flash('تنظیمات بلک فرایدی ذخیره شد'); break;
        case 'add_coupon':
            $pdo->prepare('INSERT IGNORE INTO coupons (code,percent) VALUES (?,?)')
                ->execute([strtoupper(trim($_POST['code'] ?? '')), min(90, max(1, (int)($_POST['percent'] ?? 0)))]);
            flash('کد تخفیف ثبت شد'); break;
        case 'delete_coupon':
            $pdo->prepare('DELETE FROM coupons WHERE id=?')->execute([$id]);
            flash('کد تخفیف حذف شد'); break;
    }
    redirect('index.php');
}

$editing = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $editing = $st->fetch() ?: null;
}
$ed = $editing ?? [];
$stats = $pdo->query("SELECT COUNT(*) AS c, COALESCE(SUM(total),0) AS s FROM orders WHERE status<>'cancelled'")->fetch();
$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
$orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 100')->fetchAll();
$coupons = $pdo->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
$bfOn = setting('bf_active') === '1';
$bfEnds = substr((string)setting('bf_ends'), 0, 16);

$base = '../';
$title = 'پنل ادمین';
include '../includes/header.php';
?>
<h1>پنل مدیریت</h1>
<section class="stats">
  <div>تعداد سفارش‌ها: <b><?= (int)$stats['c'] ?></b></div>
  <div>مجموع فروش: <b><?= money((int)$stats['s']) ?></b></div>
  <div>بلک فرایدی: <b><?= $bfOn ? 'فعال' : 'غیرفعال' ?></b></div>
</section>

<section class="box">
  <h2>تنظیمات بلک فرایدی</h2>
  <form method="post" class="form inline">
    <?= csrf_field() ?><input type="hidden" name="act" value="settings">
    <label><input type="checkbox" name="bf_active" <?= $bfOn ? 'checked' : '' ?> style="width:auto"> فعال باشد</label>
    <label>درصد تخفیف <input type="number" name="bf_percent" min="0" max="90" value="<?= (int)setting('bf_percent') ?>"></label>
    <label>پایان کمپین <input type="datetime-local" name="bf_ends" value="<?= e(str_replace(' ', 'T', $bfEnds)) ?>"></label>
    <button class="btn">ذخیره</button>
  </form>
</section>

<section class="box">
  <h2><?= $editing ? 'ویرایش محصول' : 'افزودن محصول' ?></h2>
  <form method="post" class="form grid2">
    <?= csrf_field() ?><input type="hidden" name="act" value="save_product">
    <input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
    <label>عنوان <input name="title" required value="<?= e($ed['title'] ?? '') ?>"></label>
    <label>دسته
      <select name="category"><?php foreach (['زنانه', 'مردانه', 'بچگانه'] as $c): ?>
        <option <?= ($ed['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
      <?php endforeach; ?></select>
    </label>
    <label>قیمت (تومان) <input type="number" name="price" required value="<?= e($ed['price'] ?? '') ?>"></label>
    <label>تخفیف اختصاصی محصول (٪) <input type="number" name="discount_percent" min="0" max="90" value="<?= e($ed['discount_percent'] ?? 0) ?>"></label>
    <label>سایزها (با ویرگول) <input name="sizes" value="<?= e($ed['sizes'] ?? 'S,M,L,XL') ?>"></label>
    <label>رنگ / مدل‌ها (با ویرگول) <input name="colors" value="<?= e($ed['colors'] ?? 'مشکی,سفید') ?>"></label>
    <label>موجودی <input type="number" name="stock" value="<?= e($ed['stock'] ?? 10) ?>"></label>
    <label>آدرس تصویر (اختیاری) <input name="image" value="<?= e($ed['image'] ?? '') ?>"></label>
    <label class="full">توضیحات <textarea name="description" rows="3"><?= e($ed['description'] ?? '') ?></textarea></label>
    <label><input type="checkbox" name="is_active" style="width:auto" <?= !isset($ed['is_active']) || $ed['is_active'] ? 'checked' : '' ?>> نمایش در سایت</label>
    <button class="btn">ذخیره محصول</button>
  </form>

  <table class="cart" style="margin-top:24px">
    <tr><th>عنوان</th><th>دسته</th><th>قیمت</th><th>تخفیف</th><th>موجودی</th><th></th></tr>
    <?php foreach ($products as $p): ?>
    <tr>
      <td><?= e($p['title']) ?></td>
      <td><?= e($p['category']) ?></td>
      <td><?= money((int)$p['price']) ?></td>
      <td><?= (int)$p['discount_percent'] ?>٪</td>
      <td><?= (int)$p['stock'] ?></td>
      <td>
        <a href="index.php?edit=<?= $p['id'] ?>">ویرایش</a>
        <form method="post" class="inline" onsubmit="return confirm('این محصول حذف شود؟')">
          <?= csrf_field() ?><input type="hidden" name="act" value="delete_product"><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="link danger">حذف</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>

<section class="box">
  <h2>سفارش‌ها</h2>
  <table class="cart">
    <tr><th>شماره</th><th>مشتری</th><th>تاریخ</th><th>مبلغ</th><th>وضعیت</th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= $o['id'] ?></td>
      <td><?= e($o['full_name']) ?><br><small><?= e($o['phone']) ?> — <?= e($o['address']) ?></small></td>
      <td><?= e($o['created_at']) ?></td>
      <td><?= money((int)$o['total']) ?><?= $o['coupon'] ? ' ('.e($o['coupon']).')' : '' ?></td>
      <td>
        <form method="post" class="inline">
          <?= csrf_field() ?><input type="hidden" name="act" value="order_status"><input type="hidden" name="id" value="<?= $o['id'] ?>">
          <select name="status" onchange="this.form.submit()">
            <?php foreach (ORDER_STATUSES as $k => $label): ?>
              <option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>

<section class="box">
  <h2>کدهای تخفیف</h2>
  <form method="post" class="form inline">
    <?= csrf_field() ?><input type="hidden" name="act" value="add_coupon">
    <input name="code" placeholder="مثلا BF30" required>
    <input type="number" name="percent" min="1" max="90" placeholder="٪" required>
    <button class="btn">افزودن</button>
  </form>
  <ul class="coupons">
    <?php foreach ($coupons as $c): ?>
    <li>
      <b><?= e($c['code']) ?></b> — <?= (int)$c['percent'] ?>٪
      <form method="post" class="inline">
        <?= csrf_field() ?><input type="hidden" name="act" value="delete_coupon"><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <button class="link danger">حذف</button>
      </form>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php include '../includes/footer.php'; ?>
