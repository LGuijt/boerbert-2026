<?php
//* info: CONFIG 
//todo (aanpassen!)
const BERT_MAIL   = 'bert@example.com';     //* info: e-mailadres van Bert
const FROM_MAIL   = 'camping@example.com';  //* info: afzender (liefst een adres op jullie eigen domein)
const ADMIN_KEY   = 'verander-mij';         //* info wachtwoord beheer (Bert)
const STAFF_KEY   = 'verander-mij-personeel'; //* info: wachtwoord beheer (personeel)
const SCANNER_KEY = 'verander-mij-ook';     //* info: sleutel voor de pasjeslezers
const LAT = 52.09; const LON = 5.12;        //* info: locatie camping (voor zonsondergang)
// ===============================
date_default_timezone_set('Europe/Amsterdam');

//* info: cap = max tegelijk (0/leeg = geen limiet) | u = groep losse units, cap = aantal werkende units
//* info: man = Bert kan open/dicht zetten | sun = dicht na zonsondergang | always = altijd open
const FACILITIES = [
  'douche'  => ['n'=>'Douches',                 'i'=>'🚿', 'u'=>'d'],
  'wc'      => ['n'=>'Toiletten',               'i'=>'🚻', 'u'=>'w'],
  'inv'     => ['n'=>'Invalidetoilet',          'i'=>'♿', 'cap'=>1, 'always'=>1],
  'stal'    => ['n'=>'Melkstal (max 8 tegelijk)','i'=>'🐄', 'cap'=>8, 'man'=>1],
  'zwem'    => ['n'=>'Buitenzwembad',           'i'=>'🏊', 'sun'=>1],
  'kantine' => ['n'=>'Kantine (oude melkstal)', 'i'=>'🍺', 'man'=>1],
];
//* info: Losse units: sleutel = letter + nummer (d0..d11, w0..w13, m0..m5)
const UNITS = ['d'=>['n'=>12,'name'=>'Douche'], 'w'=>['n'=>14,'name'=>'Toilet'], 'm'=>['n'=>6,'name'=>'Wasmachine']];

//* info: opslag 
function state_open(){
  global $S,$fh,$poolOpen,$sunset;
  $dir = __DIR__.'/data';
  if(!is_dir($dir)){ mkdir($dir,0750); file_put_contents("$dir/.htaccess","Require all denied\n"); }
  $fh = fopen("$dir/state.json",'c+'); flock($fh,LOCK_EX);
  $S = (json_decode(stream_get_contents($fh),true) ?: []) + ['in'=>[],'vis'=>[],'def'=>[],'u'=>[],'open'=>['stal'=>1,'kantine'=>1],'meld'=>[],'hok'=>[],'clean'=>[],'rl'=>[]];
  foreach(UNITS as $g=>$u){ //* info: oude 'mach' wordt overgenomen
    $old = $S['u'][$g] ?? ($g=='m' ? ($S['mach'] ?? []) : []);
    $S['u'][$g] = array_pad(array_slice($old,0,$u['n']),$u['n'],1);
  }
  unset($S['mach']);
  $sun = date_sun_info(time(),LAT,LON)['sunset'] ?? strtotime('20:30');
  $poolOpen = time() < $sun; $sunset = date('H:i',$sun);
  if(!$poolOpen) $S['in']['zwem'] = []; // zon onder: zwembad leeg
}
function save(){ global $fh,$S; ftruncate($fh,0); rewind($fh); fwrite($fh,json_encode($S)); }

//* info: helpers 
function h($s){ return htmlspecialchars((string)$s,ENT_QUOTES); }
function short_name($id){ return explode(' (',FACILITIES[$id]['n'])[0]; }
//* info Schoonmaakhok: items die nog buiten het hok zijn (een 'terug' haalt de oudste 'uit' met hetzelfde middel weg, liefst van dezelfde persoon)
function hok_open(){
  global $S; $open=[];
  foreach($S['hok'] as $x){
    if($x['a']=='uit'){ $open[]=$x; continue; }
    foreach([true,false] as $strict) foreach($open as $i=>$o)
      if(strtolower($o['m'])==strtolower($x['m']) && (!$strict || $o['w']==$x['w'])){ unset($open[$i]); continue 3; }
  }
  return $open;
}
function unit($k){ return (preg_match('/^([dwm])(\d+)$/',$k,$x) && $x[2]<UNITS[$x[1]]['n']) ? [$x[1],(int)$x[2]] : null; }
function valid_key($k){ return unit($k) || (isset(FACILITIES[$k]) && !isset(FACILITIES[$k]['u'])); }
function label($k){ $u=unit($k); return $u ? UNITS[$u[0]]['name'].' '.($u[1]+1) : (isset(FACILITIES[$k]) ? short_name($k) : $k); }
function working($g){ global $S; return array_sum($S['u'][$g]); }
function capacity($id){ $f=FACILITIES[$id]; return isset($f['u']) ? working($f['u']) : ($f['cap'] ?? 0); }
function is_closed($id){ global $S; return !empty(FACILITIES[$id]['man']) && empty($S['open'][$id]); }

//* info: [tekst, kleurklasse, subtekst]
function status($id){
  global $S,$poolOpen; $f=FACILITIES[$id]; $o=count($S['in'][$id] ?? []); $c=capacity($id);
  if(!empty($S['def'][$id]))        return ['Defect','bad','Het personeel is op de hoogte gesteld'];
  if(isset($f['u']) && !$c)         return ['Defect','bad','Alles defect – het personeel is op de hoogte gesteld'];
  if(!empty($f['sun']) && !$poolOpen) return ['Gesloten','off','Zon onder – zwembad leeg'];
  if(is_closed($id))                return ['Gesloten','off',''];
  if($c && $o>=$c)                  return ['Vol','warn',"$o van $c bezet"];
  if($c)                            return ['Beschikbaar','ok',"$o van $c bezet"];
  return ['Open','ok',"$o aanwezig".(!empty($f['sun']) ? ' (geen limiet)' : '')];
}

