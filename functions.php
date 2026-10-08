<?php
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function url($path=''){global $config; return rtrim($config['app']['base_url'],'/').'/'.ltrim($path,'/');}
function redirect($path){header('Location: '.(preg_match('#^https?://#',$path)?$path:url($path)));exit;}
function csrf_token(){if(empty($_SESSION['_csrf']))$_SESSION['_csrf']=bin2hex(random_bytes(32));return $_SESSION['_csrf'];}
function verify_csrf(){if($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['_csrf']??'',$_POST['_csrf']??'')){http_response_code(419);die('درخواست نامعتبر است.');}}
function flash($key,$msg=null){if($msg!==null){$_SESSION['_flash'][$key]=$msg;return;} $v=$_SESSION['_flash'][$key]??null; unset($_SESSION['_flash'][$key]); return $v;}
function user(){global $pdo;if(empty($_SESSION['uid']))return null; static $u=null;if($u!==null)return $u;$s=$pdo->prepare('SELECT * FROM users WHERE id=?');$s->execute([$_SESSION['uid']]);$u=$s->fetch();return $u?:null;}
function auth(){if(!user())redirect('login.php');}
function admin(){return user() && in_array(user()['role'],['admin','superadmin'],true);}
function admin_only(){if(!admin()){http_response_code(403);die('دسترسی مجاز نیست.');}}
function money($n){return number_format((int)$n).' '.e($GLOBALS['config']['app']['currency']);}
function setting($key,$default=''){global $pdo;static $cache=[];if(array_key_exists($key,$cache))return $cache[$key];$s=$pdo->prepare('SELECT value FROM settings WHERE `key`=?');$s->execute([$key]);return $cache[$key]=$s->fetchColumn() ?: $default;}
function cart_items(){global $pdo;$cart=$_SESSION['cart']??[];if(!$cart)return [];$ids=array_keys($cart);$in=implode(',',array_fill(0,count($ids),'?'));$s=$pdo->prepare("SELECT * FROM products WHERE id IN ($in) AND active=1");$s->execute($ids);$rows=[];foreach($s as $p){$q=max(0,(int)($cart[$p['id']]??0));if($q) {$p['qty']=$q;$p['line']=$q*$p['price'];$rows[]=$p;}}return $rows;}
function cart_count(){return array_sum($_SESSION['cart']??[]);}
function cart_total(){return array_sum(array_column(cart_items(),'line'));}
function add_cart($id,$qty=1){global $pdo;$s=$pdo->prepare('SELECT id,stock,active FROM products WHERE id=?');$s->execute([$id]);$p=$s->fetch();if(!$p||!$p['active'])return false;$old=(int)($_SESSION['cart'][$id]??0);$new=min($p['stock'],$old+max(1,(int)$qty));$_SESSION['cart'][$id]=$new;return true;}
function discount_for($code,$subtotal){global $pdo;if(!$code)return [0,null];$s=$pdo->prepare('SELECT * FROM coupons WHERE code=? AND active=1 AND (expires_at IS NULL OR expires_at>NOW()) AND (usage_limit IS NULL OR used_count<usage_limit)');$s->execute([$code]);$c=$s->fetch();if(!$c||$subtotal<$c['min_amount'])return [0,null];$d=$c['type']==='percent'?min($subtotal,round($subtotal*$c['value']/100)):$c['value'];return [$d,$c];}
