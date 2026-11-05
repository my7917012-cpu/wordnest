
<?php
session_start();

// Oturumdaki tüm bilgileri temizle
$_SESSION = [];

// Oturum çerezini sil
if (ini_get("session.use_cookies")) {
    $parametreler = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametreler["path"],
        $parametreler["domain"],
        $parametreler["secure"],
        $parametreler["httponly"]
    );
}

// Oturumu tamamen kapat
session_destroy();

// Ana sayfaya yönlendir
header("Location: ../index.php");
exit;
