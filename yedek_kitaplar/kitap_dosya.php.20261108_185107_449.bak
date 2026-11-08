<?php
require_once __DIR__ . "/config/baglanti.php";
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit("Dosya bulunamadı.");
}
$sorgu = $db->prepare("SELECT baslik, uzanti, dosya_adi FROM kitap_dosyalari WHERE id = ?");
$sorgu->execute([$id]);
$kitap = $sorgu->fetch();
if (!$kitap) {
    http_response_code(404);
    exit("Dosya bulunamadı.");
}
$dosya = __DIR__ . "/uploads/kitaplar/" . basename($kitap["dosya_adi"]);
if (!is_file($dosya)) {
    http_response_code(404);
    exit("Dosya bulunamadı.");
}
$mime = $kitap["uzanti"] === "pdf" ? "application/pdf" : "text/plain; charset=UTF-8";
$ad = preg_replace('/[^\pL\pN ._-]+/u', "", $kitap["baslik"]) ?: "kitap";
$indir = isset($_GET["indir"]);
header("X-Content-Type-Options: nosniff");
header("Content-Type: " . $mime);
header("Content-Length: " . filesize($dosya));
header("Content-Disposition: " . ($indir ? "attachment" : "inline") . "; filename*=UTF-8''" . rawurlencode($ad . "." . $kitap["uzanti"]));
readfile($dosya);
