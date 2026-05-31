<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; if(empty($_SESSION['user'])) redirect('choose_role.php'); require_login(); ensure_warehouse_schema($pdo);
$title='Dashboard'; $subtitle='Përmbledhje e përgjithshme e sistemit të inventarit.';
$products=(int)$pdo->query('SELECT COUNT(*) FROM Produktet')->fetchColumn();
$sup=(int)$pdo->query('SELECT COUNT(*) FROM Furnizuesit')->fetchColumn();
$customers=(int)$pdo->query('SELECT COUNT(*) FROM Klientet')->fetchColumn();
$warehouses=(int)$pdo->query('SELECT COUNT(*) FROM Magazinat')->fetchColumn();
$low=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok <= stok_minimal')->fetchColumn();
$value=(float)$pdo->query('SELECT COALESCE(SUM(cmimi*sasia_ne_stok),0) FROM Produktet')->fetchColumn();
$orders=0; $paid=0; try{ $orders=(int)$pdo->query('SELECT COUNT(*) FROM Porosite')->fetchColumn(); $paid=(int)$pdo->query("SELECT COUNT(*) FROM Porosite WHERE statusi IN ('E paguar','E perfunduar')")->fetchColumn(); }catch(Exception $e){}
$latestOrders=[]; try{ $latestOrders=$pdo->query("SELECT p.*, k.emri AS klient FROM Porosite p LEFT JOIN Klientet k ON k.klient_id=p.klient_id ORDER BY p.porosi_id DESC LIMIT 5")->fetchAll(); }catch(Exception $e){}
$top=$pdo->query("SELECT p.emri, COALESCE(SUM(dp.sasia),0) shitje, p.barkodi FROM Produktet p LEFT JOIN DetajetPorosise dp ON dp.produkt_id=p.produkt_id GROUP BY p.produkt_id ORDER BY shitje DESC, p.sasia_ne_stok DESC LIMIT 4")->fetchAll();
$critical=$pdo->query("SELECT emri, sasia_ne_stok, stok_minimal FROM Produktet WHERE sasia_ne_stok<=stok_minimal ORDER BY sasia_ne_stok ASC LIMIT 4")->fetchAll();
$movement=$pdo->query("SELECT p.emri, COALESCE(SUM(d.sasia),0) dalje FROM Produktet p LEFT JOIN Daljet d ON d.produkt_id=p.produkt_id AND d.data_daljes >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY p.produkt_id ORDER BY dalje DESC LIMIT 5")->fetchAll();
$maxMove=max(1,...array_map(fn($x)=>(int)$x['dalje'],$movement ?: [['dalje'=>1]]));
include 'includes/header.php'; ?>
<div class="mock-kpis">
  <a class="mock-kpi" href="products.php"><div><span>Produkte</span><b><?=$products?></b><small>Gjithsej produkte</small></div><i class="ico blue">▣</i></a>
  <a class="mock-kpi" href="suppliers.php"><div><span>Furnizues</span><b><?=$sup?></b><small>Gjithsej furnizues</small></div><i class="ico green">▰</i></a>
  <a class="mock-kpi" href="warehouses.php"><div><span>Magazina</span><b><?=$warehouses?></b><small>Lokacione aktive</small></div><i class="ico violet">⌂</i></a>
  <a class="mock-kpi" href="admin_paypal.php"><div><span>Stok / Vlera Totale</span><b><?=money($value)?></b><small>Vlera totale në stok</small></div><i class="ico orange">€</i></a>
</div>
<div class="mock-grid-main">
  <section class="mock-card orders-card"><div class="card-head"><h2>Porositë e fundit</h2><a href="admin_orders.php">Shiko të gjitha</a></div><table class="mock-table"><tr><th># Porosia</th><th>Data</th><th>Klienti</th><th>Vlera</th><th>Statusi</th><th></th></tr><?php foreach($latestOrders as $o): ?><tr><td>#<?=e($o['porosi_id'])?></td><td><?=e(substr($o['data_porosise'] ?? '',0,10))?></td><td><?=e($o['klient'] ?? '-')?></td><td><?=money($o['totali'] ?? 0)?></td><td><span class="status-pill blue"><?=e($o['statusi'] ?? 'Në proces')?></span></td><td>›</td></tr><?php endforeach; if(!$latestOrders): ?><tr><td colspan="6">Nuk ka porosi.</td></tr><?php endif; ?></table></section>
  <section class="mock-card chart-card"><div class="card-head"><h2>Vlera e stokut (30 ditë)</h2><span class="select-pill">30 ditë⌄</span></div><div class="line-chart"><svg viewBox="0 0 500 230" preserveAspectRatio="none"><defs><linearGradient id="fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#2563eb" stop-opacity=".32"/><stop offset="1" stop-color="#2563eb" stop-opacity="0"/></linearGradient></defs><path d="M0,190 L45,175 L90,120 L135,95 L180,115 L225,70 L270,90 L315,60 L360,75 L405,58 L450,35 L500,15 L500,230 L0,230 Z" fill="url(#fill)"/><polyline points="0,190 45,175 90,120 135,95 180,115 225,70 270,90 315,60 360,75 405,58 450,35 500,15" fill="none" stroke="#2563eb" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg><div class="axis"><span>06 Apr</span><span>16 Apr</span><span>26 Apr</span><span>05 Maj</span></div></div></section>
  <section class="mock-card low-card"><div class="card-head"><h2>Produkte me stok të ulët</h2><a href="alerts.php">Shiko të gjitha</a></div><?php foreach($critical as $c): ?><div class="low-row"><div class="mini-thumb">I</div><b><?=e($c['emri'])?></b><span>Stok: <?=e($c['sasia_ne_stok'])?></span></div><?php endforeach; if(!$critical): ?><p class="muted">Nuk ka produkte me stok të ulët.</p><?php endif; ?></section>
  <section class="mock-card"><div class="card-head"><h2>Lëvizjet më aktive (30 ditë)</h2></div><?php foreach($movement as $m): $w=round(((int)$m['dalje'])/$maxMove*100); ?><div class="move-row"><span><?=e($m['emri'])?></span><div class="move-track"><i style="width:<?=$w?>%"></i></div><b><?=e($m['dalje'])?></b></div><?php endforeach; ?></section>
  <section class="mock-card"><div class="card-head"><h2>Njoftime të fundit</h2><a href="admin_logs.php">Shiko të gjitha</a></div><div class="notice-row ok-icon"><b>Porositë aktive</b><span><?=$orders?> porosi në sistem.</span></div><div class="notice-row truck-icon"><b>Pagesa</b><span><?=$paid?> pagesa të regjistruara.</span></div><div class="notice-row alert-icon"><b>Alarm stoku</b><span><?=$low?> produkte kanë nevojë për furnizim.</span></div></section>
  <section class="mock-card assistant-widget"><div class="card-head"><h2>Asistenti Virtual</h2><span class="active-dot">Aktiv</span></div><p>Pyet për stok, porosi, produkte dhe furnizim.</p><input class="assistant-input" placeholder="Shkruaj pyetjen tënde këtu..."><a class="btn primary wide" href="ai.php">Hap chatbot-in</a><div class="quick-tags"><span>Bëj raport inventari</span><span>Produktet kritike</span><span>Çfarë duhet furnizuar?</span></div></section>
</div>
<?php include 'includes/footer.php'; ?>
