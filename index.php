<?php
require __DIR__.'/lib.php';
session_start();
state_open();
$role = $_SESSION['role'] ?? ''; // '' | 'staff' | 'admin'

function go($v=''){ header('Location: index.php'.($v ? "?v=$v" : '')); exit; }

/* ---------- formulieren verwerken (Post/Redirect/Get) ---------- */
if($_SERVER['REQUEST_METHOD']=='POST'){
  if(isset($_POST['pw'])){
    $nieuw = hash_equals(ADMIN_KEY,$_POST['pw']) ? 'admin' : (hash_equals(STAFF_KEY,$_POST['pw']) ? 'staff' : '');
    if($nieuw){ session_regenerate_id(true); $_SESSION['role']=$nieuw; }
    else $_SESSION['flash']='Onjuist wachtwoord';
    go('b');
  }
  [$a,$p1,$p2] = array_pad(explode(':',$_POST['x'] ?? ''),3,'');
  if($a=='logout'){ unset($_SESSION['role']); go(); }
  if(isset($_POST['card'])) $_SESSION['card']=$_POST['card'];
  $hokKnop = $p2!=='' ? (array) json_decode(base64_decode($p2),true) : null; // [wie, middel] van een Teruggebracht-knop
  $b = match($a){
    'scan'    => ['f'=>$p1,'dir'=>$p2,'card'=>$_POST['card'] ?? ''],
    'defect'  => ['key'=>$p1,'on'=>$p2=='on'],
    'report'  => ['key'=>$p1],
    'open'    => ['f'=>$p1,'on'=>$p2=='on'],
    'cleaned' => ['f'=>$p1],
    // hok:uit / hok:terug lezen de invulvelden; hok:terug:<b64> komt van de knop bij een openstaand item
    'hok'     => ['a'=>$p1,'w'=>$hokKnop[0] ?? $_POST['hw'] ?? '','m'=>$hokKnop[1] ?? $_POST['hm'] ?? ''],
    default   => null,
  };
  if($b!==null){
    $err = act($a,$b,$role ?: 'guest');
    if($err) $_SESSION['flash']=$err[0];
    else { save(); if($a=='report') $_SESSION['flash']='Bedankt! Het personeel is gewaarschuwd.'; }
  }
  go($a=='report' ? '' : 'b');
}

/* ---------- pagina ---------- */
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$melden = $_GET['melden'] ?? '';
if($melden!=='' && !valid_key($melden)) $melden='';
$view = ($_GET['v'] ?? '')=='b' ? 'b' : 'g';
$open = array_filter($S['meld'],fn($m)=>!$m['done']);
$dag  = ['zondag','maandag','dinsdag','woensdag','donderdag','vrijdag','zaterdag'][date('w')];
?><!doctype html>
<html lang="nl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php if($view=='g' && !$melden): ?><meta http-equiv="refresh" content="10"><?php endif ?>
<title>Boerencamping – Faciliteiten</title>
<link rel="stylesheet" href="style.css">
</head><body>
<header><h1>🌾 Boerencamping – Faciliteiten</h1><p><?=$dag?> <?=date('H:i')?></p></header>
<nav>
  <a href="index.php" class="<?=$view=='g'?'on':''?>">Gasten</a>
  <a href="?v=b" class="<?=$view=='b'?'on':''?>">Beheer<?=$role && $open ? ' 🔔'.count($open) : ''?></a>
  <?php if($role): ?><form method="post"><button name="x" value="logout">Uitloggen</button></form><?php endif ?>
</nav>
<main>
<?php if($flash): ?><div class="alert"><?=h($flash)?></div><?php endif ?>
<?php
if($melden!==''){
  include __DIR__.'/views/melden.php';
} elseif($view=='b' && !$role){
  include __DIR__.'/views/login.php';
} else {
  include __DIR__.'/views/'.($view=='b' ? ($role=='admin' ? 'admin' : 'staff') : 'guest').'.php';
}
?>
</main>
</body></html>