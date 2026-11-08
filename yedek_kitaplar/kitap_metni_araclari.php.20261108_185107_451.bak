<?php

function wordnestMetniSayfalaraBol(string $metin, int $hedefUzunluk = 2400): array
{
    $metin = trim(str_replace(["\r\n", "\r"], "\n", $metin));
    if ($metin === "") {
        return [];
    }

    $formBeslemeli = preg_split("/\f/u", $metin);
    if (is_array($formBeslemeli) && count($formBeslemeli) > 1) {
        return array_values(array_map("trim", $formBeslemeli));
    }

    $paragraflar = preg_split("/\n{2,}/u", $metin) ?: [$metin];
    $sayfalar = [];
    $sayfa = "";

    foreach ($paragraflar as $paragraf) {
        $paragraf = trim($paragraf);
        if ($paragraf === "") {
            continue;
        }
        $parca = "";
        while (mb_strlen($paragraf, "UTF-8") > $hedefUzunluk) {
            $bolum = mb_substr($paragraf, 0, $hedefUzunluk, "UTF-8");
            $bosluk = mb_strrpos($bolum, " ", 0, "UTF-8");
            if ($bosluk !== false && $bosluk > (int) ($hedefUzunluk * 0.65)) {
                $bolum = mb_substr($paragraf, 0, $bosluk, "UTF-8");
            }
            $parca = trim($bolum);
            $paragraf = trim(mb_substr($paragraf, mb_strlen($bolum, "UTF-8"), null, "UTF-8"));
            if ($sayfa !== "") {
                $sayfalar[] = trim($sayfa);
                $sayfa = "";
            }
            $sayfalar[] = $parca;
        }
        if ($paragraf === "") {
            continue;
        }
        $eklenecek = ($sayfa === "" ? "" : "\n\n") . $paragraf;
        if (mb_strlen($sayfa . $eklenecek, "UTF-8") > $hedefUzunluk && $sayfa !== "") {
            $sayfalar[] = trim($sayfa);
            $sayfa = $paragraf;
        } else {
            $sayfa .= $eklenecek;
        }
    }

    if (trim($sayfa) !== "") {
        $sayfalar[] = trim($sayfa);
    }
    return $sayfalar;
}

function wordnestPdfSayfalariniOku(string $dosyaYolu): array
{
    $autoload = __DIR__ . "/vendor/autoload.php";
    if (!is_file($autoload)) {
        throw new RuntimeException("PDF okuyucu paketi kurulu değil.");
    }
    require_once $autoload;
    $ayar = new \Smalot\PdfParser\Config();
    $ayar->setDecodeMemoryLimit(64 * 1024 * 1024);
    $parser = new \Smalot\PdfParser\Parser([], $ayar);
    $belge = $parser->parseFile($dosyaYolu);
    $sayfalar = [];
    foreach ($belge->getPages() as $sayfa) {
        $sayfalar[] = trim($sayfa->getText());
    }
    return $sayfalar;
}
