<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';
require_login();
require_once 'config/paypal.php';
ensure_portal_schema($pdo);

$title = 'Pagesat Online';
$subtitle = '';
$msg = '';
$err = '';
$cfgFile = __DIR__.'/config/paypal.php';

$clientId = defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : 'sb';
$secret = defined('PAYPAL_SECRET') ? PAYPAL_SECRET : '';
$currency = defined('PAYPAL_CURRENCY') ? PAYPAL_CURRENCY : 'EUR';
$merchant = defined('PAYPAL_MERCHANT_EMAIL') ? PAYPAL_MERCHANT_EMAIL : '';
$enabled = defined('PAYPAL_ENABLED') ? PAYPAL_ENABLED : '1';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $clientId = trim($_POST['client_id'] ?? '');
    $secret = trim($_POST['secret'] ?? '');
    $currency = trim($_POST['currency'] ?? 'EUR');
    $merchant = trim($_POST['merchant_email'] ?? '');
    $enabled = isset($_POST['enabled']) ? '1' : '0';

    if($clientId === ''){
        $err = 'Vendos Client ID.';
    }elseif(!in_array($currency, ['EUR','ALL','USD'], true)){
        $err = 'Zgjidh monedhë të vlefshme.';
    }elseif($merchant !== '' && !filter_var($merchant, FILTER_VALIDATE_EMAIL)){
        $err = 'Email-i i biznesit nuk është i vlefshëm.';
    }else{
        $safe = function($v){ return str_replace("'", "\'", $v); };
        $php = "<?php\n"
            ."if(!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', '".$safe($clientId)."');\n"
            ."if(!defined('PAYPAL_SECRET')) define('PAYPAL_SECRET', '".$safe($secret)."');\n"
            ."if(!defined('PAYPAL_CURRENCY')) define('PAYPAL_CURRENCY', '".$safe($currency)."');\n"
            ."if(!defined('PAYPAL_MODE')) define('PAYPAL_MODE', 'live');\n"
            ."if(!defined('PAYPAL_MERCHANT_EMAIL')) define('PAYPAL_MERCHANT_EMAIL', '".$safe($merchant)."');\n"
            ."if(!defined('PAYPAL_ENABLED')) define('PAYPAL_ENABLED', '".$enabled."');\n"
            ."?>\n";
        if(@file_put_contents($cfgFile, $php) !== false){
            $msg = 'Konfigurimi u ruajt me sukses.';
        }else{
            $err = 'Nuk u ruajt dot config/paypal.php.';
        }
    }
}

$ordersCount = 0;
$paidCount = 0;
$pendingCount = 0;
$paidTotal = 0;
$lastPayments = [];
$clients = [];

