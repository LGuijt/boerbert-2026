<!--//* info: voor het schoonmaakhok-->
<?php
$buiten = hok_open();
$terug  = array_reverse(array_filter($S['hok'],fn($x)=>$x['a']=='terug'));
?>
<h2>Schoonmaakhok</h2>
<div class="card">
  <?php if($buiten): ?>
    <div class="mut">Nog buiten het hok:</div>
    //* info: meld alles wat nog uit het hok is, zover we weten
    <?php foreach($buiten as $e): ?>
      <div class="row"><span><b><?=h($e['m'])?></b> · <?=h($e['w'])?> · sinds <?=h($e['t'])?></span>
        <button class="btn p" name="x" value="hok:terug:<?=base64_encode(json_encode([$e['w'],$e['m']]))?>">Teruggebracht</button></div>
    <?php endforeach ?>
  <?php else: ?><div class="mut">✅ Alles is teruggebracht</div><?php endif ?>

  <div style="margin-top:12px"><input name="hw" placeholder="Wie (pas/naam)" size="12"> <input name="hm" placeholder="Middel" size="12">
    <button class="btn p" name="x" value="hok:uit">Meegenomen</button><button class="btn" name="x" value="hok:terug">Teruggebracht</button></div>

  <?php if($terug): ?>
    <div class="mut" style="margin-top:12px">Laatst teruggebracht:</div>
    <?php foreach(array_slice($terug,0,5) as $x): ?>
      <div class="row"><span><?=h($x['t'])?> · <?=h($x['w'])?> · <?=h($x['m'])?></span><span class="mut">✓ afgehandeld</span></div>
    <?php endforeach ?>
  <?php endif ?>
</div>