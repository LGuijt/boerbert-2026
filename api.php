<?php
// ===== CONFIG (aanpassen!) =====
const BERT_MAIL  = 'bert@example.com';      // e-mailadres van Bert
const FROM_MAIL  = 'camping@example.com';   // afzender (liefst een adres op jullie eigen domein)
const ADMIN_KEY  = 'verander-mij';          // wachtwoord beheerscherm
const SCANNER_KEY= 'verander-mij-ook';      // sleutel voor de pasjeslezers
const LAT = 52.09; const LON = 5.12;        // locatie camping (voor zonsondergang)
// ===============================
date_default_timezone_set('Europe/Amsterdam');
header('Content-Type: application/json');
$CAP = ['douche'=>12,'wc'=>14,'inv'=>1,'stal'=>8,'zwem'=>0,'speel'=>0,'winkel'=>0,'kantine'=>0]; // 0 = geen limiet
$NAME = ['douche'=>'Douches','wc'=>'Toiletten','inv'=>'Invalidetoilet','stal'=>'Melkstal','zwem'=>'Buitenzwembad','speel'=>'Speelweide','winkel'=>'Winkeltje','kantine'=>'Kantine'];
$dir = __DIR__.'/data'; if(!is_dir($dir)){mkdir($dir,0750);file_put_contents("$dir/.htaccess","Require all denied\n");}
$fh = fopen("$dir/state.json",'c+'); flock($fh,LOCK_EX);
$raw = stream_get_contents($fh);
$S = $raw ? json_decode($raw,true) : [];
$S += ['in'=>[],'vis'=>[],'def'=>[],'mach'=>[1,1,1,1,1,1],'open'=>['stal'=>1,'winkel'=>1,'kantine'=>1],'meld'=>[],'hok'=>[],'clean'=>[],'melk'=>['dag'=>'','tijd'=>''],'rl'=>[]];

$key = $_SERVER['HTTP_X_KEY'] ?? '';
$admin = hash_equals(ADMIN_KEY,$key);
$scanner = $admin || hash_equals(SCANNER_KEY,$key);
$sun = date_sun_info(time(),LAT,LON)['sunset'] ?? strtotime('20:30');
$poolOpen = time() < $sun;
if(!$poolOpen) $S['in']['zwem'] = []; // zon onder: zwembad leeg

function out($d,$c=200){ http_response_code($c); echo json_encode($d); exit; }
function need($ok){ if(!$ok) out(['error'=>'Geen toegang'],401); }
function label($k){ global $NAME; return $k[0]=='m' && strlen($k)==2 ? 'Wasmachine '.($k[1]+1) : ($NAME[$k] ?? $k); }
function save(){ global $fh,$S; ftruncate($fh,0); rewind($fh); fwrite($fh,json_encode($S)); }

$a = $_GET['a'] ?? 'state';
$b = json_decode(file_get_contents('php://input'),true) ?: [];

if($a=='scan'){
  need($scanner);
  $f=$b['f']??''; $card=trim($b['card']??''); if(!isset($CAP[$f])||$card==='') out(['error'=>'Ongeldig'],400);
  $in=&$S['in'][$f]; $in=$in??[];
  if(($b['dir']??'')=='out'){ $in=array_values(array_diff($in,[$card])); }
  else {
    if(!empty($S['def'][$f])) out(['error'=>'Defect'],409);
    if(isset($S['open'][$f]) && !$S['open'][$f]) out(['error'=>'Gesloten'],409);
    if($f=='zwem' && !$poolOpen) out(['error'=>'Gesloten (zon onder)'],409);
    if(!in_array($card,$in)){
      if($CAP[$f] && count($in)>=$CAP[$f]) out(['error'=>'Vol'],409); // wie het eerst komt...
      $in[]=$card; $S['vis'][$f]=($S['vis'][$f]??0)+1;
    }
  }
}
elseif($a=='defect' || $a=='report'){
  $k=$b['key']??'';
  if($a=='report'){ // gasten: alleen melden, max 5 per uur per IP
    if(!(isset($CAP[$k])||preg_match('/^m[0-5]$/',$k))) out(['error'=>'Ongeldig'],400);
    $ip=md5($_SERVER['REMOTE_ADDR']??'');
    $S['rl'][$ip]=array_values(array_filter($S['rl'][$ip]??[],function($t){return $t>time()-3600;}));
    if(count($S['rl'][$ip])>=5) out(['error'=>'Te veel meldingen, probeer het later opnieuw'],429);
    $S['rl'][$ip][]=time(); $b['on']=true;
  } else need($admin);
  $on=!empty($b['on']);
  if($k[0]=='m' && strlen($k)==2) $S['mach'][(int)$k[1]] = $on?0:1; else $S['def'][$k]=$on;
  if($on){
    $dup=false; foreach($S['meld'] as $m) if($m['k']==$k && !$m['done']) $dup=true;
    if(!$dup){
      $by=$a=='report'?'gast':'beheer';
      $S['meld'][]=['k'=>$k,'w'=>label($k),'t'=>date('H:i'),'by'=>$by,'done'=>0];
      @mail(BERT_MAIL,'Defect: '.label($k),label($k)." is defect gemeld door ".$by." om ".date('H:i')." (".date('d-m-Y').").\nGasten zien nu dat het niet werkt.","From: ".FROM_MAIL);
    }
  } else foreach($S['meld'] as &$m) if($m['k']==$k) $m['done']=1;
  unset($m);
  $S['meld']=array_slice($S['meld'],-50);
}
elseif($a=='open'){ need($admin); $S['open'][$b['f']]=!empty($b['on']); }
elseif($a=='cleaned'){ need($admin); $S['vis'][$b['f']]=0; $S['clean'][$b['f']]=date('H:i'); }
elseif($a=='hok'){ need($admin); $S['hok'][]=['w'=>substr($b['w'],0,40),'m'=>substr($b['m'],0,40),'a'=>$b['a']=='uit'?'uit':'terug','t'=>date('H:i')]; $S['hok']=array_slice($S['hok'],-100); }
elseif($a=='melk'){ need($admin); $S['melk']=['dag'=>substr($b['dag'],0,20),'tijd'=>substr($b['tijd'],0,10)]; }
save(); flock($fh,LOCK_UN);

$pub = ['n'=>array_map('count',$S['in']),'def'=>$S['def'],'mach'=>$S['mach'],'open'=>$S['open'],'melk'=>$S['melk'],'pool'=>$poolOpen,'sunset'=>date('H:i',$sun)];
if($admin) $pub += ['vis'=>$S['vis'],'meld'=>$S['meld'],'hok'=>$S['hok'],'clean'=>$S['clean'],'admin'=>true];
out($pub);