try{ $ordersCount = (int)$pdo->query("SELECT COUNT(*) FROM Porosite")->fetchColumn(); }catch(Exception $e){}
try{ $pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM Porosite WHERE statusi LIKE '%pritje%' OR statusi LIKE '%proces%'")->fetchColumn(); }catch(Exception $e){}
try{ $paidCount = (int)$pdo->query("SELECT COUNT(*) FROM Pagesat")->fetchColumn(); }catch(Exception $e){}
try{ $paidTotal = (float)$pdo->query("SELECT COALESCE(SUM(shuma),0) FROM Pagesat")->fetchColumn(); }catch(Exception $e){}
try{
    $lastPayments = $pdo->query("SELECT pg.*, p.statusi, p.klient_id, k.emri klient
        FROM Pagesat pg
        LEFT JOIN Porosite p ON p.porosi_id=pg.porosi_id
        LEFT JOIN Klientet k ON k.klient_id=p.klient_id
        ORDER BY pg.data_pageses DESC
        LIMIT 12")->fetchAll();
}catch(Exception $e){}
try{ $clients = $pdo->query("SELECT klient_id, emri, email FROM Klientet ORDER BY emri LIMIT 15")->fetchAll(); }catch(Exception $e){}

include 'includes/header.php';
?>

<?php if($msg): ?><div class="alert success"><?=e($msg)?></div><?php endif; ?>
<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<section class="page-title-card">
    <div>
        <span class="eyebrow">Pagesat</span>
        <h1>Pagesat Online</h1>
        <p>Konfigurimi i PayPal dhe monitorimi i pagesave që vijnë nga porositë e klientëve.</p>
    </div>
    <div class="hero-total compact">
        <span>Total i paguar</span>
        <b><?=money($paidTotal)?></b>
    </div>
</section>

<div class="grid saas-kpis compact-kpis payment-kpis action-kpi-row">
    <a class="card kpi-card blue" href="admin_orders.php">
        <span>Porosi</span>
        <b><?=$ordersCount?></b>
        <small>gjithsej</small>
    </a>
    <a class="card kpi-card orange" href="admin_orders.php?status=Ne+proces">
        <span>Në proces</span>
        <b><?=$pendingCount?></b>
        <small>presin pagesë ose konfirmim</small>
    </a>
    <a class="card kpi-card green" href="admin_paypal.php#pagesat">
        <span>Pagesa</span>
        <b><?=$paidCount?></b>
        <small>transaksione</small>
    </a>
    <a class="card kpi-card violet" href="admin_paypal.php#konfigurimi">
        <span>Monedha</span>
        <b><?=e($currency)?></b>
        <small>aktive</small>
    </a>
</div>

<div class="saas-layout payment-layout">
    <section class="card payment-config-card" id="konfigurimi">
        <div class="section-head">
            <div>
                <h2>Konfigurimi i PayPal</h2>
                <p>Vendos të dhënat e llogarisë PayPal që përdoret për pagesat e klientëve.</p>
            </div>
        </div>

        <form method="post" class="form-grid">
            <div>
                <label>Monedha</label>
                <select class="input" name="currency">
                    <option value="EUR" <?=$currency==='EUR'?'selected':''?>>Euro (EUR)</option>
                    <option value="ALL" <?=$currency==='ALL'?'selected':''?>>Lek (ALL)</option>
                    <option value="USD" <?=$currency==='USD'?'selected':''?>>Dollar (USD)</option>
                </select>
            </div>

            <div>
                <label>Statusi</label>
                <label class="switch-line">
                    <input type="checkbox" name="enabled" <?=$enabled==='1'?'checked':''?>>
                    Prano pagesa online
                </label>
            </div>

            <div class="span-all">
                <label>Client ID</label>
                <input class="input" name="client_id" value="<?=e($clientId)?>" placeholder="Client ID" required>
            </div>

            <div class="span-all secret-field">
                <label>Secret Key</label>
                <input id="paypalSecret" class="input" type="password" name="secret" value="<?=e($secret)?>" placeholder="Secret Key">
                <button type="button" onclick="const x=document.getElementById('paypalSecret');x.type=x.type==='password'?'text':'password'" class="btn secondary">Shfaq</button>
            </div>

            <div class="span-all">
                <label>Email biznesi</label>
                <input class="input" type="email" name="merchant_email" value="<?=e($merchant)?>" placeholder="biznesi@email.com">
            </div>

            <div class="span-all">
                <button class="btn primary">Ruaj konfigurimin</button>
                <a class="btn secondary" href="admin_orders.php">Menaxho porositë</a>
            </div>
        </form>
    </section>

    <aside class="card id-panel">
        <h2>Klientët</h2>
        <p class="muted">Identifikuesit e klientëve që lidhen me porositë dhe pagesat.</p>
        <div class="id-scroll">
            <?php foreach($clients as $c): ?>
                <p>
                    <b>CL-<?=str_pad($c['klient_id'],5,'0',STR_PAD_LEFT)?></b>
                    <span><?=e($c['emri'])?> · <?=e($c['email'])?></span>
                </p>
            <?php endforeach; ?>
            <?php if(!$clients): ?>
                <p class="empty-state">Nuk ka klientë të regjistruar.</p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<section class="card" id="pagesat">
    <div class="section-head">
        <div>
            <h2>Pagesat e fundit</h2>
            <p>Lista e transaksioneve të regjistruara nga porositë e klientëve.</p>
        </div>
        <a class="btn secondary small" href="admin_orders.php">Shiko porositë</a>
    </div>

    <div class="table-wrap">
        <table>
            <tr>
                <th>Data</th>
                <th>ID pagesë</th>
                <th>Porosia</th>
                <th>Client ID</th>
                <th>Klienti</th>
                <th>Shuma</th>
                <th>Mënyra</th>
            </tr>
            <?php foreach($lastPayments as $p): ?>
            <tr>
                <td><?=e($p['data_pageses'] ?? '')?></td>
                <td><?=e($p['paypal_order_id'] ?? '-')?></td>
                <td>#<?=e($p['porosi_id'] ?? '-')?></td>
                <td>CL-<?=str_pad((int)($p['klient_id'] ?? 0),5,'0',STR_PAD_LEFT)?></td>
                <td><?=e($p['klient'] ?? '-')?></td>
                <td><?=money($p['shuma'] ?? 0)?></td>
                <td><?=e($p['menyra_pageses'] ?? '-')?></td>
            </tr>
            <?php endforeach; if(!$lastPayments): ?>
            <tr>
                <td colspan="7" class="empty-state">Nuk ka pagesa të regjistruara.</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
