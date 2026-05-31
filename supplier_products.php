<?php
session_start(); require 'config/db.php'; require 'includes/functions.php'; ensure_portal_schema($pdo); ensure_warehouse_schema($pdo);
if(empty($_SESSION['supplier'])) redirect('supplier_login.php');

$supplier=$_SESSION['supplier'];
$sid=(int)$supplier['furnizues_id'];
$err='';
$ok='';

try{
    $pdo->query("ALTER TABLE Produktet ADD COLUMN pershkrimi TEXT NULL");
}catch(Exception $e){}

if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $st=$pdo->prepare("DELETE FROM Produktet WHERE produkt_id=? AND furnizues_id=?");
    $st->execute([$id,$sid]);
    redirect('supplier_products.php');
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['produkt_id'] ?? 0);
    $kategoria_id=(int)($_POST['kategoria_id'] ?? 0);
    $magazine_id=(int)($_POST['magazine_id'] ?? 1);
    $barkodi=trim($_POST['barkodi'] ?? '');
    $emri=trim($_POST['emri'] ?? '');
    $pershkrimi=trim($_POST['pershkrimi'] ?? '');
    $njesia=trim($_POST['njesia'] ?? 'cope');
    $cmimi=max(0,(float)($_POST['cmimi'] ?? 0));
    $sasia=max(0,(int)($_POST['sasia_ne_stok'] ?? 0));
    $min=max(0,(int)($_POST['stok_minimal'] ?? 5));
    $max=max($min,(int)($_POST['stok_maksimal'] ?? 100));

    if($emri===''){
        $err='Shkruaj emrin e produktit.';
    }else{
        if($barkodi===''){
            $barkodi='SUP'.$sid.date('His').rand(10,99);
        }

        if($id>0){
            $st=$pdo->prepare("UPDATE Produktet SET kategoria_id=?, magazine_id=?, barkodi=?, emri=?, pershkrimi=?, njesia=?, cmimi=?, sasia_ne_stok=?, stok_minimal=?, stok_maksimal=? WHERE produkt_id=? AND furnizues_id=?");
            $st->execute([$kategoria_id,$magazine_id,$barkodi,$emri,$pershkrimi,$njesia,$cmimi,$sasia,$min,$max,$id,$sid]);
        }else{
            $st=$pdo->prepare("INSERT INTO Produktet(kategoria_id,furnizues_id,magazine_id,barkodi,emri,pershkrimi,njesia,cmimi,sasia_ne_stok,stok_minimal,stok_maksimal) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $st->execute([$kategoria_id,$sid,$magazine_id,$barkodi,$emri,$pershkrimi,$njesia,$cmimi,$sasia,$min,$max]);
        }

        redirect('supplier_products.php');
    }
}

$cats=$pdo->query('SELECT * FROM Kategorite ORDER BY emri')->fetchAll();
$whs=$pdo->query('SELECT * FROM Magazinat ORDER BY emri')->fetchAll();

$edit=null;
if(isset($_GET['edit'])){
    $st=$pdo->prepare('SELECT * FROM Produktet WHERE produkt_id=? AND furnizues_id=?');
    $st->execute([(int)$_GET['edit'],$sid]);
    $edit=$st->fetch();
}

$st=$pdo->prepare('SELECT p.*, k.emri kategori, m.emri magazine FROM Produktet p LEFT JOIN Kategorite k ON k.kategoria_id=p.kategoria_id LEFT JOIN Magazinat m ON m.magazine_id=p.magazine_id WHERE p.furnizues_id=? ORDER BY p.produkt_id DESC');
$st->execute([$sid]);
$products=$st->fetchAll();

$total=count($products);
$low=0;
$value=0;
foreach($products as $p){
    if($p['sasia_ne_stok'] <= $p['stok_minimal']) $low++;
    $value += $p['sasia_ne_stok']*$p['cmimi'];
}

$portalRole='supplier';
$portalUser=$supplier;
$pageTitle='Produktet e mia';
include 'includes/portal_header.php';
?>

<?php if($err): ?><div class="alert error"><?=e($err)?></div><?php endif; ?>

<section class="page-title-card">
    <div>
        <span class="eyebrow">Furnizues</span>
        <h1>Produktet e mia</h1>
        <p>Furnitori mund të shtojë dhe të menaxhojë produktet e veta.</p>
    </div>
    <a class="btn primary" href="supplier_products.php?action=create">+ Shto produkt</a>
</section>

<div class="grid saas-kpis compact-kpis action-kpi-row supplier-products-kpis">
    <a class="card kpi-card blue" href="supplier_products.php">
        <span>Produkte</span>
        <b><?=$total?></b>
        <small>të lidhura me furnizuesin</small>
    </a>
    <a class="card kpi-card orange" href="supplier_products.php">
        <span>Nevojë furnizimi</span>
        <b><?=$low?></b>
        <small>nën minimum</small>
    </a>
    <a class="card kpi-card green" href="supplier_products.php">
        <span>Vlera stokut</span>
        <b><?=money($value)?></b>
        <small>produkte aktive</small>
    </a>
