<?php
// File: reset_final.php
require_once 'config/koneksi.php';

if (!$conn) die("Koneksi Database Gagal.");

// --- DATA ADMIN ---
$email_target = 'admin@sinergi.com'; // Email admin Anda
$pass_baru    = 'admin123';          // Password baru

// 1. Generate Hash
$hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);

// 2. Update Database
$sql = "UPDATE USERS SET PASSWORD = :pass, IS_VERIF = 1, ROLE_ID = 5 WHERE EMAIL = :email";
$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ':pass', $hash_baru);
oci_bind_by_name($stmt, ':email', $email_target);

if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
    echo "<h1>RESET BERHASIL</h1>";
    echo "Password untuk <b>$email_target</b> sudah diubah menjadi: <b>$pass_baru</b><br>";
    echo "Hash Baru: $hash_baru<br><br>";
    echo "<a href='index.php?page=login'>COBA LOGIN SEKARANG</a>";
} else {
    $e = oci_error($stmt);
    echo "<h1>GAGAL UPDATE</h1>";
    echo "Pesan Error: " . $e['message'];
    echo "<br>Pastikan email <b>$email_target</b> sudah ada di tabel USERS.";
}
?>