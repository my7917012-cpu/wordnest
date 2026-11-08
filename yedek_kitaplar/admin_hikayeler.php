
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

// CSRF token oluştur
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mesaj = "";
$mesajTuru = "";

// Hikâye durumunu güncelle
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $mesaj = "Güvenlik doğrulaması başarısız!";
        $mesajTuru = "hata";
    } else {

        $hikayeId = filter_input(INPUT_POST, "hikaye_id", FILTER_VALIDATE_INT);
        $yeniDurum = $_POST["yeni_durum"] ?? "";

        if (!$hikayeId || !in_array($yeniDurum, ["taslak", "yayinda"], true)) {
            $mesaj = "Geçersiz işlem!";
            $mesajTuru = "hata";
        } else {
            try {
                $guncelle = $db->prepare("
                    UPDATE hikayeler
                    SET durum = ?, guncelleme_tarihi = NOW()
                    WHERE id = ?
                ");

                $guncelle->execute([$yeniDurum, $hikayeId]);

                if ($guncelle->rowCount() > 0) {
                    $mesaj = $yeniDurum === "yayinda"
                        ? "Hikâye başarıyla yayınlandı."
                        : "Hikâye taslağa alındı.";
                    $mesajTuru = "basarili";
                } else {
                    $kontrol = $db->prepare("SELECT id, durum FROM hikayeler WHERE id = ?");
                    $kontrol->execute([$hikayeId]);
                    $mevcut = $kontrol->fetch();

                    if ($mevcut && $mevcut["durum"] === $yeniDurum) {
                        $mesaj = "Hikâyenin durumu zaten seçtiğin durumda.";
                        $mesajTuru = "bilgi";
                    } else {
                        $mesaj = "Hikâye bulunamadı.";
                        $mesajTuru = "hata";
                    }
                }

            } catch (PDOException $e) {
                $mesaj = "Hikâye güncellenirken bir hata oluştu.";
                $mesajTuru = "hata";
            }
        }
    }
}

