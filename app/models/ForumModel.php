<?php
// File: app/models/ForumModel.php

// --- PERUBAHAN 1: Pindahkan require_once ke atas ---
require_once __DIR__ . '/UserModel.php';
require_once __DIR__ . '/MessageModel.php';


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
// Di dalam app/models/ForumModel.php
function getForumById($forum_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getForumById.");
        return null; 
    }

    // --- PERUBAHAN: Tambahkan f.forum_image ---
    $sql = "SELECT 
                forum_id, 
                nama_forum, 
                deskripsi, 
                forum_image, 
                created_by_user_id, 
                TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at 
            FROM forums 
            WHERE forum_id = :fid";
    
    $stmt = oci_parse($conn, $sql); 
    
    $clean_forum_id = (int)$forum_id;
    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         // ... (error handling) ...
         $e = oci_error($stmt);
         error_log("OCI8 Error in getForumById: " . $e['message']);
         @oci_close($conn);
         return null;
    }
    
    $forum = oci_fetch_assoc($stmt);
    // ... (sisanya sama) ...
    oci_free_statement($stmt);
    @oci_close($conn);

    return $forum ? array_change_key_case($forum, CASE_LOWER) : null;
}

/**
 * Mengambil semua forum yang diikuti user, TERMASUK pesan terakhir dan hitungan belum dibaca.
 * VERSI BARU: Menggunakan kolom 'last_read_message_id' dari 'forum_members'
 *
 * @param int $user_id ID pengguna.
 * @return array Array berisi daftar forum.
 */
function getForumsByUserId($user_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getForumsByUserId.");
        return [];
    }

    $safe_user_id = (int)$user_id; 

    // Query ini lebih simpel! Tidak perlu LEFT JOIN ke tabel status.
    $sql = "SELECT 
                f.forum_id, f.nama_forum, f.forum_image,
                lm.isi_pesan AS last_message_text,
                lm.message_type AS last_message_type,
                lm.sender_nama AS last_message_sender,
                lm.sender_id AS last_message_sender_id,
                TO_CHAR(lm.created_at, 'HH24:MI') AS last_message_time,
                (
                    SELECT COUNT(mc.message_id)
                    FROM messages mc
                    WHERE mc.forum_id = f.forum_id
                    -- LANGSUNG AMBIL DARI fm.last_read_message_id
                    AND mc.message_id > NVL(fm.last_read_message_id, 0) 
                    AND mc.sender_id != :uid1 -- Jangan hitung pesan kita sendiri
                ) AS unread_count
            FROM 
                forums f
            JOIN 
                forum_members fm ON f.forum_id = fm.forum_id
            -- LEFT JOIN user_forum_read_status r... (INI HILANG, JADI LEBIH CEPAT)
            OUTER APPLY (
                SELECT 
                    m.isi_pesan, m.message_type, m.sender_id,
                    u.nama_lengkap AS sender_nama, m.created_at
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.forum_id = f.forum_id
                ORDER BY m.created_at DESC
                FETCH FIRST 1 ROW ONLY
            ) lm
            WHERE 
                fm.user_id = :uid2
            ORDER BY 
                lm.created_at DESC NULLS LAST, f.nama_forum ASC";
    
    $stmt = oci_parse($conn, $sql);
    
    // Kita bind user_id dua kali
    oci_bind_by_name($stmt, ':uid1', $safe_user_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':uid2', $safe_user_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getForumsByUserId (V3-Simple): " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $forums = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Proses CLOB untuk pesan terakhir
        $last_message_string = '';
        if (isset($row['LAST_MESSAGE_TEXT']) && $row['LAST_MESSAGE_TEXT'] instanceof OCILob) {
            $last_message_string = $row['LAST_MESSAGE_TEXT']->read($row['LAST_MESSAGE_TEXT']->size());
        } elseif (isset($row['LAST_MESSAGE_TEXT']) && is_string($row['LAST_MESSAGE_TEXT'])) {
            $last_message_string = $row['LAST_MESSAGE_TEXT'];
        }
        $row['LAST_MESSAGE_TEXT'] = $last_message_string; 

        $forums[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $forums;
}


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
 * VERSI AMAN: Otomatis membersihkan nama & memberi default 'Seseorang'
 *
 * @param int $user_id ID pengguna.
 * @param int $forum_id ID forum.
 * @param string $user_nama Nama pengguna (dari session, BISA KOTOR/NULL).
 * @return bool True jika berhasil, false jika gagal.
 */
function joinForum($user_id, $forum_id, $user_nama = 'Seseorang', $custom_message = null) {
    require __DIR__ . '/../../config/koneksi.php';
    
    // 1. Insert ke forum_members
    $sql = "BEGIN
                INSERT INTO forum_members (user_id, forum_id) VALUES (:uid, :fid);
            EXCEPTION
                WHEN DUP_VAL_ON_INDEX THEN NULL; 
            END;";
    
    $stmt = oci_parse($conn, $sql);
    $clean_user_id = (int)$user_id;
    $clean_forum_id = (int)$forum_id;

    oci_bind_by_name($stmt, ':uid', $clean_user_id);
    oci_bind_by_name($stmt, ':fid', $clean_forum_id);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) { 
         // Error handling
         @oci_close($conn); return false;
    }
    oci_commit($conn); // Commit anggota baru dulu

    // 2. Buat Pesan Sistem
    // Bersihkan nama
    $nama_asli = $user_nama ?? 'Seseorang';
    $nama_final = trim(preg_replace('/[[:cntrl:]\s]/u', ' ', $nama_asli)); 
    if (empty($nama_final)) $nama_final = 'Seseorang';

    // Tentukan pesan: Pakai custom jika ada, jika tidak pakai default "telah bergabung"
    $pesan_sistem = $custom_message ? $custom_message : ($nama_final . ' telah bergabung dengan forum.');

    createSystemMessage($clean_forum_id, $clean_user_id, 'join', $pesan_sistem);

    oci_free_statement($stmt);
    @oci_close($conn);
    return true;
}

