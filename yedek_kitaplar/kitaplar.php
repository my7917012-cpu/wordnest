<?php
require_once __DIR__ . "/config/baglanti.php";

$kategoriler = $db->query("SELECT id, kategori_adi FROM kategoriler ORDER BY id")->fetchAll();
$kitapSorgu = $db->query(
    "SELECT kt.kategori_id, kt.baslik, kt.yazar, kt.yayin_yili, kt.kapak_id, kt.kitap_url
     FROM kitaplar kt
     ORDER BY kt.kategori_id, kt.baslik"
);
$kitaplar = [];
foreach ($kitapSorgu->fetchAll() as $kitap) {
    $kitaplar[(int) $kitap["kategori_id"]][] = $kitap;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordNest - Kitaplar</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        *{box-sizing:border-box} body{margin:0;background:#fff8f2;font-family:Arial,sans-serif;color:#2f2925}
        .kitaplar-ust{background:#ff6b00;color:#fff;padding:20px 5%;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
        .kitaplar-ust a{color:#b94300;background:#fff;padding:10px 15px;border-radius:8px;text-decoration:none;font-weight:bold}
        .kitaplar-main{max-width:1200px;margin:auto;padding:32px 20px 60px}.kitaplar-main h1{margin:0 0 8px;font-size:34px}.kitaplar-aciklama{margin:0 0 30px;color:#716760;line-height:1.5}
        .kitap-kategori{margin:32px 0}.kitap-kategori h2{font-size:24px;border-bottom:2px solid #ffd2b5;padding-bottom:10px}
        .kitap-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:18px}
        .kitap-karti{background:#fff;border-radius:14px;padding:14px;box-shadow:0 6px 20px rgba(60,36,20,.08);display:flex;flex-direction:column;min-height:330px}
        .kitap-kapak{height:210px;background:#fff0e6;border-radius:9px;display:grid;place-items:center;overflow:hidden;color:#c45314;font-size:42px}
        .kitap-kapak img{width:100%;height:100%;object-fit:contain}.kitap-karti h3{font-size:16px;margin:13px 0 6px;line-height:1.35}.kitap-yazar{font-size:14px;color:#716760}.kitap-yil{font-size:12px;color:#92877f;margin-top:5px}
        .kitap-karti a{margin-top:auto;padding-top:14px;color:#e85d00;text-decoration:none;font-weight:bold;font-size:14px}.bos-kitap{padding:18px;background:#fff;border-radius:10px;color:#716760}
        @media(max-width:520px){.kitaplar-main h1{font-size:28px}.kitap-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.kitap-karti{padding:10px;min-height:300px}.kitap-kapak{height:170px}}
    </style>
</head>
<body>
<header class="kitaplar-ust">
    <strong>ğŸ“š WordNest KitaplarÄ±</strong>
    <a href="index.php">Ana sayfaya dÃ¶n</a>
</header>
<main class="kitaplar-main">
    <h1>Kategorilere GÃ¶re Kitaplar</h1>
    <p class="kitaplar-aciklama">Her kategoride seÃ§ilmiÅŸ kitaplarÄ± keÅŸfet. Kapak ve kÃ¼nye bilgileri Open Library kataloÄŸundan alÄ±nÄ±r.</p>
    <?php foreach ($kategoriler as $kategori): ?>
        <?php $liste = $kitaplar[(int) $kategori["id"]] ?? []; ?>
        <section class="kitap-kategori">
            <h2><?= htmlspecialchars($kategori["kategori_adi"], ENT_QUOTES, "UTF-8") ?></h2>
            <?php if ($liste): ?>
                <div class="kitap-grid">
                    <?php foreach ($liste as $kitap): ?>
                        <article class="kitap-karti">
                            <div class="kitap-kapak">
                                <?php if (!empty($kitap["kapak_id"])): ?>
                                    <img loading="lazy" src="https://covers.openlibrary.org/b/id/<?= (int) $kitap["kapak_id"] ?>-M.jpg" alt="<?= htmlspecialchars($kitap["baslik"], ENT_QUOTES, "UTF-8") ?> kitabÄ±nÄ±n kapaÄŸÄ±">
                                <?php else: ?>ğŸ“–<?php endif; ?>
                            </div>
                            <h3><?= htmlspecialchars($kitap["baslik"], ENT_QUOTES, "UTF-8") ?></h3>
                            <div class="kitap-yazar"><?= htmlspecialchars($kitap["yazar"] ?: "Yazar bilgisi yok", ENT_QUOTES, "UTF-8") ?></div>
                            <?php if (!empty($kitap["yayin_yili"])): ?><div class="kitap-yil"><?= (int) $kitap["yayin_yili"] ?></div><?php endif; ?>
                            <a href="<?= htmlspecialchars($kitap["kitap_url"], ENT_QUOTES, "UTF-8") ?>" target="_blank" rel="noopener noreferrer">Katalogda incele â†—</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="bos-kitap">Bu kategoride henÃ¼z kitap yok.</p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>