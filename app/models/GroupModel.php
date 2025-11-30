<?php
// File: app/models/GroupModel.php

// --- PERUBAHAN 1: Pindahkan require_once ke atas ---
require_once __DIR__ . '/UserModel.php';
require_once __DIR__ . '/MessageModel.php';



/**
 * Mengambil semua group dari database.
 * (Digunakan oleh MessageController jika tidak ada user_id / belum login)
 *
 * @return array Array berisi daftar group, atau array kosong jika tidak ada/error.
 */
function getAllGroups() {
    require __DIR__ . '/../../config/koneksi.php'; 
    if (!$conn) {
        error_log("Koneksi DB gagal di getAllGroups.");
        return [];
    }

    // Query 1 baris (menghindari ORA-01745)
    $sql = "SELECT group_id, nama_group, deskripsi, created_by_user_id, created_at FROM groups ORDER BY created_at DESC";

    $stmt = oci_parse($conn, $sql);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getAllGroups: " . $e['message']);
         @oci_close($conn);
         return []; 
    }

    $groups = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $groups[] = array_change_key_case($row, CASE_LOWER); 
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $groups;
} // <--- PASTIKAN ADA '}' DI SINI


/**
 * Mengambil detail satu group berdasarkan ID.
 * (Dibutuhkan oleh MessageController saat group dipilih)
 *
 * @param int $group_id ID group yang ingin diambil.
 * @return array|null Array berisi info group, atau null jika tidak ditemukan/error.
 */
// Di dalam app/models/GroupModel.php
function getGroupById($group_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getGroupById.");
        return null; 
    }

    // --- PERUBAHAN: Tambahkan f.group_image ---
    $sql = "SELECT 
                group_id, 
                nama_group, 
                deskripsi, 
                group_image, 
                created_by_user_id, 
                TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at 
            FROM groups 
            WHERE group_id = :fid";
    
    $stmt = oci_parse($conn, $sql); 
    
    $clean_group_id = (int)$group_id;
    oci_bind_by_name($stmt, ':fid', $clean_group_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         // ... (error handling) ...
         $e = oci_error($stmt);
         error_log("OCI8 Error in getGroupById: " . $e['message']);
         @oci_close($conn);
         return null;
    }
    
    $group = oci_fetch_assoc($stmt);
    // ... (sisanya sama) ...
    oci_free_statement($stmt);
    @oci_close($conn);

    return $group ? array_change_key_case($group, CASE_LOWER) : null;
}

/**
 * Mengambil semua group yang diikuti user, TERMASUK pesan terakhir dan hitungan belum dibaca.
 * VERSI BARU: Menggunakan kolom 'last_read_message_id' dari 'group_members'
 *
 * @param int $user_id ID pengguna.
 * @return array Array berisi daftar group.
 */
