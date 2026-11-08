<?php
session_start();
require_once __DIR__ . '/../config/baglanti.php';

$oturumId = (int) ($_SESSION['kullanici_id'] ?? 0);
$profilId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$profilId) $profilId = $oturumId;
if ($profilId < 1) {
    header('Location: giris.php');
    exit;
}

$profilSorgu = $db->prepare('SELECT id, ad_soyad, kullanici_adi, profil_fotografi, biyografi, kayit_tarihi, durum FROM kullanicilar WHERE id = ? LIMIT 1');
$profilSorgu->execute([$profilId]);
$profil = $profilSorgu->fetch(PDO::FETCH_ASSOC);
$kendiProfilim = $oturumId === $profilId;
if (!$profil || (!$kendiProfilim && isset($profil['durum']) && (int) $profil['durum'] !== 1)) {
    http_response_code(404);
    exit('Profil bulunamadı.');
}

$profilMesaji = '';
$profilMesajTuru = 'error';
if (empty($_SESSION['profil_duzenle_csrf'])) {
    $_SESSION['profil_duzenle_csrf'] = bin2hex(random_bytes(32));
}
$profilCsrf = $_SESSION['profil_duzenle_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!$kendiProfilim) {
        http_response_code(403);
        exit('Bu profili düzenleme iznin yok.');
    }
    $postedCsrf = (string) ($_POST['csrf'] ?? '');
    $yeniAd = trim((string) ($_POST['ad_soyad'] ?? ''));
    $yeniBio = trim((string) ($_POST['biyografi'] ?? ''));
    $yeniFotoYolu = (string) ($profil['profil_fotografi'] ?? '');
    $yeniFotoDosyasi = null;

    if (!hash_equals($profilCsrf, $postedCsrf)) {
        $profilMesaji = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar dene.';
    } elseif ($yeniAd === '' || mb_strlen($yeniAd, 'UTF-8') > 100) {
        $profilMesaji = 'Ad soyad alanını doldur. En fazla 100 karakter olabilir.';
    } elseif (mb_strlen($yeniBio, 'UTF-8') > 2000) {
        $profilMesaji = 'Hakkımda bilgisi en fazla 2000 karakter olabilir.';
    } else {
        try {
            if (isset($_FILES['profil_fotografi']) && $_FILES['profil_fotografi']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = $_FILES['profil_fotografi'];
                if ($upload['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Fotoğraf yüklenemedi. Tekrar dene.');
                if ((int) $upload['size'] > 3 * 1024 * 1024) throw new RuntimeException('Fotoğraf en fazla 3 MB olabilir.');
                $imageInfo = @getimagesize($upload['tmp_name']);
                $mime = is_array($imageInfo) ? ($imageInfo['mime'] ?? '') : '';
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime])) throw new RuntimeException('JPG, PNG veya WebP türünde bir fotoğraf seç.');

                $uploadFolder = dirname(__DIR__) . '/uploads/profil';
                if (!is_dir($uploadFolder) && !mkdir($uploadFolder, 0755, true) && !is_dir($uploadFolder)) {
                    throw new RuntimeException('Fotoğraf klasörü oluşturulamadı.');
                }
                $htaccess = $uploadFolder . '/.htaccess';
                if (!is_file($htaccess)) {
                    $protection = "Options -Indexes\n" . '<FilesMatch "\\.(php|phtml|phar)$">' . "\nRequire all denied\n</FilesMatch>\n";
                    if (file_put_contents($htaccess, $protection, LOCK_EX) === false) throw new RuntimeException('Fotoğraf klasörü güvenli hâle getirilemedi.');
                }
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                $destination = $uploadFolder . '/' . $filename;
                if (!move_uploaded_file($upload['tmp_name'], $destination)) throw new RuntimeException('Fotoğraf kaydedilemedi.');
                $yeniFotoDosyasi = $destination;
                $yeniFotoYolu = 'uploads/profil/' . $filename;
            }

            $guncelle = $db->prepare('UPDATE kullanicilar SET ad_soyad = ?, biyografi = ?, profil_fotografi = ? WHERE id = ?');
            $guncelle->execute([$yeniAd, $yeniBio, $yeniFotoYolu, $profilId]);
            $_SESSION['profil_gorunen_ad'] = $yeniAd;
            header('Location: profil.php?id=' . $profilId . '&saved=1');
            exit;
        } catch (Throwable $e) {
            if ($yeniFotoDosyasi && is_file($yeniFotoDosyasi)) @unlink($yeniFotoDosyasi);
            $profilMesaji = $e instanceof RuntimeException ? $e->getMessage() : 'Profil kaydedilemedi. Değişiklikler korunmadı.';
        }
    }
}
if (isset($_GET['saved'])) {
    $profilMesaji = 'Profil bilgilerin güncellendi.';
    $profilMesajTuru = 'success';
}
$formAd = isset($yeniAd) ? $yeniAd : (string) ($profil['ad_soyad'] ?? '');
$formBio = isset($yeniBio) ? $yeniBio : (string) ($profil['biyografi'] ?? '');
$formAcik = $profilMesaji !== '' && $profilMesajTuru === 'error';

