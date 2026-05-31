<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login();
$title='Alarmet'; $subtitle='Monitoro produktet me stok të ulët ose të lartë.';
$filter=$_GET['filter'] ?? 'all';
$where='(p.sasia_ne_stok <= p.stok_minimal OR p.sasia_ne_stok >= p.stok_maksimal)';
if($filter==='low') $where='p.sasia_ne_stok <= p.stok_minimal';
if($filter==='high') $where='p.sasia_ne_stok >= p.stok_maksimal';
$rows=[];
try{
  $st=$pdo->prepare("SELECT p.*, k.emri kategori, f.emri furnizues FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id LEFT JOIN Furnizuesit f ON f.furnizues_id=p.furnizues_id WHERE $where ORDER BY CASE WHEN p.sasia_ne_stok <= p.stok_minimal THEN 0 ELSE 1 END, p.sasia_ne_stok ASC");
  $st->execute(); $rows=$st->fetchAll();
}catch(Exception $e){}
$low=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok <= stok_minimal')->fetchColumn();
$high=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok >= stok_maksimal')->fetchColumn();
$total=$low+$high;
include 'includes/header.php'; ?>
<section class="alerts-hero card">
  <div><span class="eyebrow">Alarme stoku</span><h1>Kontrolli i stokut</h1><p class="muted">Filtro produktet sipas stokut të ulët ose stokut të lartë.</p></div>
  <div class="alert-filter-tabs">
    <a class="pill <?=$filter==='all'?'active':''?>" href="alerts.php?filter=all">Të gjitha</a>
    <a class="pill low <?=$filter==='low'?'active':''?>" href="alerts.php?filter=low">Stok i ulët</a>
    <a class="pill high <?=$filter==='high'?'active':''?>" href="alerts.php?filter=high">Stok i lartë</a>
  </div>
</section>
<div class="grid alert-kpis">
  <a class="card alert-kpi low" href="alerts.php?filter=low"><div class="alert-icon-big">↘</div><span>Stok i ulët</span><b><?=$low?></b><small>produkte nën minimum</small></a>
  <a class="card alert-kpi high" href="alerts.php?filter=high"><div class="alert-icon-big">↗</div><span>Stok i lartë</span><b><?=$high?></b><small>produkte mbi maksimum</small></a>
  <a class="card alert-kpi all" href="alerts.php?filter=all"><div class="alert-icon-big">🔔</div><span>Të gjitha alarmet</span><b><?=$total?></b><small>alarme aktive</small></a>
</div>
<section class="card alert-table-card">
  <div class="section-head"><h2><?= $filter==='low'?'Produktet me stok të ulët':($filter==='high'?'Produktet me stok të lartë':'Lista e alarmeve') ?></h2><a class="btn secondary small" href="products.php">Shiko produktet</a></div>
  <div class="table-wrap"><table class="modern-table"><tr><th>Produkti</th><th>Kategoria</th><th>Furnizuesi</th><th>Stoku aktual</th><th>Pragu</th><th>Tipi</th><th>Veprim</th></tr>
  <?php foreach($rows as $r): $isLow=$r['sasia_ne_stok'] <= $r['stok_minimal']; ?>
    <tr><td><b><?=e($r['emri'])?></b><br><span class="muted"><?=e($r['barkodi'])?></span></td><td><?=e($r['kategori'])?></td><td><?=e($r['furnizues'])?></td><td><b class="<?=$isLow?'text-red':'text-green'?>"><?=e($r['sasia_ne_stok'])?></b></td><td><?= $isLow ? '≤ '.e($r['stok_minimal']) : '≥ '.e($r['stok_maksimal']) ?></td><td><span class="badge <?=$isLow?'low':'ok'?>"><?=$isLow?'Stok i ulët':'Stok i lartë'?></span></td><td><a class="btn secondary small" href="stock.php?product=<?=$r['produkt_id']?>">Lëviz stokun</a></td></tr>
  <?php endforeach; if(!$rows): ?><tr><td colspan="7" class="empty-state">Nuk ka produkte për këtë filtër.</td></tr><?php endif; ?></table></div>
</section>
<?php include 'includes/footer.php'; ?>
