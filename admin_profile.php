<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_portal_schema($pdo);
$title='Profili im';
$user=$_SESSION['user']; $msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['emri_plote']??''); $username=trim($_POST['username']??''); $pass=trim($_POST['fjalekalimi']??'');
    if($name==='' || $username==='') $err='Plotëso emrin dhe username.';
    else{
        if($pass!==''){
            $st=$pdo->prepare('UPDATE Perdoruesit SET emri_plote=?, username=?, fjalekalimi=? WHERE perdorues_id=?');
            $st->execute([$name,$username,$pass,$user['perdorues_id']]);
        } else {
            $st=$pdo->prepare('UPDATE Perdoruesit SET emri_plote=?, username=? WHERE perdorues_id=?');
            $st->execute([$name,$username,$user['perdorues_id']]);
        }
        $st=$pdo->prepare('SELECT * FROM Perdoruesit WHERE perdorues_id=?'); $st->execute([$user['perdorues_id']]); $_SESSION['user']=$st->fetch();
        $msg='Profili u përditësua.';
    }
}
include 'includes/header.php'; ?>
<section class="profile-hero card">
  <div class="profile-avatar">A</div>
  <div><h2><?=e($_SESSION['user']['emri_plote'] ?? 'Administrator')?></h2><p>Administrator i sistemit të menaxhimit të inventarit.</p></div>
</section>
<?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?><?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?>
<div class="section">
  <div class="card"><h2>Të dhënat e profilit</h2><form method="post" class="form-grid">
    <div><label>Emri i plotë</label><input class="input" name="emri_plote" value="<?=e($_SESSION['user']['emri_plote'] ?? '')?>"></div>
    <div><label>Username</label><input class="input" name="username" value="<?=e($_SESSION['user']['username'] ?? '')?>"></div>
    <div class="full secret-field"><label>Fjalëkalim i ri</label><input id="adminProfilePass" class="input" type="password" name="fjalekalimi" placeholder="Lëre bosh nëse nuk do ndryshim"><button type="button" class="btn secondary password-toggle" data-target="adminProfilePass">Shfaq</button></div>
    <div class="full"><button class="btn primary">Ruaj ndryshimet</button></div>
  </form></div>
  <div class="card"><h2>Siguria</h2><p class="muted">Përdor fjalëkalim të fortë dhe dil nga llogaria kur përdor kompjuter publik.</p><a class="btn secondary" href="last_logins.php">Shiko hyrjet e fundit</a></div>
</div>
<script src="assets/app.js"></script>
<?php include 'includes/footer.php'; ?>
