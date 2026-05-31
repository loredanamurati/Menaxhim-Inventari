<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_warehouse_schema($pdo); ensure_chat_schema($pdo);
$title='Asistenti Virtual';
$question=trim($_POST['question'] ?? '');
if(isset($_POST['clear_history'])){ clear_chat_history($pdo,'admin',null); redirect('ai.php'); }
$answer='Përshëndetje! Mund të më pyesësh për stokun, furnizimet, magazinat, produktet kritike, produktet jashtë stoku ose raportin e përgjithshëm.';
if($question){ $answer=smart_ai_answer($pdo,$question,'admin',null); save_chat_log($pdo,'admin',null,$question,$answer); }
$history=get_chat_history($pdo,'admin',null,30);
$low=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok<=stok_minimal')->fetchColumn();
$out=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok<=0')->fetchColumn();
$value=$pdo->query('SELECT COALESCE(SUM(cmimi*sasia_ne_stok),0) FROM Produktet')->fetchColumn();
$top=$pdo->query("SELECT p.emri, COALESCE(SUM(d.sasia),0) dalje FROM Produktet p LEFT JOIN Daljet d ON d.produkt_id=p.produkt_id AND d.data_daljes>=DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY p.produkt_id ORDER BY dalje DESC LIMIT 5")->fetchAll();
include 'includes/header.php'; ?>
<div class="ai-page-grid">
  <div class="card ai-main-card">
    <div class="ai-title-row"><div><h2>Chat për menaxhimin e stokut</h2></div><div class="ai-actions"><span class="ai-live-dot">Aktiv</span><form method="post" onsubmit="return confirm('Do ta fshish historikun e bisedave?')"><button class="btn secondary small" name="clear_history" value="1">Fshi historikun</button></form></div></div>
    <p class="muted">Bisedat ruhen automatikisht dhe mund t’i shohësh përsëri më poshtë.</p>
    <div id="chatHistory" class="chat-window-pro history-window">
      <?php if(!$history): ?><div class="chat-bubble bot">Përshëndetje! Unë jam asistenti virtual. Si mund t’ju ndihmoj?</div><?php endif; ?>
      <?php foreach($history as $h): ?>
        <div class="chat-bubble user"><?=e($h['pyetja'])?></div>
        <div class="chat-bubble bot"><?=nl2br(e($h['pergjigja']))?></div>
      <?php endforeach; ?>
    </div>
    <form method="post" class="ai-input-row">
      <textarea class="input" name="question" placeholder="Shkruaj pyetjen tënde këtu..." autofocus></textarea>
      <button class="btn primary">Dërgo</button>
    </form>
    <div class="quick-prompts-pro"><?php foreach(['Bëj raport inventari','Cilat produkte janë kritike?','Çfarë duhet furnizuar?','Si janë magazinat?','Cilat produkte janë më aktive?'] as $p): ?><form method="post"><input type="hidden" name="question" value="<?=e($p)?>"><button><?=$p?></button></form><?php endforeach; ?></div>
  </div>
  <div class="card ai-side-card"><h2>Sinjale të shpejta</h2><div class="ai-metric"><span>Stok i ulët</span><b><?=$low?></b></div><div class="ai-metric"><span>Jashtë stoku</span><b><?=$out?></b></div><div class="ai-metric"><span>Vlera e stokut</span><b><?=money($value)?></b></div><h3>Produktet aktive</h3><?php foreach($top as $t): ?><div class="mini-insight"><b><?=e($t['emri'])?></b><span><?=e($t['dalje'])?> dalje në 30 ditë</span></div><?php endforeach; ?></div>
</div>

<script>
(function(){
  const box = document.getElementById('chatHistory');
  if(box){ box.scrollTop = box.scrollHeight; }
  const form = document.querySelector('.ai-input-row');
  if(form){ form.addEventListener('submit', function(){ setTimeout(function(){ if(box){ box.scrollTop = box.scrollHeight; } }, 50); }); }
})();
</script>

<?php include 'includes/footer.php'; ?>