function getGroupsByUserId($user_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getGroupsByUserId.");
        return [];
    }

    $safe_user_id = (int)$user_id; 

    // Query ini lebih simpel! Tidak perlu LEFT JOIN ke tabel status.
    $sql = "SELECT 
                f.group_id, f.nama_group, f.group_image,
                lm.isi_pesan AS last_message_text,
                lm.message_type AS last_message_type,
                lm.sender_nama AS last_message_sender,
                lm.sender_id AS last_message_sender_id,
                TO_CHAR(lm.created_at, 'HH24:MI') AS last_message_time,
                (
                    SELECT COUNT(mc.message_id)
                    FROM messages mc
                    WHERE mc.group_id = f.group_id
                    -- LANGSUNG AMBIL DARI fm.last_read_message_id
                    AND mc.message_id > NVL(fm.last_read_message_id, 0) 
                    AND mc.sender_id != :uid1 -- Jangan hitung pesan kita sendiri
                ) AS unread_count
            FROM 
                groups f
            JOIN 
                group_members fm ON f.group_id = fm.group_id
            -- LEFT JOIN user_group_read_status r... (INI HILANG, JADI LEBIH CEPAT)
            OUTER APPLY (
                SELECT 
                    m.isi_pesan, m.message_type, m.sender_id,
                    u.nama_lengkap AS sender_nama, m.created_at
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE m.group_id = f.group_id
                ORDER BY m.created_at DESC
                FETCH FIRST 1 ROW ONLY
            ) lm
            WHERE 
                fm.user_id = :uid2
            ORDER BY 
                lm.created_at DESC NULLS LAST, f.nama_group ASC";
    
    $stmt = oci_parse($conn, $sql);
    
    // Kita bind user_id dua kali
    oci_bind_by_name($stmt, ':uid1', $safe_user_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':uid2', $safe_user_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getGroupsByUserId (V3-Simple): " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $groups = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Proses CLOB untuk pesan terakhir
        $last_message_string = '';
        if (isset($row['LAST_MESSAGE_TEXT']) && $row['LAST_MESSAGE_TEXT'] instanceof OCILob) {
            $last_message_string = $row['LAST_MESSAGE_TEXT']->read($row['LAST_MESSAGE_TEXT']->size());
        } elseif (isset($row['LAST_MESSAGE_TEXT']) && is_string($row['LAST_MESSAGE_TEXT'])) {
            $last_message_string = $row['LAST_MESSAGE_TEXT'];
        }
        $row['LAST_MESSAGE_TEXT'] = $last_message_string; 

        $groups[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $groups;
}


/**
 * Menyimpan group baru ke database.
 *
 * @param string $nama_group Nama group.
 * @param string $deskripsi Deskripsi (CLOB).
 * @param int $creator_user_id ID pembuat.
 * @param string|null $image_name Nama file gambar (atau null).
 * @return int|false ID group baru jika berhasil, false jika gagal.
 */
function createGroup($nama_group, $deskripsi, $creator_user_id, $image_name) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di createGroup.");
        return false; 
    }

    // Siapkan variabel untuk menampung ID baru
    $new_group_id = 0;

    // Query INSERT (GENERATED ALWAYS AS IDENTITY)
    // Kita tambahkan kolom group_image
    // Kita gunakan RETURNING... INTO... untuk mengambil ID yang baru dibuat
    $sql = "INSERT INTO groups (nama_group, deskripsi, created_by_user_id, group_image, created_at)
            VALUES (:nama, EMPTY_CLOB(), :creator_id, :img_name, SYSDATE)
            RETURNING group_id, deskripsi INTO :new_id, :desk_clob";

    $stmt = oci_parse($conn, $sql);
    
    $clob = oci_new_descriptor($conn, OCI_D_LOB);
    $clean_creator_id = (int)$creator_user_id;

    oci_bind_by_name($stmt, ':nama', $nama_group);
    oci_bind_by_name($stmt, ':creator_id', $clean_creator_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':img_name', $image_name);
    
    // Bind untuk CLOB
    oci_bind_by_name($stmt, ':desk_clob', $clob, -1, OCI_B_CLOB);
    // Bind untuk ID baru
    oci_bind_by_name($stmt, ':new_id', $new_group_id, -1, SQLT_INT);


    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in createGroup execute: " . $e['message']);
         oci_rollback($conn);
         @oci_close($conn);
         return false;
    }

    // Simpan deskripsi ke CLOB
    if (!$clob->save($deskripsi)) {
        oci_rollback($conn);
        error_log("OCI8 Error saving CLOB in createGroup.");
        @oci_close($conn);
        return false;
    }
    
    if (!oci_commit($conn)) {
        oci_rollback($conn);
        error_log("OCI8 Error committing in createGroup.");
        @oci_close($conn);
        return false;
    }

    oci_free_statement($stmt);
    oci_free_descriptor($clob);
    @oci_close($conn);

    // Kembalikan ID group baru
    return $new_group_id; 
}


/**
 * Mendaftarkan user ke dalam group (bergabung).
 * VERSI AMAN: Otomatis membersihkan nama & memberi default 'Seseorang'
 *
 * @param int $user_id ID pengguna.
 * @param int $group_id ID group.
 * @param string $user_nama Nama pengguna (dari session, BISA KOTOR/NULL).
 * @return bool True jika berhasil, false jika gagal.
 */
function joinGroup($user_id, $group_id, $user_nama = 'Seseorang', $custom_message = null) {
    require __DIR__ . '/../../config/koneksi.php';
    
    // 1. Insert ke group_members
    $sql = "BEGIN
                INSERT INTO group_members (user_id, group_id) VALUES (:uid, :fid);
            EXCEPTION
                WHEN DUP_VAL_ON_INDEX THEN NULL; 
            END;";
    
    $stmt = oci_parse($conn, $sql);
    $clean_user_id = (int)$user_id;
    $clean_group_id = (int)$group_id;

    oci_bind_by_name($stmt, ':uid', $clean_user_id);
    oci_bind_by_name($stmt, ':fid', $clean_group_id);

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
    $pesan_sistem = $custom_message ? $custom_message : ($nama_final . ' telah bergabung dengan group.');

    createSystemMessage($clean_group_id, $clean_user_id, 'join', $pesan_sistem);

    oci_free_statement($stmt);
    @oci_close($conn);
    return true;
}

