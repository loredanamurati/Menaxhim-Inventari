<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_warehouse_schema($pdo);
$title='Magazinat'; $subtitle='';
if(isset($_GET['delete'])){
  $id=(int)$_GET['delete'];
  $pdo->prepare('UPDATE Produktet SET magazine_id=1 WHERE magazine_id=?')->execute([$id]);
  $pdo->prepare('DELETE FROM Magazinat WHERE magazine_id=?')->execute([$id]);
  redirect('warehouses.php');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['magazine_id'] ?? 0);
  $data=[trim($_POST['emri']), trim($_POST['adresa']), trim($_POST['qyteti']), (int)($_POST['kapaciteti'] ?: 500)];
  if($id){ $data[]=$id; $pdo->prepare('UPDATE Magazinat SET emri=?, adresa=?, qyteti=?, kapaciteti=? WHERE magazine_id=?')->execute($data); }
  else $pdo->prepare('INSERT INTO Magazinat(emri,adresa,qyteti,kapaciteti) VALUES(?,?,?,?)')->execute($data);
  redirect('warehouses.php');
}
$edit=null;
if(isset($_GET['edit'])){ $st=$pdo->prepare('SELECT * FROM Magazinat WHERE magazine_id=?'); $st->execute([(int)$_GET['edit']]); $edit=$st->fetch(); }
$rows=$pdo->query("SELECT m.*, COUNT(p.produkt_id) produkte, COALESCE(SUM(p.sasia_ne_stok),0) sasia_totale,
  SUM(CASE WHEN p.sasia_ne_stok <= p.stok_minimal THEN 1 ELSE 0 END) stok_ulet,
  SUM(CASE WHEN p.sasia_ne_stok >= p.stok_maksimal THEN 1 ELSE 0 END) stok_larte
  FROM Magazinat m LEFT JOIN Produktet p ON p.magazine_id=m.magazine_id GROUP BY m.magazine_id ORDER BY m.emri")->fetchAll();
include 'includes/header.php';
?>
<div class="grid two-col">
  <div class="card">
    <h2><?= $edit ? 'Ndrysho magazinë' : 'Shto magazinë' ?></h2>
    <form method="post" class="form-grid">
      <input type="hidden" name="magazine_id" value="<?=e($edit['magazine_id'] ?? '')?>">
      <div><label>Emri</label><input class="input" name="emri" required value="<?=e($edit['emri'] ?? '')?>"></div>
      <div><label>Qyteti</label><input class="input" name="qyteti" value="<?=e($edit['qyteti'] ?? '')?>"></div>
      <div><label>Kapaciteti</label><input class="input" type="number" name="kapaciteti" value="<?=e($edit['kapaciteti'] ?? 500)?>"></div>
      <div class="full"><label>Adresa</label><input class="input" name="adresa" value="<?=e($edit['adresa'] ?? '')?>"></div>
      <div class="full"><button class="btn primary">Ruaj magazinën</button> <?php if($edit): ?><a class="btn gray" href="warehouses.php">Anulo</a><?php endif; ?></div>
    </form>
  </div>
  <div class="card">
    <h2>Analizë e magazinave</h2>
    <p class="muted">Sistemi kontrollon kapacitetin, produktet për çdo magazinë dhe nevojën për furnizim shtesë.</p>
    <a class="btn secondary" href="export_csv.php?type=warehouses">Eksporto magazinat CSV</a>
  </div>
</div>
<div class="card" style="margin-top:18px">
  <div class="toolbar"><h2>Lista e magazinave</h2></div>
  <div class="table-wrap"><table><tr><th>Magazina</th><th>Qyteti</th><th>Produkte</th><th>Sasi totale</th><th>Kapacitet</th><th>Status</th><th>Veprime</th></tr>
    <?php foreach($rows as $r): $cap=max(1,(int)($r['kapaciteti'] ?? 500)); $total=(int)$r['sasia_totale']; $pct=min(100, round($total/$cap*100)); $status=$total>=$cap?'warn':($total<$cap*0.35?'low':'ok'); ?>
    <tr>
      <td><b><?=e($r['emri'])?></b><br><span class="muted"><?=e($r['adresa'])?></span></td>
      <td><?=e($r['qyteti'])?></td>
      <td><?=e($r['produkte'])?></td>
      <td><?=e($total)?></td>
      <td><div class="mini-bar"><span style="width:<?=$pct?>%"></span></div><span class="muted"><?=$pct?>%</span></td>
      <td><span class="badge <?=$status?>"><?=$status==='low'?'Ka nevojë për produkte shtesë':($status==='warn'?'Afër/mbi kapacitet':'Në rregull')?></span></td>
      <td class="actions"><a class="btn gray" href="warehouses.php?edit=<?=$r['magazine_id']?>">Edit</a><a class="btn danger" data-confirm="Ta fshij këtë magazinë? Produktet do kalojnë te Magazina Kryesore." href="warehouses.php?delete=<?=$r['magazine_id']?>">Fshi</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php include 'includes/footer.php'; ?>
// updated by Frida Cani
