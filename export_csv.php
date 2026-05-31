<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_warehouse_schema($pdo);
$type=$_GET['type'] ?? 'products';
$map=[
 'products'=>['produkte.csv', "SELECT p.produkt_id, p.barkodi, p.emri, k.emri AS kategoria, f.emri AS furnizuesi, p.cmimi, p.sasia_ne_stok, p.stok_minimal, p.stok_maksimal FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id LEFT JOIN Furnizuesit f ON f.furnizues_id=p.furnizues_id ORDER BY p.emri"],
 'suppliers'=>['furnizuesit.csv', "SELECT furnizues_id, emri, person_kontakti, telefoni, email, adresa, qyteti FROM Furnizuesit ORDER BY emri"],
 'entries'=>['hyrjet.csv', "SELECT h.hyrje_id, p.emri AS produkti, f.emri AS furnizuesi, h.sasia, h.cmimi_njesi, h.data_hyrjes, h.shenim FROM Hyrjet h LEFT JOIN Produktet p ON p.produkt_id=h.produkt_id LEFT JOIN Furnizuesit f ON f.furnizues_id=h.furnizues_id ORDER BY h.data_hyrjes DESC"],
 'exits'=>['daljet.csv', "SELECT d.dalje_id, p.emri AS produkti, d.sasia, d.arsye, d.data_daljes FROM Daljet d LEFT JOIN Produktet p ON p.produkt_id=d.produkt_id ORDER BY d.data_daljes DESC"],
 'warehouses'=>['magazinat.csv', "SELECT m.magazine_id, m.emri, m.qyteti, m.adresa, COALESCE(m.kapaciteti,500) AS kapaciteti, COUNT(p.produkt_id) AS produkte, COALESCE(SUM(p.sasia_ne_stok),0) AS sasia_totale, CASE WHEN COALESCE(SUM(p.sasia_ne_stok),0) >= COALESCE(m.kapaciteti,500) THEN 'E mbushur' WHEN COALESCE(SUM(p.sasia_ne_stok),0) < COALESCE(m.kapaciteti,500)*0.35 THEN 'Ka nevoje per produkte shtese' ELSE 'Normal' END AS statusi FROM Magazinat m LEFT JOIN Produktet p ON p.magazine_id=m.magazine_id GROUP BY m.magazine_id ORDER BY m.emri"],
 'alerts'=>['alarmet.csv', "SELECT p.produkt_id, p.emri, k.emri AS kategoria, f.emri AS furnizuesi, p.sasia_ne_stok, p.stok_minimal, p.stok_maksimal, CASE WHEN p.sasia_ne_stok <= p.stok_minimal THEN 'Stok i ulet' WHEN p.sasia_ne_stok >= p.stok_maksimal THEN 'Stok i larte' ELSE 'Normal' END AS statusi FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id LEFT JOIN Furnizuesit f ON f.furnizues_id=p.furnizues_id WHERE p.sasia_ne_stok <= p.stok_minimal OR p.sasia_ne_stok >= p.stok_maksimal ORDER BY statusi, p.emri"]
];
if(!isset($map[$type])) $type='products';
[$filename,$sql]=$map[$type];
$rows=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.$filename);
$out=fopen('php://output','w');
fputs($out, "\xEF\xBB\xBF");
if($rows){ fputcsv($out, array_keys($rows[0])); foreach($rows as $r) fputcsv($out,$r); }
else { fputcsv($out, ['Nuk ka te dhena']); }
fclose($out); exit;
