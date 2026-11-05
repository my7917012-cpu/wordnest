
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

// Admin kontrolü
if (
    !isset($_SESSION["kullanici_id"]) ||
    ($_SESSION["rol"] ?? "") !== "admin"
) {
    header("Location: ../kullanici/giris.php");
    exit;
}

// CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mesaj = "";
$mesajTuru = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Güvenlik doğrulaması başarısız!");
    }

    $kategoriAdi = trim($_POST["kategori_adi"] ?? "");
    $aciklama = trim($_POST["aciklama"] ?? "");

    if ($kategoriAdi === "") {
        $mesaj = "Kategori adı boş bırakılamaz.";
        $mesajTuru = "hata";
    } else {

        try {
            // Aynı isimde kategori var mı?
            $kontrol = $db->prepare(
                "SELECT id FROM kategoriler WHERE kategori_adi = ?"
            );
            $kontrol->execute([$kategoriAdi]);

            if ($kontrol->fetch()) {
                $mesaj = "Bu kategori zaten mevcut.";
                $mesajTuru = "hata";
            } else {

                $ekle = $db->prepare(
                    "INSERT INTO kategoriler (kategori_adi, aciklama)
                     VALUES (?, ?)"
                );

                $ekle->execute([
                    $kategoriAdi,
                    $aciklama !== "" ? $aciklama : null
                ]);

                $mesaj = "Yeni kategori başarıyla oluşturuldu.";
                $mesajTuru = "basarili";
            }

        } catch (PDOException $e) {
            $mesaj = "Kategori oluşturulurken bir hata meydana geldi.";
            $mesajTuru = "hata";
        }
    }
}

// Mevcut kategorileri getir
$sorgu = $db->query(
    "SELECT * FROM kategoriler ORDER BY kategori_adi ASC"
);
$kategoriler = $sorgu->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>WordNest - Yeni Kategori</title>

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
    gap: 15px;
}

header h1 {
    font-size: 23px;
}

.header-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.header-buttons a {
    padding: 11px 17px;
    border-radius: 9px;
    text-decoration: none;
    font-weight: bold;
    border: 2px solid white;
}

.home-button {
    background: white;
    color: #e85d00;
}

.panel-button {
    background: #e85d00;
    color: white;
}

.container {
    width: 90%;
    max-width: 850px;
    margin: 40px auto;
}

h2 {
    color: #e85d00;
    margin-bottom: 10px;
}

.aciklama {
    color: #777;
    margin-bottom: 25px;
}

.kutu {
    background: white;
    padding: 28px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,.06);
    margin-bottom: 25px;
}

.kutu h3 {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

input, textarea {
    width: 100%;
    padding: 13px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 15px;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

button {
    background: #ff6b00;
    color: white;
    border: none;
    padding: 13px 22px;
    border-radius: 9px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #e85d00;
}

.mesaj {
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.basarili {
    background: #e5f6e9;
    color: #24733a;
}

.hata {
    background: #ffe5e5;
    color: #a51f1f;
}

.kategori {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.kategori:last-child {
    border-bottom: none;
}

.kategori strong {
    color: #e85d00;
}

.kategori p {
    color: #777;
    margin-top: 6px;
}

@media(max-width: 600px) {
    header {
        flex-direction: column;
        align-items: stretch;
    }

    .header-buttons {
        flex-direction: column;
    }

    .header-buttons a {
        text-align: center;
    }
}
</style>
</head>

<body>

<header>
    <h1>📚 WordNest - Kategori Yönetimi</h1>

    <div class="header-buttons">
        <a href="../index.php" class="home-button">🏠 Ana Sayfaya Dön</a>
        <a href="panel.php" class="panel-button">⚙️ Admin Paneli</a>
    </div>
</header>

<div class="container">

    <h2>Yeni Kategori Oluştur</h2>
    <p class="aciklama">
        WordNest hikâyeleri için yeni kategoriler oluşturabilirsin.
    </p>

    <?php if ($mesaj !== ""): ?>
        <div class="mesaj <?= htmlspecialchars($mesajTuru) ?>">
            <?= htmlspecialchars($mesaj) ?>
        </div>
    <?php endif; ?>

    <div class="kutu">

        <h3>➕ Kategori Bilgileri</h3>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >

            <label>Kategori Adı</label>

            <input
                type="text"
                name="kategori_adi"
                placeholder="Örneğin: Polisiye"
                maxlength="100"
                required
            >

            <label>Kategori Açıklaması</label>

            <textarea
                name="aciklama"
                placeholder="Bu kategori hakkında kısa bir açıklama yaz..."
            ></textarea>

            <button type="submit">
                ➕ Kategoriyi Oluştur
            </button>

        </form>

    </div>

    <div class="kutu">

        <h3>📂 Mevcut Kategoriler (<?= count($kategoriler) ?>)</h3>

        <?php foreach ($kategoriler as $kategori): ?>

            <div class="kategori">

                <strong>
                    <?= htmlspecialchars($kategori["kategori_adi"]) ?>
                </strong>

                <p>
                    <?= htmlspecialchars(
                        $kategori["aciklama"] ?? "Açıklama bulunmuyor."
                    ) ?>
                </p>

            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>
