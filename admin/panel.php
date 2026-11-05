
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

// Sadece admin kullanıcıları girebilir.
if (!isset($_SESSION["kullanici_id"]) || ($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: ../kullanici/giris.php");
    exit;
}

// İstatistikleri getir.
$kullaniciSayisi = $db->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn();
$hikayeSayisi = $db->query("SELECT COUNT(*) FROM hikayeler")->fetchColumn();
$bolumSayisi = $db->query("SELECT COUNT(*) FROM bolumler")->fetchColumn();
$kategoriSayisi = $db->query("SELECT COUNT(*) FROM kategoriler")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WordNest Yönetim Paneli</title>

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
    padding: 22px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

header h1 {
    font-size: 25px;
}

.header-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.header-buttons a {
    text-decoration: none;
    padding: 11px 17px;
    border-radius: 9px;
    font-weight: bold;
    border: 2px solid white;
    transition: 0.2s;
}

.home-button {
    background: white;
    color: #e85d00;
}

.logout-button {
    background: #e85d00;
    color: white;
}

.header-buttons a:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.container {
    width: 90%;
    max-width: 1150px;
    margin: 40px auto;
}

.welcome {
    margin-bottom: 30px;
}

.welcome h2 {
    color: #e85d00;
    margin-bottom: 10px;
    font-size: 25px;
}

.welcome p {
    color: #777;
    line-height: 1.6;
}

.section-title {
    color: #444;
    margin-bottom: 18px;
    font-size: 20px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 20px;
}

.card {
    background: white;
    padding: 25px 22px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border-left: 5px solid #ff6b00;
    transition: 0.2s;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 7px 20px rgba(0,0,0,0.09);
}

.card-icon {
    font-size: 30px;
    margin-bottom: 15px;
}

.card h3 {
    color: #777;
    font-size: 15px;
    margin-bottom: 12px;
}

.card strong {
    font-size: 34px;
    color: #e85d00;
}

.management {
    margin-top: 40px;
}

.management-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 18px;
}

.management-item {
    display: block;
    background: white;
    padding: 22px;
    border-radius: 13px;
    text-decoration: none;
    color: #333;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #ffe1cc;
    transition: 0.2s;
}

.management-item:hover {
    border-color: #ff6b00;
    transform: translateY(-3px);
}

.management-item strong {
    display: block;
    color: #e85d00;
    font-size: 17px;
    margin-bottom: 9px;
}

.management-item span {
    color: #777;
    font-size: 14px;
    line-height: 1.5;
}

footer {
    text-align: center;
    padding: 25px;
    color: #999;
    font-size: 13px;
    margin-top: 45px;
}

@media(max-width: 600px) {
    header {
        flex-direction: column;
        align-items: stretch;
    }

    header h1 {
        font-size: 21px;
        text-align: center;
    }

    .header-buttons {
        flex-direction: column;
    }

    .header-buttons a {
        text-align: center;
    }

    .container {
        margin: 25px auto;
    }

    .welcome h2 {
        font-size: 21px;
    }
}
</style>
</head>

<body>

<header>
    <h1>📚 WordNest Yönetim Paneli</h1>

    <div class="header-buttons">
        <a href="../index.php" class="home-button">🏠 Ana Sayfaya Dön</a>
        <a href="../kullanici/cikis.php" class="logout-button">Çıkış Yap</a>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <h2>
            Hoş geldin,
            <?= htmlspecialchars($_SESSION["kullanici_adi"] ?? "", ENT_QUOTES, "UTF-8") ?>! 👋
        </h2>

        <p>
            WordNest platformunun genel durumunu ve yönetim işlemlerini
            buradan takip edebilirsin.
        </p>
    </section>

    <h3 class="section-title">📊 Platform İstatistikleri</h3>

    <section class="cards">

        <div class="card">
            <div class="card-icon">👥</div>
            <h3>Toplam Kullanıcı</h3>
            <strong><?= (int)$kullaniciSayisi ?></strong>
        </div>

        <div class="card">
            <div class="card-icon">📚</div>
            <h3>Toplam Hikâye</h3>
            <strong><?= (int)$hikayeSayisi ?></strong>
        </div>

        <div class="card">
            <div class="card-icon">📖</div>
            <h3>Toplam Bölüm</h3>
            <strong><?= (int)$bolumSayisi ?></strong>
        </div>

        <div class="card">
            <div class="card-icon">🏷️</div>
            <h3>Toplam Kategori</h3>
            <strong><?= (int)$kategoriSayisi ?></strong>
        </div>

    </section>

    <section class="management">
        <h3 class="section-title">⚙️ Yönetim İşlemleri</h3>

        <div class="management-grid">

            <a href="kullanicilar.php" class="management-item">
                <strong>👥 Kullanıcı Yönetimi</strong>
                <span>
                    Kayıtlı kullanıcıları görüntüle ve hesaplarını yönet.
                </span>
            </a>

            <a href="kategori_ekle.php" class="management-item">
                <strong>🏷️ Kategori Yönetimi</strong>
                <span>
                    Yeni hikâye kategorileri oluştur ve mevcut kategorileri görüntüle.
                </span>
            </a>

            <a href="hikayeler.php" class="management-item">
                <strong>📚 Hikâye Yönetimi</strong>
                <span>
                    Platformdaki hikâyeleri görüntüle ve yayın durumlarını yönet.
                </span>
            </a>

        </div>
    </section>

</main>

<footer>
    © <?= date("Y") ?> WordNest - Yönetim Paneli
</footer>

</body>
</html>
