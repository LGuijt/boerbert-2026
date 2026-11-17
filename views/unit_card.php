<!--//* templates voor de facilities cards-->

<?php // vars: $title, $g, $mode
$w=working($g); $n=count($S['u'][$g]); ?>
<div class="card">
  <h3><?=$title?></h3>
  <?php if($mode=='admin'): ?>
    <div class="mut">Tik = defect/werkend</div>
  <?php else: ?>
    <span class="pill <?=$w==$n?'ok':($w?'warn':'bad')?>"><?=$w?> van <?=$n?> werken</span>
  <?php endif ?>
  <?php units($g,$mode) ?>
  <?php if($mode!='admin'): ?><div class="mut" style="margin-top:6px">✕ = werkt niet, het personeel weet het. Iets stuk? Tik op het nummer om te melden.</div><?php endif ?>
</div>