<?php
session_start();
require_once __DIR__ . "/../config/baglanti.php";

$kategoriId = filter_input(INPUT_GET, "kategori", FILTER_VALIDATE_INT);
$kategori = null;
$kategoriler = $db->query("SELECT id, kategori_adi FROM kategoriler ORDER BY kategori_adi")->fetchAll();

if ($kategoriId) {
    $kategoriSorgu = $db->prepare("SELECT id, kategori_adi FROM kategoriler WHERE id = ? LIMIT 1");
    $kategoriSorgu->execute([$kategoriId]);
    $kategori = $kategoriSorgu->fetch();
    if (!$kategori) {
        $kategoriId = null;
    }
}

$sql = "SELECT h.id, h.baslik, h.aciklama, h.kapak_resmi, h.goruntulenme, h.olusturma_tarihi, u.kullanici_adi, c.kategori_adi
        FROM hikayeler h
        LEFT JOIN kullanicilar u ON u.id = h.kullanici_id
        LEFT JOIN kategoriler c ON c.id = h.kategori_id
        WHERE h.durum IN ('yayinda', 'tamamlandi')";
$params = [];
if ($kategoriId) {
    $sql .= " AND h.kategori_id = ?";
    $params[] = $kategoriId;
}
$sql .= " ORDER BY h.olusturma_tarihi DESC";
$hikayeSorgu = $db->prepare($sql);
$hikayeSorgu->execute($params);
$hikayeler = $hikayeSorgu->fetchAll();
$baslik = $kategori ? $kategori["kategori_adi"] . " Hikâyeleri" : "Tüm Hikâyeler";
function listeTemizle($deger): string
{
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= listeTemizle($baslik) ?> - WordNest</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{background:#fff}.liste-sayfa{padding:55px 0 70px}.liste-ust{display:flex;justify-content:space-between;align-items:end;gap:18px;flex-wrap:wrap;margin-bottom:28px}.liste-ust h1{font-size:34px;color:#292929}.liste-ust p{color:#777;margin-top:7px}.liste-filtre{display:flex;align-items:center;gap:9px}.liste-filtre select,.liste-filtre button{padding:11px 13px;border:1px solid #eee0d5;border-radius:8px;background:white;font:inherit}.liste-filtre button{background:#f07824;color:white;border:0;font-weight:bold;cursor:pointer}.liste-bos{background:#fff8f2;border:1px solid #ffeadb;border-radius:13px;padding:25px;color:#777}.hikaye-yazar{margin-top:12px;color:#777;font-size:13px}
        @media(max-width:600px){.liste-sayfa{padding-top:35px}.liste-ust h1{font-size:28px}.liste-filtre{width:100%}.liste-filtre select{flex:1;min-width:0}}
    </style>
</head>
<body>
<header class="ust-menu"><div class="container menu-icerik"><a href="../index.php" class="logo"><span>Word</span>Nest</a><nav class="ana-menu"><a href="../index.php">Ana Sayfa</a><a href="liste.php" class="aktif">Hikâyeler</a><a href="../kitaplar.php">Kitaplar</a></nav><div class="menu-butonlar"><?php if (isset($_SESSION["kullanici_id"])): ?><a class="giris-buton" href="hikayelerim.php">Hikâyelerim</a><?php else: ?><a class="giris-buton" href="../kullanici/giris.php">Giriş Yap</a><?php endif; ?></div></div></header>
<main class="container liste-sayfa">
    <div class="liste-ust"><div><span class="ust-baslik">WORDNEST'TE KEŞFET</span><h1><?= listeTemizle($baslik) ?></h1><p><?= $kategori ? "Bu kategorideki yayınlanmış hikâyeler." : "Yayınlanmış hikâyelere göz at." ?></p></div><form class="liste-filtre" method="get" action="liste.php"><label for="kategori">Kategori</label><select id="kategori" name="kategori" onchange="this.form.submit()"><option value="">Tüm kategoriler</option><?php foreach ($kategoriler as $secenek): ?><option value="<?= (int) $secenek["id"] ?>" <?= $kategoriId === (int) $secenek["id"] ? "selected" : "" ?>><?= listeTemizle($secenek["kategori_adi"]) ?></option><?php endforeach; ?></select><noscript><button type="submit">Göster</button></noscript></form></div>
    <?php if ($hikayeler): ?><div class="hikaye-listesi"><?php foreach ($hikayeler as $hikaye): ?><a href="oku.php?id=<?= (int) $hikaye["id"] ?>" class="hikaye-karti"><div class="hikaye-kapak"><?php if (!empty($hikaye["kapak_resmi"])): ?><img src="<?= listeTemizle($hikaye["kapak_resmi"]) ?>" alt="<?= listeTemizle($hikaye["baslik"]) ?>"><?php else: ?><div class="kapak-yok">📖</div><?php endif; ?></div><div class="hikaye-bilgi"><span class="hikaye-kategori"><?= listeTemizle($hikaye["kategori_adi"] ?? "Genel") ?></span><h3><?= listeTemizle($hikaye["baslik"]) ?></h3><p><?= listeTemizle(mb_strimwidth($hikaye["aciklama"] ?? "", 0, 110, "...", "UTF-8")) ?></p><div class="hikaye-yazar">✍️ <?= listeTemizle($hikaye["kullanici_adi"] ?? "Bilinmeyen yazar") ?></div></div></a><?php endforeach; ?></div><?php else: ?><div class="liste-bos"><?= $kategori ? "Bu kategoride henüz yayınlanmış hikâye yok." : "Henüz yayınlanmış hikâye bulunmuyor." ?></div><?php endif; ?>
</main>
</body>
</html>
