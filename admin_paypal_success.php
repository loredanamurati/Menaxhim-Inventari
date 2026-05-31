<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
require_login();
ensure_portal_schema($pdo);

$id = (int)($_GET['id'] ?? 0);
$paypal_order_id = trim($_GET['paypal_order_id'] ?? '');

$st = $pdo->prepare("UPDATE AdminPayments SET statusi='Paguar', paypal_order_id=?, data_perditesimit=NOW() WHERE admin_payment_id=?");
$st->execute([$paypal_order_id, $id]);

$title = 'Pagesa u krye';
include 'includes/header.php';
?>
<section class="page-title-card">
    <div>
        <span class="eyebrow">PayPal</span>
        <h1>Pagesa u krye me sukses</h1>
    </div>
    <a class="btn primary" href="admin_paypal.php#pagesat-furnizuesve">Shiko pagesat</a>
</section>
<section class="card">
    <h2>Konfirmim pagese</h2>
    <p>Pagesa për furnizuesin u regjistrua si <b>Paguar</b>.</p>
    <p>ID PayPal: <b><?=e($paypal_order_id ?: '-')?></b></p>
</section>
<?php include 'includes/footer.php'; ?>
