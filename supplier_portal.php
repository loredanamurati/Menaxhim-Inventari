<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; ensure_warehouse_schema($pdo);
if(empty($_SESSION['supplier'])) redirect('supplier_login.php');
$supplier=$_SESSION['supplier']; $sid=(int)$supplier['furnizues_id'];

$st=$pdo->prepare('SELECT p.*, k.emri kategori FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id WHERE p.furnizues_id=? ORDER BY p.emri');
$st->execute([$sid]);
$products=$st->fetchAll();

$low=0; $high=0; $value=0; $stockTotal=0;
foreach($products as $p){
    if($p['sasia_ne_stok'] <= $p['stok_minimal']) $low++;
    if($p['sasia_ne_stok'] >= $p['stok_maksimal']) $high++;
    $stockTotal += (int)$p['sasia_ne_stok'];
    $value += ((float)$p['cmimi']*(int)$p['sasia_ne_stok']);
}

$sales=[];
try{
    $hs=$pdo->prepare('SELECT ss.*, p.emri produkt FROM SupplierSales ss LEFT JOIN Produktet p ON p.produkt_id=ss.produkt_id WHERE ss.furnizues_id=? ORDER BY ss.data_krijimit DESC LIMIT 6');
    $hs->execute([$sid]);
    $sales=$hs->fetchAll();
}catch(Exception $e){}

$top=array_slice($products,0,6);
$maxStock=max(1,...array_map(fn($x)=>(int)$x['sasia_ne_stok'],$top ?: [['sasia_ne_stok'=>1]]));

$portalRole='supplier'; $portalUser=$supplier; $pageTitle='Dashboard'; include 'includes/portal_header.php';
?>

<section class="saas-hero supplier-hero clean-dashboard-hero">
  <div>
    <span class="eyebrow">Dashboard furnitori</span>
    <h2><?=e($supplier['emri'])?></h2>
    <p>Përmbledhje e thjeshtë për produktet, stokun dhe vlerën totale të inventarit.</p>
  </div>
  <div class="hero-actions">
    <a class="btn secondary" href="supplier_ai.php">Asistenti</a>
  </div>
</section>

<div class="grid saas-kpis compact-kpis clean-kpis">
    <a href="supplier_products.php" class="card kpi-card blue">
        <span>Produktet e mia</span>
        <b><?=count($products)?></b>
        <small>produkte aktive</small>
    </a>
    <div class="card kpi-card green">
        <span>Sasia totale</span>
        <b><?=$stockTotal?></b>
        <small>copë në inventar</small>
    </div>
    <div class="card kpi-card orange">
        <span>Stok kritik</span>
        <b><?=$low?></b>
        <small>produkte nën minimum</small>
    </div>
    <div class="card kpi-card violet">
        <span>Vlera e stokut</span>
        <b><?=money($value)?></b>
        <small>vlerë e llogaritur</small>
    </div>
</div>

<div class="saas-layout alt clean-dashboard-grid">
  <div class="card clean-panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Stoku</span>
            <h2>Gjendja e produkteve</h2>
            <p class="muted">Produktet kryesore të lidhura me furnitorin.</p>
        </div>
    </div>

    <div class="bar-chart modern-bars slim">
        <?php foreach($top as $p): $w=round(((int)$p['sasia_ne_stok'])/$maxStock*100); ?>
        <div class="bar-row">
            <span><?=e($p['emri'])?></span>
            <div class="bar-track"><i style="width:<?=$w?>%"></i></div>
            <b><?=e($p['sasia_ne_stok'])?></b>
        </div>
        <?php endforeach; if(!$top): ?>
        <div class="empty-state">Nuk ka produkte të lidhura me këtë furnitor.</div>
        <?php endif; ?>
    </div>
  </div>

  <div class="card insight-card clean-panel">
    <span class="eyebrow">Përmbledhje</span>
    <h2>Inventari i furnitorit</h2>
    <div class="big-number"><?=money($value)?></div>
    <p class="muted">Kjo vlerë llogaritet nga çmimi dhe sasia aktuale e produkteve.</p>
    <div class="clean-mini-list">
        <span><b><?=$low?></b> produkte me stok kritik</span>
        <span><b><?=$high?></b> produkte mbi maksimum</span>
    </div>
  </div>
</div>

<div class="saas-layout alt clean-dashboard-grid">
  <div class="card clean-panel">
    <div class="section-head">
        <div>
            <span class="eyebrow">Aktiviteti</span>
            <h2>Lëvizjet e fundit</h2>
            <p class="muted">Regjistrimet e fundit të furnizimit në sistem.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <tr><th>Data</th><th>Produkt</th><th>Sasia</th><th>Totali</th><th>Status</th></tr>
            <?php foreach($sales as $s): ?>
            <tr>
                <td><?=e($s['data_krijimit'])?></td>
                <td><?=e($s['produkt'] ?? '-')?></td>
                <td><?=e($s['sasia'])?></td>
                <td><?=money($s['cmimi_total'])?></td>
                <td><span class="badge ok"><?=e($s['statusi'])?></span></td>
            </tr>
            <?php endforeach; if(!$sales): ?>
            <tr><td colspan="5" class="empty-state">Nuk ka ende lëvizje të regjistruara.</td></tr>
            <?php endif; ?>
        </table>
    </div>
  </div>

  <div class="card assistant-card visible-card clean-panel">
    <span class="eyebrow">Asistenti Virtual</span>
    <h2>Ndihmë për stokun</h2>
    <p class="muted">Pyet për gjendjen e produkteve, stokun kritik dhe rekomandime të përgjithshme.</p>
    <a class="btn primary wide" href="supplier_ai.php">Hap chatbot-in</a>
  </div>
</div>

<?php include 'includes/portal_footer.php'; ?>
