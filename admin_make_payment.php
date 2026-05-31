<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
require_login();
require_once 'config/paypal.php';
ensure_portal_schema($pdo);

$title = 'Pagesë furnizuesi';
$subtitle = '';
$msg = '';
$err = '';

try { $suppliers = $pdo->query("SELECT furnizues_id, emri, email, telefoni FROM Furnizuesit ORDER BY emri")->fetchAll(); } catch(Exception $e){ $suppliers=[]; }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $furnizues_id = (int)($_POST['furnizues_id'] ?? 0);
    $shuma = (float)($_POST['shuma'] ?? 0);
    $pershkrimi = trim($_POST['pershkrimi'] ?? 'Pagesë furnizuesi');
    $currency = defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'EUR';

    if($furnizues_id <= 0){
        $err = 'Zgjidh furnizuesin.';
    }elseif($shuma <= 0){
        $err = 'Vendos një shumë të vlefshme.';
    }else{
        $st = $pdo->prepare("INSERT INTO AdminPayments(furnizues_id, shuma, monedha, pershkrimi, statusi) VALUES(?,?,?,?, 'Ne proces')");
        $st->execute([$furnizues_id, $shuma, $currency, $pershkrimi]);
        $id = (int)$pdo->lastInsertId();
        redirect('admin_paypal_checkout.php?id='.$id);
    }
}

try{
    $payments = $pdo->query("SELECT ap.*, f.emri furnizues, f.email FROM AdminPayments ap LEFT JOIN Furnizuesit f ON f.furnizues_id=ap.furnizues_id ORDER BY ap.admin_payment_id DESC LIMIT 12")->fetchAll();
}catch(Exception $e){ $payments=[]; }

include 'includes/header.php';
?>
<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<section class="page-title-card">
    <div>
        <span class="eyebrow">Pagesa</span>
        <h1>Bëj pagesë furnizuesi</h1>
    </div>
    <a class="btn secondary" href="admin_paypal.php">Kthehu te pagesat</a>
</section>

<div class="saas-layout payment-layout">
<section class="card payment-config-card">
    <div class="section-head"><h2>Pagesë e re</h2></div>
    <form method="post" class="form-grid">
        <div class="span-all">
            <label>Furnizuesi</label>
            <select class="input" name="furnizues_id" required>
                <option value="">Zgjidh furnizuesin</option>
                <?php foreach($suppliers as $s): ?>
                    <option value="<?=e($s['furnizues_id'])?>">SUP-<?=str_pad($s['furnizues_id'],5,'0',STR_PAD_LEFT)?> · <?=e($s['emri'])?> · <?=e($s['email'])?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Shuma</label>
            <input class="input" type="number" step="0.01" min="0.01" name="shuma" required placeholder="0.00">
        </div>
        <div>
            <label>Monedha</label>
            <input class="input" value="<?=e(defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'EUR')?>" readonly>
        </div>
        <div class="span-all">
            <label>Përshkrimi</label>
            <input class="input" name="pershkrimi" value="Pagesë për furnizim produktesh" placeholder="Arsyeja e pagesës">
        </div>
        <div class="span-all">
            <button class="btn primary">Vazhdo te PayPal</button>
        </div>
    </form>
</section>

<aside class="card id-panel">
    <h2>Si funksionon</h2>
    <div class="id-scroll">
        <p><b>1</b><span>Zgjidh furnizuesin dhe shumën.</span></p>
        <p><b>2</b><span>Sistemi krijon një pagesë me status “Në proces”.</span></p>
        <p><b>3</b><span>Pas konfirmimit në PayPal, statusi bëhet “Paguar”.</span></p>
    </div>
</aside>
</div>

<section class="card">
    <div class="section-head"><h2>Pagesat e krijuara nga admini</h2></div>
    <div class="table-wrap">
        <table>
            <tr><th>Data</th><th>Furnizuesi</th><th>ID</th><th>Shuma</th><th>Statusi</th><th>PayPal ID</th><th>Veprim</th></tr>
            <?php foreach($payments as $p): ?>
            <tr>
                <td><?=e($p['data_pageses'] ?? '')?></td>
                <td><?=e($p['furnizues'] ?? '-')?></td>
                <td>SUP-<?=str_pad((int)($p['furnizues_id'] ?? 0),5,'0',STR_PAD_LEFT)?></td>
                <td><?=money($p['shuma'] ?? 0)?></td>
                <td><span class="status-pill"><?=e($p['statusi'] ?? '-')?></span></td>
                <td><?=e($p['paypal_order_id'] ?? '-')?></td>
                <td><?php if(($p['statusi'] ?? '') !== 'Paguar'): ?><a class="btn secondary small" href="admin_paypal_checkout.php?id=<?=e($p['admin_payment_id'])?>">Paguaj</a><?php else: ?><span class="muted">Përfunduar</span><?php endif; ?></td>
            </tr>
            <?php endforeach; if(!$payments): ?>
            <tr><td colspan="7" class="empty-state">Nuk ka pagesa të krijuara.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
