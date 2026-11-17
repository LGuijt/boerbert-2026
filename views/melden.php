//* info: card voor het melden van defecten

<div class="card">
  <h3>⚠️ Melden dat <?=h(label($melden))?> niet werkt?</h3>
  <div class="mut">Het personeel krijgt hier direct bericht van.</div>
  <form method="post"><button class="btn d" name="x" value="report:<?=h($melden)?>">Ja, melden</button> <a class="btn" href="index.php">Annuleren</a></form>
</div>