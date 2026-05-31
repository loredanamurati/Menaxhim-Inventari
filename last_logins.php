<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require_login(); ensure_portal_schema($pdo);
if(isset($_GET['delete'])){ $pdo->prepare('DELETE FROM LoginLogs WHERE log_id=?')->execute([(int)$_GET['delete']]); redirect('last_logins.php'); }
if(isset($_GET['clear']) && $_GET['clear']==='all'){ $pdo->query('DELETE FROM LoginLogs'); redirect('last_logins.php'); }
$title='Past Logins'; $subtitle='';
$rows=$pdo->query('SELECT * FROM LoginLogs ORDER BY data_login DESC LIMIT 200')->fetchAll();
include 'includes/header.php'; ?>
<div class="card"><div class="section-head"><div><h2>Past Logins</h2><p class="muted">Hyrjet e përdoruesve dhe email-et e përdorura në sistem.</p></div><a class="btn danger" data-confirm="Të fshihen të gjitha logimet?" href="last_logins.php?clear=all">Fshi të gjitha</a></div><div class="table-wrap"><table><tr><th>Data</th><th>Roli</th><th>Përdoruesi</th><th>Email / Username</th><th>Statusi</th><th>IP</th><th>Veprime</th></tr><?php foreach($rows as $r): ?><tr><td><?=e($r['data_login'])?></td><td><?=e($r['role_type'])?></td><td><b><?=e($r['user_label'])?></b></td><td><?=e($r['email_username'])?></td><td class="<?=$r['statusi']==='sukses'?'status-success':'status-fail'?>"><?=e($r['statusi'])?></td><td><?=e($r['ip_address'])?></td><td><a class="btn danger" data-confirm="Ta fshij këtë logim?" href="last_logins.php?delete=<?=$r['log_id']?>">Fshi</a></td></tr><?php endforeach; ?><?php if(!$rows): ?><tr><td colspan="7" class="empty-state">Nuk ka ende logime të regjistruara.</td></tr><?php endif; ?></table></div></div>
<?php include 'includes/footer.php'; ?>
