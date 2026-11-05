
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

$bolumId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($bolumId <= 0) {
    header("Location: ../index.php");
    exit;
}

/* BÖLÜM VE HİKÂYE BİLGİLERİNİ GETİR */
$sorgu = $db->prepare(
    "SELECT
        b.id AS bolum_id,
        b.bolum_basligi,
        b.icerik,
        b.bolum_sirasi,
        h.id AS hikaye_id,
        h.baslik AS hikaye_basligi,
        h.durum,
        h.kullanici_id AS hikaye_sahibi_id,
        k.kullanici_adi,
        k.ad_soyad
     FROM bolumler b
     INNER JOIN hikayeler h ON b.hikaye_id = h.id
     INNER JOIN kullanicilar k ON h.kullanici_id = k.id
     WHERE b.id = ?
     LIMIT 1"
);

$sorgu->execute([$bolumId]);
$bolum = $sorgu->fetch();

if (!$bolum) {
    header("Location: ../index.php");
    exit;
}

/* TASLAK HİKÂYELERİ SADECE SAHİBİ VE ADMİN OKUYABİLİR */
$girisYapildi = isset($_SESSION["kullanici_id"]);

$adminMi = $girisYapildi &&
    ($_SESSION["rol"] ?? "") === "admin";

$sahibiMi = $girisYapildi &&
    (int) $_SESSION["kullanici_id"] ===
    (int) $bolum["hikaye_sahibi_id"];

if ($bolum["durum"] !== "yayinda" && !$sahibiMi && !$adminMi) {
    header("Location: ../index.php");
    exit;
}

$hikayeId = (int) $bolum["hikaye_id"];
$bolumSirasi = (int) $bolum["bolum_sirasi"];

/* ÖNCEKİ BÖLÜM */
$oncekiSorgu = $db->prepare(
    "SELECT id
     FROM bolumler
     WHERE hikaye_id = ?
       AND bolum_sirasi < ?
     ORDER BY bolum_sirasi DESC
     LIMIT 1"
);

$oncekiSorgu->execute([$hikayeId, $bolumSirasi]);
$oncekiBolum = $oncekiSorgu->fetch();

/* SONRAKİ BÖLÜM */
$sonrakiSorgu = $db->prepare(
    "SELECT id
     FROM bolumler
     WHERE hikaye_id = ?
       AND bolum_sirasi > ?
     ORDER BY bolum_sirasi ASC
     LIMIT 1"
);

$sonrakiSorgu->execute([$hikayeId, $bolumSirasi]);
$sonrakiBolum = $sonrakiSorgu->fetch();

$bolumIcerigi = $bolum["icerik"] ?? "";
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($bolum["bolum_basligi"]) ?> - WordNest
    </title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Georgia, "Times New Roman", serif;
            background: #fffaf5;
            color: #333;
        }

        header {
            background: #ff6b00;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            font-family: Arial, sans-serif;
        }

        header h1 {
            color: white;
            font-size: 27px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-buttons a {
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 9px;
            font-weight: bold;
            border: 2px solid white;
            transition: 0.2s;
        }

        .home-btn {
            background: white;
            color: #e85d00;
        }

        .back-btn {
            background: #e85d00;
            color: white;
        }

        .nav-buttons a:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }

        .container {
            width: 90%;
            max-width: 850px;
            margin: 40px auto;
        }

        .hikaye-bilgisi {
            text-align: center;
            margin-bottom: 30px;
        }

        .hikaye-bilgisi a {
            color: #e85d00;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }

        .hikaye-bilgisi a:hover {
            text-decoration: underline;
        }

        .hikaye-adi {
            color: #777;
            font-size: 15px;
            margin-top: 12px;
        }

        .bolum-karti {
            background: white;
            padding: 45px 50px;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.06);
            border: 1px solid #f2e4d9;
        }

        .bolum-numarasi {
            color: #ff6b00;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .bolum-basligi {
            text-align: center;
            color: #333;
            font-size: 30px;
            line-height: 1.4;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
        }

        .yazar {
            text-align: center;
            color: #888;
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .ayrac {
            width: 65px;
            height: 3px;
            background: #ff6b00;
            margin: 0 auto 35px;
            border-radius: 5px;
        }

        .bolum-icerik {
            font-size: 19px;
            line-height: 2;
            color: #444;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .bolum-navigasyon {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
            font-family: Arial, sans-serif;
        }

        .bolum-navigasyon a,
        .bolum-navigasyon span {
            display: inline-block;
            padding: 13px 20px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
        }

        .onceki {
            background: #fff0e5;
            color: #e85d00;
        }

        .sonraki {
            background: #ff6b00;
            color: white;
        }

        .pasif {
            background: #eee;
            color: #999;
        }

        .bolum-navigasyon a:hover {
            opacity: 0.85;
        }

        .alt-bilgi {
            text-align: center;
            color: #999;
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin-top: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 600px) {
            header {
                flex-direction: column;
                align-items: stretch;
            }

            header h1 {
                text-align: center;
            }

            .nav-buttons {
                flex-direction: column;
            }

            .nav-buttons a {
                text-align: center;
            }

            .container {
                width: 94%;
                margin: 25px auto;
            }

            .bolum-karti {
                padding: 28px 20px;
            }

            .bolum-basligi {
                font-size: 25px;
            }

            .bolum-icerik {
                font-size: 17px;
                line-height: 1.9;
            }

            .bolum-navigasyon {
                flex-direction: column;
            }

            .bolum-navigasyon a,
            .bolum-navigasyon span {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>📚 WordNest</h1>

    <div class="nav-buttons">
        <a href="../index.php" class="home-btn">
            🏠 Ana Sayfaya Dön
        </a>

        <a href="oku.php?id=<?= $hikayeId ?>" class="back-btn">
            📖 Hikâyeye Geri Dön
        </a>
    </div>
</header>

<div class="container">

    <div class="hikaye-bilgisi">

        <a href="oku.php?id=<?= $hikayeId ?>">
            <?= htmlspecialchars($bolum["hikaye_basligi"]) ?>
        </a>

        <p class="hikaye-adi">
            ✍️ Yazar:
            <?= htmlspecialchars($bolum["ad_soyad"] ?: $bolum["kullanici_adi"]) ?>
        </p>

    </div>

    <article class="bolum-karti">

        <p class="bolum-numarasi">
            <?= $bolumSirasi ?>. BÖLÜM
        </p>

        <h2 class="bolum-basligi">
            <?= htmlspecialchars($bolum["bolum_basligi"]) ?>
        </h2>

        <p class="yazar">
            WordNest Hikâye Okuma Sayfası
        </p>

        <div class="ayrac"></div>

        <div class="bolum-icerik"><?=
            nl2br(htmlspecialchars($bolumIcerigi), false)
        ?></div>

    </article>

    <div class="bolum-navigasyon">

        <?php if ($oncekiBolum): ?>

            <a
                href="bolum_oku.php?id=<?= (int) $oncekiBolum["id"] ?>"
                class="onceki"
            >
                ← Önceki Bölüm
            </a>

        <?php else: ?>

            <span class="pasif">
                İlk Bölüm
            </span>

        <?php endif; ?>

        <?php if ($sonrakiBolum): ?>

            <a
                href="bolum_oku.php?id=<?= (int) $sonrakiBolum["id"] ?>"
                class="sonraki"
            >
                Sonraki Bölüm →
            </a>

        <?php else: ?>

            <span class="pasif">
                Son Bölüm
            </span>

        <?php endif; ?>

    </div>

    <p class="alt-bilgi">
        ❤️ WordNest'te keyifli okumalar!
    </p>

</div>

</body>
</html>
