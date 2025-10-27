<?php
require_once "../../config/koneksi.php";

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $sql_select = "SELECT * FROM users WHERE verifikasi_kode = :code";
    $stmt_select = oci_parse($conn, $sql_select);
    oci_bind_by_name($stmt_select, ":code", $code);
    oci_execute($stmt_select);
    $user = oci_fetch_assoc($stmt_select);

    if ($user) {
        if ($user['IS_VERIF'] == 1) {
echo "<script>alert('Akun sudah diverifikasi sebelumnya.');window.location='../../index.php?page=login';</script>";
        } else {
            $sql_update = "UPDATE users SET is_verif = 1 WHERE verifikasi_kode = :code";
            $stmt_update = oci_parse($conn, $sql_update);
            oci_bind_by_name($stmt_update, ":code", $code);

            if (oci_execute($stmt_update)) {
echo "<script>alert('Verifikasi akun berhasil! Silakan login.');window.location='../../index.php?page=login';</script>";
            } else {
echo "<script>alert('Gagal memperbarui status verifikasi!');window.location='../../index.php?page=register';</script>";
            }
            oci_free_statement($stmt_update);
        }
    } else {
echo "<script>alert('Kode verifikasi tidak valid!');window.location='../../index.php?page=register';</script>";    }

    oci_free_statement($stmt_select);
    oci_close($conn);

} else {
echo "<script>alert('Kode verifikasi tidak ditemukan!');window.location='../../index.php?page=register';</script>";}
?>
