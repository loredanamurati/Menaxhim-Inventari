<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
if(empty($_SESSION['client'])) redirect('client_login.php');
ensure_portal_schema($pdo);

$client = $_SESSION['client'];
$clientId = (int)($client['klient_id'] ?? $client['id'] ?? 0);

function table_exists_local($pdo, $table){
    try{
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    }catch(Exception $e){ return false; }
}

function table_columns_local($pdo, $table){
    try{
        $cols=[];
        $st=$pdo->query("SHOW COLUMNS FROM `$table`");
        foreach($st->fetchAll(PDO::FETCH_ASSOC) as $c){ $cols[]=$c['Field']; }
        return $cols;
    }catch(Exception $e){ return []; }
}

function first_col_local($cols, $options){
    foreach($options as $o){ if(in_array($o, $cols, true)) return $o; }
    return null;
}

$error='';
$orders=[];

try{
    if(!table_exists_local($pdo,'Porosite')){
        throw new Exception('Tabela Porosite nuk ekziston. Importo databazen e projektit.');
    }

    $orderCols = table_columns_local($pdo,'Porosite');
    $orderIdCol = first_col_local($orderCols, ['porosi_id','order_id','id']);
    $clientCol = first_col_local($orderCols, ['klient_id','client_id','user_id']);
    $dateCol = first_col_local($orderCols, ['data_porosise','created_at','data','date']);
    $statusCol = first_col_local($orderCols, ['statusi','status','order_status']);
    $totalCol = first_col_local($orderCols, ['totali','total','shuma','amount']);

    if(!$orderIdCol || !$clientCol){
        throw new Exception('Tabela Porosite ka strukture te papritur.');
    }

    if(isset($_GET['delete'])){
        $id=(int)$_GET['delete'];
        $check=$pdo->prepare("SELECT `$orderIdCol` FROM Porosite WHERE `$orderIdCol`=? AND `$clientCol`=?");
        $check->execute([$id,$clientId]);
        if($check->fetch()){
            try{
                $pdo->beginTransaction();
                if(table_exists_local($pdo,'DetajetPorosise')){
                    $detCols = table_columns_local($pdo,'DetajetPorosise');
                    $detOrderCol = first_col_local($detCols, ['porosi_id','order_id']);
                    if($detOrderCol){ $pdo->prepare("DELETE FROM DetajetPorosise WHERE `$detOrderCol`=?")->execute([$id]); }
                }
                if(table_exists_local($pdo,'Pagesat')){
                    $payCols = table_columns_local($pdo,'Pagesat');
                    $payOrderCol = first_col_local($payCols, ['porosi_id','order_id']);
                    if($payOrderCol){ $pdo->prepare("DELETE FROM Pagesat WHERE `$payOrderCol`=?")->execute([$id]); }
                }
                $pdo->prepare("DELETE FROM Porosite WHERE `$orderIdCol`=? AND `$clientCol`=?")->execute([$id,$clientId]);
                $pdo->commit();
            }catch(Exception $e){ if($pdo->inTransaction()) $pdo->rollBack(); }
        }
        redirect('client_orders.php');
    }

    $select = "SELECT * FROM Porosite WHERE `$clientCol`=?";
    if($dateCol) $select .= " ORDER BY `$dateCol` DESC";
    else $select .= " ORDER BY `$orderIdCol` DESC";
    $st=$pdo->prepare($select);
    $st->execute([$clientId]);
    $rawOrders=$st->fetchAll(PDO::FETCH_ASSOC);

    $paymentAvailable = table_exists_local($pdo,'Pagesat');
    $payCols = $paymentAvailable ? table_columns_local($pdo,'Pagesat') : [];
    $payOrderCol = first_col_local($payCols, ['porosi_id','order_id']);
    $payIdCol = first_col_local($payCols, ['pagese_id','payment_id','id']);
    $payStatusCol = first_col_local($payCols, ['statusi','payment_status','status']);
    $payAmountCol = first_col_local($payCols, ['shuma','amount','total']);
    $payMethodCol = first_col_local($payCols, ['menyra_pageses','metoda','method']);
    $payRefCol = first_col_local($payCols, ['paypal_payment_id','paypal_order_id','transaction_id']);
    $payDateCol = first_col_local($payCols, ['data_pageses','created_at','date']);

    foreach($rawOrders as $o){
        $oid = $o[$orderIdCol];
        $payment = null;
        if($paymentAvailable && $payOrderCol){
            $sql="SELECT * FROM Pagesat WHERE `$payOrderCol`=?";
            if($payDateCol) $sql .= " ORDER BY `$payDateCol` DESC";
            elseif($payIdCol) $sql .= " ORDER BY `$payIdCol` DESC";
            $sql .= " LIMIT 1";
            $pst=$pdo->prepare($sql);
            $pst->execute([$oid]);
            $payment=$pst->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        $orders[]=[
            'id'=>$oid,
            'date'=>$dateCol ? ($o[$dateCol] ?? '-') : '-',
            'order_status'=>$statusCol ? ($o[$statusCol] ?? 'Në proces') : 'Në proces',
            'total'=>$totalCol ? ($o[$totalCol] ?? 0) : 0,
            'payment_status'=>$payment && $payStatusCol ? ($payment[$payStatusCol] ?? 'Paguar') : ($payment ? 'Paguar' : 'Në pritje'),
            'payment_ref'=>$payment && $payRefCol ? ($payment[$payRefCol] ?? '-') : '-',
            'payment_method'=>$payment && $payMethodCol ? ($payment[$payMethodCol] ?? '-') : '-',
            'payment_date'=>$payment && $payDateCol ? ($payment[$payDateCol] ?? '-') : '-',
        ];
    }
}catch(Exception $e){
    $error = 'Porositë nuk mund të ngarkohen: '.$e->getMessage();
}

$portalRole='client';
$portalUser=$client;
$pageTitle='Porositë';
include 'includes/portal_header.php';
?>
<?php if($error): ?>
<div class="alert error"><?=e($error)?></div>
<?php endif; ?>

<section class="page-title-card">
  <div>
    <span class="eyebrow">Porosi</span>
    <h1>Porositë e mia</h1>
    <p class="muted">Statusi i porosisë dhe pagesa online janë bashkuar në këtë faqe.</p>
  </div>
  <a class="btn primary" href="client_shop.php">Blej produkte</a>
</section>

<div class="card">
  <div class="section-head">
    <div><h2>Historia e porosive</h2></div>
  </div>
  <div class="table-wrap">
    <table>
      <tr>
        <th>ID</th>
        <th>Data</th>
        <th>Status porosie</th>
        <th>Totali</th>
        <th>Status pagese</th>
        <th>Metoda</th>
        <th>ID PayPal</th>
        <th>Veprime</th>
      </tr>
      <?php foreach($orders as $o): ?>
      <tr>
        <td>#<?=e($o['id'])?></td>
        <td><?=e($o['date'])?></td>
        <td><span class="badge ok"><?=e($o['order_status'])?></span></td>
        <td><?=money($o['total'])?></td>
        <td><?=e($o['payment_status'])?></td>
        <td><?=e($o['payment_method'])?></td>
        <td><?=e($o['payment_ref'])?></td>
        <td><a class="btn danger" data-confirm="Ta fshij këtë porosi?" href="client_orders.php?delete=<?=urlencode($o['id'])?>">Fshi</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$orders): ?>
      <tr><td colspan="8" class="empty-state">Nuk ka porosi.</td></tr>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php include 'includes/portal_footer.php'; ?>
