<?php
session_start();
require 'includes/functions.php';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$validActions = ['login','register'];
if($action && !in_array($action,$validActions,true)) $action='';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST' && $action){
    $role=$_POST['role'] ?? '';
    if($action==='register' && $role==='admin') $role='';
    if($role==='admin') redirect('login.php');
    if($role==='client') redirect('client_login.php?tab='.($action==='register'?'register':'login'));
    if($role==='supplier') redirect('supplier_login.php?tab='.($action==='register'?'register':'login'));
    $error='Zgjidh një rol të vlefshëm.';
}
?>
<!doctype html>
<html lang="sq">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hyrje në sistem</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="choose-saas">
<div class="choose-shell">
  <div class="choose-hero">
    <div class="logo big">I</div>
    <h1>Sistemi i Menaxhimit të Inventarit</h1><p>Zgjidh hyrjen ose regjistrimin dhe më pas rolin përkatës.</p>
  </div>

  <?php if(!$action): ?>
    <div class="entry-choice choose-cards">
      <a class="entry-button" href="choose_role.php?action=login"><span>Hyr</span><small>Kam një llogari</small></a>
      <a class="entry-button accent" href="choose_role.php?action=register"><span>Regjistrohu</span><small>Krijo llogari klient/furnizues</small></a>
    </div>
  <?php else: ?>
    <div class="role-form-card choose-form">
      <h2><?= $action==='login' ? 'Zgjidh rolin për hyrje' : 'Zgjidh rolin për regjistrim' ?></h2>
      <?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
      <form method="post" class="role-select-form">
        <input type="hidden" name="action" value="<?=e($action)?>">
        <label>Roli i përdoruesit</label>
        <select class="input role-dropdown" name="role" required>
          <option value="">-- Zgjidh rolin --</option>
          <?php if($action==='login'): ?><option value="admin">Administrator</option><?php endif; ?>
          <option value="client">Klient</option>
          <option value="supplier">Furnizues</option>
        </select>
        <button class="btn primary wide"><?= $action==='login' ? 'Vazhdo te hyrja' : 'Vazhdo te regjistrimi' ?></button>
      </form>
      <p class="role-back"><a href="choose_role.php">← Kthehu</a></p>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
