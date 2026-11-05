
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: ../kullanici/giris.php");
    exit;
}

$kullanici_id = (int) $_SESSION["kullanici_id"];

$hikaye_id = filter_input(INPUT_GET, "hikaye_id", FILTER_VALIDATE_INT);

if (!$hikaye_id || $hikaye_id < 1) {
    header("Location: hikayelerim.php");
    exit;
}

$sorgu = $db->prepare("
    SELECT id, baslik
    FROM hikayeler
    WHERE id = ? AND kullanici_id = ?
");
$sorgu->execute([$hikaye_id, $kullanici_id]);
$hikaye = $sorgu->fetch();

if (!$hikaye) {
    header("Location: hikayelerim.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$hata = "";
$basarili = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $hata = "Güvenlik doğrulaması başarısız. Sayfayı yenileyip tekrar dene.";
    } else {

        $bolum_basligi = trim($_POST["bolum_basligi"] ?? "");
        $icerik = trim($_POST["icerik"] ?? "");

        if ($bolum_basligi === "" || $icerik === "") {
            $hata = "Bölüm başlığı ve içerik boş bırakılamaz.";
        } elseif (mb_strlen($bolum_basligi) > 150) {
            $hata = "Bölüm başlığı en fazla 150 karakter olabilir.";
        } else {

            try {
                $db->beginTransaction();

                $siraSorgu = $db->prepare("
                    SELECT COALESCE(MAX(bolum_sirasi), 0) + 1
                    FROM bolumler
                    WHERE hikaye_id = ?
                ");

                $siraSorgu->execute([$hikaye_id]);
                $bolum_sirasi = (int) $siraSorgu->fetchColumn();

                $ekle = $db->prepare("
                    INSERT INTO bolumler
                    (hikaye_id, bolum_basligi, icerik, bolum_sirasi)
                    VALUES (?, ?, ?, ?)
                ");

                $ekle->execute([
                    $hikaye_id,
                    $bolum_basligi,
                    $icerik,
                    $bolum_sirasi
                ]);

                $db->commit();

                $basarili = "Bölümün başarıyla eklendi!";
                $_POST = [];

            } catch (PDOException $e) {

                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                // Gerçek veritabanı hatasını geçici olarak gösteriyoruz.
                $hata = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bölüm Ekle | WordNest</title>

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
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            font-size: 25px;
        }

        header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-karti {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            border: 1px solid #f0e4da;
        }

        h1 {
            color: #e85d00;
            margin-bottom: 10px;
        }

        .hikaye-adi {
            color: #777;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 9px;
            margin-top: 20px;
        }

        input, textarea {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            font-family: Arial, sans-serif;
            outline: none;
        }

        input:focus, textarea:focus {
            border-color: #ff6b00;
        }

        textarea {
            min-height: 350px;
            resize: vertical;
            line-height: 1.7;
        }

        .butonlar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .buton {
            display: inline-block;
            padding: 13px 20px;
            background: #ff6b00;
            color: white;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .buton:hover {
            background: #e85d00;
        }

        .geri {
            background: #777;
        }

        .geri:hover {
            background: #555;
        }

        .mesaj {
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
        }

        .hata {
            background: #ffe4e4;
            color: #b00020;
        }

        .basarili {
            background: #e4f7e8;
            color: #247a35;
        }

        @media(max-width: 600px) {
            .form-karti {
                padding: 20px;
            }

            textarea {
                min-height: 280px;
            }

            .butonlar a, .butonlar button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>📚 WordNest</h2>
    <a href="hikayelerim.php">Hikâyelerime Dön</a>
</header>

<div class="container">

    <div class="form-karti">

        <h1>Yeni Bölüm Ekle</h1>

        <p class="hikaye-adi">
            Hikâye:
            <strong>
                <?= htmlspecialchars($hikaye["baslik"], ENT_QUOTES, "UTF-8") ?>
            </strong>
        </p>

        <?php if ($hata): ?>
            <div class="mesaj hata">
                <?= htmlspecialchars($hata, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($basarili): ?>
            <div class="mesaj basarili">
                <?= htmlspecialchars($basarili, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"
            >

            <label for="bolum_basligi">Bölüm Başlığı</label>

            <input
                type="text"
                id="bolum_basligi"
                name="bolum_basligi"
                maxlength="150"
                placeholder="Örneğin: 1. Bölüm - Yeni Bir Başlangıç"
                value="<?= htmlspecialchars($_POST["bolum_basligi"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="icerik">Bölüm İçeriği</label>

            <textarea
                id="icerik"
                name="icerik"
                placeholder="Hikâyenin bu bölümünü buraya yaz..."
                required
            ><?= htmlspecialchars($_POST["icerik"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>

            <div class="butonlar">

                <button type="submit" class="buton">
                    Bölümü Kaydet
                </button>

                <a href="hikayelerim.php" class="buton geri">
                    Hikâyelerime Dön
                </a>

            </div>

        </form>

    </div>

</div>

</body>
</html>
