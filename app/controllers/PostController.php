<?php
require_once "../../config/koneksi.php"; 


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