<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
require 'config/paypal.php';
require_login();
ensure_portal_schema($pdo);

if(defined('PAYPAL_ENABLED') && PAYPAL_ENABLED !== '1'){
    die('PayPal nuk është aktivizuar nga administratori.');
}

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT ap.*, f.emri furnizues, f.email FROM AdminPayments ap LEFT JOIN Furnizuesit f ON f.furnizues_id=ap.furnizues_id WHERE ap.admin_payment_id=? LIMIT 1");
$st->execute([$id]);
$payment = $st->fetch();
if(!$payment) redirect('admin_make_payment.php');

$paypalCurrency = paypal_currency_code();
$displayCurrency = defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : $paypalCurrency;
?>
<!doctype html>
<html lang="sq">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pagesë furnizuesi</title>
<link rel="stylesheet" href="assets/style.css">
<script src="<?=e(paypal_sdk_src())?>"></script>
</head>
<body class="login-page">
<div class="checkout-card">
    <h1>Pagesë për furnizuesin</h1>
    <p><b><?=e($payment['furnizues'] ?? 'Furnizues')?></b> · SUP-<?=str_pad((int)$payment['furnizues_id'],5,'0',STR_PAD_LEFT)?></p>
    <p><?=e($payment['pershkrimi'] ?? '')?></p>
    <h2><?=money($payment['shuma'])?></h2>
    <?php if($displayCurrency === 'ALL'): ?>
        <div class="alert error">PayPal nuk mbështet pagesë direkte në Lek. Për pagesën online përdoret EUR/USD nga konfigurimi i PayPal.</div>
    <?php endif; ?>
    <div id="paypal-button-container"></div>
    <a href="admin_make_payment.php" class="btn secondary">Kthehu</a>
</div>
<script>
paypal.Buttons({
    createOrder:function(data,actions){
        return actions.order.create({
            purchase_units:[{
                description:'Pagesë furnizuesi SUP-<?=str_pad((int)$payment['furnizues_id'],5,'0',STR_PAD_LEFT)?>',
                amount:{ value:'<?=number_format((float)$payment['shuma'],2,'.','')?>' }
            }]
        });
    },
    onApprove:function(data,actions){
        return actions.order.capture().then(function(details){
            window.location='admin_paypal_success.php?id=<?=$id?>&paypal_order_id='+encodeURIComponent(data.orderID);
        });
    },
    onError:function(err){
        alert('Pagesa nuk u krye. Kontrollo Client ID dhe lidhjen me internetin.');
        console.log(err);
    }
}).render('#paypal-button-container');
</script>
</body>
</html>
