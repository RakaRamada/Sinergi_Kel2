<?php
// File: app/models/PostModel.php

/**
 * Mengambil semua postingan dari database Oracle.
 */
function getAllPosts() {
    // 1. Sertakan file koneksi. Variabel $conn akan tersedia dari sini.
    // Sesuaikan path jika file koneksi.php Anda namanya berbeda atau lokasinya berbeda.
require_once $_SERVER['DOCUMENT_ROOT'] . '/sinergi/config/koneksi.php';

    // 2. Query SQL untuk mengambil postingan, user, dan role
    $sql = "SELECT p.*, u.nama_lengkap, u.username AS user_username, r.role_name
            FROM postingan p
            JOIN users u ON p.user_id = u.user_id
            JOIN roles r ON u.role_id = r.role_id
            ORDER BY p.created_at DESC";

    // 3. Persiapkan query
    $stmt = oci_parse($conn, $sql);

    // 4. Eksekusi query dan tangani error jika ada
    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Execute Error in getAllPosts: " . $e['message']);
         @oci_close($conn); // Coba tutup koneksi jika error
         return []; // Kembalikan array kosong jika gagal
    }

    // 5. Ambil semua hasil ke dalam array
    $posts = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Mengubah nama kolom dari Oracle (huruf besar) menjadi huruf kecil agar konsisten
        $posts[] = array_change_key_case($row, CASE_LOWER); 
    }

    // 6. Bebaskan resource
    oci_free_statement($stmt);
    
    // 7. Tutup koneksi (opsional, tergantung konfigurasi server Anda)
    @oci_close($conn);

    // 8. Kembalikan array berisi data postingan asli
    return $posts;
}

// Tambahkan fungsi lain di sini nanti (misal: getPostById)

?>