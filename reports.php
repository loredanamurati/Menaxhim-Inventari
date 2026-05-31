<?php session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_warehouse_schema($pdo); $title='Raporte'; $subtitle='';
$products=$pdo->query('SELECT COUNT(*) c FROM Produktet')->fetch()['c'];
$suppliers=$pdo->query('SELECT COUNT(*) c FROM Furnizuesit')->fetch()['c'];
$entries=$pdo->query('SELECT COUNT(*) c FROM Hyrjet')->fetch()['c'];
$exits=$pdo->query('SELECT COUNT(*) c FROM Daljet')->fetch()['c'];
$warehouses=$pdo->query('SELECT COUNT(*) c FROM Magazinat')->fetch()['c'];
$low=$pdo->query('SELECT COUNT(*) c FROM Produktet WHERE sasia_ne_stok <= stok_minimal')->fetch()['c'];
$value=$pdo->query('SELECT COALESCE(SUM(cmimi*sasia_ne_stok),0) v FROM Produktet')->fetch()['v'];
include 'includes/header.php'; ?>
<section class="page-hero clean-hero"><div><span class="eyebrow">Raporte</span><h1>Raporte</h1></div></section>
<div class="grid saas-kpis compact-kpis">
  <div class="card kpi-card blue"><span>Produkte</span><b><?=$products?></b><small>regjistrime</small></div>
  <div class="card kpi-card green"><span>Furnizues</span><b><?=$suppliers?></b><small>aktivë</small></div>
  <div class="card kpi-card orange"><span>Hyrje</span><b><?=$entries?></b><small>lëvizje</small></div>
  <div class="card kpi-card red"><span>Dalje</span><b><?=$exits?></b><small>lëvizje</small></div>
</div>
<div class="saas-layout reports-layout">
  <div class="card action-panel">
    <div class="section-head"><h2>Eksportim të dhënash</h2></div>
    <div class="actions-grid clean-actions">
      <a class="btn primary" href="export_csv.php?type=products">Produktet</a>
      <a class="btn secondary" href="export_csv.php?type=suppliers">Furnizuesit</a>
      <a class="btn secondary" href="export_csv.php?type=entries">Hyrjet</a>
      <a class="btn secondary" href="export_csv.php?type=exits">Daljet</a>
      <a class="btn secondary" href="export_csv.php?type=alerts">Alarmet</a>
      <a class="btn secondary" href="export_csv.php?type=warehouses">Magazinat</a>
      <a class="btn secondary" href="export_pdf.php" target="_blank">Raport PDF</a>
    </div>
  </div>
  <div class="card summary-panel">
    <h2>Përmbledhje</h2>
    <div class="summary-row"><span>Vlera totale</span><b><?=money($value)?></b></div>
    <div class="summary-row"><span>Stok i ulët</span><b><?=$low?></b></div>
    <div class="summary-row"><span>Hyrje/Dalje</span><b><?=$entries + $exits?></b></div>
    <div class="summary-row"><span>Magazina</span><b><?=$warehouses?></b></div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