/**
 * Menghapus keanggotaan user dari sebuah group (Keluar Group).
 * VERSI AMAN: Otomatis membersihkan nama & memberi default 'Seseorang'
 *
 * @param int $user_id ID pengguna.
 * @param int $group_id ID group.
 * @param string $user_nama Nama pengguna (dari session, BISA KOTOR/NULL).
 * @return bool True jika berhasil, false jika gagal.
 */
function leaveGroup($user_id, $group_id, $user_nama = 'Seseorang') {

    // (require_once sudah dipindah ke atas file)

    // 1. Koneksi stabil
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di leaveGroup.");
        return false;
    }

    $safe_user_id = (int)$user_id;
    $safe_group_id = (int)$group_id;

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
    createSystemMessage($safe_group_id, $safe_user_id, 'leave', $nama_final_untuk_pesan . ' telah keluar dari group.');

    // 5. Query SQL DELETE
    $sql = "DELETE FROM group_members 
            WHERE user_id = " . $safe_user_id . " 
            AND group_id = " . $safe_group_id;
    
    $stmt = oci_parse($conn, $sql);

    // 6. Eksekusi (dengan auto-commit)
    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in leaveGroup: " . $e['message']);
         @oci_close($conn);
         return false;
    }

    oci_free_statement($stmt);
    @oci_close($conn);

    return true; // Sukses
}

/**
 * Mencari group berdasarkan nama.
 *
 * @param string $searchTerm Kata kunci pencarian.
 * @return array Array berisi group yang cocok.
 */
function searchGroups($searchTerm) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di searchGroups.");
        return [];
    }

    // Kita akan mencari group yang namanya mengandung (LIKE) searchTerm.
    // Kita gunakan UPPER() di kedua sisi agar pencarian tidak case-sensitive (tidak peduli huruf besar/kecil).
    $sql = "SELECT group_id, nama_group, deskripsi, group_image
            FROM groups 
            WHERE UPPER(nama_group) LIKE :term";
            
    // Siapkan bind variable dengan wildcard (%)
    $searchTermWildcard = '%' . strtoupper($searchTerm) . '%';

    $stmt = oci_parse($conn, $sql);
    
    // Bind searchTerm
    oci_bind_by_name($stmt, ':term', $searchTermWildcard);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in searchGroups: " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $groups = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Kita perlu memproses CLOB (deskripsi) jika ada
        if (isset($row['DESKRIPSI']) && $row['DESKRIPSI'] instanceof OCILob) {
            $row['DESKRIPSI'] = $row['DESKRIPSI']->read($row['DESKRIPSI']->size());
        }
        $groups[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $groups;
}

/**
 * Mengambil daftar anggota (members) dari sebuah group.
 *
 * @param int $group_id ID group.
 * @return array Array berisi daftar anggota (user).
 */
function getGroupMembers($group_id) {
    // 1. Koneksi stabil
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getGroupMembers.");
        return [];
    }

    // Pastikan ID aman (dari bug ORA-01745)
    $safe_group_id = (int)$group_id;

    // 2. Query SQL (JOIN 3 tabel: group_members -> users -> roles)
    // Kita ambil info user dan role mereka di group
    $sql = "SELECT 
                u.user_id, 
                u.username, 
                u.nama_lengkap, 
                r.role_name 
            FROM 
                group_members fm
            JOIN 
                users u ON fm.user_id = u.user_id
            JOIN 
                roles r ON u.role_id = r.role_id
            WHERE 
                fm.group_id = " . $safe_group_id . "
            ORDER BY 
                u.nama_lengkap ASC"; // Urutkan A-Z

    $stmt = oci_parse($conn, $sql);

    // 3. Eksekusi
    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getGroupMembers: " . $e['message']);
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
 * Memperbarui data group (Nama, Deskripsi, Gambar) di database.
 *
 * @param int $group_id ID group yang akan di-update.
 * @param string $nama_group Nama baru.
 * @param string $deskripsi Deskripsi baru (CLOB).
 * @param string|null $image_name Nama file gambar baru (atau null jika tidak berubah).
 * @return bool True jika berhasil, false jika gagal.
 */
