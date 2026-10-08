<?php
require 'includes/bootstrap.php';
$mode = $_GET['mode'] ?? 'login';
if ($mode === 'logout') { $_SESSION = []; session_destroy(); redirect('index.php'); }
$title = $mode === 'register' ? 'ثبت نام' : 'ورود';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    if ($mode === 'register') {
        $name = trim($_POST['name'] ?? '');
        if (mb_strlen($name) < 3) $errors[] = 'نام را وارد کنید';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل معتبر نیست';
        if (strlen($pass) < 6) $errors[] = 'رمز عبور باید حداقل ۶ کاراکتر باشد';
        if (!$errors) {
            $exists = db()->prepare('SELECT 1 FROM users WHERE email=?');
            $exists->execute([$email]);
            if ($exists->fetch()) $errors[] = 'این ایمیل قبلا ثبت شده است';
        }
        if (!$errors) {
            db()->prepare('INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)')
                ->execute([$name, $email, trim($_POST['phone'] ?? ''), password_hash($pass, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)db()->lastInsertId();
            flash('حساب شما ساخته شد. خوش آمدید!');
            redirect('index.php');
        }
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email=?');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            redirect($u['is_admin'] ? 'admin/index.php' : 'index.php');
        }
        $errors[] = 'ایمیل یا رمز عبور اشتباه است';
    }
}

include 'includes/header.php';
?>
<div class="auth box">
  <h1><?= $mode === 'register' ? 'ساخت حساب' : 'ورود به حساب' ?></h1>
  <?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <?php if ($mode === 'register'): ?>
      <label>نام و نام خانوادگی <input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
      <label>شماره موبایل <input name="phone" value="<?= e($_POST['phone'] ?? '') ?>"></label>
    <?php endif; ?>
    <label>ایمیل <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label>
    <label>رمز عبور <input type="password" name="password" required></label>
    <button class="btn"><?= $mode === 'register' ? 'ثبت نام' : 'ورود' ?></button>
  </form>
  <p class="note"><?= $mode === 'register'
      ? '<a href="auth.php?mode=login">حساب دارید؟ وارد شوید</a>'
      : '<a href="auth.php?mode=register">حساب ندارید؟ ثبت نام کنید</a>' ?></p>
</div>
<?php include 'includes/footer.php'; ?>
