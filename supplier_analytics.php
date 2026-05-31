<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; if(empty($_SESSION['supplier'])) redirect('supplier_login.php'); $supplier=$_SESSION['supplier']; $sid=(int)$supplier['furnizues_id'];
$products=$pdo->prepare('SELECT emri,sasia_ne_stok,stok_minimal,stok_maksimal FROM Produktet WHERE furnizues_id=? ORDER BY sasia_ne_stok DESC LIMIT 8'); $products->execute([$sid]); $products=$products->fetchAll(); $max=max(1,...array_map(fn($x)=>(int)$x['sasia_ne_stok'],$products ?: [['sasia_ne_stok'=>1]]));
$portalRole='supplier'; $portalUser=$supplier; $pageTitle='Analiza furnizimi'; include 'includes/portal_header.php'; ?>
<section class="page-title-card"><div><span class="eyebrow">Furnizues</span><h1>Analiza e furnizimit</h1><p class="muted">Kontroll i stokut, produkteve që duhen furnizuar dhe rekomandimeve.</p></div><a class="btn primary" href="supplier_sell.php">Furnizo mall</a></section>
<?php
$lowCount=0; $highCount=0; $totalStock=0; foreach($products as $pp){ $totalStock+=(int)$pp['sasia_ne_stok']; if($pp['sasia_ne_stok'] <= $pp['stok_minimal']) $lowCount++; if($pp['sasia_ne_stok'] >= $pp['stok_maksimal']) $highCount++; }
?>
<div class="grid saas-kpis compact-kpis supplier-analytics-kpis">
  <a class="card kpi-card blue" href="supplier_products.php"><span>Produkte aktive</span><b><?=count($products)?></b><small>në portofol</small></a>
  <a class="card kpi-card orange" href="supplier_requests.php"><span>Duhet furnizim</span><b><?=$lowCount?></b><small>nën minimum</small></a>
  <a class="card kpi-card green" href="supplier_sell.php"><span>Stok total</span><b><?=$totalStock?></b><small>copë në magazina</small></a>
  <a class="card kpi-card violet" href="supplier_analytics.php"><span>Mbi maksimum</span><b><?=$highCount?></b><small>kontroll porosie</small></a>
</div>
<div class="soft-grid supplier-analytics-layout">
  <div class="card visual-card"><div class="section-head"><div><h2>Gjendja e stokut</h2><p class="muted">Produktet kryesore sipas sasisë aktuale.</p></div><a class="btn secondary small" href="supplier_products.php">Produktet e mia</a></div><div class="bar-chart modern-bars"><?php foreach($products as $p): $w=round(((int)$p['sasia_ne_stok'])/$max*100); ?><a class="bar-row clickable-row" href="supplier_sell.php?product=<?=$p['produkt_id']??''?>"><span><?=e($p['emri'])?></span><div class="bar-track"><i style="width:<?=$w?>%"></i></div><b><?=e($p['sasia_ne_stok'])?></b></a><?php endforeach; ?></div></div>
  <div class="card visual-card"><h2>Plan furnizimi</h2><p class="muted">Rekomandim automatik për të nisur me produktet nën minimum.</p><div class="supplier-insights"><div><b><?=$lowCount?></b><span>produkte kërkojnë furnizim</span></div><div><b><?=$highCount?></b><span>produkte janë mbi maksimum</span></div><div><b><?=count($products)?></b><span>produkte aktive</span></div></div><a class="btn primary wide" href="supplier_requests.php">Shiko kërkesat</a></div>
</div><?php include 'includes/portal_footer.php'; ?>
