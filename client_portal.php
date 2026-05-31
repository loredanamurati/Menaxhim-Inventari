<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; ensure_portal_schema($pdo); ensure_warehouse_schema($pdo);
if(empty($_SESSION['client'])) redirect('client_login.php');
$client=$_SESSION['client']; $cid=(int)$client['klient_id'];

$total=(int)$pdo->query('SELECT COUNT(*) FROM Produktet')->fetchColumn();
$available=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok > 0')->fetchColumn();
$out=(int)$pdo->query('SELECT COUNT(*) FROM Produktet WHERE sasia_ne_stok <= 0')->fetchColumn();

$spentStmt=$pdo->prepare("SELECT COALESCE(SUM(totali),0) FROM Porosite WHERE klient_id=? AND statusi LIKE '%paguar%'");
$spentStmt->execute([$cid]);
$spent=(float)$spentStmt->fetchColumn();

$popular=$pdo->query('SELECT p.emri, COALESCE(SUM(d.sasia),0) total FROM Produktet p LEFT JOIN DetajetPorosise d ON d.produkt_id=p.produkt_id GROUP BY p.produkt_id ORDER BY total DESC LIMIT 6')->fetchAll();

$latestProducts=$pdo->query('SELECT p.emri, p.sasia_ne_stok, p.cmimi, k.emri kategori FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id ORDER BY p.produkt_id DESC LIMIT 6')->fetchAll();

$portalRole='client'; $portalUser=$client; $pageTitle='Dashboard'; include 'includes/portal_header.php';
?>

<section class="saas-hero client-hero clean-dashboard-hero">
  <div>
    <span class="eyebrow">Dashboard klienti</span>
    <h2>Mirë se erdhe, <?=e($client['emri'])?></h2>
    <p>Përmbledhje e produkteve, stokut dhe aktivitetit në sistem.</p>
  </div>
  <div class="hero-actions">
    <a class="btn secondary" href="client_ai.php">Asistenti</a>
  </div>
</section>

<div class="grid saas-kpis compact-kpis clean-kpis">
  <a href="client_shop.php" class="card kpi-card blue">
    <span>Produkte</span>
    <b><?=e($total)?></b>
    <small>në katalog</small>
  </a>
  <a href="client_shop.php?status=in" class="card kpi-card green">
    <span>Në stok</span>
    <b><?=e($available)?></b>
    <small>gati për blerje</small>
  </a>
  <a href="client_shop.php?status=out" class="card kpi-card orange">
    <span>Jashtë stoku</span>
    <b><?=e($out)?></b>
    <small>pa gjendje</small>
  </a>
  <div class="card kpi-card violet">
    <span>Vlera e blerjeve</span>
    <b><?=money($spent)?></b>
    <small>porosi të paguara</small>
  </div>
</div>

<div class="saas-layout alt clean-dashboard-grid">
  <div class="card clean-panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Katalogu</span>
            <h2>Produktet më të kërkuara</h2>
            <p class="muted">Produktet me më shumë lëvizje në sistem.</p>
        </div>
    </div>

    <div class="bar-chart modern-bars slim">
        <?php $max=max(1,...array_map(fn($x)=>(int)$x['total'],$popular ?: [['total'=>1]])); foreach($popular as $p): $w=round(((int)$p['total'])/$max*100); ?>
        <div class="bar-row">
            <span><?=e($p['emri'])?></span>
            <div class="bar-track"><i style="width:<?=$w?>%"></i></div>
            <b><?=e($p['total'])?></b>
        </div>
        <?php endforeach; if(!$popular): ?>
        <div class="empty-state">Nuk ka ende të dhëna për produktet.</div>
        <?php endif; ?>
    </div>
  </div>

  <div class="card insight-card clean-panel">
    <span class="eyebrow">Përmbledhje</span>
    <h2>Gjendja e katalogut</h2>
    <div class="big-number"><?=e($available)?></div>
    <p class="muted">Produkte aktualisht të disponueshme për blerje.</p>
    <div class="clean-mini-list">
        <span><b><?=e($total)?></b> produkte gjithsej</span>
        <span><b><?=e($out)?></b> jashtë stoku</span>
    </div>
  </div>
</div>

<div class="saas-layout alt clean-dashboard-grid">
  <div class="card clean-panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Të reja</span>
            <h2>Produktet e fundit</h2>
            <p class="muted">Produktet e shtuara së fundmi në sistem.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <tr><th>Produkt</th><th>Kategoria</th><th>Stoku</th><th>Çmimi</th></tr>
            <?php foreach($latestProducts as $p): ?>
            <tr>
                <td><?=e($p['emri'])?></td>
                <td><?=e($p['kategori'] ?? '-')?></td>
                <td><span class="badge <?=$p['sasia_ne_stok']>0?'ok':'low'?>"><?=e($p['sasia_ne_stok'])?></span></td>
                <td><?=money($p['cmimi'])?></td>
            </tr>
            <?php endforeach; if(!$latestProducts): ?>
            <tr><td colspan="4" class="empty-state">Nuk ka produkte të regjistruara.</td></tr>
            <?php endif; ?>
        </table>
    </div>
  </div>

  <div class="card assistant-card visible-card clean-panel">
    <span class="eyebrow">Asistenti Virtual</span>
    <h2>Ndihmë për produktet</h2>
    <p class="muted">Pyet për stokun, kategoritë, çmimet dhe informacionet e produkteve.</p>
    <a href="client_ai.php" class="btn primary wide">Hap chatbot-in</a>
  </div>
</div>

<?php include 'includes/portal_footer.php'; ?>
