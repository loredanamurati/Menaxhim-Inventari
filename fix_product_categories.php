<?php
require 'config/db.php';

function infer_product_category_id_local($name){
    $n = mb_strtolower($name, 'UTF-8');
    $rules = [
        ['usb-c hub',11], ['hub usb',11], ['adapter usb-c',11], ['adapter',6],
        ['all-in-one',2], ['mini pc',2], ['access point',11], ['patch panel',11],
        ['switch',11], ['router',11], ['modem',11], ['kabell',11], ['kabëll',11],
        ['ethernet',11], ['hdmi',11], ['vga',11], ['karikues',6], ['charger',6],
        ['laptop',1], ['tablet',4], ['telefon',3], ['iphone',3], ['monitor',10],
        ['printer',13], ['skaner',13], ['scanner',13], ['barcode',13], ['kamera',8],
        ['webcam',8], ['kufje',5], ['airpods',5], ['jbl',14], ['altoparlant',14],
        ['mikrofon',14], ['watch',7], ['smartwatch',7], ['gaming',9], ['mouse',11],
        ['tastiere',11], ['ssd',11], ['hdd',11], ['ram',11], ['ups',11], ['bateri',11],
        ['motherboard',11], ['procesor',11], ['graf',11], ['cooling',11], ['stand',11],
        ['power bank',6], ['furnizues energjie',11], ['lexues kartash',11]
    ];
    foreach($rules as $r){
        if(strpos($n, $r[0]) !== false) return $r[1];
    }
    return 11;
}

$rows = $pdo->query("SELECT produkt_id, emri FROM Produktet")->fetchAll();
$st = $pdo->prepare("UPDATE Produktet SET kategoria_id=? WHERE produkt_id=?");
foreach($rows as $p){
    $st->execute([infer_product_category_id_local($p['emri']), $p['produkt_id']]);
}

echo "Kategorite e produkteve u rregulluan me sukses.";
