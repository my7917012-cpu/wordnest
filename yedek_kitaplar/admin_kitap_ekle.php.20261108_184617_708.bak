<?php
session_start();
require_once __DIR__ . "/../config/baglanti.php";
require_once __DIR__ . "/../kitap_metni_araclari.php";

if (!isset($_SESSION["kullanici_id"]) || ($_SESSION["rol"] ?? "") !== "admin") {
    header("Location: ../kullanici/giris.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$mesaj = "";
$mesajTuru = "";
$kategoriler = $db->query("SELECT id, kategori_adi FROM kategoriler ORDER BY kategori_adi")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        $mesaj = "İşlem doğrulanamadı. Sayfayı yenileyip tekrar dene.";
        $mesajTuru = "hata";
    } elseif (($_POST["eylem"] ?? "") === "sil") {
        $id = filter_input(INPUT_POST, "kitap_id", FILTER_VALIDATE_INT);
        if (!$id) {
            $mesaj = "Kitap seçilemedi.";
            $mesajTuru = "hata";
        } else {
            $sorgu = $db->prepare("SELECT dosya_adi FROM kitap_dosyalari WHERE id = ?");
            $sorgu->execute([$id]);
            $kitap = $sorgu->fetch();
            if ($kitap) {
                $sil = $db->prepare("DELETE FROM kitap_dosyalari WHERE id = ?");
                $sil->execute([$id]);
                $dosya = __DIR__ . "/../uploads/kitaplar/" . basename($kitap["dosya_adi"]);
                if (is_file($dosya)) {
                    unlink($dosya);
                }
                $mesaj = "Kitap WordNest'ten kaldırıldı.";
                $mesajTuru = "basarili";
            } else {
                $mesaj = "Kitap bulunamadı.";
                $mesajTuru = "hata";
            }
        }
    } else {
        $kategoriId = filter_input(INPUT_POST, "kategori_id", FILTER_VALIDATE_INT);
        $baslik = trim((string) ($_POST["baslik"] ?? ""));
        $yazar = trim((string) ($_POST["yazar"] ?? ""));
        $dosya = $_FILES["kitap_dosyasi"] ?? null;

        if (!$kategoriId || $baslik === "" || mb_strlen($baslik, "UTF-8") > 200 || mb_strlen($yazar, "UTF-8") > 150) {
            $mesaj = "Kategori, en fazla 200 karakterlik kitap adı ve geçerli yazar bilgisi gerekli.";
            $mesajTuru = "hata";
        } elseif (empty($_POST["icerik_onayi"])) {
            $mesaj = "Yüklemeden önce kitabın Türkçe, uygun içerikli ve yükleme iznine sahip olduğunu onayla.";
            $mesajTuru = "hata";
        } elseif (!$dosya || $dosya["error"] !== UPLOAD_ERR_OK) {
            $mesaj = "Dosya yüklenemedi. Dosya boyutunun PHP yükleme sınırını aşmadığını kontrol et.";
            $mesajTuru = "hata";
        } else {
            $uzanti = strtolower(pathinfo($dosya["name"], PATHINFO_EXTENSION));
            $gecerliBoyut = $dosya["size"] > 0 && $dosya["size"] <= 15 * 1024 * 1024;
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($dosya["tmp_name"]);
            $gecerliMime = ($uzanti === "pdf" && $mime === "application/pdf")
                || ($uzanti === "txt" && in_array($mime, ["text/plain", "application/octet-stream"], true));
            $kategoriKontrol = $db->prepare("SELECT COUNT(*) FROM kategoriler WHERE id = ?");
            $kategoriKontrol->execute([$kategoriId]);

            if (!in_array($uzanti, ["pdf", "txt"], true) || !$gecerliMime || !$gecerliBoyut) {
                $mesaj = "Yalnızca geçerli PDF veya TXT dosyası yüklenebilir. En fazla 15 MB.";
                $mesajTuru = "hata";
            } elseif ((int) $kategoriKontrol->fetchColumn() !== 1) {
                $mesaj = "Seçilen kategori bulunamadı.";
                $mesajTuru = "hata";
            } else {
                try {
                    if ($uzanti === "pdf") {
                        $sayfalar = wordnestPdfSayfalariniOku($dosya["tmp_name"]);
                        if (trim(implode("", $sayfalar)) === "") {
                            throw new RuntimeException("PDF'den yazı çıkarılamadı. Taranmış görüntü PDF'leri yerine metin içeren bir PDF veya TXT yükle.");
                        }
                    } else {
                        $hamMetin = file_get_contents($dosya["tmp_name"]);
                        if ($hamMetin === false) {
                            throw new RuntimeException("TXT dosyası okunamadı.");
                        }
                        if (!mb_check_encoding($hamMetin, "UTF-8")) {
                            $hamMetin = mb_convert_encoding($hamMetin, "UTF-8", "Windows-1254");
                        }
                        $sayfalar = wordnestMetniSayfalaraBol($hamMetin);
                    }
                    if (empty($sayfalar)) {
                        throw new RuntimeException("Dosyada okunacak metin bulunamadı.");
                    }
                    $icerik = json_encode(["v" => 1, "sayfalar" => $sayfalar], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    if ($icerik === false) {
                        throw new RuntimeException("Kitap sayfaları kaydedilemedi.");
                    }
                } catch (Throwable $e) {
                    $mesaj = $e instanceof RuntimeException
                        ? $e->getMessage()
                        : "Dosyanın metni okunamadı. Metin içeren PDF veya TXT deneyebilirsin.";
                    $mesajTuru = "hata";
                }

                if ($mesaj === "") {
                    $klasor = __DIR__ . "/../uploads/kitaplar";
                    if (!is_dir($klasor) && !mkdir($klasor, 0755, true) && !is_dir($klasor)) {
                        throw new RuntimeException("Kitap yükleme klasörü oluşturulamadı.");
                    }
                    $dosyaAdi = bin2hex(random_bytes(16)) . "." . $uzanti;
                    $hedef = $klasor . "/" . $dosyaAdi;
                    if (!move_uploaded_file($dosya["tmp_name"], $hedef)) {
                        $mesaj = "Dosya WordNest klasörüne kaydedilemedi.";
                        $mesajTuru = "hata";
                    } else {
                        try {
                            $ekle = $db->prepare("INSERT INTO kitap_dosyalari (kategori_id, baslik, yazar, uzanti, dosya_adi, icerik) VALUES (?, ?, ?, ?, ?, ?)");
                            $ekle->execute([$kategoriId, $baslik, $yazar, $uzanti, $dosyaAdi, $icerik]);
                            $mesaj = "Kitap yüklendi. WordNest'in okuma sayfasında görünecek.";
                            $mesajTuru = "basarili";
                        } catch (Throwable $e) {
                            if (is_file($hedef)) {
                                unlink($hedef);
                            }
                            throw $e;
                        }
                    }
                }
            }
        }
    }
}

$kitapSorgu = $db->query("SELECT k.id, k.baslik, k.yazar, k.uzanti, c.kategori_adi FROM kitap_dosyalari k JOIN kategoriler c ON c.id = k.kategori_id ORDER BY k.eklenme_tarihi DESC");
$kitaplar = $kitapSorgu->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Yönetimi - WordNest</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{background:#fffaf5}.kitap-admin{max-width:950px;margin:38px auto;padding:0 20px 50px}.kitap-admin h1{font-size:30px;margin:0 0 8px}.kitap-admin .aciklama{color:#777;margin-bottom:24px}.kitap-form,.kitap-satir{background:#fff;border:1px solid #f2dfd1;border-radius:14px;padding:22px;margin:16px 0;box-shadow:0 5px 20px rgba(0,0,0,.04)}.kitap-form label{display:block;font-weight:700;margin:14px 0 6px}.kitap-form input,.kitap-form select{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font:inherit}.kitap-form input[type=checkbox]{width:auto;margin-right:8px}.kitap-form .onay{font-weight:400;line-height:1.5}.kitap-form button,.kitap-satir button{border:0;background:#f07824;color:white;border-radius:8px;padding:12px 18px;font-weight:bold;cursor:pointer;margin-top:15px}.kitap-satir{display:flex;justify-content:space-between;gap:20px;align-items:center}.kitap-satir form{margin:0}.mesaj{padding:13px 16px;border-radius:9px;background:#e8f7e9;margin:16px 0}.mesaj.hata{background:#fff0ed;color:#8d2715}.kitap-admin nav{display:flex;gap:18px;margin:18px 0;color:#e85d00;font-weight:bold}@media(max-width:600px){.kitap-satir{align-items:flex-start;flex-direction:column}}
    </style>
</head>
<body>
<header class="ust-menu"><div class="container menu-icerik"><a href="../index.php" class="logo"><span>Word</span>Nest</a><nav class="ana-menu"><a href="panel.php">Yönetim Paneli</a><a href="../kitaplar.php">Kitaplar</a></nav><div class="menu-butonlar"><a class="giris-buton" href="../index.php">Ana Sayfa</a></div></div></header>
<main class="kitap-admin">
    <h1>Kitap Yönetimi</h1>
    <p class="aciklama">Türkçe ve uygun olduğunu kontrol ettiğin, yükleme iznin bulunan PDF veya TXT kitabı bilgisayarından seçip WordNest'e ekle. PDF, WordNest'in kendi okuma görünümünde açılır. En fazla 15 MB.</p>
    <?php if ($mesaj !== ""): ?><div class="mesaj <?= htmlspecialchars($mesajTuru, ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($mesaj, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <form class="kitap-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
        <label for="baslik">Kitabın adı</label><input id="baslik" name="baslik" maxlength="200" required>
        <label for="yazar">Yazar</label><input id="yazar" name="yazar" maxlength="150">
        <label for="kategori_id">Kategori</label><select id="kategori_id" name="kategori_id" required><option value="">Kategori seç</option><?php foreach ($kategoriler as $kategori): ?><option value="<?= (int) $kategori["id"] ?>"><?= htmlspecialchars($kategori["kategori_adi"], ENT_QUOTES, "UTF-8") ?></option><?php endforeach; ?></select>
        <label for="kitap_dosyasi">Kitap dosyası (PDF veya TXT)</label><input id="kitap_dosyasi" name="kitap_dosyasi" type="file" accept=".pdf,.txt,application/pdf,text/plain" required>
        <label class="onay"><input type="checkbox" name="icerik_onayi" value="1" required> Kitabın Türkçe ve WordNest'e uygun olduğunu kontrol ettim; bu dosyayı yükleme ve paylaşma iznim var.</label>
        <button type="submit">Kitabı WordNest'e yükle</button>
    </form>
    <h2>Eklenen kitaplar</h2>
    <?php if (!$kitaplar): ?><p>Henüz kitap eklenmedi.</p><?php endif; ?>
    <?php foreach ($kitaplar as $kitap): ?><div class="kitap-satir"><div><strong><?= htmlspecialchars($kitap["baslik"], ENT_QUOTES, "UTF-8") ?></strong><br><?= htmlspecialchars($kitap["yazar"] ?: "Yazar belirtilmedi", ENT_QUOTES, "UTF-8") ?> · <?= htmlspecialchars($kitap["kategori_adi"], ENT_QUOTES, "UTF-8") ?> · <?= strtoupper(htmlspecialchars($kitap["uzanti"], ENT_QUOTES, "UTF-8")) ?><br><a href="../kitap_oku.php?id=<?= (int) $kitap["id"] ?>">WordNest'te görüntüle</a></div><form method="post" onsubmit="return confirm('Bu kitap WordNest’ten kaldırılsın mı?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"><input type="hidden" name="eylem" value="sil"><input type="hidden" name="kitap_id" value="<?= (int) $kitap["id"] ?>"><button type="submit">Kaldır</button></form></div><?php endforeach; ?>
</main>
</body>
</html>