function updateGroup($group_id, $nama_group, $deskripsi, $image_name) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di updateGroup.");
        return false; 
    }

    // 1. Tentukan query SQL
    // Kita perlu 2 query: satu jika gambar diubah, satu jika tidak.
    if ($image_name !== null) {
        // Jika ada gambar baru, update semua 3 kolom
        $sql = "UPDATE groups 
                SET nama_group = :nama, 
                    deskripsi = EMPTY_CLOB(), 
                    group_image = :img_name 
                WHERE group_id = :fid
                RETURNING deskripsi INTO :desk_clob";
    } else {
        // Jika tidak ada gambar baru, HANYA update nama dan deskripsi
        $sql = "UPDATE groups 
                SET nama_group = :nama, 
                    deskripsi = EMPTY_CLOB() 
                WHERE group_id = :fid
                RETURNING deskripsi INTO :desk_clob";
    }

    $stmt = oci_parse($conn, $sql);
    
    $clob = oci_new_descriptor($conn, OCI_D_LOB);
    $clean_group_id = (int)$group_id;

    // 2. Bind parameter
    oci_bind_by_name($stmt, ':nama', $nama_group);
    oci_bind_by_name($stmt, ':fid', $clean_group_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':desk_clob', $clob, -1, OCI_B_CLOB);
    
    // Bind gambar HANYA jika query-nya memerlukannya
    if ($image_name !== null) {
        oci_bind_by_name($stmt, ':img_name', $image_name);
    }

    // 3. Eksekusi
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in updateGroup execute: " . $e['message']);
         oci_rollback($conn);
         @oci_close($conn);
         return false;
    }

    // 4. Simpan deskripsi ke CLOB
    if (!$clob->save($deskripsi)) {
        oci_rollback($conn);
        error_log("OCI8 Error saving CLOB in updateGroup.");
        @oci_close($conn);
        return false;
    }
    
    // 5. Commit
    if (!oci_commit($conn)) {
        oci_rollback($conn);
        error_log("OCI8 Error committing in updateGroup.");
        @oci_close($conn);
        return false;
    }

    oci_free_statement($stmt);
    oci_free_descriptor($clob);
    @oci_close($conn);

    return true; // Sukses
}

/**
 * Mengambil daftar user yang BELUM menjadi anggota group ini.
 * Digunakan untuk fitur "Tambah Anggota".
 */
function getUsersAvailableForGroup($group_id, $search = '') {
    require __DIR__ . '/../../config/koneksi.php';

    // KITA KEMBALIKAN 'foto_profil' KE SINI
    $sql = "SELECT user_id, username, nama_lengkap, foto_profil 
            FROM users 
            WHERE user_id NOT IN (
                SELECT user_id FROM group_members WHERE group_id = :fid
            )";

    if (!empty($search)) {
        $sql .= " AND (UPPER(nama_lengkap) LIKE :search OR UPPER(username) LIKE :search)";
    }

    $sql .= " ORDER BY nama_lengkap ASC FETCH FIRST 20 ROWS ONLY"; 

    $stmt = oci_parse($conn, $sql);
    
    $fid = (int)$group_id;
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
function kickMember($group_id, $target_user_id, $target_user_name) {
    require __DIR__ . '/../../config/koneksi.php';

    // 1. Pesan Sistem (Jalan duluan, koneksi sendiri)
    $nama_final = trim(preg_replace('/[[:cntrl:]\s]/u', ' ', $target_user_name));
    if(empty($nama_final)) $nama_final = 'Member';
    
    createSystemMessage((int)$group_id, (int)$target_user_id, 'leave', $nama_final . ' telah dikeluarkan oleh Admin Group.');

    // 2. Hapus dari database
    // GANTI :uid JADI :p_user_id
    $sql = "DELETE FROM group_members 
            WHERE user_id = :p_user_id AND group_id = :p_group_id";
            
    $stmt = oci_parse($conn, $sql);
    
    $clean_uid = (int)$target_user_id;
    $clean_fid = (int)$group_id;
    
    // Bind dengan nama baru yang AMAN
    oci_bind_by_name($stmt, ':p_user_id', $clean_uid);
    oci_bind_by_name($stmt, ':p_group_id', $clean_fid);

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