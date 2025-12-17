<?php
// PERBAIKAN: Tambahkan session_start() di paling atas.
// Ini wajib karena file ini dipanggil langsung oleh <img> (request terpisah)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function acakCaptcha() {
    $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $pass = array(); 
    // PERBAIKAN: Max index adalah strlen() - 1, bukan - 2
    $panjangAlpha = strlen($alphabet) - 1; 
    for ($i = 0; $i < 5; $i++) {
        $n = rand(0, $panjangAlpha);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}

$code = acakCaptcha();
$_SESSION["code"] = $code; // Sekarang ini aman karena session sudah dimulai

$wh = imagecreatetruecolor(173, 50);
$bgc = imagecolorallocate($wh, 0, 0, 0); // Background biru
$fc = imagecolorallocate($wh, 223, 230, 233); // Text color abu-abu
imagefill($wh, 0, 0, $bgc);
imagestring($wh, 10, 50, 15, $code, $fc);

header('content-type: image/jpg');
imagejpeg($wh);
imagedestroy($wh);
?>