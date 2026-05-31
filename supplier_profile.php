<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; ensure_portal_schema($pdo);
if(empty($_SESSION['supplier'])) redirect('supplier_login.php');
$sid=(int)$_SESSION['supplier']['furnizues_id']; $ok=''; $err='';
$st=$pdo->prepare('SELECT * FROM Furnizuesit WHERE furnizues_id=?'); $st->execute([$sid]); $supplier=$st->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $emri=trim($_POST['emri']??''); $person=trim($_POST['person_kontakti']??''); $email=trim($_POST['email']??''); $tel=trim($_POST['telefoni']??''); $adresa=trim($_POST['adresa']??''); $qyteti=trim($_POST['qyteti']??''); $pass=trim($_POST['password']??'');
  if($emri==='' || $person==='' || !valid_email($email)) $err='Plotëso kompaninë, personin e kontaktit dhe email të vlefshëm.';
  else{
    if($pass!=='') $pdo->prepare('UPDATE Furnizuesit SET emri=?, person_kontakti=?, telefoni=?, email=?, adresa=?, qyteti=?, password=? WHERE furnizues_id=?')->execute([$emri,$person,$tel,$email,$adresa,$qyteti,$pass,$sid]);
    else $pdo->prepare('UPDATE Furnizuesit SET emri=?, person_kontakti=?, telefoni=?, email=?, adresa=?, qyteti=? WHERE furnizues_id=?')->execute([$emri,$person,$tel,$email,$adresa,$qyteti,$sid]);
    $st=$pdo->prepare('SELECT * FROM Furnizuesit WHERE furnizues_id=?'); $st->execute([$sid]); $supplier=$st->fetch(); $_SESSION['supplier']=$supplier; $ok='Profili u përditësua me sukses.';
  }
}
?>
<?php $portalRole='supplier'; $portalUser=$supplier; include 'includes/portal_header.php'; ?>
<section class="page-title-block"><h1>Profili im</h1><p>Përditëso të dhënat e furnizuesit dhe sigurinë e llogarisë.</p></section>
<?php if($ok):?><div class="alert success"><?=e($ok)?></div><?php endif;?><?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<div class="card profile-card"><div class="profile-photo placeholder">F</div><form method="post" class="form-grid"><div><label>Emri i kompanisë</label><input class="input" name="emri" value="<?=e($supplier['emri'])?>" required></div><div><label>Person kontakti</label><input class="input" name="person_kontakti" value="<?=e($supplier['person_kontakti']??'')?>" required></div><div><label>Email</label><input class="input" type="email" name="email" value="<?=e($supplier['email'])?>" required></div><div><label>Telefon</label><input class="input" name="telefoni" value="<?=e($supplier['telefoni']??'')?>"></div><div><label>Qyteti</label><input class="input" name="qyteti" value="<?=e($supplier['qyteti']??'')?>"></div><div><label>Fjalëkalim i ri</label><div class="password-wrap"><input class="input" id="newPass" type="password" name="password" placeholder="Lëre bosh nëse nuk ndryshon"><button type="button" class="password-toggle" data-target="newPass">Shfaq</button></div></div><div class="full"><label>Adresa</label><input class="input" name="adresa" value="<?=e($supplier['adresa']??'')?>"></div><div class="full"><button class="btn primary">Ruaj ndryshimet</button></div></form></div>
<?php include 'includes/portal_footer.php'; ?>
