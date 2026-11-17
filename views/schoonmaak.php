<!--//* info: card voor de schoonmaak-->

<!--//todo: moet ook nog iets van voorspellend vermogen/algmene gemiddelden hebben-->

<?php $mx = max(1, ...array_map(fn($id)=>$S['vis'][$id] ?? 0, array_keys(FACILITIES))); ?>
<h2>Drukte voor schoonmakers</h2>
<div class="card">
<?php foreach(FACILITIES as $id=>$f): if($id=='stal') continue; $v=$S['vis'][$id] ?? 0; ?>
  <div class="row"><span style="min-width:130px"><?=$f['i']?> <?=h(short_name($id))?></span>
    <span style="flex:1"><div class="bar"><i style="width:<?=$v/$mx*100?>%"></i></div></span>
    <span class="mut"><?=$v?>×</span><button class="btn" name="x" value="cleaned:<?=$id?>">Schoongemaakt</button></div>
  <?php if(!empty($S['clean'][$id])): ?><div class="mut">laatst schoongemaakt <?=h($S['clean'][$id])?></div><?php endif ?>
<?php endforeach ?>
  <div class="mut" style="margin-top:6px">Bezoeken sinds laatste schoonmaak</div>
</div>
