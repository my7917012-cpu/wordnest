<?php
require_once __DIR__ . "/../config/baglanti.php";
require_once __DIR__ . "/../config/email_sender.php";

$email = trim($_GET["email"] ?? $_POST["email"] ?? "");
$hata = "";
$bilgi = "";

if (($_GET["gonderildi"] ?? "") === "1") {
    $bilgi = "Doğrulama kodunu e-posta adresine gönderdik. Kod 10 dakika geçerlidir.";
} elseif (($_GET["gonderildi"] ?? "") === "0") {
    $hata = "E-posta gönderilemedi. Ayarlar düzeltildikten sonra yeni kod isteyebilirsin.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $islem = $_POST["islem"] ?? "dogrula";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hata = "E-posta adresi geçersiz.";
    } else {
        $sorgu = $db->prepare("SELECT id, ad_soyad, email, email_dogrulandi, email_dogrulama_hash, (email_dogrulama_son_tarih >= NOW()) AS kod_gecerli FROM kullanicilar WHERE email = ? LIMIT 1");
        $sorgu->execute([$email]);
        $uye = $sorgu->fetch();

        if (!$uye) {
            $hata = "Bu e-posta ile bir üyelik bulunamadı.";
        } elseif ((int) $uye["email_dogrulandi"] === 1) {
            $bilgi = "E-posta adresin zaten doğrulanmış. Giriş yapabilirsin.";
        } elseif ($islem === "yeniden_gonder") {
            $kod = (string) random_int(100000, 999999);
            $kodHash = hash("sha256", $kod);
            $guncelle = $db->prepare("UPDATE kullanicilar SET email_dogrulama_hash = ?, email_dogrulama_son_tarih = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
            $guncelle->execute([$kodHash, $uye["id"]]);
            try {
                wordnestSendVerificationCode($uye["email"], $uye["ad_soyad"], $kod);
                $bilgi = "Yeni doğrulama kodunu e-posta adresine gönderdik.";
            } catch (Throwable $e) {
                error_log("WordNest doğrulama e-postası gönderilemedi: " . $e->getMessage());
                $hata = "E-posta gönderilemedi. Gmail ayarlarını kontrol et.";
            }
        } else {
            $kod = trim($_POST["kod"] ?? "");
            $gonderilenHash = hash("sha256", $kod);
            if (!preg_match("/^\d{6}$/", $kod) || (int) $uye["kod_gecerli"] !== 1 || !hash_equals((string) $uye["email_dogrulama_hash"], $gonderilenHash)) {
                $hata = "Kod hatalı veya süresi dolmuş. Yeni kod isteyebilirsin.";
            } else {
                $dogrula = $db->prepare("UPDATE kullanicilar SET email_dogrulandi = 1, email_dogrulama_hash = NULL, email_dogrulama_son_tarih = NULL WHERE id = ?");
                $dogrula->execute([$uye["id"]]);
                header("Location: giris.php?dogrulandi=1");
                exit;
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
    <title>WordNest - E-posta Doğrulama</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{margin:0;min-height:100vh;background:#fff5ec;font-family:Arial,sans-serif;display:grid;place-items:center;padding:20px;box-sizing:border-box}
        .kutu{width:100%;max-width:440px;background:#fff;padding:32px;border-radius:16px;box-shadow:0 10px 35px rgba(0,0,0,.08);box-sizing:border-box}
        h1{text-align:center;margin-top:0}.aciklama{text-align:center;color:#666;line-height:1.5}
        label{display:block;font-weight:bold;margin:18px 0 7px}input{width:100%;box-sizing:border-box;padding:13px;border:1px solid #ddd;border-radius:8px;font-size:16px}
        button{width:100%;margin-top:14px;padding:13px;border:0;border-radius:8px;background:#f07824;color:#fff;font-weight:bold;font-size:16px;cursor:pointer}
        .mesaj{padding:12px;border-radius:8px;margin:14px 0;text-align:center}.hata{background:#ffe6e6;color:#a00}.bilgi{background:#e5f8e9;color:#247a36}
        .alt{text-align:center;margin-top:20px}.alt a{color:#e85d00;font-weight:bold}
    </style>
<link rel="stylesheet" href="../assets/css/gece-modu.css">
<script>try{if(localStorage.getItem("wordnest-tema")==="gece"){document.documentElement.setAttribute("data-wordnest-theme","gece")}}catch(e){}</script>
<link rel="stylesheet" href="../assets/css/dil-degistir.css">
<script>try{if(localStorage.getItem("wordnest-dil")==="en"){document.documentElement.lang="en";document.documentElement.setAttribute("data-wordnest-language","en")}}catch(e){}</script>
</head>
<body>
<main class="kutu">
    <h1>📩 E-posta doğrulama</h1>
    <p class="aciklama">WordNest üyeliğini tamamlamak için e-posta adresine gönderilen 6 haneli kodu gir.</p>
    <?php if ($hata !== ""): ?><div class="mesaj hata"><?= htmlspecialchars($hata, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <?php if ($bilgi !== ""): ?><div class="mesaj bilgi"><?= htmlspecialchars($bilgi, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <form method="post">
        <label for="email">E-posta adresin</label>
        <input id="email" type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>" required>
        <label for="kod">6 haneli kod</label>
        <input id="kod" type="text" name="kod" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
        <button type="submit" name="islem" value="dogrula">E-postamı doğrula</button>
    </form>
    <form method="post">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, "UTF-8") ?>">
        <button type="submit" name="islem" value="yeniden_gonder">Yeni kod gönder</button>
    </form>
    <p class="alt"><a href="giris.php">Giriş sayfasına dön</a></p>
</main>
<script src="../assets/js/gece-modu.js"></script>
<script src="../assets/js/dil-degistir.js"></script>
</body>
</html>