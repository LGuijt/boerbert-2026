<?php // vars: $g (d|w|m), $mode ('admin' = tik zet defect aan/uit, anders tik = melden) ?>
<div class="units">
<?php foreach($S['u'][$g] as $i=>$werkt): $k=$g.$i; $n=$i+1; $c=$werkt?'ok':'bad'; ?>
  <?php if($mode=='admin'): ?>
    <button class="<?=$c?>" name="x" value="defect:<?=$k?>:<?=$werkt?'on':'off'?>"><?=$n?></button>
  <?php elseif($werkt): ?>
    <a class="ok" href="?melden=<?=$k?>"><?=$n?> ✓</a>
  <?php else: ?>
    <b class="bad"><?=$n?> ✕</b>
  <?php endif ?>
<?php endforeach ?>
</div>
