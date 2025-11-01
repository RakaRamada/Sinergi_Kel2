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

/**
 * Menyimpan forum baru ke database.
 *
 * @param string $nama_forum Nama forum.
 * @param string $deskripsi Deskripsi (CLOB).
 * @param int $creator_user_id ID pembuat.
 * @param string|null $image_name Nama file gambar (atau null).
 * @return int|false ID forum baru jika berhasil, false jika gagal.
 */
function createForum($nama_forum, $deskripsi, $creator_user_id, $image_name) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di createForum.");
        return false; 
    }

    // Siapkan variabel untuk menampung ID baru
    $new_forum_id = 0;

    // Query INSERT (GENERATED ALWAYS AS IDENTITY)
    // Kita tambahkan kolom forum_image
    // Kita gunakan RETURNING... INTO... untuk mengambil ID yang baru dibuat
    $sql = "INSERT INTO forums (nama_forum, deskripsi, created_by_user_id, forum_image, created_at)
            VALUES (:nama, EMPTY_CLOB(), :creator_id, :img_name, SYSDATE)
            RETURNING forum_id, deskripsi INTO :new_id, :desk_clob";

    $stmt = oci_parse($conn, $sql);
    
    $clob = oci_new_descriptor($conn, OCI_D_LOB);
    $clean_creator_id = (int)$creator_user_id;

    oci_bind_by_name($stmt, ':nama', $nama_forum);
    oci_bind_by_name($stmt, ':creator_id', $clean_creator_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':img_name', $image_name);
    
    // Bind untuk CLOB
    oci_bind_by_name($stmt, ':desk_clob', $clob, -1, OCI_B_CLOB);
    // Bind untuk ID baru
    oci_bind_by_name($stmt, ':new_id', $new_forum_id, -1, SQLT_INT);


    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in createForum execute: " . $e['message']);
         oci_rollback($conn);
         @oci_close($conn);
         return false;
    }

    // Simpan deskripsi ke CLOB
    if (!$clob->save($deskripsi)) {
        oci_rollback($conn);
        error_log("OCI8 Error saving CLOB in createForum.");
        @oci_close($conn);
        return false;
    }
    
    if (!oci_commit($conn)) {
        oci_rollback($conn);
        error_log("OCI8 Error committing in createForum.");
        @oci_close($conn);
        return false;
    }

    oci_free_statement($stmt);
    oci_free_descriptor($clob);
    @oci_close($conn);

    // Kembalikan ID forum baru
    return $new_forum_id; 
}


/**
 * Mendaftarkan user ke dalam forum (bergabung).
 *
 * @param int $user_id ID pengguna.
 * @param int $forum_id ID forum.
 * @return bool True jika berhasil, false jika gagal.
 */
function joinForum($user_id, $forum_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) { return false; }

    $sql = "BEGIN
                INSERT INTO forum_members (user_id, forum_id) VALUES (:uid, :fid);
            EXCEPTION
                WHEN DUP_VAL_ON_INDEX THEN
                    NULL; -- Abaikan jika sudah ada
            END;";
    
    $stmt = oci_parse($conn, $sql);
    
    $clean_user_id = (int)$user_id;
    $clean_forum_id = (int)$forum_id;

    oci_bind_by_name($stmt, ':uid', $clean_user_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);

    // --- PERBAIKAN DI SINI ---
    // 1. Eksekusi TANPA auto-commit
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) { 
         $e = oci_error($stmt);
         error_log("OCI8 Error in joinForum: " . $e['message']);
         oci_rollback($conn); // Batalkan jika eksekusi gagal
         @oci_close($conn);
         return false;
    }
    
    // 2. Lakukan COMMIT manual
    if (!oci_commit($conn)) {
        $e = oci_error($conn);
        error_log("OCI8 Error committing in joinForum: " . $e['message']);
        oci_rollback($conn); // Batalkan jika commit gagal
        @oci_close($conn);
        return false;
    }
    // --- AKHIR PERBAIKAN ---

    oci_free_statement($stmt);
    @oci_close($conn);
    return true;
}

?>