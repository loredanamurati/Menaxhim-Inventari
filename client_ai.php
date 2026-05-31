<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; ensure_portal_schema($pdo); ensure_warehouse_schema($pdo); ensure_chat_schema($pdo);
if(empty($_SESSION['client'])) redirect('client_login.php');
$client=$_SESSION['client'];
$question=trim($_POST['question'] ?? '');
if(isset($_POST['clear_history'])){ clear_chat_history($pdo,'client',$client['klient_id']); redirect('client_ai.php'); }
if($question){ $answer=smart_ai_answer($pdo,$question,'client',$client['klient_id']); save_chat_log($pdo,'client',$client['klient_id'],$question,$answer); }
$history=get_chat_history($pdo,'client',$client['klient_id'],30);
$available=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok>0')->fetchColumn();
$out=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok<=0')->fetchColumn();
$cheap=$pdo->query('SELECT emri, cmimi, sasia_ne_stok FROM Produktet WHERE sasia_ne_stok>0 ORDER BY cmimi ASC LIMIT 5')->fetchAll();
?>
<?php $portalRole='client'; $portalUser=$client; $pageTitle='Asistenti Virtual'; include 'includes/portal_header.php'; ?>
<section class="page-title-block"><h1>Asistenti Virtual</h1><p>Bëj pyetje të lira për produktet, stokun, çmimet dhe porositë.</p></section>
<div class="ai-page-grid"><div class="card ai-main-card"><div class="ai-title-row"><div><h2>Pyetje të lira për katalogun</h2></div><div class="ai-actions"><span class="ai-live-dot">Aktiv</span><form method="post" onsubmit="return confirm('Do ta fshish historikun e bisedave?')"><button class="btn secondary small" name="clear_history" value="1">Fshi historikun</button></form></div></div><p class="muted">Shkruaj pyetjen tënde. Bisedat ruhen në këtë faqe.</p><div id="chatHistory" class="chat-window-pro history-window"><?php if(!$history): ?><div class="chat-bubble bot">Përshëndetje! Unë jam asistenti virtual. Si mund t’ju ndihmoj?</div><?php endif; ?><?php foreach($history as $h): ?><div class="chat-bubble user"><?=e($h['pyetja'])?></div><div class="chat-bubble bot"><?=nl2br(e($h['pergjigja']))?></div><?php endforeach; ?></div><form method="post" class="ai-input-row"><textarea class="input" name="question" placeholder="P.sh. më sugjero laptopë në stok me çmim të mirë"></textarea><button class="btn primary">Dërgo</button></form><div class="quick-prompts-pro"><?php foreach(['Më sugjero produkte për të blerë','Cilat janë produktet më të lira?','Cilat produkte janë jashtë stoku?','A ka laptopë në stok?'] as $p): ?><form method="post"><input type="hidden" name="question" value="<?=e($p)?>"><button><?=$p?></button></form><?php endforeach; ?></div></div>
<div class="card ai-side-card"><h2>Gjendja e katalogut</h2><div class="ai-metric"><span>Në stok</span><b><?=$available?></b></div><div class="ai-metric"><span>Jashtë stokut</span><b><?=$out?></b></div><h3>Produkte ekonomike</h3><?php foreach($cheap as $c): ?><div class="mini-insight"><b><?=e($c['emri'])?></b><span><?=money($c['cmimi'])?> · stok <?=e($c['sasia_ne_stok'])?></span></div><?php endforeach; ?></div></div>

<script>
(function(){
  const box = document.getElementById('chatHistory');
  if(box){ box.scrollTop = box.scrollHeight; }
  const form = document.querySelector('.ai-input-row');
  if(form){ form.addEventListener('submit', function(){ setTimeout(function(){ if(box){ box.scrollTop = box.scrollHeight; } }, 50); }); }
})();
</script>

<?php include 'includes/portal_footer.php'; ?>
