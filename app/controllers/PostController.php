<?php
require_once "../../config/koneksi.php"; 

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Query untuk mencari user berdasarkan kode verifikasi
    // Gunakan nama tabel 'users' agar konsisten
    $sql_select = "SELECT * FROM users WHERE verifikasi_kode = '$code'";

    // 2. Persiapkan dan eksekusi query SELECT
    $statement_select = oci_parse($conn, $sql_select);
    oci_execute($statement_select);

    // 3. Ambil data user
    // oci_fetch_assoc akan menghasilkan false jika kode tidak ditemukan
    $user_data = oci_fetch_assoc($statement_select);

    // 4. Cek apakah user dengan kode tersebut ada
    if ($user_data) {
        // Jika user ditemukan, lakukan UPDATE
        // Ambil ID user. Ingat, Oracle sering mengembalikan nama kolom sbg HURUF BESAR
        $user_id = $user_data['ID']; 

        $sql_update = "UPDATE users SET is_verif = 1 WHERE id = $user_id";

        // Persiapkan dan eksekusi query UPDATE
        $statement_update = oci_parse($conn, $sql_update);
        
        if(oci_execute($statement_update)) {
            echo "<script>alert('Verifikasi berhasil, silahkan login');window.location='login.php'</script>";
        } else {
            // Jika update gagal karena alasan teknis
            echo "<script>alert('Gagal memperbarui status verifikasi!');window.location='register.php'</script>";
        }
        // Bebaskan statement update
        oci_free_statement($statement_update);

    } else {
        // Jika kode verifikasi tidak ditemukan di database
        echo "<script>alert('Verifikasi tidak valid!');window.location='register.php'</script>";
    }
    
    // Bebaskan statement select dan tutup koneksi
    oci_free_statement($statement_select);
    oci_close($conn);

} else {
    echo "Kode verifikasi tidak ditemukan!";
}
/**
 * Fungsi ini akan dipanggil oleh router saat pengguna
 * mengakses halaman detail postingan (index.php?page=post-detail).
 */
function show() {
    // Untuk saat ini, tugas fungsi ini hanya satu:
    // memuat dan menampilkan file view 'post_detail.php'.
    require 'app/views/post_detail.php';
}

?>