/**
 * Menghapus keanggotaan user dari sebuah forum (Keluar Forum).
 * VERSI AMAN: Otomatis membersihkan nama & memberi default 'Seseorang'
 *
 * @param int $user_id ID pengguna.
 * @param int $forum_id ID forum.
 * @param string $user_nama Nama pengguna (dari session, BISA KOTOR/NULL).
 * @return bool True jika berhasil, false jika gagal.
 */
function leaveForum($user_id, $forum_id, $user_nama = 'Seseorang') {

    // (require_once sudah dipindah ke atas file)

    // 1. Koneksi stabil
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di leaveForum.");
        return false;
    }

    $safe_user_id = (int)$user_id;
    $safe_forum_id = (int)$forum_id;

    // === PERBAIKAN LOGIKA FINAL (DI DALAM MODEL) ===
    
    // 1. Ambil nama (dari parameter atau default 'Seseorang')
    $nama_asli = $user_nama ?? 'Seseorang';

    // 2. Buat versi bersih HANYA untuk tes (hapus SEMUA spasi & karakter aneh/control)
    $nama_untuk_tes = preg_replace('/[[:cntrl:]\s]/u', '', $nama_asli);

    // 3. Cek: Apakah versi bersihnya itu KOSONG?
    $nama_final_untuk_pesan = '';
    if (empty($nama_untuk_tes)) {
        // Jika ya, nama itu pasti "kosong" atau spasi "ajaib". Gunakan default.
        $nama_final_untuk_pesan = 'Seseorang';
    } else {
        // Jika tidak, nama itu valid. Gunakan nama ASLI (tapi trim spasi biasa).
        $nama_final_untuk_pesan = trim($nama_asli);
    }
    // ========================================================

    // 4. Buat pesan sistem DULUAN (dengan nama bersih)
    createSystemMessage($safe_forum_id, $safe_user_id, 'leave', $nama_final_untuk_pesan . ' telah keluar dari forum.');

    // 5. Query SQL DELETE
    $sql = "DELETE FROM forum_members 
            WHERE user_id = " . $safe_user_id . " 
            AND forum_id = " . $safe_forum_id;
    
    $stmt = oci_parse($conn, $sql);

    // 6. Eksekusi (dengan auto-commit)
    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in leaveForum: " . $e['message']);
         @oci_close($conn);
         return false;
    }

    oci_free_statement($stmt);
    @oci_close($conn);

    return true; // Sukses
}

