<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; require 'config/paypal.php'; ensure_portal_schema($pdo); ensure_warehouse_schema($pdo);
if(empty($_SESSION['client'])) redirect('client_login.php');
try{ $pdo->query("CREATE TABLE IF NOT EXISTS ClientWishlist (id INT AUTO_INCREMENT PRIMARY KEY, klient_id INT, produkt_id INT, data_krijimit DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_wish(klient_id,produkt_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $pdo->query("CREATE TABLE IF NOT EXISTS ClientFavorites (id INT AUTO_INCREMENT PRIMARY KEY, klient_id INT, produkt_id INT, data_krijimit DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_fav(klient_id,produkt_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(Exception $e){}
$client=$_SESSION['client']; $err=''; $ok='';
if(!isset($_SESSION['cart'])) $_SESSION['cart']=[];
if($_SERVER['REQUEST_METHOD']==='POST' && in_array(($_POST['action']??''), ['add_cart','buy_now','wishlist','favorite'], true)){
  $pid=(int)($_POST['produkt_id']??0); $qty=max(1,(int)($_POST['sasia']??1));
  $st=$pdo->prepare('SELECT produkt_id, sasia_ne_stok FROM Produktet WHERE produkt_id=?'); $st->execute([$pid]); $p=$st->fetch();
  if(!$p) $err='Produkti nuk u gjet.';
  elseif(($_POST['action']??'')==='wishlist'){ $pdo->prepare('INSERT IGNORE INTO ClientWishlist(klient_id,produkt_id) VALUES(?,?)')->execute([$client['klient_id'],$pid]); $ok='Produkti u shtua në wishlist.'; }
  elseif(($_POST['action']??'')==='favorite'){ $pdo->prepare('INSERT IGNORE INTO ClientFavorites(klient_id,produkt_id) VALUES(?,?)')->execute([$client['klient_id'],$pid]); $ok='Produkti u shtua te favorite.'; }
  elseif($p['sasia_ne_stok'] < $qty) $err='Ky produkt nuk ka stok të mjaftueshëm.'; else {
    if(($_POST['action']??'')==='buy_now'){ $_SESSION['cart']=[$pid=>$qty]; redirect('client_cart.php'); }
    $_SESSION['cart'][$pid]=($_SESSION['cart'][$pid]??0)+$qty;
    if($_SESSION['cart'][$pid] > $p['sasia_ne_stok']) $_SESSION['cart'][$pid]=$p['sasia_ne_stok'];
    $ok='Produkti u shtua në shportë.';
  }
}
$q=trim($_GET['q']??''); $filter=$_GET['status']??'all'; $where=[]; $params=[];
if($q){$where[]='(p.emri LIKE ? OR p.barkodi LIKE ? OR k.emri LIKE ?)'; $params=["%$q%","%$q%","%$q%"];}
if($filter==='in') $where[]='p.sasia_ne_stok > 0'; if($filter==='out') $where[]='p.sasia_ne_stok <= 0';
$sql='SELECT p.*, k.emri kategori, f.emri furnizues FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id LEFT JOIN Furnizuesit f ON f.furnizues_id=p.furnizues_id'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY p.emri LIMIT 120';
$st=$pdo->prepare($sql); $st->execute($params); $products=$st->fetchAll();
$cartCount=array_sum($_SESSION['cart']);
$portalRole='client'; $portalUser=$client; $pageTitle='Produktet'; include 'includes/portal_header.php';
?>
<?php if($err):?><div class="alert error"><?=e($err)?></div><?php endif;?><?php if($ok):?><div class="alert success"><?=e($ok)?> <a href="client_cart.php"><b>Shiko shportën</b></a></div><?php endif;?>
<section class="page-title-card"><div><span class="eyebrow">Katalog</span><h1>Produktet</h1></div><a class="btn primary" href="client_cart.php">Shporta (<?=$cartCount?>)</a></section>
<div class="card catalog-card"><form class="section-head catalog-filter catalog-filter-inline" method="get"><div class="catalog-title-search"><h2>Katalogu i produkteve</h2><input class="input portal-search" name="q" value="<?=e($q)?>" placeholder="Kërko produkt/kategori..."></div><div class="catalog-filter-actions"><select class="input" name="status"><option value="all">Të gjitha</option><option value="in" <?=$filter==='in'?'selected':''?>>Vetëm në stok</option><option value="out" <?=$filter==='out'?'selected':''?>>Jashtë stoku</option></select><button class="btn primary small">Filtro</button></div></form><div class="products-table-card">
    <div class="products-list-header">
        <span>Produkti</span>
        <span>Kategoria</span>
        <span>Çmimi</span>
        <span>Stoku</span>
        <span>Info</span>
        <span>Veprime</span>
    </div>

    <?php foreach($products as $p): $in=$p['sasia_ne_stok']>0; ?>
    <div class="products-list-row">
        <div class="product-cell-name">
            <b><?=e($p['emri'])?></b>
            <small>Kodi: <?=e($p['barkodi'])?></small>
        </div>

        <div class="product-cell-category"><?=e($p['kategori'])?></div>

        <div class="product-cell-price"><?=money($p['cmimi'])?></div>

        <div class="product-cell-stock">
            <span class="badge <?=$in?'ok':'low'?>">
                <?=$in?'Në stok: '.$p['sasia_ne_stok']:'Jashtë stoku'?>
            </span>
        </div>

        <div class="product-cell-info">
            <button type="button" class="info-product-btn" onclick="toggleProductInfo(this)" title="Informacion">i</button>

            <div class="product-info-pop">
                <strong><?=e($p['emri'])?></strong>
                <ul class="product-info-list">
                    <li><b>Kategoria:</b> <?=e($p['kategori'])?></li>
                    <li><b>Kodi:</b> <?=e($p['barkodi'])?></li>
                    <li>
                        <b>Përshkrimi:</b>
                        <?php
                            $emri = strtolower($p['emri'].' '.$p['kategori']);

                            if(str_contains($emri,'adapter fast charge')){
                                echo 'Adapter karikimi i shpejtë për furnizim energjie në telefona dhe pajisje USB.';
                            }elseif(str_contains($emri,'adapter usb-c') || str_contains($emri,'usb-c hub')){
                                echo 'Hub USB-C për lidhjen e aksesorëve, memorieve dhe pajisjeve shtesë me laptop ose kompjuter.';
                            }elseif(str_contains($emri,'access point')){
                                echo 'Pajisje rrjeti që zgjeron sinjalin Wi-Fi dhe lidh pajisjet në rrjet pa kabllo.';
                            }elseif(str_contains($emri,'barcode scanner')){
                                echo 'Skaner barkodesh për leximin e kodeve të produkteve dhe regjistrim më të shpejtë në sistem.';
                            }elseif(str_contains($emri,'printer')){
                                echo 'Printer për printimin e faturave, dokumenteve dhe materialeve të biznesit.';
                            }elseif(str_contains($emri,'cooling pad')){
                                echo 'Mbajtëse ftohëse që ndihmon laptopin të ulë temperaturën gjatë përdorimit.';
                            }elseif(str_contains($emri,'bateri')){
                                echo 'Bateri zëvendësuese ose shtesë për furnizim energjie të pajisjeve elektronike.';
                            }elseif(str_contains($emri,'altoparlant') || str_contains($emri,'jbl')){
                                echo 'Altoparlant për dëgjim muzike dhe audio me volum më të lartë.';
                            }elseif(str_contains($emri,'karikues')){
                                echo 'Karikues për furnizimin me energji të telefonave, tabletëve ose pajisjeve elektronike.';
                            }elseif(str_contains($emri,'kabell') || str_contains($emri,'kabëll')){
                                echo 'Kabëll për lidhje, karikim ose transmetim të të dhënave ndërmjet pajisjeve.';
                            }elseif(str_contains($emri,'laptop')){
                                echo 'Laptop për punë, mësim, internet, programe zyre dhe përdorim profesional.';
                            }elseif(str_contains($emri,'monitor')){
                                echo 'Monitor për shfaqjen e pamjes nga kompjuteri dhe punë më të rehatshme.';
                            }elseif(str_contains($emri,'kufje')){
                                echo 'Kufje për muzikë, telefonata, video-konferenca dhe përdorim audio.';
                            }elseif(str_contains($emri,'mouse')){
                                echo 'Mouse për navigim dhe kontroll më të saktë në kompjuter.';
                            }elseif(str_contains($emri,'tastiere')){
                                echo 'Tastierë për shkrim, komanda dhe përdorim të përditshëm në kompjuter.';
                            }elseif(str_contains($emri,'kamera')){
                                echo 'Kamerë për foto, video, komunikim ose monitorim sigurie.';
                            }elseif(str_contains($emri,'telefon')){
                                echo 'Telefon për komunikim, internet, aplikacione dhe përdorim të përditshëm.';
                            }else{
                                echo 'Produkt elektronik i përdorur për nevoja teknologjike dhe përdorim të përditshëm.';
                            }
                        ?>
                    </li>
                </ul>
            </div>
        </div>

        <div class="product-cell-actions">
            <?php if($in):?>
            <form method="post" class="product-actions-list">
                <input type="hidden" name="produkt_id" value="<?=$p['produkt_id']?>">
                <input class="input qty" type="number" name="sasia" value="1" min="1" max="<?=$p['sasia_ne_stok']?>">
                <button class="btn secondary icon-only" title="Shto në shportë" name="action" value="add_cart">🛒</button>
                <button class="btn primary icon-only" title="Bli tani" name="action" value="buy_now">🛍️</button>
                <button class="btn ghost icon-only" title="Favorite" name="action" value="favorite">❤️</button>
                <button class="btn ghost icon-only" title="Wishlist" name="action" value="wishlist">💎</button>
            </form>
            <?php else:?>
            <button class="btn disabled" disabled>Nuk disponohet</button>
            <?php endif;?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function toggleProductInfo(btn){
    const info = btn.closest('.product-cell-info');
    if(!info) return;

    const popup = info.querySelector('.product-info-pop');
    if(!popup) return;

    document.querySelectorAll('.product-info-pop.show-info').forEach(function(el){
        if(el !== popup) el.classList.remove('show-info');
    });

    popup.classList.toggle('show-info');
}

document.addEventListener('click', function(e){
    if(!e.target.closest('.product-cell-info')){
        document.querySelectorAll('.product-info-pop.show-info').forEach(function(el){
            el.classList.remove('show-info');
        });
    }
});
</script>

<?php include 'includes/portal_footer.php'; ?>
