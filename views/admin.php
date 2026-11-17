<!-- //* info: admin pagina specifiek voor Bestie Bert -->
<form method="post">
<button name="x" value="noop" hidden></button>

<!--//* info: displayt defectmeldingen -->
<?php include __DIR__.'/meldingen.php' ?>

<!--//* info: zodat bert de poortjes kan testen zonder pas -->
<h2>Pas-scans</h2>
<div class="card"><div class="mut">Test-pas (de echte lezers sturen naar <code>api.php</code> met de scanner-sleutel)</div>
  <input name="card" placeholder="Pasnummer" value="<?=h($_SESSION['card'] ?? '')?>"></div>


<!--//* info: loop voor bijna alle facilities-->
<div class="grid" style="margin-top:12px">
<?php foreach(FACILITIES as $id=>$f): $cap=capacity($id); ?>
  <div class="card">
    <h3><?=$f['i']?> <?=h($f['n'])?></h3>
    <div class="mut">Nu: <?=count($S['in'][$id] ?? [])?><?=$cap ? " / $cap" : ''?></div>
    <button class="btn p" name="x" value="scan:<?=$id?>:in">Pas in</button>
    <button class="btn" name="x" value="scan:<?=$id?>:out">Pas uit</button>
    <?php if(!empty($f['man'])): ?><button class="btn" name="x" value="open:<?=$id?>:<?=is_closed($id)?'on':'off'?>"><?=is_closed($id)?'Openen':'Sluiten'?></button><?php endif ?>
    <?php if(isset($f['u'])): units($f['u'],'admin');
    else: ?><button class="btn d" name="x" value="defect:<?=$id?>:<?=empty($S['def'][$id])?'on':'off'?>"><?=empty($S['def'][$id])?'Defect melden':'Defect opheffen'?></button><?php endif ?>
  </div>
<?php endforeach ?>
  <?php unit_card('🧺 Wasmachines','m','admin') ?>
</div>

<h2>Zwembad</h2>
<div class="card mut">Zon onder vandaag om <?=$sunset?> (automatisch berekend) – het zwembad is nu <?=$poolOpen ? 'open' : 'gesloten en leeg'?></div>

<?php include __DIR__.'/schoonmaak.php' ?>
<?php include __DIR__.'/hok.php' ?>
</form>