
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

if (!isset($_SESSION["kullanici_id"])) {
    header("Location: ../kullanici/giris.php");
    exit;
}

$kullanici_id = (int) $_SESSION["kullanici_id"];

$sorgu = $db->prepare("
    SELECT
        h.id,
        h.baslik,
        h.aciklama,
        h.kapak_resmi,
        h.durum,
        h.olusturma_tarihi,
        k.kategori_adi,
        COUNT(b.id) AS bolum_sayisi
    FROM hikayeler h
    LEFT JOIN kategoriler k ON k.id = h.kategori_id
    LEFT JOIN bolumler b ON b.hikaye_id = h.id
    WHERE h.kullanici_id = ?
    GROUP BY
        h.id,
        h.baslik,
        h.aciklama,
        h.kapak_resmi,
        h.durum,
        h.olusturma_tarihi,
        k.kategori_adi
    ORDER BY h.olusturma_tarihi DESC
");

$sorgu->execute([$kullanici_id]);
$hikayeler = $sorgu->fetchAll();

function guvenli($deger) {
    return htmlspecialchars((string) $deger, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hikâyelerim - WordNest</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            margin: 0;
            background: #fffaf5;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .ust-menu {
            background: #ff6b00;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .ust-menu h2 {
            color: white;
            margin: 0;
        }

        .header-butonlar {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
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
            transition: .2s ease;
        }

        .ana-sayfa-buton {
            background: white;
            color: #e85d00;
        }

        .ana-sayfa-buton:hover {
            background: #fff0e5;
            transform: translateY(-2px);
        }

        .sayfa {
            width: 90%;
            max-width: 1100px;
            margin: 45px auto;
        }

        .sayfa-baslik {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .sayfa-baslik h1 {
            color: #f36b21;
            margin: 0;
        }

        .buton {
            display: inline-block;
            background: #f36b21;
            color: white;
            padding: 11px 17px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin: 4px 3px 4px 0;
        }

        .buton:hover {
            background: #d95510;
        }

        .buton-ikincil {
            background: #444;
        }

        .buton-ikincil:hover {
            background: #222;
        }

        .hikaye-kart {
            display: flex;
            gap: 22px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
            flex-wrap: wrap;
            align-items: flex-start;
        }

        .hikaye-kapak {
            width: 150px;
            height: 190px;
            object-fit: cover;
            border-radius: 9px;
            background: #eee;
            flex-shrink: 0;
        }

        .kapak-yok {
            width: 100px;
            height: 140px;
            min-width: 100px;
            border-radius: 9px;
            background: #ffe4d2;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #d95510;
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            flex-shrink: 0;
        }

        .hikaye-bilgi {
            flex: 1;
            min-width: 220px;
        }

        .hikaye-bilgi h2 {
            margin: 0 0 12px;
            color: #333;
        }

        .hikaye-bilgi p {
            line-height: 1.6;
            color: #666;
            margin: 7px 0;
        }

        .durum {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .durum-taslak {
            background: #fff0c2;
            color: #946200;
        }

        .durum-yayinda {
            background: #d8f5df;
            color: #20743a;
        }

        .durum-tamamlandi {
            background: #dce9ff;
            color: #2456a0;
        }

        .bos-alan {
            background: white;
            padding: 40px 20px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .bos-alan h2 {
            color: #f36b21;
        }

        @media (max-width: 600px) {
            .ust-menu {
                flex-direction: column;
                align-items: stretch;
            }

            .header-butonlar {
                flex-direction: column;
                width: 100%;
            }

            .header-buton {
                width: 100%;
                box-sizing: border-box;
            }

            .sayfa {
                width: 92%;
                margin: 25px auto;
            }

            .hikaye-kart {
                gap: 15px;
            }

            .hikaye-kapak {
                width: 100px;
                height: 140px;
            }

            .kapak-yok {
                width: 100px;
                height: 140px;
            }

            .sayfa-baslik {
                align-items: flex-start;
            }

            .hikaye-bilgi {
                min-width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="ust-menu">
    <h2>📚 WordNest</h2>

    <div class="header-butonlar">
        <a href="../index.php" class="header-buton ana-sayfa-buton">
            🏠 Ana Sayfaya Dön
        </a>
    </div>
</header>

<div class="sayfa">

    <div class="sayfa-baslik">
        <h1>📚 Hikâyelerim</h1>

        <a href="ekle.php" class="buton">
            + Yeni Hikâye Oluştur
        </a>
    </div>

    <?php if (count($hikayeler) > 0): ?>

        <?php foreach ($hikayeler as $hikaye): ?>

            <div class="hikaye-kart">

                <?php if (!empty($hikaye["kapak_resmi"])): ?>

                    <img
                        src="../uploads/kapaklar/<?php echo rawurlencode(basename($hikaye["kapak_resmi"])); ?>"
                        alt="Hikâye Kapağı"
                        class="hikaye-kapak"
                    >

                <?php else: ?>

                    <div class="kapak-yok">
                        Kapak<br>Görseli Yok
                    </div>

                <?php endif; ?>

                <div class="hikaye-bilgi">

                    <h2><?php echo guvenli($hikaye["baslik"]); ?></h2>

                    <p>
                        <?php
                        $aciklama = $hikaye["aciklama"] ?? "";
                        echo guvenli(mb_strimwidth($aciklama, 0, 180, "...", "UTF-8"));
                        ?>
                    </p>

                    <p>
                        <strong>Kategori:</strong>
                        <?php echo guvenli($hikaye["kategori_adi"] ?? "Kategorisiz"); ?>
                    </p>

                    <p>
                        <strong>Bölüm Sayısı:</strong>
                        <?php echo (int) $hikaye["bolum_sayisi"]; ?>
                    </p>

                    <p>
                        <strong>Durum:</strong>

                        <?php
                        $durum = $hikaye["durum"];
                        $durum_sinifi = "durum-taslak";

                        if ($durum === "yayinda") {
                            $durum_sinifi = "durum-yayinda";
                        } elseif ($durum === "tamamlandi") {
                            $durum_sinifi = "durum-tamamlandi";
                        }
                        ?>

                        <span class="durum <?php echo $durum_sinifi; ?>">
                            <?php echo guvenli(ucfirst($durum)); ?>
                        </span>
                    </p>

                    <div style="margin-top: 15px;">

                        <a
                            href="bolum_ekle.php?hikaye_id=<?php echo (int) $hikaye["id"]; ?>"
                            class="buton"
                        >
                            + Bölüm Ekle
                        </a>

                        <a
                            href="bolumlerim.php?hikaye_id=<?php echo (int) $hikaye["id"]; ?>"
                            class="buton buton-ikincil"
                        >
                            Bölümlerimi Yönet
                        </a>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="bos-alan">
            <h2>Henüz bir hikâyen yok 📖</h2>

            <p>
                WordNest'te kendi hikâyeni oluşturmaya başlayabilirsin.
            </p>

            <a href="ekle.php" class="buton">
                İlk Hikâyemi Oluştur
            </a>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
