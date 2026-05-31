<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_portal_schema($pdo);
if(isset($_GET['delete_client'])){
  $id=(int)$_GET['delete_client'];
  try{
    $pdo->beginTransaction();
    $orders=$pdo->prepare('SELECT porosi_id FROM Porosite WHERE klient_id=?'); $orders->execute([$id]);
    foreach($orders->fetchAll(PDO::FETCH_COLUMN) as $oid){
      $pdo->prepare('DELETE FROM Pagesat WHERE porosi_id=?')->execute([$oid]);
      $pdo->prepare('DELETE FROM DetajetPorosise WHERE porosi_id=?')->execute([$oid]);
    }
    $pdo->prepare('DELETE FROM Porosite WHERE klient_id=?')->execute([$id]);
    try{ $pdo->prepare('DELETE FROM Daljet WHERE klient_id=?')->execute([$id]); }catch(Exception $e){}
    $pdo->prepare('DELETE FROM Klientet WHERE klient_id=?')->execute([$id]);
    $pdo->commit();
  }catch(Exception $e){ if($pdo->inTransaction()) $pdo->rollBack(); }
  redirect('portal_accounts.php');
}
if(isset($_GET['delete_supplier'])){
  $id=(int)$_GET['delete_supplier'];
  try{
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE Produktet SET furnizues_id=NULL WHERE furnizues_id=?')->execute([$id]);
    try{ $pdo->prepare('DELETE FROM Hyrjet WHERE furnizues_id=?')->execute([$id]); }catch(Exception $e){}
    $pdo->prepare('DELETE FROM Furnizuesit WHERE furnizues_id=?')->execute([$id]);
    $pdo->commit();
  }catch(Exception $e){ if($pdo->inTransaction()) $pdo->rollBack(); }
  redirect('portal_accounts.php');
}
$title='Llogaritë e Portalit'; $subtitle='';
$clients=$pdo->query('SELECT klient_id, emri, telefoni, email, adresa, qyteti, password FROM Klientet ORDER BY klient_id DESC')->fetchAll();
$suppliers=$pdo->query('SELECT furnizues_id, emri, person_kontakti, telefoni, email, adresa, qyteti, password FROM Furnizuesit ORDER BY furnizues_id DESC')->fetchAll();
include 'includes/header.php'; ?>
<div class="grid two-col">
  <div class="card">
    <div class="section-head"><div><h2>Klientët</h2></div><a class="btn secondary" href="customers.php">Menaxho klientët</a></div>
    <div class="table-wrap"><table>
      <tr><th>#</th><th>Klienti</th><th>Email</th><th>Password</th><th>Qyteti</th><th>Veprime</th></tr>
      <?php foreach($clients as $c): ?>
      <tr>
        <td><?=e($c['klient_id'])?></td>
        <td><b><?=e($c['emri'])?></b><br><span class="muted"><?=e($c['telefoni'])?></span></td>
        <td><?=e($c['email'])?></td>
        <td><button type="button" class="btn secondary reveal-pass-btn" data-password="<?=e($c['password'] ?: '12345')?>">Shfaq password</button><span class="revealed-pass"></span></td>
        <td><?=e($c['qyteti'])?></td>
        <td><a class="btn danger" data-confirm="Ta fshij këtë klient?" href="portal_accounts.php?delete_client=<?=$c['klient_id']?>">Fshi</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$clients): ?><tr><td colspan="6" class="empty-state">Nuk ka klientë.</td></tr><?php endif; ?>
    </table></div>
  </div>

  <div class="card">
    <div class="section-head"><div><h2>Furnizuesit</h2></div><a class="btn secondary" href="suppliers.php">Menaxho furnizuesit</a></div>
    <div class="table-wrap"><table>
      <tr><th>#</th><th>Furnizuesi</th><th>Email</th><th>Password</th><th>Qyteti</th><th>Veprime</th></tr>
      <?php foreach($suppliers as $s): ?>
      <tr>
        <td><?=e($s['furnizues_id'])?></td>
        <td><b><?=e($s['emri'])?></b><br><span class="muted"><?=e($s['person_kontakti'])?></span></td>
        <td><?=e($s['email'])?></td>
        <td><button type="button" class="btn secondary reveal-pass-btn" data-password="<?=e($s['password'] ?: '12345')?>">Shfaq password</button><span class="revealed-pass"></span></td>
        <td><?=e($s['qyteti'])?></td>
        <td><a class="btn danger" data-confirm="Ta fshij këtë furnizues?" href="portal_accounts.php?delete_supplier=<?=$s['furnizues_id']?>">Fshi</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$suppliers): ?><tr><td colspan="6" class="empty-state">Nuk ka furnizues.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