$hikayeSorgu = $db->prepare("SELECT h.id, h.baslik, h.aciklama, h.kapak_resmi, h.durum, h.goruntulenme,
        h.olusturma_tarihi, c.kategori_adi, COUNT(b.id) AS bolum_sayisi
    FROM hikayeler h
    LEFT JOIN kategoriler c ON c.id = h.kategori_id
    LEFT JOIN bolumler b ON b.hikaye_id = h.id
    WHERE h.kullanici_id = ?" . ($kendiProfilim ? "" : " AND LOWER(TRIM(h.durum)) = 'onaylandi'") . "
    GROUP BY h.id, h.baslik, h.aciklama, h.kapak_resmi, h.durum, h.goruntulenme, h.olusturma_tarihi, c.kategori_adi
    ORDER BY h.olusturma_tarihi DESC");
$hikayeSorgu->execute([$profilId]);
$hikayeler = $hikayeSorgu->fetchAll(PDO::FETCH_ASSOC);

$okunanKitaplar = [];
$okunanHikayeler = [];
$gecmisHazir = true;
if ($kendiProfilim) {
    try {
        $gecmisSorgu = $db->prepare("SELECT g.son_sayfa, g.son_okuma_tarihi, k.id, k.baslik, k.yazar, k.uzanti
            FROM okuma_gecmisi g
            INNER JOIN kitap_dosyalari k ON k.id = g.kitap_id
            WHERE g.kullanici_id = ? AND g.kitap_id IS NOT NULL
            ORDER BY g.son_okuma_tarihi DESC");
        $gecmisSorgu->execute([$profilId]);
        $okunanKitaplar = $gecmisSorgu->fetchAll(PDO::FETCH_ASSOC);
        $hikayeGecmisi = $db->prepare("SELECT g.son_bolum_id, g.son_okuma_tarihi, h.id, h.baslik, h.kapak_resmi, b.bolum_basligi
            FROM okuma_gecmisi g
            INNER JOIN hikayeler h ON h.id = g.hikaye_id
            LEFT JOIN bolumler b ON b.id = g.son_bolum_id
            WHERE g.kullanici_id = ? AND g.hikaye_id IS NOT NULL
            ORDER BY g.son_okuma_tarihi DESC");
        $hikayeGecmisi->execute([$profilId]);
        $okunanHikayeler = $hikayeGecmisi->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $gecmisHazir = false;
    }
}

function profilTemizle($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$adGoster = trim((string) ($profil['ad_soyad'] ?? '')) ?: (string) $profil['kullanici_adi'];
$fotoYolu = trim((string) ($profil['profil_fotografi'] ?? ''));
$fotoSrc = $fotoYolu !== '' ? '../' . ltrim($fotoYolu, '/') : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= profilTemizle($profil['kullanici_adi']) ?> - WordNest Profili</title>
    <link rel="stylesheet" href="../assets/css/gece-modu.css">
    <link rel="stylesheet" href="../assets/css/dil-degistir.css">
    <script>try{if(localStorage.getItem('wordnest-tema')==='gece')document.documentElement.setAttribute('data-wordnest-theme','gece');if(localStorage.getItem('wordnest-dil')==='en'){document.documentElement.lang='en';document.documentElement.setAttribute('data-wordnest-language','en')}}catch(e){}</script>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#fffaf5;color:#302820;font-family:Arial,sans-serif}.top{background:#ff6b00;color:white;padding:17px 5%;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}.top a{color:white;font-weight:bold;text-decoration:none}.wrap{width:92%;max-width:1100px;margin:34px auto 65px}.profile-card{display:flex;align-items:center;gap:22px;background:white;border:1px solid #f1e3d8;border-radius:16px;padding:24px;box-shadow:0 5px 18px rgba(50,35,25,.05)}.avatar{width:94px;height:94px;flex:0 0 94px;border-radius:50%;display:grid;place-items:center;overflow:hidden;background:#fff0e5;color:#e85d00;font-size:36px;font-weight:bold}.avatar img{width:100%;height:100%;object-fit:cover}.profile-name{margin:0;color:#e85d00;font-size:27px;overflow-wrap:anywhere}.name-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:5px}.username{margin:0;color:#796d63}.bio{margin:12px 0 0;line-height:1.6;color:#594f47;white-space:pre-wrap}.edit-toggle{border:1px solid #e85d00;border-radius:8px;padding:8px 12px;background:#fff0e5;color:#a84612;font:700 13px Arial,sans-serif;cursor:pointer}.edit-toggle:hover{background:#ffe3d0}.section{margin-top:30px}.section h2{font-size:22px;margin:0 0 14px;color:#312a24}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:15px}.item{background:#fff;border:1px solid #f1e3d8;border-radius:13px;padding:17px;box-shadow:0 4px 14px rgba(50,35,25,.04)}.item h3{margin:0 0 8px;color:#d95700;font-size:18px;overflow-wrap:anywhere}.item p{margin:6px 0;color:#70665e;line-height:1.45}.item a{display:inline-block;margin-top:9px;color:#b84a00;font-weight:bold;text-decoration:none}.item a:hover{text-decoration:underline}.status{display:inline-block;padding:5px 9px;border-radius:14px;background:#fff0df;color:#77521f;font-size:12px;font-weight:bold}.empty,.notice{background:white;border:1px solid #f1e3d8;border-radius:12px;padding:18px;color:#796d63;line-height:1.5}.notice{margin-top:18px;background:#fff3e9}.notice.success{background:#e8f7ed;color:#216e38}.notice.error{background:#ffe9e9;color:#a12e2e}.date{font-size:12px;color:#91877e!important}.edit-card{margin-top:18px;padding:22px;background:#fff;border:1px solid #f1e3d8;border-radius:14px;box-shadow:0 4px 15px rgba(50,35,25,.04)}.edit-card[hidden]{display:none!important}.edit-card h2{margin:0 0 16px;color:#312a24;font-size:21px}.edit-form{display:grid;gap:10px}.edit-form label{font-weight:bold;color:#574b41;margin-top:4px}.edit-form input[type=text],.edit-form textarea{width:100%;padding:11px 12px;border:1px solid #d9cec4;border-radius:8px;font:inherit;color:#302820;background:#fff}.edit-form textarea{min-height:120px;resize:vertical;line-height:1.5}.edit-form input[type=file]{max-width:100%;font:inherit}.edit-help{color:#776d65;font-size:13px;line-height:1.5}.save-button{width:max-content;max-width:100%;border:0;border-radius:8px;padding:12px 18px;background:#e85d00;color:#fff;font-weight:bold;font-size:15px;cursor:pointer;margin-top:8px}.save-button:hover{background:#c84d00}@media(max-width:600px){.top{align-items:stretch;flex-direction:column}.top a{text-align:center}.profile-card{align-items:flex-start;padding:18px;gap:14px}.avatar{width:68px;height:68px;flex-basis:68px;font-size:27px}.profile-name{font-size:22px}.wrap{margin-top:22px}.grid{grid-template-columns:1fr}.edit-card{padding:17px}.save-button{width:100%}}
        html[data-wordnest-theme="gece"] body{background:#171615!important;color:#f1ede8!important}html[data-wordnest-theme="gece"] .profile-card,html[data-wordnest-theme="gece"] .item,html[data-wordnest-theme="gece"] .empty,html[data-wordnest-theme="gece"] .notice,html[data-wordnest-theme="gece"] .edit-card{background:#262321!important;border-color:#45403b!important;color:#f1ede8!important}html[data-wordnest-theme="gece"] .section h2,html[data-wordnest-theme="gece"] .edit-card h2,html[data-wordnest-theme="gece"] .username,html[data-wordnest-theme="gece"] .bio,html[data-wordnest-theme="gece"] .item p,html[data-wordnest-theme="gece"] .date,html[data-wordnest-theme="gece"] .edit-form label,html[data-wordnest-theme="gece"] .edit-help{color:#ddd5cd!important}html[data-wordnest-theme="gece"] .avatar{background:#302a25!important}html[data-wordnest-theme="gece"] .edit-form input[type=text],html[data-wordnest-theme="gece"] .edit-form textarea{background:#1b1917!important;color:#f1ede8!important;border-color:#514a44!important}html[data-wordnest-theme="gece"] .edit-toggle{background:#302a25!important;color:#fff1e5!important;border-color:#6a5545!important}
    </style>
</head>
<body>
<header class="top"><strong>📚 WordNest</strong><a href="../index.php">🏠 Ana Sayfaya Dön</a></header>
<main class="wrap">
    <section class="profile-card">
        <div class="avatar"><?php if ($fotoSrc !== ''): ?><img src="<?= profilTemizle($fotoSrc) ?>" alt="<?= profilTemizle($adGoster) ?> profil fotoğrafı"><?php else: ?><?= profilTemizle(mb_substr($adGoster, 0, 1, 'UTF-8')) ?><?php endif; ?></div>
        <div><div class="name-row"><h1 class="profile-name"><?= profilTemizle($adGoster) ?></h1><?php if ($kendiProfilim): ?><button id="profilDuzenleAc" class="edit-toggle" type="button" aria-expanded="<?= $formAcik ? 'true' : 'false' ?>"><?= $formAcik ? 'Düzenlemeyi Kapat' : 'Profili Düzenle' ?></button><?php endif; ?></div><p class="username">@<?= profilTemizle($profil['kullanici_adi']) ?></p><?php if (trim((string) ($profil['biyografi'] ?? '')) !== ''): ?><p class="bio"><?= profilTemizle($profil['biyografi']) ?></p><?php endif; ?></div>
    </section>

    <?php if ($kendiProfilim): ?>
        <?php if ($profilMesaji !== ''): ?><div class="notice <?= profilTemizle($profilMesajTuru) ?>"><?= profilTemizle($profilMesaji) ?></div><?php endif; ?>
        <section id="profilDuzenle" class="edit-card" <?= $formAcik ? '' : 'hidden' ?>>
            <h2>Profili Düzenle</h2>
            <form class="edit-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf" value="<?= profilTemizle($profilCsrf) ?>">
                <label for="ad_soyad">Ad Soyad</label>
                <input id="ad_soyad" type="text" name="ad_soyad" maxlength="100" required value="<?= profilTemizle($formAd) ?>">
                <label for="profil_fotografi">Profil Fotoğrafı</label>
                <input id="profil_fotografi" type="file" name="profil_fotografi" accept="image/jpeg,image/png,image/webp">
                <span class="edit-help">JPG, PNG veya WebP seç. En fazla 3 MB.</span>
                <label for="biyografi">Hakkımda</label>
                <textarea id="biyografi" name="biyografi" maxlength="2000" placeholder="Kendinden kısaca bahset..."><?= profilTemizle($formBio) ?></textarea>
                <span class="edit-help">En fazla 2000 karakter.</span>
                <button class="save-button" type="submit">Değişiklikleri Kaydet</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="section">
        <h2><?= $kendiProfilim ? 'Yazdığım Hikâyeler' : 'Yayımladığı Hikâyeler' ?></h2>
        <?php if ($hikayeler): ?><div class="grid">
            <?php foreach ($hikayeler as $hikaye): ?>
                <article class="item"><h3><?= profilTemizle($hikaye['baslik']) ?></h3>
                    <p><?= profilTemizle($hikaye['kategori_adi'] ?? 'Kategorisiz') ?> · <?= (int) $hikaye['bolum_sayisi'] ?> bölüm</p>
                    <p><?= number_format((int) $hikaye['goruntulenme'], 0, ',', '.') ?> görüntülenme</p>
                    <?php if ($kendiProfilim && strtolower(trim((string) $hikaye['durum'])) !== 'onaylandi'): ?><span class="status"><?= profilTemizle($hikaye['durum']) ?></span><?php endif; ?>
                    <?php if (strtolower(trim((string) $hikaye['durum'])) === 'onaylandi'): ?><a href="../hikayeler/oku.php?id=<?= (int) $hikaye['id'] ?>">Hikâyeyi oku →</a><?php elseif ($kendiProfilim): ?><a href="../hikayeler/hikayelerim.php">Hikâyelerimi yönet →</a><?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div><?php else: ?><div class="empty">Henüz gösterilecek hikâye yok.</div><?php endif; ?>
    </section>

    <?php if ($kendiProfilim): ?>
        <section class="section">
            <h2>Okuduğum Kitaplar ve Hikâyeler</h2>
            <?php if (!$gecmisHazir): ?><div class="notice">Okuma geçmişini açmak için kitap okuma geçmişi tablosunun phpMyAdmin’de kurulması gerekiyor.</div>
            <?php elseif ($okunanKitaplar || $okunanHikayeler): ?><div class="grid">
                <?php foreach ($okunanKitaplar as $kitap): ?><article class="item"><h3>📖 <?= profilTemizle($kitap['baslik']) ?></h3><p><?= profilTemizle($kitap['yazar'] ?: 'Yazar belirtilmedi') ?></p><p>Son kaldığım sayfa: <?= (int) $kitap['son_sayfa'] ?></p><p class="date">Son okuma: <?= profilTemizle($kitap['son_okuma_tarihi']) ?></p><a href="../kitap_oku.php?id=<?= (int) $kitap['id'] ?>&sayfa=<?= (int) $kitap['son_sayfa'] ?>">Okumaya devam et →</a></article><?php endforeach; ?>
                <?php foreach ($okunanHikayeler as $hikaye): ?><article class="item"><h3>📚 <?= profilTemizle($hikaye['baslik']) ?></h3><?php if (!empty($hikaye['bolum_basligi'])): ?><p>Son kaldığım bölüm: <?= profilTemizle($hikaye['bolum_basligi']) ?></p><?php endif; ?><p class="date">Son okuma: <?= profilTemizle($hikaye['son_okuma_tarihi']) ?></p><a href="<?= !empty($hikaye['son_bolum_id']) ? '../hikayeler/bolum_oku.php?id=' . (int) $hikaye['son_bolum_id'] : '../hikayeler/oku.php?id=' . (int) $hikaye['id'] ?>">Okumaya devam et →</a></article><?php endforeach; ?>
            </div><?php else: ?><div class="empty">Bir kitabı veya hikâyeyi WordNest’te okumaya başlayınca burada görünür.</div><?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?php if ($kendiProfilim): ?>
<script>
(function () {
    var acmaDugmesi = document.getElementById('profilDuzenleAc');
    var formBolumu = document.getElementById('profilDuzenle');
    if (!acmaDugmesi || !formBolumu) return;
    acmaDugmesi.addEventListener('click', function () {
        var ac = formBolumu.hidden;
        formBolumu.hidden = !ac;
        acmaDugmesi.setAttribute('aria-expanded', ac ? 'true' : 'false');
        var ingilizce = false;
        try { ingilizce = localStorage.getItem('wordnest-dil') === 'en'; } catch (e) {}
        acmaDugmesi.textContent = ingilizce
            ? (ac ? 'Close editor' : 'Edit profile')
            : (ac ? 'Düzenlemeyi Kapat' : 'Profili Düzenle');
        if (ac) {
            formBolumu.scrollIntoView({ behavior: 'smooth', block: 'start' });
            var ilkAlan = document.getElementById('ad_soyad');
            if (ilkAlan) ilkAlan.focus({ preventScroll: true });
        }
    });
})();
</script>
<?php endif; ?>
<script src="../assets/js/gece-modu.js"></script>
<script src="../assets/js/dil-degistir.js"></script>
</body>
</html>