</div>

<?php if(($_GET['action'] ?? '')==='create' || $edit): 
$row=$edit ?: ['produkt_id'=>'','kategoria_id'=>'','magazine_id'=>'1','barkodi'=>'','emri'=>'','pershkrimi'=>'','njesia'=>'cope','cmimi'=>'','sasia_ne_stok'=>'0','stok_minimal'=>'5','stok_maksimal'=>'100'];
?>
<section class="card">
    <div class="section-head">
        <div>
            <h2><?=$edit?'Ndrysho produkt':'Shto produkt të ri'?></h2>
            <p class="muted">Produkti lidhet automatikisht me llogarinë e furnitorit.</p>
        </div>
    </div>

    <form method="post" class="form-grid">
        <input type="hidden" name="produkt_id" value="<?=e($row['produkt_id'])?>">

        <div>
            <label>Emri</label>
            <input class="input" name="emri" required value="<?=e($row['emri'])?>">
        </div>

        <div>
            <label>Barkodi</label>
            <input class="input" name="barkodi" value="<?=e($row['barkodi'])?>" placeholder="Lëre bosh për kod automatik">
        </div>

        <div>
            <label>Kategoria</label>
            <select class="input" name="kategoria_id">
                <?php foreach($cats as $c): ?>
                <option value="<?=$c['kategoria_id']?>" <?=$row['kategoria_id']==$c['kategoria_id']?'selected':''?>><?=e($c['emri'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Magazina</label>
            <select class="input" name="magazine_id">
                <?php foreach($whs as $w): ?>
                <option value="<?=$w['magazine_id']?>" <?=$row['magazine_id']==$w['magazine_id']?'selected':''?>><?=e($w['emri'])?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Çmimi</label>
            <input class="input" type="number" step="0.01" name="cmimi" required value="<?=e($row['cmimi'])?>">
        </div>

        <div>
            <label>Njësia</label>
            <input class="input" name="njesia" value="<?=e($row['njesia'])?>">
        </div>

        <div>
            <label>Sasia në stok</label>
            <input class="input" type="number" name="sasia_ne_stok" value="<?=e($row['sasia_ne_stok'])?>">
        </div>

        <div>
            <label>Stok minimal</label>
            <input class="input" type="number" name="stok_minimal" value="<?=e($row['stok_minimal'])?>">
        </div>

        <div>
            <label>Stok maksimal</label>
            <input class="input" type="number" name="stok_maksimal" value="<?=e($row['stok_maksimal'])?>">
        </div>

        <div class="full">
            <label>Përshkrimi</label>
            <textarea name="pershkrimi" class="input" rows="3"><?=e($row['pershkrimi'])?></textarea>
        </div>

        <div class="full">
            <button class="btn primary">Ruaj produktin</button>
            <a class="btn gray" href="supplier_products.php">Anulo</a>
        </div>
    </form>
</section>
<?php endif; ?>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Lista e produkteve</h2>
            <p class="muted">Produktet që i përkasin këtij furnitori.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <tr>
                <th>Barkodi</th>
                <th>Produkti</th>
                <th>Kategoria</th>
                <th>Magazina</th>
                <th>Çmimi</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Veprime</th>
            </tr>

            <?php foreach($products as $p): 
                $status=$p['sasia_ne_stok'] <= $p['stok_minimal']?'low':($p['sasia_ne_stok'] >= $p['stok_maksimal']?'warn':'ok');
            ?>
            <tr>
                <td><?=e($p['barkodi'])?></td>
                <td><b><?=e($p['emri'])?></b></td>
                <td><?=e($p['kategori'])?></td>
                <td><?=e($p['magazine'])?></td>
                <td><?=money($p['cmimi'])?></td>
                <td><?=e($p['sasia_ne_stok'])?></td>
                <td><span class="badge <?=$status?>"><?=$status==='low'?'I ulët':($status==='warn'?'I lartë':'OK')?></span></td>
                <td class="actions">
                    <a class="btn gray" href="supplier_products.php?edit=<?=$p['produkt_id']?>">Edit</a>
                    <a class="btn danger" data-confirm="Ta fshij produktin?" href="supplier_products.php?delete=<?=$p['produkt_id']?>">Fshi</a>
                </td>
            </tr>
            <?php endforeach; if(!$products): ?>
            <tr><td colspan="8" class="empty-state">Nuk ka produkte të regjistruara për këtë furnitor.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</section>

<?php include 'includes/portal_footer.php'; ?>
