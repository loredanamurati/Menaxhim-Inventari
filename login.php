<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
ensure_portal_schema($pdo);
if(!empty($_SESSION['user'])) redirect('index.php');

$error='';
$rememberAdmin = $_COOKIE['remember_admin_user'] ?? 'admin';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $u=trim($_POST['username']??'');
    $p=trim($_POST['password']??'');
    $st=$pdo->prepare('SELECT * FROM Perdoruesit WHERE username=? LIMIT 1');
    $st->execute([$u]);
    $user=$st->fetch();
    if($user && $user['fjalekalimi']===$p){
        if(!empty($_POST['remember'])){ setcookie('remember_admin_user', $u, time()+60*60*24*30, '/'); } else { setcookie('remember_admin_user', '', time()-3600, '/'); }
        $_SESSION['user']=$user;
        log_login($pdo, 'administrator', $user['emri_plote'] ?? 'Administrator', $u, 'sukses');
        redirect('index.php');
    } else {
        log_login($pdo, 'administrator', '', $u, 'gabim');
        $error='Username ose fjalëkalim i gabuar.';
    }
}
?>
<!doctype html>
<html lang="sq">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hyrje Administratori - Inventari</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<form class="login-card" method="post" autocomplete="off">
  <div class="logo" style="margin-bottom:18px">I</div>
  <h1>Hyrje Administratori</h1>
  <p>Hyr në panelin e menaxhimit të inventarit.</p>
  <?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
  <label>Username</label>
  <input class="input" name="username" value="<?=e($rememberAdmin)?>" required>
  <br><br>
  <label>Fjalëkalimi</label>
  <div class="password-wrap"><input class="input" id="adminPassword" name="password" type="password" value="12345" required><button type="button" class="password-toggle" data-target="adminPassword">Shfaq</button></div>
  <br><br>
  <label class="remember-line"><input type="checkbox" name="remember" checked> Më mbaj mend</label>
  <br><br>
  <button class="btn primary" style="width:100%">Hyr në sistem</button>
  <div class="login-options"><a href="choose_role.php">Kthehu te zgjedhja e rolit</a></div>
</form>
<script src="assets/app.js"></script>
</body>
</html>
