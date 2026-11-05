
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

$hata = "";
$basari = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $adSoyad = trim($_POST["ad_soyad"] ?? "");
    $kullaniciAdi = trim($_POST["kullanici_adi"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $sifre = $_POST["sifre"] ?? "";
    $sifreTekrar = $_POST["sifre_tekrar"] ?? "";

    if (
        empty($adSoyad) ||
        empty($kullaniciAdi) ||
        empty($email) ||
        empty($sifre) ||
        empty($sifreTekrar)
    ) {
        $hata = "Lütfen bütün alanları doldurun.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hata = "Geçerli bir e-posta adresi girin.";

    } elseif (strlen($sifre) < 6) {
        $hata = "Şifreniz en az 6 karakter olmalıdır.";

    } elseif ($sifre !== $sifreTekrar) {
        $hata = "Şifreler birbiriyle uyuşmuyor.";

    } else {

        $kontrol = $db->prepare("
            SELECT id FROM kullanicilar
            WHERE kullanici_adi = ? OR email = ?
        ");

        $kontrol->execute([$kullaniciAdi, $email]);

        if ($kontrol->fetch()) {
            $hata = "Bu kullanıcı adı veya e-posta zaten kullanılıyor.";

        } else {

            $sifreHash = password_hash($sifre, PASSWORD_DEFAULT);

            $ekle = $db->prepare("
                INSERT INTO kullanicilar
                (kullanici_adi, ad_soyad, email, sifre)
                VALUES (?, ?, ?, ?)
            ");

            $ekle->execute([
                $kullaniciAdi,
                $adSoyad,
                $email,
                $sifreHash
            ]);

            $basari = "Kaydınız başarıyla oluşturuldu! Giriş yapabilirsiniz.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordNest - Kayıt Ol</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #fff5ec;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .kayit-alani {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 15px;
        }

        .kayit-kutu {
            width: 100%;
            max-width: 480px;
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.07);
        }

        .kayit-kutu h1 {
            text-align: center;
            margin-bottom: 8px;
            font-size: 30px;
        }

        .kayit-kutu .aciklama {
            text-align: center;
            color: #888;
            margin-bottom: 25px;
        }

        .form-grup {
            margin-bottom: 17px;
        }

        .form-grup label {
            display: block;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-grup input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            font-size: 15px;
        }

        .form-grup input:focus {
            border-color: #f07824;
        }

        .kayit-submit {
            width: 100%;
            border: none;
            padding: 14px;
            background: #f07824;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 8px;
        }

        .kayit-submit:hover {
            background: #d96313;
        }

        .mesaj {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
            text-align: center;
        }

        .hata {
            background: #ffe6e6;
            color: #b00020;
        }

        .basari {
            background: #e5f8e9;
            color: #247a36;
        }

        .alt-link {
            text-align: center;
            margin-top: 22px;
            color: #777;
        }

        .alt-link a {
            color: #f07824;
            font-weight: bold;
        }

        @media (max-width: 500px) {
            .kayit-kutu {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

<header class="ust-menu">
    <div class="container menu-icerik">
        <a href="../index.php" class="logo">
            <span>Word</span>Nest
        </a>

        <a href="../index.php" class="giris-buton">
            Ana Sayfaya Dön
        </a>
    </div>
</header>

<main class="kayit-alani">

    <div class="kayit-kutu">

        <h1>Word<span style="color:#f07824;">Nest</span></h1>

        <p class="aciklama">
            Aramıza katıl, hikayelerin dünyasını keşfet!
        </p>

        <?php if (!empty($hata)): ?>
            <div class="mesaj hata">
                <?php echo htmlspecialchars($hata); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($basari)): ?>
            <div class="mesaj basari">
                <?php echo htmlspecialchars($basari); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-grup">
                <label>Ad Soyad</label>
                <input
                    type="text"
                    name="ad_soyad"
                    placeholder="Adınızı ve soyadınızı girin"
                    value="<?php echo htmlspecialchars($_POST['ad_soyad'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-grup">
                <label>Kullanıcı Adı</label>
                <input
                    type="text"
                    name="kullanici_adi"
                    placeholder="Bir kullanıcı adı belirleyin"
                    value="<?php echo htmlspecialchars($_POST['kullanici_adi'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-grup">
                <label>E-posta</label>
                <input
                    type="email"
                    name="email"
                    placeholder="ornek@mail.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-grup">
                <label>Şifre</label>
                <input
                    type="password"
                    name="sifre"
                    placeholder="En az 6 karakter"
                    required
                >
            </div>

            <div class="form-grup">
                <label>Şifre Tekrar</label>
                <input
                    type="password"
                    name="sifre_tekrar"
                    placeholder="Şifrenizi tekrar girin"
                    required
                >
            </div>

            <button type="submit" class="kayit-submit">
                Hesap Oluştur
            </button>

        </form>

        <div class="alt-link">
            Zaten hesabın var mı?
            <a href="giris.php">Giriş Yap</a>
        </div>

    </div>

</main>

</body>
</html>