/**
 * Mencari forum berdasarkan nama.
 *
 * @param string $searchTerm Kata kunci pencarian.
 * @return array Array berisi forum yang cocok.
 */
function searchForums($searchTerm) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di searchForums.");
        return [];
    }

    // Kita akan mencari forum yang namanya mengandung (LIKE) searchTerm.
    // Kita gunakan UPPER() di kedua sisi agar pencarian tidak case-sensitive (tidak peduli huruf besar/kecil).
    $sql = "SELECT forum_id, nama_forum, deskripsi, forum_image
            FROM forums 
            WHERE UPPER(nama_forum) LIKE :term";
            
    // Siapkan bind variable dengan wildcard (%)
    $searchTermWildcard = '%' . strtoupper($searchTerm) . '%';

    $stmt = oci_parse($conn, $sql);
    
    // Bind searchTerm
    oci_bind_by_name($stmt, ':term', $searchTermWildcard);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in searchForums: " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $forums = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Kita perlu memproses CLOB (deskripsi) jika ada
        if (isset($row['DESKRIPSI']) && $row['DESKRIPSI'] instanceof OCILob) {
            $row['DESKRIPSI'] = $row['DESKRIPSI']->read($row['DESKRIPSI']->size());
        }
        $forums[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $forums;
}

/**
 * Mengambil daftar anggota (members) dari sebuah forum.
 *
 * @param int $forum_id ID forum.
 * @return array Array berisi daftar anggota (user).
 */
function getForumMembers($forum_id) {
    // 1. Koneksi stabil
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getForumMembers.");
        return [];
    }

    // Pastikan ID aman (dari bug ORA-01745)
    $safe_forum_id = (int)$forum_id;

    // 2. Query SQL (JOIN 3 tabel: forum_members -> users -> roles)
    // Kita ambil info user dan role mereka di forum
    $sql = "SELECT 
                u.user_id, 
                u.username, 
                u.nama_lengkap, 
                r.role_name 
            FROM 
                forum_members fm
            JOIN 
                users u ON fm.user_id = u.user_id
            JOIN 
                roles r ON u.role_id = r.role_id
            WHERE 
                fm.forum_id = " . $safe_forum_id . "
            ORDER BY 
                u.nama_lengkap ASC"; // Urutkan A-Z

    $stmt = oci_parse($conn, $sql);

    // 3. Eksekusi
    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getForumMembers: " . $e['message']);
         @oci_close($conn);
         return [];
    }

    // 4. Ambil semua hasilnya
    $members = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $members[] = array_change_key_case($row, CASE_LOWER);
    }

    // 5. Bebaskan resource & tutup koneksi
    oci_free_statement($stmt);
    @oci_close($conn);

    return $members;
}

/**
 * Memperbarui data forum (Nama, Deskripsi, Gambar) di database.
 *
 * @param int $forum_id ID forum yang akan di-update.
 * @param string $nama_forum Nama baru.
 * @param string $deskripsi Deskripsi baru (CLOB).
 * @param string|null $image_name Nama file gambar baru (atau null jika tidak berubah).
 * @return bool True jika berhasil, false jika gagal.
 */
