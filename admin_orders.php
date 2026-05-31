<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_portal_schema($pdo);
if(isset($_GET['delete'])){
  $id=(int)$_GET['delete'];
  try{
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM Pagesat WHERE porosi_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM DetajetPorosise WHERE porosi_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM Porosite WHERE porosi_id=?')->execute([$id]);
    $pdo->commit();
  }catch(Exception $e){ if($pdo->inTransaction()) $pdo->rollBack(); }
  redirect('admin_orders.php');
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['status_id'])){
  $id=(int)$_POST['status_id'];
  $status=$_POST['statusi'] ?? 'Ne proces';
  $allowed=['Ne proces','Në pritje PayPal','E paguar','E perfunduar','Anuluar'];
  if(in_array($status,$allowed,true)) $pdo->prepare('UPDATE Porosite SET statusi=? WHERE porosi_id=?')->execute([$status,$id]);
  redirect('admin_orders.php');
}
$title='Porositë'; $subtitle='';
$status=trim($_GET['status']??''); $q=trim($_GET['q']??'');
$where=[]; $params=[];
if($status!==''){ $where[]='p.statusi=?'; $params[]=$status; }
if($q!==''){ $where[]='(k.emri LIKE ? OR k.email LIKE ? OR p.porosi_id LIKE ?)'; $params[]="%$q%"; $params[]="%$q%"; $params[]="%$q%"; }
$sql="SELECT p.*, k.emri klient, k.email, COUNT(dp.detaj_id) artikuj, COALESCE(SUM(dp.sasia),0) sasia_totale, COALESCE(MAX(pg.data_pageses),NULL) pagesa_date
      FROM Porosite p LEFT JOIN Klientet k ON k.klient_id=p.klient_id
      LEFT JOIN DetajetPorosise dp ON dp.porosi_id=p.porosi_id
      LEFT JOIN Pagesat pg ON pg.porosi_id=p.porosi_id".($where?' WHERE '.implode(' AND ',$where):'')."
      GROUP BY p.porosi_id ORDER BY p.data_porosise DESC LIMIT 250";
$st=$pdo->prepare($sql); $st->execute($params); $orders=$st->fetchAll();
$stats=['total'=>0,'paid'=>0,'pending'=>0,'value'=>0];
try{ $stats['total']=(int)$pdo->query('SELECT COUNT(*) FROM Porosite')->fetchColumn(); }catch(Exception $e){}
try{ $stats['paid']=(int)$pdo->query("SELECT COUNT(*) FROM Porosite WHERE statusi IN ('E paguar','E perfunduar')")->fetchColumn(); }catch(Exception $e){}
try{ $stats['pending']=(int)$pdo->query("SELECT COUNT(*) FROM Porosite WHERE statusi NOT IN ('E paguar','E perfunduar','Anuluar')")->fetchColumn(); }catch(Exception $e){}
try{ $stats['value']=(float)$pdo->query('SELECT COALESCE(SUM(totali),0) FROM Porosite')->fetchColumn(); }catch(Exception $e){}
include 'includes/header.php';
?>
<div class="grid dashboard-grid">
  <a class="card stat blue click-card" href="admin_orders.php"><h3>Porosi gjithsej</h3><div class="num"><?=e($stats['total'])?></div></a>
  <a class="card stat green click-card" href="admin_orders.php?status=E+paguar"><h3>Të paguara</h3><div class="num"><?=e($stats['paid'])?></div></a>
  <a class="card stat orange click-card" href="admin_orders.php?status=Ne+proces"><h3>Në proces</h3><div class="num"><?=e($stats['pending'])?></div></a>
  <div class="card stat purple"><h3>Vlera totale</h3><div class="num" style="font-size:25px"><?=money($stats['value'])?></div></div>
</div>
<div class="card" style="margin-top:18px">
  <form class="toolbar" method="get">
    <h2>Lista e porosive</h2>
    <input class="input portal-search" name="q" value="<?=e($q)?>" placeholder="Kërko klient/email/ID...">
    <select class="input" name="status">
      <option value="">Të gjitha statuset</option>
      <?php foreach(['Ne proces','Në pritje PayPal','E paguar','E perfunduar','Anuluar'] as $s): ?><option value="<?=e($s)?>" <?=$status===$s?'selected':''?>><?=e($s)?></option><?php endforeach; ?>
    </select>
    <button class="btn secondary">Filtro</button>
  </form>
  <div class="table-wrap"><table>
    <tr><th>ID</th><th>Data</th><th>Klienti</th><th>Artikuj</th><th>Sasia</th><th>Totali</th><th>Statusi</th><th>Pagesa</th><th>Veprime</th></tr>
    <?php foreach($orders as $o): ?>
    <tr>
      <td><b>#<?=e($o['porosi_id'])?></b></td><td><?=e($o['data_porosise'])?></td><td><b><?=e($o['klient'] ?? '-')?></b><br><span class="muted"><?=e($o['email'] ?? '')?></span></td>
      <td><?=e($o['artikuj'])?></td><td><?=e($o['sasia_totale'])?></td><td><?=money($o['totali'])?></td>
      <td><form method="post" class="inline-status"><input type="hidden" name="status_id" value="<?=$o['porosi_id']?>"><select name="statusi" class="input mini" onchange="this.form.submit()"><?php foreach(['Ne proces','Në pritje PayPal','E paguar','E perfunduar','Anuluar'] as $s): ?><option value="<?=e($s)?>" <?=$o['statusi']===$s?'selected':''?>><?=e($s)?></option><?php endforeach; ?></select></form></td>
      <td><?=e($o['pagesa_date'] ?: '-')?></td><td><a class="btn danger" data-confirm="Ta fshij këtë porosi?" href="admin_orders.php?delete=<?=$o['porosi_id']?>">Fshi</a></td>
    </tr>
    <?php endforeach; if(!$orders): ?><tr><td colspan="9" class="empty-state">Nuk ka porosi.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php include 'includes/footer.php'; ?>
