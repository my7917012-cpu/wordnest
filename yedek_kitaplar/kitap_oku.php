<?php
require_once __DIR__ . "/config/baglanti.php";
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit("Kitap bulunamadı.");
}
$sorgu = $db->prepare("SELECT k.id, k.baslik, k.yazar, k.uzanti, k.dosya_adi, c.kategori_adi FROM kitap_dosyalari k JOIN kategoriler c ON c.id = k.kategori_id WHERE k.id = ?");
$sorgu->execute([$id]);
$kitap = $sorgu->fetch();
if (!$kitap) {
    http_response_code(404);
    exit("Kitap bulunamadı.");
}
$icerikYolu = __DIR__ . "/uploads/kitaplar/" . basename($kitap["dosya_adi"]);
if (!is_file($icerikYolu)) {
    http_response_code(404);
    exit("Kitap dosyası bulunamadı.");
}
$metin = $kitap["uzanti"] === "txt" ? file_get_contents($icerikYolu) : "";
?>
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= htmlspecialchars($kitap["baslik"], ENT_QUOTES, "UTF-8") ?> - WordNest</title><link rel="stylesheet" href="assets/css/style.css"><style>body{background:#fffaf5}.okuyucu{max-width:1000px;margin:30px auto;padding:0 20px 50px}.kitap-bilgi{background:#fff;border:1px solid #f1e3d8;border-radius:14px;padding:22px;margin-bottom:18px}.kitap-bilgi h1{font-size:28px;margin:0 0 6px}.kitap-bilgi p{color:#777;margin:0}.kitap-eylemler{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px}.kitap-eylemler a{color:#e85d00;font-weight:bold}.metin-icerik{background:#fff;border:1px solid #f1e3d8;border-radius:14px;padding:clamp(20px,5vw,55px);font:18px/1.9 Georgia,serif;white-space:pre-wrap;overflow-wrap:anywhere}.pdf-cerceve{width:100%;height:min(78vh,900px);border:1px solid #ead9ca;border-radius:12px;background:white}</style></head>
<body>
<header class="ust-menu"><div class="container menu-icerik"><a href="index.php" class="logo"><span>Word</span>Nest</a><nav class="ana-menu"><a href="kitaplar.php">Kitaplar</a></nav><div class="menu-butonlar"><a class="giris-buton" href="kitaplar.php">Kitaplığa dön</a></div></div></header>
<main class="okuyucu"><section class="kitap-bilgi"><h1><?= htmlspecialchars($kitap["baslik"], ENT_QUOTES, "UTF-8") ?></h1><p><?= htmlspecialchars($kitap["yazar"] ?: "Yazar belirtilmedi", ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars($kitap["kategori_adi"], ENT_QUOTES, "UTF-8") ?></p><div class="kitap-eylemler"><a href="kitap_dosya.php?id=<?= (int) $kitap["id"] ?>&indir=1">Dosyayı indir</a><a href="kitaplar.php">Kitaplığa dön</a></div></section>
<?php if ($kitap["uzanti"] === "pdf"): ?><iframe class="pdf-cerceve" title="<?= htmlspecialchars($kitap["baslik"], ENT_QUOTES, "UTF-8") ?> kitabı" src="kitap_dosya.php?id=<?= (int) $kitap["id"] ?>"></iframe><?php else: ?><article class="metin-icerik"><?= htmlspecialchars($metin, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") ?></article><?php endif; ?>
</main></body></html>
