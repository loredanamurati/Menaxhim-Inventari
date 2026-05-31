<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
ensure_portal_schema($pdo);

if(empty($_SESSION['client'])) redirect('client_login.php');

$order_id = (int)($_GET['order_id'] ?? 0);
$paypal = trim($_GET['paypal_order_id'] ?? '');

$st = $pdo->prepare("SELECT * FROM Porosite WHERE porosi_id=? AND klient_id=?");
$st->execute([$order_id, $_SESSION['client']['klient_id']]);
$order = $st->fetch();
if(!$order) redirect('client_orders.php');

if(!in_array($order['statusi'], ['E paguar','E perfunduar'], true)){
    $itemsStmt = $pdo->prepare('SELECT * FROM DetajetPorosise WHERE porosi_id=?');
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll();

    $pdo->beginTransaction();
    try{
        foreach($items as $it){
            $upd = $pdo->prepare('UPDATE Produktet SET sasia_ne_stok=sasia_ne_stok-? WHERE produkt_id=? AND sasia_ne_stok>=?');
            $upd->execute([$it['sasia'], $it['produkt_id'], $it['sasia']]);
            $pdo->prepare("INSERT INTO Daljet (produkt_id,klient_id,perdorues_id,sasia,arsye) VALUES (?,?,NULL,?,'Blerje klienti me PayPal')")
                ->execute([$it['produkt_id'], $_SESSION['client']['klient_id'], $it['sasia']]);
        }
        $pdo->prepare("UPDATE Porosite SET statusi='E paguar' WHERE porosi_id=?")->execute([$order_id]);
        $pdo->prepare("INSERT INTO Pagesat (porosi_id,shuma,menyra_pageses,paypal_order_id) VALUES (?,?,?,?)")
            ->execute([$order_id, $order['totali'], 'PayPal', $paypal]);
        $pdo->commit();
    }catch(Exception $e){
        $pdo->rollBack();
        die('Pagesa u aprovua, por sistemi nuk arriti të përditësojë porosinë.');
    }
}
?>
<!doctype html>
<html lang="sq">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Pagesa u krye</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-page"><div class="login-card"><h1>Pagesa u krye me sukses</h1><a class="btn primary" href="client_orders.php">Shiko porositë</a></div></body>
</html>
