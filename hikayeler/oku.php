
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

// Hikâye ID kontrolü
$hikayeId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$hikayeId || $hikayeId < 1) {
    header("Location: ../index.php");
    exit;
}

// Hikâyeyi getir
$sorgu = $db->prepare("
    SELECT
        h.*,
        k.kullanici_adi,
        k.ad_soyad,
        ka.kategori_adi
    FROM hikayeler h
    INNER JOIN kullanicilar k ON h.kullanici_id = k.id
    LEFT JOIN kategoriler ka ON h.kategori_id = ka.id
    WHERE h.id = ?
    LIMIT 1
");

$sorgu->execute([$hikayeId]);
$hikaye = $sorgu->fetch();

// Hikâye bulunamadıysa
if (!$hikaye) {
    header("Location: ../index.php");
    exit;
}

// Taslak hikâyeleri sadece hikâyenin sahibi veya admin görebilir.
$girisYapanId = $_SESSION["kullanici_id"] ?? 0;
$adminMi = in_array(($_SESSION["rol"] ?? ""), ["admin", "root_admin"], true);
$sahibiMi = (int)$girisYapanId === (int)$hikaye["kullanici_id"];

$hikayeDurumu = mb_strtolower(trim((string) $hikaye["durum"]), "UTF-8");
if ($hikayeDurumu !== "onaylandi" && !$adminMi && !$sahibiMi) {
    header("Location: ../index.php");
    exit;
}

if ($hikayeDurumu === "onaylandi" && $girisYapanId && !$sahibiMi) {
    try {
        $okumaKaydi = $db->prepare("INSERT INTO okuma_gecmisi (kullanici_id, hikaye_id, son_okuma_tarihi)
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE son_okuma_tarihi = CURRENT_TIMESTAMP");
        $okumaKaydi->execute([(int) $girisYapanId, (int) $hikayeId]);
    } catch (PDOException $e) {
        // Story reading remains available until the reading-history table is installed.
    }
}

// Görüntülenme sayısını artır
$db->prepare("
    UPDATE hikayeler
    SET goruntulenme = goruntulenme + 1
    WHERE id = ?
")->execute([$hikayeId]);

// Bölümleri getir
$bolumSorgu = $db->prepare("
    SELECT id, bolum_basligi, bolum_sirasi, olusturma_tarihi
    FROM bolumler
    WHERE hikaye_id = ?
    ORDER BY bolum_sirasi ASC
");

$bolumSorgu->execute([$hikayeId]);
$bolumler = $bolumSorgu->fetchAll();

function temizle($veri) {
    return htmlspecialchars((string)$veri, ENT_QUOTES, "UTF-8");
}

$kapak = $hikaye["kapak_resmi"] ?? "";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= temizle($hikaye["baslik"]) ?> - WordNest</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #fffaf5;
    color: #333;
}

header {
    background: #ff6b00;
    color: white;
    padding: 20px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

header h1 {
    font-size: 24px;
}

.header-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.header-buttons a {
    text-decoration: none;
    padding: 11px 16px;
    border-radius: 9px;
    font-weight: bold;
    border: 2px solid white;
    transition: 0.2s;
}

.home-button {
    background: white;
    color: #e85d00;
}

.back-button {
    background: #e85d00;
    color: white;
}

.header-buttons a:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.container {
    width: 90%;
    max-width: 950px;
    margin: 35px auto;
}

.story-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
}

.story-cover {
    width: 100%;
    max-height: 350px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 25px;
}

.story-title {
    color: #e85d00;
    font-size: 30px;
    margin-bottom: 15px;
    overflow-wrap: anywhere;
}

.story-info {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
    color: #777;
    font-size: 14px;
}

.info-item {
    background: #fff3e9;
    padding: 8px 12px;
    border-radius: 20px;
}

.story-description {
    color: #555;
    font-size: 16px;
    line-height: 1.8;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.chapter-section {
    margin-top: 30px;
    background: white;
    padding: 28px;
    border-radius: 16px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
}

.chapter-section h2 {
    color: #e85d00;
    margin-bottom: 20px;
    font-size: 23px;
}

.chapter-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.chapter-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 17px;
    background: #fffaf5;
    border: 1px solid #ffe1cc;
    border-radius: 10px;
    text-decoration: none;
    color: #333;
    transition: 0.2s;
}

.chapter-item:hover {
    border-color: #ff6b00;
    transform: translateX(4px);
}

.chapter-name {
    font-weight: bold;
    overflow-wrap: anywhere;
}

.chapter-number {
    color: #e85d00;
    font-size: 13px;
    white-space: nowrap;
}

.empty-message {
    background: #fff3e9;
    padding: 20px;
    border-radius: 10px;
    color: #777;
    line-height: 1.6;
}

.status {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 20px;
    background: #fff0df;
    color: #e85d00;
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 15px;
}

footer {
    text-align: center;
    padding: 25px;
    color: #999;
    font-size: 13px;
}

@media(max-width: 600px) {
    header {
        flex-direction: column;
        align-items: stretch;
    }

    header h1 {
        text-align: center;
        font-size: 21px;
    }

    .header-buttons {
        flex-direction: column;
    }

    .header-buttons a {
        text-align: center;
    }

    .container {
        width: 94%;
        margin: 20px auto;
    }

    .story-card,
    .chapter-section {
        padding: 20px;
    }

    .story-title {
        font-size: 24px;
    }

    .chapter-item {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
<link rel="stylesheet" href="../assets/css/gece-modu.css">
<script>try{if(localStorage.getItem("wordnest-tema")==="gece")document.documentElement.setAttribute("data-wordnest-theme","gece")}catch(e){}</script>
<link rel="stylesheet" href="../assets/css/dil-degistir.css">
<script>try{if(localStorage.getItem("wordnest-dil")==="en"){document.documentElement.lang="en";document.documentElement.setAttribute("data-wordnest-language","en")}}catch(e){}</script>
</head>

<body>

<header>
    <h1>📚 WordNest</h1>

    <div class="header-buttons">
        <a href="../index.php" class="home-button">🏠 Ana Sayfaya Dön</a>
        <a href="hikayelerim.php" class="back-button">📖 Hikâyelerime Dön</a>
    </div>
</header>

<main class="container">

    <section class="story-card">

        <?php if (!empty($kapak)): ?>
            <img
                src="../<?= temizle($kapak) ?>"
                alt="<?= temizle($hikaye["baslik"]) ?>"
                class="story-cover"
            >
        <?php endif; ?>

        <?php if ($hikayeDurumu === "taslak"): ?>
            <span class="status">Taslak Hikâye</span>
        <?php elseif ($hikayeDurumu === "incelemede"): ?>
            <span class="status">Yönetici incelemesinde</span>
        <?php elseif ($hikayeDurumu === "reddedildi"): ?>
            <span class="status">Yayımlama reddedildi</span>
        <?php endif; ?>

        <h2 class="story-title">
            <?= temizle($hikaye["baslik"]) ?>
        </h2>

        <div class="story-info">

            <span class="info-item">
                ✍️ <a href="../kullanici/profil.php?id=<?= (int) $hikaye["kullanici_id"] ?>"><?= temizle($hikaye["ad_soyad"] ?: $hikaye["kullanici_adi"]) ?></a>
            </span>

            <span class="info-item">
                🏷️ <?= temizle($hikaye["kategori_adi"] ?? "Kategorisiz") ?>
            </span>

            <span class="info-item">
                📖 <?= count($bolumler) ?> Bölüm
            </span>

            <span class="info-item">
                👁️ <?= (int)$hikaye["goruntulenme"] + 1 ?> Görüntülenme
            </span>

        </div>

        <div class="story-description">
            <?= temizle($hikaye["aciklama"] ?: "Bu hikâye için henüz açıklama eklenmemiş.") ?>
        </div>

    </section>

    <section class="chapter-section">

        <h2>📚 Hikâyenin Bölümleri</h2>

        <?php if (count($bolumler) > 0): ?>

            <div class="chapter-list">

                <?php foreach ($bolumler as $bolum): ?>

                    <a
                        href="bolum_oku.php?id=<?= (int)$bolum["id"] ?>"
                        class="chapter-item"
                    >
                        <span class="chapter-name">
                            <?= temizle($bolum["bolum_basligi"]) ?>
                        </span>

                        <span class="chapter-number">
                            <?= (int)$bolum["bolum_sirasi"] ?>. Bölüm →
                        </span>
                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-message">
                Bu hikâyeye henüz bölüm eklenmemiş.
            </div>

        <?php endif; ?>

    </section>

</main>

<footer>
    © <?= date("Y") ?> WordNest - Keyifli okumalar!
</footer>

<script src="../assets/js/gece-modu.js"></script>
<script src="../assets/js/dil-degistir.js"></script>
</body>
</html>
