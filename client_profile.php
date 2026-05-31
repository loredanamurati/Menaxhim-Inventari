<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; if(empty($_SESSION['client'])) redirect('client_login.php');
$client=$_SESSION['client']; $clientId=(int)$client['klient_id']; $ok=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $emri=trim($_POST['emri']??''); $tel=trim($_POST['telefoni']??''); $email=trim($_POST['email']??''); $adresa=trim($_POST['adresa']??''); $qyteti=trim($_POST['qyteti']??''); $pass=trim($_POST['password']??'');
  if($emri==='' || !valid_email($email)) $err='Plotëso emrin dhe një email të vlefshëm.';
  else{
    if($pass!=='') $pdo->prepare('UPDATE Klientet SET emri=?, telefoni=?, email=?, adresa=?, qyteti=?, password=? WHERE klient_id=?')->execute([$emri,$tel,$email,$adresa,$qyteti,$pass,$clientId]);
    else $pdo->prepare('UPDATE Klientet SET emri=?, telefoni=?, email=?, adresa=?, qyteti=? WHERE klient_id=?')->execute([$emri,$tel,$email,$adresa,$qyteti,$clientId]);
    $st=$pdo->prepare('SELECT * FROM Klientet WHERE klient_id=?'); $st->execute([$clientId]); $_SESSION['client']=$st->fetch(); $client=$_SESSION['client']; $ok='Profili u përditësua.';
  }
}
$portalRole='client'; $portalUser=$client; $pageTitle='Profili im'; include 'includes/portal_header.php'; ?>
<?php if($ok):?><div class="alert success"><?=e($ok)?></div><?php endif;?><?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<section class="page-title-block"><h1>Profili im</h1><p>Përditëso të dhënat e llogarisë.</p></section>
<form method="post" class="form-grid"><div><label>Emër/Mbiemër</label><input class="input" name="emri" value="<?=e($client['emri'])?>" required></div><div><label>Email</label><input class="input" name="email" type="email" value="<?=e($client['email'])?>" required></div><div><label>Telefon</label><input class="input" name="telefoni" value="<?=e($client['telefoni']??'')?>"></div><div><label>Qyteti</label><input class="input" name="qyteti" value="<?=e($client['qyteti']??'')?>"></div><div class="full"><label>Adresa</label><input class="input" name="adresa" value="<?=e($client['adresa']??'')?>"></div><div><label>Fjalëkalim i ri</label><div class="password-wrap"><input class="input" id="newPass" type="password" name="password" placeholder="Lëre bosh nëse nuk ndryshon"><button type="button" class="password-toggle" data-target="newPass">Shfaq</button></div></div><div class="full"><button class="btn primary">Ruaj ndryshimet</button></div></form></div>
<?php include 'includes/portal_footer.php'; ?>
