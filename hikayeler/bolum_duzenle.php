
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: ../kullanici/giris.php");
    exit;
}

$kullanici_id = (int) $_SESSION["kullanici_id"];

$bolum_id = filter_input(INPUT_GET, "bolum_id", FILTER_VALIDATE_INT);

if (!$bolum_id || $bolum_id < 1) {
    header("Location: hikayelerim.php");
    exit;
}

$sorgu = $db->prepare("
    SELECT
        b.id,
        b.hikaye_id,
        b.bolum_basligi,
        b.icerik,
        b.bolum_sirasi,
        h.baslik AS hikaye_baslik
    FROM bolumler b
    INNER JOIN hikayeler h ON h.id = b.hikaye_id
    WHERE b.id = ? AND h.kullanici_id = ?
");

$sorgu->execute([$bolum_id, $kullanici_id]);
$bolum = $sorgu->fetch();

if (!$bolum) {
    header("Location: hikayelerim.php");
    exit;
}

$hata = "";
$basarili = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

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

                $guncelle = $db->prepare("
                    UPDATE bolumler
                    SET bolum_basligi = ?, icerik = ?
                    WHERE id = ? AND hikaye_id = ?
                ");

                $guncelle->execute([
                    $bolum_basligi,
                    $icerik,
                    $bolum_id,
                    $bolum["hikaye_id"]
                ]);

                $bolum["bolum_basligi"] = $bolum_basligi;
                $bolum["icerik"] = $icerik;

                $basarili = "Bölümün başarıyla güncellendi!";

            } catch (PDOException $e) {
                $hata = "Bölüm güncellenirken bir hata oluştu.";
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

    <title>Bölüm Düzenle | WordNest</title>

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

        .header-butonlar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .header-buton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 18px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            border: 2px solid white;
            transition: 0.2s ease;
        }

        .ana-sayfa-buton {
            background: white;
            color: #e85d00;
        }

        .ana-sayfa-buton:hover {
            background: #fff0e5;
            transform: translateY(-2px);
        }

        .bolumler-buton {
            background: #e85d00;
            color: white;
        }

        .bolumler-buton:hover {
            background: #c94f00;
            transform: translateY(-2px);
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
            text-align: center;
            transition: 0.2s ease;
        }

        .buton:hover {
            background: #e85d00;
            transform: translateY(-2px);
        }

        .mesaj {
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            header {
                padding: 18px 20px;
            }

            .header-butonlar {
                width: 100%;
                flex-direction: column;
            }

            .header-butonlar a {
                width: 100%;
                text-align: center;
            }

            .form-karti {
                padding: 20px;
            }

            textarea {
                min-height: 280px;
            }

            .butonlar button {
                width: 100%;
            }
        }
    </style>
<link rel="stylesheet" href="../assets/css/gece-modu.css">
<script>try{if(localStorage.getItem("wordnest-tema")==="gece"){document.documentElement.setAttribute("data-wordnest-theme","gece")}}catch(e){}</script>
<link rel="stylesheet" href="../assets/css/dil-degistir.css">
<script>try{if(localStorage.getItem("wordnest-dil")==="en"){document.documentElement.lang="en";document.documentElement.setAttribute("data-wordnest-language","en")}}catch(e){}</script>
</head>

<body>

<header>
    <h2>📚 WordNest</h2>

    <div class="header-butonlar">

        <a href="../index.php" class="header-buton ana-sayfa-buton">
            🏠 Ana Sayfaya Dön
        </a>

        <a
            href="bolumlerim.php?hikaye_id=<?php echo (int)$bolum["hikaye_id"]; ?>"
            class="header-buton bolumler-buton"
        >
            📖 Bölümlerime Dön
        </a>

    </div>
</header>

<div class="container">

    <div class="form-karti">

        <h1>✏️ Bölümü Düzenle</h1>

        <p class="hikaye-adi">
            Hikâye:
            <strong>
                <?php echo htmlspecialchars($bolum["hikaye_baslik"], ENT_QUOTES, "UTF-8"); ?>
            </strong>
        </p>

        <?php if ($hata): ?>
            <div class="mesaj hata">
                <?php echo htmlspecialchars($hata, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <?php if ($basarili): ?>
            <div class="mesaj basarili">
                <?php echo htmlspecialchars($basarili, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
            >

            <label for="bolum_basligi">Bölüm Başlığı</label>

            <input
                type="text"
                id="bolum_basligi"
                name="bolum_basligi"
                maxlength="150"
                value="<?php echo htmlspecialchars($bolum["bolum_basligi"], ENT_QUOTES, "UTF-8"); ?>"
                required
            >

            <label for="icerik">Bölüm İçeriği</label>

            <textarea
                id="icerik"
                name="icerik"
                required
            ><?php echo htmlspecialchars($bolum["icerik"], ENT_QUOTES, "UTF-8"); ?></textarea>

            <div class="butonlar">

                <button type="submit" class="buton">
                    Değişiklikleri Kaydet
                </button>

            </div>

        </form>

    </div>

</div>

<script src="../assets/js/gece-modu.js"></script>
<script src="../assets/js/dil-degistir.js"></script>
</body>
</html>
