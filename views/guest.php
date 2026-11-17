//* info: pagina voor de gasten

<div class="grid">
<?php foreach(FACILITIES as $id=>$f):
  [$tekst,$kleur,$sub] = status($id); $o=count($S['in'][$id] ?? []); $cap=capacity($id); ?>
  <div class="card">
    <h3><?=$f['i']?> <?=h($f['n'])?></h3>
    <span class="pill <?=$kleur?>"><?=$tekst?></span>
    <?php if($cap && in_array($kleur,['ok','warn'])): ?><div class="bar"><i style="width:<?=min(100,$o/$cap*100)?>%"></i></div><?php endif ?>
    <div class="mut"><?=h($sub)?>
      <?php if(isset($f['u']) && working($f['u'])<count($S['u'][$f['u']])): ?> · <?=working($f['u'])?> van <?=count($S['u'][$f['u']])?> werken<?php endif ?>
      <?=!empty($f['always']) ? ' · altijd open' : ''?></div>
    <?php if(isset($f['u'])): units($f['u'],'guest');
    elseif(empty($S['def'][$id])): ?><a class="btn d" href="?melden=<?=$id?>">⚠️ Werkt niet? Meld het</a><?php endif ?>
  </div>
<?php endforeach ?>

  <?php unit_card('🧺 Wasruimte','m','guest') ?>
</div>