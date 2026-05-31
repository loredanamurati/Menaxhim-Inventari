<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
ensure_portal_schema($pdo);
$tab = $_GET['tab'] ?? 'login';
$error=''; $ok='';
$rememberSupplierEmail = $_COOKIE['remember_supplier_email'] ?? '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $mode=$_POST['mode']??'login';
    if($mode==='register'){
        $emri=trim($_POST['emri']??''); $person=trim($_POST['person']??''); $email=trim($_POST['email']??''); $adresa=trim($_POST['adresa']??''); $qyteti=trim($_POST['qyteti']??''); $tel=trim($_POST['telefoni']??''); $pass=trim($_POST['password']??''); $tab='register';
        if($emri==='' || $person==='' || $email==='' || $adresa==='' || $pass==='') $error='Plotëso të gjitha fushat e detyrueshme.';
        elseif(!valid_email($email)) $error='Vendos një adresë emaili të vlefshme.';
        else {
            $check=$pdo->prepare('SELECT furnizues_id FROM Furnizuesit WHERE email=? LIMIT 1'); $check->execute([$email]);
            if($check->fetch()) $error='Ky email ekziston në sistem.';
            else {
                $st=$pdo->prepare('INSERT INTO Furnizuesit (emri, person_kontakti, telefoni, email, adresa, qyteti, password) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $st->execute([$emri, $person, $tel, $email, $adresa, $qyteti, $pass]);
                $ok='Regjistrimi u krye me sukses. Tani mund të hysh me llogarinë tënde.'; $tab='login';
            }
        }
    } else {
        $email=trim($_POST['supplier_email']??''); $pass=trim($_POST['supplier_password']??'');
        $st=$pdo->prepare('SELECT * FROM Furnizuesit WHERE email=? LIMIT 1'); $st->execute([$email]); $u=$st->fetch();
        if($u && (($u['password']??'')===$pass)){ if(!empty($_POST['remember'])){ setcookie('remember_supplier_email', $email, time()+60*60*24*30, '/'); } else { setcookie('remember_supplier_email', '', time()-3600, '/'); } $_SESSION['supplier']=$u; log_login($pdo, 'furnizues', $u['emri'] ?? '', $email, 'sukses'); redirect('supplier_portal.php'); }
        else { log_login($pdo, 'furnizues', '', $email, 'gabim'); $error='Email ose fjalëkalim i gabuar.'; $tab='login'; }
    }
}
?>
<!doctype html><html lang="sq"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Furnizues - Inventari</title><link rel="stylesheet" href="assets/style.css"></head><body class="login-page"><div class="login-card" style="max-width:560px;width:100%"><div class="logo" style="margin-bottom:18px">I</div><h1>Portali i Furnizuesit</h1><div class="login-options" style="justify-content:flex-start;margin:18px 0"><a class="btn <?= $tab==='login'?'primary':'secondary' ?>" href="supplier_login.php?tab=login">Hyr</a><a class="btn <?= $tab==='register'?'primary':'secondary' ?>" href="supplier_login.php?tab=register">Regjistrohu</a></div><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?><?php if($ok):?><div class="alert success"><?=e($ok)?></div><?php endif;?>
<?php if($tab==='register'): ?><form method="post" autocomplete="off"><input type="hidden" name="mode" value="register"><label>Emri i kompanisë</label><input class="input" name="emri" required autocomplete="off"><br><br><label>Person kontakti</label><input class="input" name="person" required autocomplete="off"><br><br><label>Email</label><input class="input" name="email" type="email" required autocomplete="off"><br><br><div class="two"><div><label>Telefon</label><input class="input" name="telefoni" autocomplete="off"></div><div><label>Qyteti</label><input class="input" name="qyteti" autocomplete="off"></div></div><br><label>Adresa</label><input class="input" name="adresa" required autocomplete="off"><br><br><label>Fjalëkalimi</label><div class="password-wrap"><input class="input" id="passwordField" name="password" type="password" required autocomplete="new-password"><button type="button" class="password-toggle" data-target="passwordField">Shfaq</button></div><br><br><button class="btn primary" style="width:100%">Regjistrohu</button></form>
<?php else: ?><form method="post" autocomplete="off"><input type="hidden" name="mode" value="login"><label>Email</label><input class="input" name="supplier_email" type="email" value="<?=e($rememberSupplierEmail)?>" required autocomplete="off"><br><br><label>Fjalëkalimi</label><div class="password-wrap"><input class="input" id="loginPasswordField" name="supplier_password" type="password" value="" required autocomplete="new-password"><button type="button" class="password-toggle" data-target="loginPasswordField">Shfaq</button></div><br><br><label class="remember-line"><input type="checkbox" name="remember" <?= $rememberSupplierEmail!=='' ? 'checked' : '' ?>> Më mbaj mend</label><br><br><button class="btn primary" style="width:100%">Hyr</button></form><?php endif; ?><div class="login-options"><a href="choose_role.php">Kthehu te zgjedhja hyr/regjistrohu</a></div></div><script src="assets/app.js"></script></body></html>
