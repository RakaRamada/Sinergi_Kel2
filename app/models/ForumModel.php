<?php
// File: app/models/ForumModel.php

/**
 * Mengambil semua forum dari database.
 * (Digunakan oleh MessageController jika tidak ada user_id / belum login)
 *
 * @return array Array berisi daftar forum, atau array kosong jika tidak ada/error.
 */
function getAllForums() {
    require __DIR__ . '/../../config/koneksi.php'; 
    if (!$conn) {
        error_log("Koneksi DB gagal di getAllForums.");
        return [];
    }

    // Query 1 baris (menghindari ORA-01745)
    $sql = "SELECT forum_id, nama_forum, deskripsi, created_by_user_id, created_at FROM forums ORDER BY created_at DESC";

    $stmt = oci_parse($conn, $sql);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getAllForums: " . $e['message']);
         @oci_close($conn);
         return []; 
    }

    $forums = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $forums[] = array_change_key_case($row, CASE_LOWER); 
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $forums;
} // <--- PASTIKAN ADA '}' DI SINI


/**
 * Mengambil detail satu forum berdasarkan ID.
 * (Dibutuhkan oleh MessageController saat forum dipilih)
 *
 * @param int $forum_id ID forum yang ingin diambil.
 * @return array|null Array berisi info forum, atau null jika tidak ditemukan/error.
 */
function getForumById($forum_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getForumById.");
        return null; 
    }

    // Query 1 baris (menghindari ORA-01745)
    $sql = "SELECT forum_id, nama_forum, deskripsi FROM forums WHERE forum_id = :fid";
    
    $stmt = oci_parse($conn, $sql); 
    
    // Bind dengan tipe data eksplisit (menghindari ORA-01745)
    $clean_forum_id = (int)$forum_id;
    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getForumById: " . $e['message']);
         @oci_close($conn);
         return null;
    }
    
    $forum = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    @oci_close($conn);

    return $forum ? array_change_key_case($forum, CASE_LOWER) : null;
} // <--- PASTIKAN ADA '}' DI SINI


/**
 * Mengambil semua forum yang telah diikuti oleh user_id tertentu.
 * (Dibutuhkan oleh MessageController)
 *
 * @param int $user_id ID pengguna.
 * @return array Array berisi daftar forum, atau array kosong jika tidak ada/error.
 */
function getForumsByUserId($user_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getForumsByUserId.");
        return [];
    }

    // Bypass ORA-01745: Suntikkan user_id yang aman (dari session)
    $safe_user_id = (int)$user_id; 
    $sql = "SELECT f.forum_id, f.nama_forum, f.deskripsi FROM forums f JOIN forum_members fm ON f.forum_id = fm.forum_id WHERE fm.user_id = " . $safe_user_id . " ORDER BY f.nama_forum ASC";
    
    $stmt = oci_parse($conn, $sql);
    // Tidak perlu bind

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getForumsByUserId: " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $forums = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $forums[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $forums;
} // <--- PASTIKAN ADA '}' DI SINI

?>