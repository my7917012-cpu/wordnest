
<?php
session_start();

require_once __DIR__ . "/config/baglanti.php";

// Kullanıcı giriş kontrolü
$girisYapildi = isset($_SESSION["kullanici_id"]);
$kullaniciAdi = $_SESSION["kullanici_adi"] ?? "";

// Kategorileri getir
$kategoriSorgu = $db->query("
    SELECT * FROM kategoriler
    ORDER BY kategori_adi ASC
");
$kategoriler = $kategoriSorgu->fetchAll();

// Son eklenen hikayeleri getir
$yeniSorgu = $db->query("
    SELECT
        hikayeler.*,
        kullanicilar.kullanici_adi,
        kategoriler.kategori_adi
    FROM hikayeler
    LEFT JOIN kullanicilar ON hikayeler.kullanici_id = kullanicilar.id
    LEFT JOIN kategoriler ON hikayeler.kategori_id = kategoriler.id
    WHERE hikayeler.durum IN ('yayinda', 'tamamlandi')
    ORDER BY hikayeler.olusturma_tarihi DESC
    LIMIT 8
");
$yeniHikayeler = $yeniSorgu->fetchAll();

// En çok görüntülenen hikayeleri getir
$populerSorgu = $db->query("
    SELECT
        hikayeler.*,
        kullanicilar.kullanici_adi,
        kategoriler.kategori_adi
    FROM hikayeler
    LEFT JOIN kullanicilar ON hikayeler.kullanici_id = kullanicilar.id
    LEFT JOIN kategoriler ON hikayeler.kategori_id = kategoriler.id
    WHERE hikayeler.durum IN ('yayinda', 'tamamlandi')
    ORDER BY hikayeler.goruntulenme DESC
    LIMIT 4
");
$populerHikayeler = $populerSorgu->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordNest - Hikayelerin Dünyası</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="ust-menu">
    <div class="container menu-icerik">

        <a href="index.php" class="logo">
            <span>Word</span>Nest
        </a>

        <nav class="ana-menu">
            <a href="index.php" class="aktif">Ana Sayfa</a>
            <a href="hikayeler/liste.php">Hikayeler</a>
            <a href="#kategoriler">Kategoriler</a>

            <?php if ($girisYapildi): ?>
                <a href="hikayeler/hikayelerim.php">Hikâyelerim</a>
            <?php endif; ?>
        </nav>

        <div class="menu-butonlar">

            <?php if ($girisYapildi): ?>

                <span class="hosgeldin">
                    Merhaba, <?= htmlspecialchars($kullaniciAdi, ENT_QUOTES, "UTF-8") ?>
                </span>

                <a href="kullanici/cikis.php" class="giris-buton">
                    Çıkış Yap
                </a>

            <?php else: ?>

                <a href="kullanici/giris.php" class="giris-buton">
                    Giriş Yap
                </a>

                <a href="kullanici/kayit.php" class="kayit-buton">
                    Kayıt Ol
                </a>

            <?php endif; ?>

        </div>

    </div>
</header>

<main>

    <section class="hero">
        <div class="container hero-icerik">

            <div class="hero-yazi">
                <span class="hero-etiket">WORDNEST'E HOŞ GELDİN</span>

                <h1>
                    Her hikayenin<br>
                    <span>bir dünyası var.</span>
                </h1>

                <p>
                    Yeni dünyalar keşfet, birbirinden güzel hikayeleri oku
                    ve kendi hikayeni yazmaya başla.
                </p>

                <div class="hero-butonlar">
                    <a href="hikayeler/liste.php" class="ana-buton">
                        Hikayeleri Keşfet
                    </a>

                    <?php if ($girisYapildi): ?>
                        <a href="hikayeler/ekle.php" class="ikincil-buton">
                            Hikaye Yaz
                        </a>

                        <a href="hikayeler/hikayelerim.php" class="ikincil-buton">
                            Hikâyelerim
                        </a>
                    <?php else: ?>
                        <a href="kullanici/kayit.php" class="ikincil-buton">
                            Yazar Ol
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-gorsel">
                <div class="kitap-kart">
                    <div class="kitap-emoji">📚</div>
                    <h3>Hayal Et.</h3>
                    <h3>Yaz.</h3>
                    <h3>Keşfet.</h3>
                    <p>Her kelime yeni bir başlangıçtır.</p>
                </div>
            </div>

        </div>
    </section>

    <section class="kategori-bolumu" id="kategoriler">
        <div class="container">

            <div class="bolum-baslik">
                <div>
                    <span class="ust-baslik">KEŞFET</span>
                    <h2>Kategoriler</h2>
                </div>
            </div>

            <div class="kategori-listesi">

                <?php if (count($kategoriler) > 0): ?>

                    <?php foreach ($kategoriler as $kategori): ?>

                        <a href="hikayeler/liste.php?kategori=<?= (int)$kategori['id'] ?>"
                           class="kategori-karti">

                            <span>📖</span>

                            <h3>
                                <?= htmlspecialchars($kategori['kategori_adi'], ENT_QUOTES, "UTF-8") ?>
                            </h3>

                        </a>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="bos-mesaj">Henüz kategori eklenmemiş.</p>

                <?php endif; ?>

            </div>

        </div>
    </section>

    <section class="hikaye-bolumu">
        <div class="container">

            <div class="bolum-baslik">
                <div>
                    <span class="ust-baslik">OKURLARIN SEÇİMİ</span>
                    <h2>Popüler Hikayeler</h2>
                </div>

                <a href="hikayeler/liste.php" class="tumunu-gor">
                    Tümünü Gör →
                </a>
            </div>

            <div class="hikaye-listesi">

                <?php if (count($populerHikayeler) > 0): ?>

                    <?php foreach ($populerHikayeler as $hikaye): ?>

                        <a href="hikayeler/detay.php?id=<?= (int)$hikaye['id'] ?>"
                           class="hikaye-karti">

                            <div class="hikaye-kapak">

                                <?php if (!empty($hikaye['kapak_resmi'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($hikaye['kapak_resmi'], ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($hikaye['baslik'], ENT_QUOTES, "UTF-8") ?>"
                                    >

                                <?php else: ?>

                                    <div class="kapak-yok">📖</div>

                                <?php endif; ?>

                            </div>

                            <div class="hikaye-bilgi">

                                <span class="hikaye-kategori">
                                    <?= htmlspecialchars($hikaye['kategori_adi'] ?? 'Genel', ENT_QUOTES, "UTF-8") ?>
                                </span>

                                <h3>
                                    <?= htmlspecialchars($hikaye['baslik'], ENT_QUOTES, "UTF-8") ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars(
                                        mb_strimwidth($hikaye['aciklama'] ?? '', 0, 90, '...'),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </p>

                                <div class="hikaye-yazar">
                                    ✍️ <?= htmlspecialchars($hikaye['kullanici_adi'] ?? 'Bilinmeyen yazar', ENT_QUOTES, "UTF-8") ?>
                                </div>

                            </div>

                        </a>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="bos-mesaj">Henüz yayınlanmış hikaye bulunmuyor.</p>

                <?php endif; ?>

            </div>

        </div>
    </section>

    <section class="hikaye-bolumu yeni-hikayeler">
        <div class="container">

            <div class="bolum-baslik">
                <div>
                    <span class="ust-baslik">WORDNEST'TE YENİ</span>
                    <h2>Son Eklenen Hikayeler</h2>
                </div>
            </div>

            <div class="hikaye-listesi">

                <?php if (count($yeniHikayeler) > 0): ?>

                    <?php foreach ($yeniHikayeler as $hikaye): ?>

                        <a href="hikayeler/detay.php?id=<?= (int)$hikaye['id'] ?>"
                           class="hikaye-karti">

                            <div class="hikaye-kapak">

                                <?php if (!empty($hikaye['kapak_resmi'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($hikaye['kapak_resmi'], ENT_QUOTES, "UTF-8") ?>"
                                        alt="<?= htmlspecialchars($hikaye['baslik'], ENT_QUOTES, "UTF-8") ?>"
                                    >

                                <?php else: ?>

                                    <div class="kapak-yok">📚</div>

                                <?php endif; ?>

                            </div>

                            <div class="hikaye-bilgi">

                                <span class="hikaye-kategori">
                                    <?= htmlspecialchars($hikaye['kategori_adi'] ?? 'Genel', ENT_QUOTES, "UTF-8") ?>
                                </span>

                                <h3>
                                    <?= htmlspecialchars($hikaye['baslik'], ENT_QUOTES, "UTF-8") ?>
                                </h3>

                                <p>
                                    <?= htmlspecialchars(
                                        mb_strimwidth($hikaye['aciklama'] ?? '', 0, 90, '...'),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </p>

                                <div class="hikaye-yazar">
                                    ✍️ <?= htmlspecialchars($hikaye['kullanici_adi'] ?? 'Bilinmeyen yazar', ENT_QUOTES, "UTF-8") ?>
                                </div>

                            </div>

                        </a>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="bos-mesaj">Henüz hikaye eklenmemiş.</p>

                <?php endif; ?>

            </div>

        </div>
    </section>

    <section class="yazar-cagrisi">
        <div class="container yazar-icerik">

            <div>
                <span>YAZARLIĞA İLK ADIMINI AT</span>
                <h2>Kendi hikayeni yazmaya hazır mısın?</h2>
                <p>
                    Hayal gücünü kelimelere dök, hikayeni WordNest
                    topluluğuyla paylaş.
                </p>
            </div>

            <?php if ($girisYapildi): ?>
                <a href="hikayeler/ekle.php" class="ana-buton">
                    Hikaye Yaz
                </a>
            <?php else: ?>
                <a href="kullanici/kayit.php" class="ana-buton">
                    Hemen Katıl
                </a>
            <?php endif; ?>

        </div>
    </section>

</main>

<footer class="alt-bilgi">
    <div class="container footer-icerik">

        <a href="index.php" class="logo">
            <span>Word</span>Nest
        </a>

        <p>Hikayelerin buluştuğu yer.</p>

        <p class="telif">
            © <?= date("Y") ?> WordNest. Tüm hakları saklıdır.
        </p>

    </div>
</footer>

</body>
</html>