function updateForum($forum_id, $nama_forum, $deskripsi, $image_name) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di updateForum.");
        return false; 
    }

    // 1. Tentukan query SQL
    // Kita perlu 2 query: satu jika gambar diubah, satu jika tidak.
    if ($image_name !== null) {
        // Jika ada gambar baru, update semua 3 kolom
        $sql = "UPDATE forums 
                SET nama_forum = :nama, 
                    deskripsi = EMPTY_CLOB(), 
                    forum_image = :img_name 
                WHERE forum_id = :fid
                RETURNING deskripsi INTO :desk_clob";
    } else {
        // Jika tidak ada gambar baru, HANYA update nama dan deskripsi
        $sql = "UPDATE forums 
                SET nama_forum = :nama, 
                    deskripsi = EMPTY_CLOB() 
                WHERE forum_id = :fid
                RETURNING deskripsi INTO :desk_clob";
    }

    $stmt = oci_parse($conn, $sql);
    
    $clob = oci_new_descriptor($conn, OCI_D_LOB);
    $clean_forum_id = (int)$forum_id;

    // 2. Bind parameter
    oci_bind_by_name($stmt, ':nama', $nama_forum);
    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':desk_clob', $clob, -1, OCI_B_CLOB);
    
    // Bind gambar HANYA jika query-nya memerlukannya
    if ($image_name !== null) {
        oci_bind_by_name($stmt, ':img_name', $image_name);
    }

    // 3. Eksekusi
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in updateForum execute: " . $e['message']);
         oci_rollback($conn);
         @oci_close($conn);
         return false;
    }

    // 4. Simpan deskripsi ke CLOB
    if (!$clob->save($deskripsi)) {
        oci_rollback($conn);
        error_log("OCI8 Error saving CLOB in updateForum.");
        @oci_close($conn);
        return false;
    }
    
    // 5. Commit
    if (!oci_commit($conn)) {
        oci_rollback($conn);
        error_log("OCI8 Error committing in updateForum.");
        @oci_close($conn);
        return false;
    }

    oci_free_statement($stmt);
    oci_free_descriptor($clob);
    @oci_close($conn);

    return true; // Sukses
}

/**
 * Mengambil daftar user yang BELUM menjadi anggota forum ini.
 * Digunakan untuk fitur "Tambah Anggota".
 */
function getUsersAvailableForForum($forum_id, $search = '') {
    require __DIR__ . '/../../config/koneksi.php';

    // KITA KEMBALIKAN 'foto_profil' KE SINI
    $sql = "SELECT user_id, username, nama_lengkap, foto_profil 
            FROM users 
            WHERE user_id NOT IN (
                SELECT user_id FROM forum_members WHERE forum_id = :fid
            )";

    if (!empty($search)) {
        $sql .= " AND (UPPER(nama_lengkap) LIKE :search OR UPPER(username) LIKE :search)";
    }

    $sql .= " ORDER BY nama_lengkap ASC FETCH FIRST 20 ROWS ONLY"; 

    $stmt = oci_parse($conn, $sql);
    
    $fid = (int)$forum_id;
    oci_bind_by_name($stmt, ':fid', $fid);

    if (!empty($search)) {
        $s = '%' . strtoupper($search) . '%';
        oci_bind_by_name($stmt, ':search', $s);
    }

    if (!@oci_execute($stmt)) {
        return [];
    }

    $users = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Pastikan key jadi huruf kecil (foto_profil)
        $users[] = array_change_key_case($row, CASE_LOWER);
    }
    
    oci_free_statement($stmt);
    @oci_close($conn);
    return $users;
} 

/**
 * Mengeluarkan member secara paksa (Kick).
 * Pesan sistem: "User X dikeluarkan oleh Admin."
 */
function kickMember($forum_id, $target_user_id, $target_user_name) {
    require __DIR__ . '/../../config/koneksi.php';

    // 1. Pesan Sistem (Jalan duluan, koneksi sendiri)
    $nama_final = trim(preg_replace('/[[:cntrl:]\s]/u', ' ', $target_user_name));
    if(empty($nama_final)) $nama_final = 'Member';
    
    createSystemMessage((int)$forum_id, (int)$target_user_id, 'leave', $nama_final . ' telah dikeluarkan oleh Admin Forum.');

    // 2. Hapus dari database
    // GANTI :uid JADI :p_user_id
    $sql = "DELETE FROM forum_members 
            WHERE user_id = :p_user_id AND forum_id = :p_forum_id";
            
    $stmt = oci_parse($conn, $sql);
    
    $clean_uid = (int)$target_user_id;
    $clean_fid = (int)$forum_id;
    
    // Bind dengan nama baru yang AMAN
    oci_bind_by_name($stmt, ':p_user_id', $clean_uid);
    oci_bind_by_name($stmt, ':p_forum_id', $clean_fid);

    // Eksekusi (Auto Commit Default)
    $res = oci_execute($stmt); 
    
    if (!$res) {
        $e = oci_error($stmt);
        // Log error biar kita tau kalau ada apa-apa
        error_log("Gagal Kick (SQL Error): " . $e['message']);
    }
    
    oci_free_statement($stmt);
    @oci_close($conn);
    
    return $res;
}

?>