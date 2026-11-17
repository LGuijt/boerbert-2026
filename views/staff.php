<!--//* inf0: pagina voor personeel dat niet Bert is-->
<form method="post">
<button name="x" value="noop" hidden></button>

<?php include __DIR__.'/meldingen.php' ?>

<h2>Defecten</h2>
<div class="grid">
<?php foreach(FACILITIES as $id=>$f): [$tekst,$kleur] = status($id); ?>
  <div class="card">
    <h3><?=$f['i']?> <?=h($f['n'])?></h3>
    <span class="pill <?=$kleur?>"><?=$tekst?></span>
    <?php if(isset($f['u'])): ?><div class="mut" style="margin-top:6px">Tik = defect/werkend</div><?php units($f['u'],'admin');
    else: ?><div><button class="btn d" name="x" value="defect:<?=$id?>:<?=empty($S['def'][$id])?'on':'off'?>"><?=empty($S['def'][$id])?'Defect melden':'Defect opheffen'?></button></div><?php endif ?>
  </div>
<?php endforeach ?>
  <?php unit_card('🧺 Wasmachines','m','admin') ?>
</div>

<?php include __DIR__.'/schoonmaak.php' ?>
<?php include __DIR__.'/hok.php' ?>
</form>