//* info: views includen 
function units($g,$mode){ global $S; include __DIR__.'/views/units.php'; }
function unit_card($title,$g,$mode){ global $S; include __DIR__.'/views/unit_card.php'; }

//* info: acties
//* info:  $who: 'admin' (Bert) | 'staff' (personeel) | 'scanner' | 'guest'. Geeft null bij succes, anders [melding, httpcode]
function act($a,$b,$who){
  global $S,$poolOpen;
  $admin = $who=='admin'; $staff = $admin || $who=='staff'; $scanner = $admin || $who=='scanner'; $no = ['Geen toegang',401];


  if ($a == 'scan') {
      if (!$scanner) return $no;

      $f = $b['f'] ?? '';
      $card = strtoupper(preg_replace(
          '/[^A-Fa-f0-9]/',
          '',
          (string)($b['card'] ?? '')
      ));

      if (!isset(FACILITIES[$f]) ||
          !preg_match('/^[A-F0-9]{8,20}$/', $card)) {
          return ['Ongeldige scan', 400];
      }

      // Voorlopige testkaarten.
      // Later vervangen door gastenregistratie.
      $allowedCards = [
          'A20E9451',
          '92237F51'
      ];

      if (!in_array($card, $allowedCards, true)) {
          return ['Onbekende kaart', 403];
      }

      $in = $S['in'][$f] ?? [];

      // Voorkom direct dubbel scannen.
      $now = microtime(true);
      $last = $S['lastscan'][$f][$card] ?? 0;

      if ($now - $last < 3) {
          $S['scanresult'] = [
              'action' => 'ignored',
              'occupancy' => count($in)
          ];
          return null;
      }

      // Is de gast al binnen?
      if (in_array($card, $in, true)) {
          $in = array_values(array_diff($in, [$card]));
          $action = 'out';
      } else {
          // Controleer de faciliteit.
          $c = capacity($f);

          if (!empty($S['def'][$f]) ||
              (isset(FACILITIES[$f]['u']) && !$c)) {
              return ['Defect', 409];
          }

          if (is_closed($f)) {
              return ['Gesloten', 409];
          }

          if ($f == 'zwem' && !$poolOpen) {
              return ['Zwembad gesloten', 409];
          }

          if ($c && count($in) >= $c) {
              return ['Faciliteit vol', 409];
          }

          $in[] = $card;
          $action = 'in';

          $S['vis'][$f] = ($S['vis'][$f] ?? 0) + 1;
      }

      $S['in'][$f] = $in;
      $S['lastscan'][$f][$card] = $now;

      $S['scanresult'] = [
          'action' => $action,
          'occupancy' => count($in)
      ];

      return null;
  }


  if($a=='report' || $a=='defect'){
    $k=$b['key']??''; if(!valid_key($k)) return ['Ongeldig',400];
    if($a=='report'){ // gasten: alleen melden, max 5 per uur per IP
      $ip=md5($_SERVER['REMOTE_ADDR']??'');
      $S['rl'][$ip]=array_values(array_filter($S['rl'][$ip]??[],fn($t)=>$t>time()-3600));
      if(count($S['rl'][$ip])>=5) return ['Te veel meldingen, probeer het later opnieuw',429];
      $S['rl'][$ip][]=time(); $on=true;
    } else { if(!$staff) return $no; $on=!empty($b['on']); }

    if($u=unit($k)) $S['u'][$u[0]][$u[1]]=$on?0:1; else $S['def'][$k]=$on;
    if($on){
      $dup=false; foreach($S['meld'] as $m) if($m['k']==$k && !$m['done']) $dup=true;
      if(!$dup){
        $by=$a=='report'?'gast':'beheer';
        $S['meld'][]=['k'=>$k,'w'=>label($k),'t'=>date('H:i'),'by'=>$by,'done'=>0];
        @mail(BERT_MAIL,'Defect: '.label($k),label($k)." is defect gemeld door $by om ".date('H:i')." (".date('d-m-Y').").\nGasten zien nu dat het niet werkt.","From: ".FROM_MAIL);
      }
    } else foreach($S['meld'] as &$m) if($m['k']==$k) $m['done']=1;
    unset($m);
    $S['meld']=array_slice($S['meld'],-50); return null;
  }

  if(!$staff) return $no;
  $f=$b['f']??'';
  switch($a){
    case 'open':    if(!$admin) return $no; if(!isset(FACILITIES[$f])) return ['Ongeldig',400]; $S['open'][$f]=!empty($b['on']); break;
    case 'cleaned': if(!isset(FACILITIES[$f])) return ['Ongeldig',400]; $S['vis'][$f]=0; $S['clean'][$f]=date('H:i'); break;
    case 'hok':
      $w=trim($b['w']??''); $m=trim($b['m']??''); if($w===''||$m==='') return ['Vul wie en welk middel in',400];
      $S['hok'][]=['w'=>substr($w,0,40),'m'=>substr($m,0,40),'a'=>($b['a']??'')=='uit'?'uit':'terug','t'=>date('H:i')];
      $S['hok']=array_slice($S['hok'],-100); break;
    default: return ['Ongeldig',400];
  }
  return null;
}