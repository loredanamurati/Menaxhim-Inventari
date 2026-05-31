<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_once 'config/paypal.php'; ensure_portal_schema($pdo); ensure_warehouse_schema($pdo);
if(empty($_SESSION['supplier'])) redirect('supplier_login.php');
$supplier=$_SESSION['supplier']; $sid=(int)$supplier['furnizues_id']; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $pid=(int)($_POST['produkt_id']??0); $qty=max(1,(int)($_POST['sasia']??1));
  $st=$pdo->prepare('SELECT produkt_id, emri, cmimi, sasia_ne_stok FROM Produktet WHERE produkt_id=? AND furnizues_id=?'); $st->execute([$pid,$sid]); $p=$st->fetch();
  if(!$p) $err='Produkti nuk u gjet për këtë furnizues.';
  else {
    $unit = isset($_POST['cmimi_njesi']) && $_POST['cmimi_njesi']!=='' ? max(0,(float)$_POST['cmimi_njesi']) : (float)$p['cmimi'];
    $total=$qty*$unit;
    $ins=$pdo->prepare('INSERT INTO SupplierSales(furnizues_id, produkt_id, sasia, cmimi_total, statusi) VALUES(?,?,?,?,?)');
    $ins->execute([$sid,$pid,$qty,$total,'Në pritje PayPal']);
    redirect('supplier_paypal_checkout.php?sale_id='.$pdo->lastInsertId());
  }
}
$st=$pdo->prepare('SELECT p.produkt_id, p.emri, p.barkodi, p.sasia_ne_stok, p.stok_minimal, p.stok_maksimal, p.cmimi, k.emri kategori FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id WHERE p.furnizues_id=? ORDER BY p.emri'); $st->execute([$sid]); $products=$st->fetchAll();
$history=[]; try{ $hs=$pdo->prepare('SELECT ss.*, p.emri produkt FROM SupplierSales ss LEFT JOIN Produktet p ON p.produkt_id=ss.produkt_id WHERE ss.furnizues_id=? ORDER BY ss.data_krijimit DESC LIMIT 10'); $hs->execute([$sid]); $history=$hs->fetchAll(); }catch(Exception $e){}
$totalProducts=count($products); $need=0; $over=0; foreach($products as $p){ if($p['sasia_ne_stok'] <= $p['stok_minimal']) $need++; if($p['sasia_ne_stok'] >= $p['stok_maksimal']) $over++; }
?>
<?php $portalRole='supplier'; $portalUser=$supplier; $pageTitle='Furnizo produkte'; include 'includes/portal_header.php'; ?>

<?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<div class="supplier-history-row action-kpi-row">
<a class="supplier-mini-card" href="supplier_products.php"><span class="muted">Produktet e mia</span><h2><?=$totalProducts?></h2></a>
<a class="supplier-mini-card" href="supplier_requests.php"><span class="muted">Duhen furnizuar</span><h2><?=$need?></h2></a>
<a class="supplier-mini-card" href="supplier_analytics.php"><span class="muted">Mbi maksimum</span><h2><?=$over?></h2></a>
</div>
<div class="supplier-action-grid"><section class="card"><h2>Furnizo produkte</h2><p class="muted">Zgjidh produktin, vendos sasinë dhe regjistro furnizimin.</p><form method="post" class="form-clean" id="supplyForm"><label>Produkti</label><select class="input" name="produkt_id" id="produktSelect" required><?php foreach($products as $p):?><option value="<?=$p['produkt_id']?>" data-stock="<?=e($p['sasia_ne_stok'])?>" data-price="<?=e($p['cmimi'])?>" data-min="<?=e($p['stok_minimal'])?>" data-max="<?=e($p['stok_maksimal'])?>"><?=e($p['emri'])?> · <?=e($p['kategori'])?> · stok <?=e($p['sasia_ne_stok'])?></option><?php endforeach;?></select><br><br><div class="form-grid"><div><label>Sasia për furnizim</label><input class="input" type="number" name="sasia" id="qtyInput" min="1" value="1" required></div><div><label>Çmimi/njësi</label><input class="input" type="number" step="0.01" name="cmimi_njesi" id="unitPrice" readonly></div></div><br><button class="btn primary">Vazhdo pagesën</button></form></section><aside class="supply-summary"><h2>Përmbledhje</h2><p class="muted">Kontroll para konfirmimit.</p><div class="ai-metric"><span>Stoku aktual</span><b id="curStock">0</b></div><div class="ai-metric"><span>Stoku pas furnizimit</span><b id="afterStock">0</b></div><div class="ai-metric"><span>Totali për pagesë</span><b id="totalPay">0.00 €</b></div><p id="stockAdvice" class="pay-note">Zgjidh produktin dhe sasinë.</p></aside></div>
<section class="card" style="margin-top:20px"><div class="section-head"><div><h2>Historia e furnizimeve</h2><p class="muted">Furnizimet e fundit të nisura nga ky panel.</p></div><a class="btn secondary" href="supplier_ai.php">Analizo</a></div><div class="table-wrap"><table><tr><th>Data</th><th>Produkt</th><th>Sasia</th><th>Totali</th><th>Statusi</th></tr><?php foreach($history as $h):?><tr><td><?=e($h['data_krijimit'])?></td><td><?=e($h['produkt'])?></td><td><?=e($h['sasia'])?></td><td><?=money($h['cmimi_total'])?></td><td><span class="badge warn"><?=e($h['statusi'])?></span></td></tr><?php endforeach; if(!$history):?><tr><td colspan="5" class="empty-state">Nuk ka ende furnizime të regjistruara.</td></tr><?php endif;?></table></div></section>
<script>
const sel=document.getElementById('produktSelect'), qty=document.getElementById('qtyInput'), unit=document.getElementById('unitPrice');

function setUnitPriceFromProduct(){
    if(!sel || !sel.selectedOptions.length || !unit) return;
    const o=sel.selectedOptions[0];
    unit.value=Number(o.dataset.price || 0).toFixed(2);
}

function calc(){
    if(!sel || !sel.selectedOptions.length) return;
    const o=sel.selectedOptions[0];
    const stock=Number(o.dataset.stock || 0);
    const price=Number(unit.value || o.dataset.price || 0);
    const q=Number(qty.value || 1);
    const min=Number(o.dataset.min || 0);
    const max=Number(o.dataset.max || 0);

    document.getElementById('curStock').textContent=stock;
    document.getElementById('afterStock').textContent=stock+q;
    document.getElementById('totalPay').textContent=(price*q).toFixed(2)+' €';

    let advice='Furnizimi duket në rregull.';
    if(stock+q<min) advice='Edhe pas furnizimit, produkti mbetet nën minimum.';
    if(stock+q>max) advice='Kujdes: pas furnizimit kalon mbi maksimum.';
    document.getElementById('stockAdvice').textContent=advice;
}

if(sel){
    sel.addEventListener('change', function(){
        setUnitPriceFromProduct();
        calc();
    });
}

if(qty) qty.addEventListener('input', calc);
if(unit) unit.addEventListener('input', calc);

setUnitPriceFromProduct();
calc();
</script>
<?php include 'includes/portal_footer.php'; ?>
