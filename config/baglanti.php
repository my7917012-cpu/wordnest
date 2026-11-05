
<?php

$host = "localhost";
$veritabani = "wordnest";
$kullanici = "root";
$sifre = "";

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$veritabani;charset=utf8mb4",
        $kullanici,
        $sifre
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Veritabanı bağlantısı başarısız!");
}

?>
