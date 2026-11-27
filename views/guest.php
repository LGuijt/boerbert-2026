<!--//* info: pagina voor de gasten -->

<div class="grid">
<?php foreach(FACILITIES as $id=>$f):
  [$tekst,$kleur,$sub] = status($id); $o=count($S['in'][$id] ?? []); $cap=capacity($id); ?>
  <div class="card">
    <h3><?=$f['i']?> <?=h($f['n'])?></h3>
    <span class="pill <?=$kleur?>"><?=$tekst?></span>
    <?php if($id=='zwem'):
      if($poolScheduleChanged): ?><div class="pool-schedule-notice"><strong>Let op: de zwemtijden zijn aangepast.</strong><span>Vandaag is het zwembad open van <?=h($sunrise)?> tot <?=h($sunset)?>.</span></div><?php endif ?>
      <?php if(!$poolOpen): ?>
        <div class="pool-closed">
          <strong>Helaas, het zwembad is momenteel gesloten.</strong>
          <p>Het zwembad opent weer <?=$poolOpensDay?> om <strong><?=h($poolOpensAt)?></strong>.</p>
          <p>Bedankt voor uw begrip.</p>
        </div>
      <?php elseif($poolOpen):
      $clockNow = time();
      $sunriseAngle = ((((int)date('g',$sunriseTime) % 12) * 60) + (int)date('i',$sunriseTime)) * 0.5;
      $sunsetAngle = ((((int)date('g',$sunsetTime) % 12) * 60) + (int)date('i',$sunsetTime)) * 0.5;
      $openAngle = ($sunsetAngle - $sunriseAngle + 360) % 360;
      $nowAngle = ((((int)date('g',$clockNow) % 12) * 60) + (int)date('i',$clockNow)) * 0.5;
      $minuteAngle = ((int)date('i',$clockNow) + (int)date('s',$clockNow) / 60) * 6;
      $secondsAngle = ((int)date('s',$clockNow) + (int)date('u',$clockNow) / 1000000) * 6;
      $clockLabel = date('H:i',$clockNow);
      $clockStyle = '--sunrise-angle:'.$sunriseAngle.'deg;--open-angle:'.$openAngle.'deg;--now-angle:'.$nowAngle.'deg;--minute-angle:'.$minuteAngle.'deg;--seconds-angle:'.$secondsAngle.'deg';
      ?>
      <div class="pool-times">Zwemtijden vandaag <strong><?=h($sunrise)?> – <?=h($sunset)?></strong></div>
      <div class="pool-clock" role="img" aria-label="Analoge klok: groen is open van <?=h($sunrise)?> tot <?=h($sunset)?>, rood is gesloten. Het is nu <?=$clockLabel?>" style="<?=$clockStyle?>">
        <span class="pool-clock__ticks" aria-hidden="true">
          <?php for($tick=0;$tick<60;$tick++): ?>
            <i class="pool-clock__tick<?=$tick%5===0?' pool-clock__tick--hour':''?>" style="--tick-angle:<?=$tick*6?>deg"></i>
          <?php endfor ?>
        </span>
        <span class="pool-clock__hand pool-clock__hand--hour"></span>
        <span class="pool-clock__hand pool-clock__hand--minute"></span>
        <span class="pool-clock__hand pool-clock__hand--seconds"></span>
        <span class="pool-clock__pin"></span>
        <span class="pool-clock__label pool-clock__label--12">12</span>
        <span class="pool-clock__label pool-clock__label--1">1</span>
        <span class="pool-clock__label pool-clock__label--2">2</span>
        <span class="pool-clock__label pool-clock__label--3">3</span>
        <span class="pool-clock__label pool-clock__label--4">4</span>
        <span class="pool-clock__label pool-clock__label--5">5</span>
        <span class="pool-clock__label pool-clock__label--6">6</span>
        <span class="pool-clock__label pool-clock__label--7">7</span>
        <span class="pool-clock__label pool-clock__label--8">8</span>
        <span class="pool-clock__label pool-clock__label--9">9</span>
        <span class="pool-clock__label pool-clock__label--10">10</span>
        <span class="pool-clock__label pool-clock__label--11">11</span>
      </div>
      <div class="pool-clock-legend"><span class="pool-clock-legend__open">Open</span><span class="pool-clock-legend__closed">Gesloten</span></div>
      <?php endif ?>
    <?php endif ?>
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