<!--//* info: defect meldingen-->
<?php if($open): ?>
  <div class="alert"><b>🔔 <?=count($open)?> open melding(en)</b>
    <?php foreach($open as $m): ?>
      <div class="row"><span><?=h($m['w'])?> defect sinds <?=h($m['t'])?></span><button class="btn" name="x" value="defect:<?=h($m['k'])?>:off">Opgelost</button></div>
    <?php endforeach ?>
  </div>
<?php else: ?><div class="alert fine">✅ Geen openstaande meldingen</div><?php endif ?>