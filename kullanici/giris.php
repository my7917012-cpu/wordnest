
<?php
session_start();

require_once __DIR__ . "/../config/baglanti.php";

$hata = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $kullanici = trim($_POST["kullanici"] ?? "");
    $sifre = $_POST["sifre"] ?? "";

    if (empty($kullanici) || empty($sifre)) {
        $hata = "Lütfen tüm alanları doldurun.";
    } else {

        $sorgu = $db->prepare(
            "SELECT id, kullanici_adi, ad_soyad, email, sifre, rol
             FROM kullanicilar
             WHERE email = ? OR kullanici_adi = ?
             LIMIT 1"
        );

        $sorgu->execute([$kullanici, $kullanici]);

        $uye = $sorgu->fetch();

        if ($uye && password_verify($sifre, $uye["sifre"])) {

            session_regenerate_id(true);

            $_SESSION["kullanici_id"] = $uye["id"];
            $_SESSION["kullanici_adi"] = $uye["kullanici_adi"];
            $_SESSION["ad_soyad"] = $uye["ad_soyad"];
            $_SESSION["email"] = $uye["email"];
            $_SESSION["rol"] = $uye["rol"];

            header("Location: ../index.php");
            exit;

        } else {
            $hata = "Kullanıcı bilgileri veya şifre hatalı.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - WordNest</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff8f2;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .giris-kutu {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 40px 32px;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }

        .logo {
            text-align: center;
            color: #f47b20;
            font-size: 34px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .alt-baslik {
            text-align: center;
            color: #777;
            margin-bottom: 30px;
        }

        .form-grup {
            margin-bottom: 20px;
        }

        .form-grup label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }

        .form-grup input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 9px;
            font-size: 15px;
            outline: none;
        }

        .form-grup input:focus {
            border-color: #f47b20;
        }

        .giris-btn {
            width: 100%;
            padding: 14px;
            background: #f47b20;
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .giris-btn:hover {
            background: #dc6814;
        }

        .hata {
            background: #ffe5e5;
            color: #b00020;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }

        .kayit-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }

        .kayit-link a {
            color: #f47b20;
            font-weight: bold;
            text-decoration: none;
        }

        .ana-sayfa {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #777;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="giris-kutu">

    <div class="logo">WordNest</div>

    <p class="alt-baslik">Hikâyelerin dünyasına hoş geldin!</p>

    <?php if (!empty($hata)): ?>
        <div class="hata">
            <?= htmlspecialchars($hata, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-grup">
            <label>E-posta veya Kullanıcı Adı</label>
            <input
                type="text"
                name="kullanici"
                placeholder="E-posta veya kullanıcı adınız"
                required
            >
        </div>

        <div class="form-grup">
            <label>Şifre</label>
            <input
                type="password"
                name="sifre"
                placeholder="Şifrenizi girin"
                required
            >
        </div>

        <button type="submit" class="giris-btn">
            Giriş Yap
        </button>

    </form>

    <div class="kayit-link">
        Hesabın yok mu?
        <a href="kayit.php">Hemen kayıt ol</a>
    </div>

    <a href="../index.php" class="ana-sayfa">
        ← Ana sayfaya dön
    </a>

</div>

</body>
</html>
