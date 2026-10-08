<?php
const DB_HOST = 'localhost';
const DB_NAME = 'pooshak_mehraboo';
const DB_USER = 'root';
const DB_PASS = '';
const SITE_NAME = 'پوشاک مهربانو';
const ORDER_STATUSES = [
    'pending' => 'در انتظار', 'processing' => 'در حال پردازش', 'shipped' => 'ارسال شده',
    'delivered' => 'تحویل داده شده', 'cancelled' => 'لغو شده',
];
session_start();

function db(): PDO {
    static $pdo;
    return $pdo ??= new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money(int $n): string { return number_format($n).' تومان'; }
function redirect(string $url): void { header("Location: $url"); exit; }
function flash(?string $set = null): ?string {
    if ($set !== null) { $_SESSION['flash'] = $set; return null; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}
function csrf_field(): string {
    $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    return '<input type="hidden" name="csrf" value="'.$_SESSION['csrf'].'">';
}
function check_csrf(): void {
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) exit('درخواست نامعتبر است');
}
function current_user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id,name,email,is_admin FROM users WHERE id=?');
            $s->execute([$_SESSION['uid']]); $u = $s->fetch() ?: null;
        }
    }
    return $u;
}
function require_login(): array {
    $u = current_user();
    if (!$u) { flash('ابتدا وارد حساب کاربری خود شوید'); redirect('auth.php?mode=login'); }
    return $u;
}
function require_admin(): void {
    $u = current_user();
    if (!$u || !$u['is_admin']) { http_response_code(403); exit('دسترسی ندارید'); }
}
function setting(string $k): ?string {
    $s = db()->prepare('SELECT v FROM settings WHERE k=?'); $s->execute([$k]);
    $v = $s->fetchColumn(); return $v === false ? null : $v;
}

/* Discount models:
   1) per-product discount  2) Black Friday campaign (site-wide, with end date)
   The larger of the two applies to a product. Coupons apply to the cart subtotal. */
function bf_percent(): int {
    static $pct = null;
    if ($pct === null) {
        $ends = setting('bf_ends');
        $on = setting('bf_active') === '1' && (!$ends || strtotime($ends) > time());
        $pct = $on ? (int)setting('bf_percent') : 0;
    }
    return $pct;
}
function unit_price(array $p): array {
    $pct = max((int)$p['discount_percent'], bf_percent());
    return ['final' => (int)round($p['price'] * (100 - $pct) / 100), 'percent' => $pct];
}
function find_coupon(?string $code): ?array {
    if (!$code) return null;
    $s = db()->prepare('SELECT * FROM coupons WHERE code=? AND is_active=1');
    $s->execute([strtoupper(trim($code))]); return $s->fetch() ?: null;
}

/* Cart lives in session: key = "productId|size|color" => qty */
function cart(): array { return $_SESSION['cart'] ?? []; }
function cart_summary(): array {
    $cart = cart(); $lines = []; $subtotal = 0;
    if ($cart) {
        $ids = array_values(array_unique(array_map(fn($k) => (int)explode('|', $k)[0], array_keys($cart))));
        $st = db()->prepare('SELECT * FROM products WHERE id IN ('.implode(',', array_fill(0, count($ids), '?')).')');
        $st->execute($ids);
        $byId = [];
        foreach ($st->fetchAll() as $p) $byId[(int)$p['id']] = $p;
        foreach ($cart as $key => $qty) {
            [$pid, $size, $color] = array_pad(explode('|', $key), 3, '');
            if (!isset($byId[(int)$pid])) continue;
            $p = $byId[(int)$pid]; $up = unit_price($p); $line = $up['final'] * $qty; $subtotal += $line;
            $lines[] = compact('key', 'p', 'size', 'color', 'qty', 'up', 'line');
        }
    }
    $coupon = find_coupon($_SESSION['coupon'] ?? null);
    $pct = (int)($coupon['percent'] ?? 0);
    $discount = (int)round($subtotal * $pct / 100);
    return ['lines' => $lines, 'subtotal' => $subtotal, 'coupon' => $coupon['code'] ?? null,
            'pct' => $pct, 'discount' => $discount, 'total' => $subtotal - $discount];
}
