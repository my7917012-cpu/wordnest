<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . "/../vendor/autoload.php";

function wordnestSendVerificationCode(string $recipientEmail, string $recipientName, string $code): void
{
    $settingsPath = "C:/xampp/wordnest_mail.php";
    if (!is_file($settingsPath)) {
        throw new RuntimeException("Gmail ayar dosyası bulunamadı.");
    }

    $settings = require $settingsPath;
    if (!is_array($settings) || empty($settings["username"]) || empty($settings["app_password"])) {
        throw new RuntimeException("Gmail ayarları eksik.");
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = $settings["username"];
    $mail->Password = str_replace(" ", "", $settings["app_password"]);
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = "UTF-8";
    $mail->setFrom($settings["username"], "WordNest");
    $mail->addAddress($recipientEmail, $recipientName);
    $mail->isHTML(true);
    $mail->Subject = "WordNest e-posta doğrulama kodu";
    $safeName = htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    $mail->Body = "<p>Merhaba {$safeName},</p><p>WordNest üyeliğini tamamlamak için doğrulama kodun:</p><p style=\"font-size:28px;font-weight:bold;letter-spacing:6px\">{$code}</p><p>Bu kod 10 dakika geçerlidir. Bu isteği sen yapmadıysan bu e-postayı yok sayabilirsin.</p>";
    $mail->AltBody = "WordNest doğrulama kodun: {$code}. Bu kod 10 dakika geçerlidir.";
    $mail->send();
}