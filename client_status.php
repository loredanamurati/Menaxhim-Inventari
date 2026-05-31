<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; ensure_portal_schema($pdo); if(empty($_SESSION['client'])) redirect('client_login.php');
$client=$_SESSION['client']; $st=$pdo->prepare('SELECT * FROM Porosite WHERE klient_id=? ORDER BY porosi_id DESC'); $st->execute([(int)$client['klient_id']]); $orders=$st->fetchAll();
$portalRole='client'; $portalUser=$client; $pageTitle='Status porosie'; include 'includes/portal_header.php'; ?>
<section class="page-title-card"><div><span class="eyebrow">Status</span><h1>Gjendja e porosive</h1></div><a class="btn primary" href="client_shop.php">Blej produkte</a></section>
<div class="card"><div class="status-timeline"><?php foreach($orders as $o): ?><div class="status-step"><div><b>Porosia #<?=e($o['porosi_id'])?></b><p class="muted"><?=e($o['data_porosise'])?> · <?=money($o['totali'])?></p></div><span class="badge ok"><?=e($o['statusi'])?></span></div><?php endforeach; if(!$orders): ?><p class="muted">Nuk ka porosi.</p><?php endif; ?></div></div><?php include 'includes/portal_footer.php'; ?>
