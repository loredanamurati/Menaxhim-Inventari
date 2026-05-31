<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; ensure_portal_schema($pdo); ensure_warehouse_schema($pdo); ensure_chat_schema($pdo);
if(empty($_SESSION['supplier'])) redirect('supplier_login.php');
$supplier=$_SESSION['supplier'];
$question=trim($_POST['question'] ?? '');
if(isset($_POST['clear_history'])){ clear_chat_history($pdo,'supplier',$supplier['furnizues_id']); redirect('supplier_ai.php'); }
if($question){ $answer=smart_ai_answer($pdo,$question,'supplier',$supplier['furnizues_id']); save_chat_log($pdo,'supplier',$supplier['furnizues_id'],$question,$answer); }
$history=get_chat_history($pdo,'supplier',$supplier['furnizues_id'],30);
$st=$pdo->prepare('SELECT COUNT(*) FROM Produktet WHERE furnizues_id=?'); $st->execute([$supplier['furnizues_id']]); $count=(int)$st->fetchColumn();
$st=$pdo->prepare('SELECT COUNT(*) FROM Produktet WHERE furnizues_id=? AND sasia_ne_stok<=stok_minimal'); $st->execute([$supplier['furnizues_id']]); $low=(int)$st->fetchColumn();
$st=$pdo->prepare('SELECT emri,sasia_ne_stok,stok_minimal FROM Produktet WHERE furnizues_id=? ORDER BY sasia_ne_stok ASC LIMIT 5'); $st->execute([$supplier['furnizues_id']]); $critical=$st->fetchAll();
?>
<?php $portalRole='supplier'; $portalUser=$supplier; $pageTitle='Asistenti Virtual'; include 'includes/portal_header.php'; ?>
<section class="page-title-block"><h1>Asistenti Virtual</h1><p>Analizo produktet e tua dhe merr sugjerime për furnizim.</p></section>
<div class="ai-page-grid"><div class="card ai-main-card"><div class="ai-title-row"><div><span class="ai-kicker">Supply Assistant</span><h2>Asistent për furnizimin</h2></div><div class="ai-actions"><span class="ai-live-dot">Aktiv</span><form method="post" onsubmit="return confirm('Do ta fshish historikun e bisedave?')"><button class="btn secondary small" name="clear_history" value="1">Fshi historikun</button></form></div></div><p class="muted">Pyet për produktet e tua, furnizimet dhe stokun kritik. Bisedat ruhen.</p><div id="chatHistory" class="chat-window-pro history-window"><?php if(!$history): ?><div class="chat-bubble bot">Përshëndetje! Unë jam asistenti virtual. Si mund t’ju ndihmoj?</div><?php endif; ?><?php foreach($history as $h): ?><div class="chat-bubble user"><?=e($h['pyetja'])?></div><div class="chat-bubble bot"><?=nl2br(e($h['pergjigja']))?></div><?php endforeach; ?></div><form method="post" class="ai-input-row"><textarea class="input" name="question" placeholder="P.sh. cilat produkte të mia duhen furnizuar?"></textarea><button class="btn primary">Dërgo</button></form><div class="quick-prompts-pro"><?php foreach(['Cilat produkte duhet të furnizoj?','Cili produkt ka stok më të ulët?','Bëj raport për produktet e mia','A kam stok të lartë?'] as $p): ?><form method="post"><input type="hidden" name="question" value="<?=e($p)?>"><button><?=$p?></button></form><?php endforeach; ?></div></div>
<div class="card ai-side-card"><h2>Gjendja jote</h2><div class="ai-metric"><span>Produkte</span><b><?=$count?></b></div><div class="ai-metric"><span>Stok kritik</span><b><?=$low?></b></div><h3>Prioritetet</h3><?php foreach($critical as $c): ?><div class="mini-insight"><b><?=e($c['emri'])?></b><span>stok <?=e($c['sasia_ne_stok'])?> / min <?=e($c['stok_minimal'])?></span></div><?php endforeach; ?></div></div>

<script>
(function(){
  const box = document.getElementById('chatHistory');
  if(box){ box.scrollTop = box.scrollHeight; }
  const form = document.querySelector('.ai-input-row');
  if(form){ form.addEventListener('submit', function(){ setTimeout(function(){ if(box){ box.scrollTop = box.scrollHeight; } }, 50); }); }
})();
</script>

<?php include 'includes/portal_footer.php'; ?>
