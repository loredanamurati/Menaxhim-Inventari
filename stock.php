<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_warehouse_schema($pdo);
try { $pdo->query("ALTER TABLE Daljet ADD COLUMN cmimi_njesi DECIMAL(10,2) NULL"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE Daljet ADD COLUMN totali DECIMAL(10,2) NULL"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE Hyrjet ADD COLUMN cmimi_njesi DECIMAL(10,2) NULL"); } catch(Exception $e){}
try { $pdo->query("ALTER TABLE Hyrjet ADD COLUMN totali DECIMAL(10,2) NULL"); } catch(Exception $e){}
$title='Hyrje / Dalje'; $subtitle='';
$msg=''; $selected=(int)($_GET['product']??0);
if(isset($_GET['delete_in'])){
    $id=(int)$_GET['delete_in'];
    $st=$pdo->prepare('SELECT produkt_id,sasia FROM Hyrjet WHERE hyrje_id=?'); $st->execute([$id]); $row=$st->fetch();
    if($row){ $pdo->beginTransaction(); $pdo->prepare('UPDATE Produktet SET sasia_ne_stok=GREATEST(sasia_ne_stok-?,0) WHERE produkt_id=?')->execute([$row['sasia'],$row['produkt_id']]); $pdo->prepare('DELETE FROM Hyrjet WHERE hyrje_id=?')->execute([$id]); $pdo->commit(); }
    redirect('stock.php');
}
if(isset($_GET['delete_out'])){
    $id=(int)$_GET['delete_out'];
    $st=$pdo->prepare('SELECT produkt_id,sasia FROM Daljet WHERE dalje_id=?'); $st->execute([$id]); $row=$st->fetch();
    if($row){ $pdo->beginTransaction(); $pdo->prepare('UPDATE Produktet SET sasia_ne_stok=sasia_ne_stok+? WHERE produkt_id=?')->execute([$row['sasia'],$row['produkt_id']]); $pdo->prepare('DELETE FROM Daljet WHERE dalje_id=?')->execute([$id]); $pdo->commit(); }
    redirect('stock.php');
}
$products=$pdo->query('SELECT produkt_id, emri, sasia_ne_stok, cmimi FROM Produktet ORDER BY emri')->fetchAll();
$sups=$pdo->query('SELECT furnizues_id, emri FROM Furnizuesit ORDER BY emri')->fetchAll();
$clients=$pdo->query('SELECT klient_id, emri FROM Klientet ORDER BY emri')->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $type=$_POST['type']; $pid=(int)$_POST['produkt_id']; $qty=(int)$_POST['sasia']; $selected=$pid;
    $st=$pdo->prepare('SELECT cmimi FROM Produktet WHERE produkt_id=?'); $st->execute([$pid]); $unit=(float)$st->fetchColumn();
    if($qty>0){
        if($type==='in'){
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE Produktet SET sasia_ne_stok=sasia_ne_stok+? WHERE produkt_id=?')->execute([$qty,$pid]);
            $pdo->prepare('INSERT INTO Hyrjet(produkt_id,furnizues_id,perdorues_id,sasia,cmimi_njesi,shenim) VALUES(?,?,?,?,?,?)')->execute([$pid,$_POST['furnizues_id'],$_SESSION['user']['perdorues_id'],$qty,$unit,$_POST['shenim']]);
            $pdo->commit(); $msg='Hyrja u regjistrua dhe stoku u rrit me sukses.';
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE Produktet SET sasia_ne_stok=GREATEST(sasia_ne_stok-?,0) WHERE produkt_id=?')->execute([$qty,$pid]);
            $pdo->prepare('INSERT INTO Daljet(produkt_id,perdorues_id,klient_id,sasia,cmimi_njesi,totali,arsye) VALUES(?,?,?,?,?,?,?)')->execute([$pid,$_SESSION['user']['perdorues_id'],$_POST['klient_id'],$qty,$unit,$unit*$qty,$_POST['shenim']]);
            $pdo->commit(); $msg='Dalja u regjistrua dhe stoku u ul me sukses.';
        }
    }
}
$mov=$pdo->query('(SELECT h.hyrje_id id, NULL dalje_id, "Hyrje" tipi, p.emri produkt, h.sasia, h.cmimi_njesi, (h.sasia*h.cmimi_njesi) totali, h.data_hyrjes data FROM Hyrjet h JOIN Produktet p ON p.produkt_id=h.produkt_id ORDER BY h.hyrje_id DESC LIMIT 20) UNION ALL (SELECT NULL id, d.dalje_id, "Dalje" tipi, p.emri produkt, d.sasia, COALESCE(d.cmimi_njesi,p.cmimi) AS cmimi_njesi, COALESCE(d.totali,d.sasia*p.cmimi) totali, d.data_daljes data FROM Daljet d JOIN Produktet p ON p.produkt_id=d.produkt_id ORDER BY d.dalje_id DESC LIMIT 20) ORDER BY data DESC LIMIT 25')->fetchAll();
include 'includes/header.php'; ?>
<?php if($msg): ?><div class="alert success"><?=e($msg)?> <a href="alerts.php"><b>Shiko alarmet</b></a></div><?php endif; ?>
<div class="section stock-layout-modern"><div class="card"><h2>Regjistro lëvizje stoku</h2><form method="post" class="form-grid"><div><label>Tipi</label><select name="type"><option value="in">Hyrje në stok (+)</option><option value="out">Dalje nga stok (-)</option></select></div><div><label>Produkti</label><select name="produkt_id" id="stockProductSelect"><?php foreach($products as $p): ?><option value="<?=$p['produkt_id']?>" data-price="<?=$p['cmimi']?>" <?=$selected==$p['produkt_id']?'selected':''?>><?=e($p['emri'])?> (stok: <?=$p['sasia_ne_stok']?>)</option><?php endforeach; ?></select></div><div><label>Furnizuesi</label><select name="furnizues_id"><?php foreach($sups as $s): ?><option value="<?=$s['furnizues_id']?>"><?=e($s['emri'])?></option><?php endforeach; ?></select></div><div><label>Klienti</label><select name="klient_id"><?php foreach($clients as $c): ?><option value="<?=$c['klient_id']?>"><?=e($c['emri'])?></option><?php endforeach; ?></select></div><div><label>Sasia</label><input class="input" type="number" name="sasia" min="1" required></div><div><label>Çmimi njësi</label><input id="unitPrice" class="input" type="number" step="0.01" name="cmimi_njesi" readonly></div><div><label>Çmimi total</label><input id="totalPrice" class="input" type="number" step="0.01" readonly></div><div class="full"><label>Shënim</label><textarea class="input" name="shenim"></textarea></div><div class="full"><button class="btn primary">Ruaj lëvizjen</button></div></form></div><div class="card"><h2>Lëvizjet e fundit</h2><div class="movement-list"><?php foreach($mov as $m): ?><div class="movement-item"><div><b><?=e($m['tipi'])?>:</b> <?=e($m['produkt'])?><br><span class="muted">Sasia: <?=e($m['sasia'])?> • Njësi: <?=money($m['cmimi_njesi'])?> • Total: <?=money($m['totali'])?></span></div><?php if($m['tipi']==='Hyrje'): ?><a class="btn danger small" data-confirm="Ta fshij këtë hyrje? Stoku do të korrigjohet." href="stock.php?delete_in=<?=$m['id']?>">Fshi</a><?php else: ?><a class="btn danger small" data-confirm="Ta fshij këtë dalje? Stoku do të korrigjohet." href="stock.php?delete_out=<?=$m['dalje_id']?>">Fshi</a><?php endif; ?></div><?php endforeach; ?></div></div></div>
<script>
function syncPrice(){
  const s=document.getElementById('stockProductSelect');
  const p=parseFloat(s.options[s.selectedIndex]?.dataset.price||0);
  const q=parseInt(document.querySelector('input[name=sasia]')?.value||1,10);
  document.getElementById('unitPrice').value=p.toFixed(2);
  document.getElementById('totalPrice').value=(p*Math.max(q,1)).toFixed(2);
}
document.getElementById('stockProductSelect').addEventListener('change',syncPrice);
document.querySelector('input[name=sasia]').addEventListener('input',syncPrice);
syncPrice();
</script>
<?php include 'includes/footer.php'; ?>
