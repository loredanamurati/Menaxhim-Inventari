<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
ensure_portal_schema($pdo);

if(empty($_SESSION['supplier'])) redirect('supplier_login.php');
$sale_id = (int)($_GET['sale_id'] ?? 0);
$sid = (int)$_SESSION['supplier']['furnizues_id'];
$paypal = trim($_GET['paypal_order_id'] ?? '');

$st = $pdo->prepare('SELECT * FROM SupplierSales WHERE sale_id=? AND furnizues_id=?');
$st->execute([$sale_id, $sid]);
$sale = $st->fetch();
if(!$sale) redirect('supplier_sell.php');

if($sale['statusi'] !== 'Paguar'){
    $pdo->beginTransaction();
    try{
        $pdo->prepare('UPDATE Produktet SET sasia_ne_stok=sasia_ne_stok+? WHERE produkt_id=? AND furnizues_id=?')
            ->execute([$sale['sasia'], $sale['produkt_id'], $sid]);
        try{
            $pdo->prepare("INSERT INTO Hyrjet(produkt_id,furnizues_id,sasia,cmimi_njesi,shenime) SELECT produkt_id,furnizues_id,?,cmimi,'Furnizim me PayPal nga portali i furnizuesit' FROM Produktet WHERE produkt_id=?")
                ->execute([$sale['sasia'], $sale['produkt_id']]);
        }catch(Exception $e){}
        $pdo->prepare("UPDATE SupplierSales SET statusi='Paguar', paypal_order_id=? WHERE sale_id=?")
            ->execute([$paypal, $sale_id]);
        $pdo->commit();
    }catch(Exception $e){
        $pdo->rollBack();
        die('Pagesa u aprovua, por sistemi nuk arriti të përditësojë furnizimin.');
    }
}
?>
<!doctype html>
<html lang="sq">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pagesa u krye</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-page"><div class="checkout-card"><h1>Pagesa u krye me sukses</h1><a class="btn primary" href="supplier_payments.php">Shiko pagesat</a></div></body>
</html>
