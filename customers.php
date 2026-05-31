<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_portal_schema($pdo);
$title='Klientët'; $subtitle='';
if(isset($_GET['delete'])){ $pdo->prepare('DELETE FROM Klientet WHERE klient_id=?')->execute([$_GET['delete']]); redirect('customers.php'); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    $emri=trim($_POST['emri']??''); $tel=trim($_POST['telefoni']??''); $email=trim($_POST['email']??''); $adresa=trim($_POST['adresa']??''); $qyteti=trim($_POST['qyteti']??''); $pass=trim($_POST['password']??'12345');
    if(!valid_email($email)) redirect('customers.php?error=email');
    if(!empty($_POST['klient_id'])){
        $pdo->prepare('UPDATE Klientet SET emri=?,telefoni=?,email=?,adresa=?,qyteti=?,password=? WHERE klient_id=?')->execute([$emri,$tel,$email,$adresa,$qyteti,$pass,$_POST['klient_id']]);
    } else {
        $pdo->prepare('INSERT INTO Klientet(emri,telefoni,email,adresa,qyteti,password) VALUES(?,?,?,?,?,?)')->execute([$emri,$tel,$email,$adresa,$qyteti,$pass]);
    }
    redirect('customers.php');
}
$edit=null; if(isset($_GET['edit'])){ $st=$pdo->prepare('SELECT * FROM Klientet WHERE klient_id=?'); $st->execute([$_GET['edit']]); $edit=$st->fetch(); }
$rows=$pdo->query('SELECT klient_id,emri,telefoni,email,adresa,qyteti,password FROM Klientet ORDER BY klient_id DESC')->fetchAll();
include 'includes/header.php'; ?>
<?php if(isset($_GET['error'])):?><div class="alert error">Vendos një adresë emaili të vlefshme.</div><?php endif;?>
<div class="section"><div class="card"><h2>Lista e klientëve</h2><div class="table-wrap"><table><tr><th>Emri</th><th>Telefon</th><th>Email</th><th>Password</th><th>Adresa</th><th>Qyteti</th><th>Veprime</th></tr><?php foreach($rows as $r): ?><tr><td><b><?=e($r['emri'])?></b></td><td><?=e($r['telefoni'])?></td><td><?=e($r['email'])?></td><td><button type="button" class="pass-chip reveal-pass-btn" data-password="<?=e($r['password'])?>">Shfaq password</button><span class="revealed-pass"></span></td><td><?=e($r['adresa'])?></td><td><?=e($r['qyteti'])?></td><td class="actions"><a class="btn gray" href="customers.php?edit=<?=$r['klient_id']?>">Edit</a><a class="btn danger" data-confirm="Ta fshij klientin?" href="customers.php?delete=<?=$r['klient_id']?>">Fshi</a></td></tr><?php endforeach; ?></table></div></div><div class="card"><h2><?=$edit?'Ndrysho klient':'Shto klient'?></h2><form method="post"><input type="hidden" name="klient_id" value="<?=e($edit['klient_id']??'')?>"><label>Emri</label><input class="input" name="emri" value="<?=e($edit['emri']??'')?>" required><br><br><label>Telefoni</label><input class="input" name="telefoni" value="<?=e($edit['telefoni']??'')?>"><br><br><label>Email</label><input class="input" name="email" type="email" value="<?=e($edit['email']??'')?>" required><br><br><label>Adresa</label><input class="input" name="adresa" value="<?=e($edit['adresa']??'')?>"><br><br><label>Qyteti</label><input class="input" name="qyteti" value="<?=e($edit['qyteti']??'')?>"><br><br><label>Password</label><div class="password-wrap"><input class="input" id="customerPassword" name="password" type="password" value="<?=e($edit['password']??'12345')?>" required><button type="button" class="password-toggle" data-target="customerPassword">Shfaq</button></div><br><button class="btn primary">Ruaj</button></form></div></div><script src="assets/app.js"></script><?php include 'includes/footer.php'; ?>