// Hikâyeleri getir
$sorgu = $db->query("
    SELECT
        hikayeler.*,
        kullanicilar.kullanici_adi,
        kategoriler.kategori_adi,
        (
            SELECT COUNT(*)
            FROM bolumler
            WHERE bolumler.hikaye_id = hikayeler.id
        ) AS bolum_sayisi
    FROM hikayeler
    LEFT JOIN kullanicilar
        ON hikayeler.kullanici_id = kullanicilar.id
    LEFT JOIN kategoriler
        ON hikayeler.kategori_id = kategoriler.id
    ORDER BY hikayeler.olusturma_tarihi DESC
");

$hikayeler = $sorgu->fetchAll();

// İstatistikler
$toplamHikaye = count($hikayeler);
$yayindakiHikaye = 0;
$taslakHikaye = 0;
$tamamlananHikaye = 0;

foreach ($hikayeler as $hikaye) {
    if ($hikaye["durum"] === "yayinda") {
        $yayindakiHikaye++;
    } elseif ($hikaye["durum"] === "taslak") {
        $taslakHikaye++;
    } elseif ($hikaye["durum"] === "tamamlandi") {
        $tamamlananHikaye++;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hikâye Yönetimi - WordNest</title>

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

        .ust-menu {
            background: #ff6b00;
            padding: 17px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .logo {
            color: white;
            font-size: 26px;
            font-weight: bold;
            text-decoration: none;
        }

        .logo span {
            color: #fff0e5;
        }

        .ust-butonlar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ust-buton {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 9px;
            text-decoration: none;
            font-weight: bold;
            border: 2px solid white;
            transition: .2s;
        }

        .ana-sayfa {
            background: white;
            color: #e85d00;
        }

        .geri-don {
            background: #e85d00;
            color: white;
        }

        .ust-buton:hover {
            opacity: .85;
            transform: translateY(-2px);
        }

        .container {
            width: 92%;
            max-width: 1250px;
            margin: 35px auto;
        }

        .sayfa-baslik {
            margin-bottom: 25px;
        }

        .sayfa-baslik span {
            color: #ff6b00;
            font-weight: bold;
            font-size: 13px;
        }

        .sayfa-baslik h1 {
            font-size: 30px;
            margin-top: 8px;
            color: #333;
        }

        .sayfa-baslik p {
            color: #777;
            margin-top: 8px;
            line-height: 1.6;
        }

        .istatistikler {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .istatistik-kart {
            background: white;
            padding: 23px;
            border-radius: 14px;
            border: 1px solid #f2e6dc;
            box-shadow: 0 4px 15px rgba(0,0,0,.04);
        }

        .istatistik-kart p {
            color: #777;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .istatistik-kart h2 {
            font-size: 29px;
            color: #e85d00;
        }

        .mesaj {
            padding: 14px 18px;
            border-radius: 9px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .mesaj.basarili {
            background: #e8f7ed;
            color: #217a3d;
        }

        .mesaj.hata {
            background: #ffe9e9;
            color: #b42318;
        }

        .mesaj.bilgi {
            background: #fff0e5;
            color: #b84a00;
        }

        .liste-karti {
            background: white;
            border-radius: 15px;
            border: 1px solid #f2e6dc;
            padding: 23px;
            box-shadow: 0 4px 15px rgba(0,0,0,.04);
        }

        .liste-baslik {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .liste-baslik h2 {
            font-size: 21px;
        }

        .arama {
            padding: 11px 14px;
            border: 1px solid #e5d8ce;
            border-radius: 9px;
            outline: none;
            width: 260px;
            max-width: 100%;
            font-size: 14px;
        }

        .arama:focus {
            border-color: #ff6b00;
        }

        .tablo-alani {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            text-align: left;
            padding: 14px 12px;
            background: #fff4eb;
            color: #b84a00;
            font-size: 13px;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #f1e9e3;
            font-size: 14px;
            vertical-align: middle;
        }

        tr:hover td {
            background: #fffaf6;
        }

        .hikaye-baslik {
            font-weight: bold;
            color: #333;
            max-width: 220px;
        }

        .durum {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .durum.yayinda {
            background: #e5f7eb;
            color: #218344;
        }

        .durum.taslak {
            background: #fff1dc;
            color: #a86400;
        }

        .durum.tamamlandi {
            background: #e9eaff;
            color: #5557a5;
        }

        .islem-formu {
            display: inline-block;
        }

        .islem-buton {
            padding: 9px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: .2s;
        }

        .taslak-buton {
            background: #fff0df;
            color: #a85b00;
        }

        .yayin-buton {
            background: #e5f7eb;
            color: #218344;
        }

        .islem-buton:hover {
            opacity: .8;
            transform: translateY(-1px);
        }

        .bos-mesaj {
            text-align: center;
            padding: 40px 15px;
            color: #888;
        }

        .gizli {
            display: none;
        }

        @media(max-width: 850px) {
            .istatistikler {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width: 600px) {
            .ust-menu {
                flex-direction: column;
                align-items: stretch;
            }

            .ust-butonlar {
                flex-direction: column;
            }

            .ust-buton {
                text-align: center;
            }

            .sayfa-baslik h1 {
                font-size: 25px;
            }

            .istatistikler {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .istatistik-kart {
                padding: 16px;
            }

            .istatistik-kart h2 {
                font-size: 24px;
            }

            .liste-karti {
                padding: 15px;
            }

            .arama {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header class="ust-menu">

    <a href="../index.php" class="logo">
        <span>Word</span>Nest
    </a>

    <div class="ust-butonlar">
        <a href="../index.php" class="ust-buton ana-sayfa">
            🏠 Ana Sayfaya Dön
        </a>

        <a href="panel.php" class="ust-buton geri-don">
            ⚙️ Yönetim Paneline Dön
        </a>
    </div>

</header>

<main class="container">

    <div class="sayfa-baslik">
        <span>WORDNEST YÖNETİM</span>
        <h1>📚 Hikâye Yönetimi</h1>
        <p>Platformdaki hikâyeleri görüntüleyebilir ve yayın durumlarını yönetebilirsin.</p>
    </div>

    <?php if ($mesaj !== ""): ?>
        <div class="mesaj <?= htmlspecialchars($mesajTuru) ?>">
            <?= htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="istatistikler">

        <div class="istatistik-kart">
            <p>Toplam Hikâye</p>
            <h2><?= $toplamHikaye ?></h2>
        </div>

        <div class="istatistik-kart">
            <p>Yayındaki Hikâyeler</p>
            <h2><?= $yayindakiHikaye ?></h2>
        </div>

        <div class="istatistik-kart">
            <p>Taslak Hikâyeler</p>
            <h2><?= $taslakHikaye ?></h2>
        </div>

        <div class="istatistik-kart">
            <p>Tamamlanan Hikâyeler</p>
            <h2><?= $tamamlananHikaye ?></h2>
        </div>

    </section>

    <section class="liste-karti">

        <div class="liste-baslik">
            <h2>Hikâye Listesi</h2>

            <input
                type="text"
                id="hikayeArama"
                class="arama"
                placeholder="Hikâye veya yazar ara..."
            >
        </div>

        <div class="tablo-alani">

            <table id="hikayeTablosu">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hikâye</th>
                        <th>Yazar</th>
                        <th>Kategori</th>
                        <th>Bölüm</th>
                        <th>Görüntülenme</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($hikayeler) > 0): ?>

                    <?php foreach ($hikayeler as $hikaye): ?>

                        <tr class="hikaye-satiri">

                            <td>
                                #<?= (int)$hikaye["id"] ?>
                            </td>

                            <td class="hikaye-baslik">
                                <?= htmlspecialchars($hikaye["baslik"], ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($hikaye["kullanici_adi"] ?? "Bilinmiyor", ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($hikaye["kategori_adi"] ?? "Kategorisiz", ENT_QUOTES, "UTF-8") ?>
                            </td>

                            <td>
                                <?= (int)$hikaye["bolum_sayisi"] ?>
                            </td>

                            <td>
                                <?= number_format((int)$hikaye["goruntulenme"], 0, ",", ".") ?>
                            </td>

                            <td>
                                <span class="durum <?= htmlspecialchars($hikaye["durum"]) ?>">
                                    <?php
                                    if ($hikaye["durum"] === "yayinda") {
                                        echo "Yayında";
                                    } elseif ($hikaye["durum"] === "taslak") {
                                        echo "Taslak";
                                    } elseif ($hikaye["durum"] === "tamamlandi") {
                                        echo "Tamamlandı";
                                    } else {
                                        echo htmlspecialchars($hikaye["durum"]);
                                    }
                                    ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($hikaye["durum"] === "yayinda"): ?>

                                    <form method="POST" class="islem-formu"
                                          onsubmit="return confirm('Bu hikâyeyi taslağa almak istediğine emin misin?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden" name="hikaye_id"
                                               value="<?= (int)$hikaye["id"] ?>">

                                        <input type="hidden" name="yeni_durum"
                                               value="taslak">

                                        <button type="submit" class="islem-buton taslak-buton">
                                            Taslağa Al
                                        </button>

                                    </form>

                                <?php elseif ($hikaye["durum"] === "taslak"): ?>

                                    <form method="POST" class="islem-formu"
                                          onsubmit="return confirm('Bu hikâyeyi yayınlamak istediğine emin misin?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden" name="hikaye_id"
                                               value="<?= (int)$hikaye["id"] ?>">

                                        <input type="hidden" name="yeni_durum"
                                               value="yayinda">

                                        <button type="submit" class="islem-buton yayin-buton">
                                            Yayınla
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span style="color:#999;">İşlem yok</span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="bos-mesaj">
                            Henüz platformda hikâye bulunmuyor.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

            <p id="aramaSonuc" class="bos-mesaj gizli">
                Aramana uygun hikâye bulunamadı.
            </p>

        </div>

    </section>

</main>

<script>
const aramaKutusu = document.getElementById("hikayeArama");
const satirlar = document.querySelectorAll(".hikaye-satiri");
const aramaSonuc = document.getElementById("aramaSonuc");

aramaKutusu.addEventListener("input", function () {
    const aranan = this.value.toLocaleLowerCase("tr-TR");
    let gorunen = 0;

    satirlar.forEach(function (satir) {
        const metin = satir.innerText.toLocaleLowerCase("tr-TR");

        if (metin.includes(aranan)) {
            satir.classList.remove("gizli");
            gorunen++;
        } else {
            satir.classList.add("gizli");
        }
    });

    if (gorunen === 0 && satirlar.length > 0) {
        aramaSonuc.classList.remove("gizli");
    } else {
        aramaSonuc.classList.add("gizli");
    }
});
</script>

</body>
</html>
