<?php
// File: app/models/GroupModel.php

// --- PERUBAHAN 1: Pindahkan require_once ke atas ---
require_once __DIR__ . '/UserModel.php';
require_once __DIR__ . '/MessageModel.php';
require_once __DIR__ . '/NotificationModel.php';



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
    if (!$conn) return null;

    $sql = "SELECT 
                group_id, 
                nama_group, 
                deskripsi, 
                group_image, 
                created_by_user_id, 
                is_private, -- Tambahkan ini
                TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at 
            FROM groups 
            WHERE group_id = :fid";
    
    // ... (sisanya sama seperti sebelumnya) ...
    $stmt = oci_parse($conn, $sql);
    $clean_group_id = (int)$group_id;
    oci_bind_by_name($stmt, ':fid', $clean_group_id);
    oci_execute($stmt);
    $group = oci_fetch_assoc($stmt);
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
                AND fm.status = 'active'
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
function createGroup($nama_group, $deskripsi, $creator_user_id, $image_name, $is_private) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return false;

    $clean_private = (int)$is_private;
    $clean_creator_id = (int)$creator_user_id;
    $new_group_id = 0;

    // FIX: Ganti :priv jadi :p_private, :nama jadi :p_nama, dll biar aman
    $sql = "INSERT INTO groups (nama_group, deskripsi, created_by_user_id, group_image, is_private, created_at)
            VALUES (:p_nama, EMPTY_CLOB(), :p_creator, :p_img, :p_private, SYSDATE)
            RETURNING group_id, deskripsi INTO :p_new_id, :p_desk_clob";

    $stmt = oci_parse($conn, $sql);
    $clob = oci_new_descriptor($conn, OCI_D_LOB);

    // Bind Variable Aman
    oci_bind_by_name($stmt, ':p_nama', $nama_group);
    oci_bind_by_name($stmt, ':p_creator', $clean_creator_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':p_img', $image_name);
    oci_bind_by_name($stmt, ':p_private', $clean_private, -1, SQLT_INT);
    
    oci_bind_by_name($stmt, ':p_desk_clob', $clob, -1, OCI_B_CLOB);
    oci_bind_by_name($stmt, ':p_new_id', $new_group_id, -1, SQLT_INT);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error createGroup: " . $e['message']);
         oci_rollback($conn); @oci_close($conn); return false;
    }

    if (!$clob->save($deskripsi)) {
        oci_rollback($conn); @oci_close($conn); return false;
    }
    
    if (!oci_commit($conn)) {
        oci_rollback($conn); @oci_close($conn); return false;
    }

    oci_free_statement($stmt);
    oci_free_descriptor($clob);
    @oci_close($conn);

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
function joinGroup($user_id, $group_id, $user_nama = 'Seseorang', $custom_message = null, $status = 'active') {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return false;
    
    $clean_uid = (int)$user_id;
    $clean_fid = (int)$group_id;
    $clean_stat = $status;

    // 1. CEK DULU: Apakah user sudah ada di tabel? (Entah invited, pending, atau left)
    // Gunakan nama bind yang aman :p_...
    $sqlCheck = "SELECT count(*) as hitung FROM group_members WHERE user_id = :p_uid AND group_id = :p_fid";
    $stmtCheck = oci_parse($conn, $sqlCheck);
    oci_bind_by_name($stmtCheck, ':p_uid', $clean_uid);
    oci_bind_by_name($stmtCheck, ':p_fid', $clean_fid);
    oci_execute($stmtCheck);
    $row = oci_fetch_assoc($stmtCheck);
    
    // 2. EKSEKUSI (INSERT ATAU UPDATE)
    if ($row['HITUNG'] > 0) {
        // KASUS INVITED: User sudah ada datanya, jadi kita UPDATE statusnya
        $sql = "UPDATE group_members 
                SET status = :p_stat, joined_at = SYSTIMESTAMP 
                WHERE user_id = :p_uid AND group_id = :p_fid";
    } else {
        // KASUS BARU: User belum pernah ada, jadi INSERT
        $sql = "INSERT INTO group_members (user_id, group_id, status, joined_at) 
                VALUES (:p_uid, :p_fid, :p_stat, SYSTIMESTAMP)";
    }

    $stmt = oci_parse($conn, $sql);
    
    // Bind Parameter Aman (Anti ORA-01745)
    oci_bind_by_name($stmt, ':p_uid', $clean_uid);
    oci_bind_by_name($stmt, ':p_fid', $clean_fid);
    oci_bind_by_name($stmt, ':p_stat', $clean_stat);

    // Eksekusi dengan COMMIT OTOMATIS agar perubahan status langsung tersimpan
    if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) { 
         $e = oci_error($stmt);
         error_log("Join Group Error: " . $e['message']);
         @oci_close($conn); 
         return false;
    }

    // 3. KIRIM PESAN SISTEM (Hanya kalau status Active)
    // Biar di chat grup muncul: "Si Fulan telah bergabung"
    if ($clean_stat === 'active') {
        $nama_asli = $user_nama ?? 'Seseorang';
        $nama_final = trim(preg_replace('/[[:cntrl:]\s]/u', ' ', $nama_asli)); 
        if (empty($nama_final)) $nama_final = 'Seseorang';

        $pesan = $custom_message ? $custom_message : ($nama_final . ' telah bergabung dengan group.');
        
        // Panggil fungsi system message (Pastikan fungsi ini juga aman bind-nya di file yang sama)
        createSystemMessage($clean_fid, $clean_uid, 'join', $pesan);
    }

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
function searchGroups($searchTerm, $current_user_id = 0) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return [];

    // Query Search + Cek Status Member User yang sedang login
    // PERHATIKAN: :uid diganti jadi :p_search_uid
    // :term diganti jadi :p_term (biar konsisten aman)
    $sql = "SELECT g.group_id, g.nama_group, g.deskripsi, g.group_image, g.is_private,
                   gm.status AS membership_status
            FROM groups g
            LEFT JOIN group_members gm ON (g.group_id = gm.group_id AND gm.user_id = :p_search_uid)
            WHERE UPPER(g.nama_group) LIKE :p_term
            ORDER BY g.created_at DESC";
            
    $searchTermWildcard = '%' . strtoupper($searchTerm) . '%';
    $stmt = oci_parse($conn, $sql);
    
    $clean_uid = (int)$current_user_id;
    
    // Bind dengan nama baru yang aman
    oci_bind_by_name($stmt, ':p_term', $searchTermWildcard);
    oci_bind_by_name($stmt, ':p_search_uid', $clean_uid);

    if (!oci_execute($stmt)) {
        // (Opsional) Uncomment untuk debugging jika masih error
        // $e = oci_error($stmt); error_log("Search Error: " . $e['message']);
        return [];
    }

    $groups = [];
    while ($row = oci_fetch_assoc($stmt)) {
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
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return [];

    $safe_group_id = (int)$group_id;

    // FIX: u.foto_profil -> u.avatar_url
    $sql = "SELECT u.user_id, u.username, u.nama_lengkap, u.avatar_url, r.role_name 
            FROM group_members fm
            JOIN users u ON fm.user_id = u.user_id
            JOIN roles r ON u.role_id = r.role_id
            WHERE fm.group_id = :fid AND fm.status = 'active'
            ORDER BY u.nama_lengkap ASC";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':fid', $safe_group_id);
    oci_execute($stmt);

    $members = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $members[] = array_change_key_case($row, CASE_LOWER);
    }
    oci_free_statement($stmt);
    @oci_close($conn);
    return $members;
}

// 2. UPDATE: getPendingMembers (Pakai avatar_url)
function getPendingMembers($group_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return [];

    $safe_group_id = (int)$group_id;

    // FIX: u.foto_profil -> u.avatar_url
    $sql = "SELECT u.user_id, u.username, u.nama_lengkap, u.avatar_url 
            FROM group_members fm
            JOIN users u ON fm.user_id = u.user_id
            WHERE fm.group_id = :fid AND fm.status = 'pending'
            ORDER BY fm.joined_at ASC";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':fid', $safe_group_id);
    oci_execute($stmt);

    $pending = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $pending[] = array_change_key_case($row, CASE_LOWER);
    }
    oci_free_statement($stmt);
    @oci_close($conn);
    return $pending;
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
function updateGroup($group_id, $nama_group, $deskripsi, $image_name, $is_private) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return false;

    $clean_group_id = (int)$group_id;
    $clean_private  = (int)$is_private;

    // FIX: Ganti nama bind variable agar tidak bentrok dengan keyword Oracle
    if ($image_name !== null) {
        $sql = "UPDATE groups 
                SET nama_group = :p_nama, 
                    deskripsi = EMPTY_CLOB(), 
                    group_image = :p_img,
                    is_private = :p_private
                WHERE group_id = :p_gid
                RETURNING deskripsi INTO :p_desk_clob";
    } else {
        $sql = "UPDATE groups 
                SET nama_group = :p_nama, 
                    deskripsi = EMPTY_CLOB(),
                    is_private = :p_private
                WHERE group_id = :p_gid
                RETURNING deskripsi INTO :p_desk_clob";
    }

    $stmt = oci_parse($conn, $sql);
    $clob = oci_new_descriptor($conn, OCI_D_LOB);

    // Bind Variable Aman
    oci_bind_by_name($stmt, ':p_nama', $nama_group);
    oci_bind_by_name($stmt, ':p_gid', $clean_group_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':p_private', $clean_private, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':p_desk_clob', $clob, -1, OCI_B_CLOB);
    
    if ($image_name !== null) {
        oci_bind_by_name($stmt, ':p_img', $image_name);
    }

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error updateGroup: " . $e['message']);
         oci_rollback($conn); @oci_close($conn); return false;
    }

    if (!$clob->save($deskripsi)) {
        oci_rollback($conn); @oci_close($conn); return false;
    }
    
    if (!oci_commit($conn)) {
        oci_rollback($conn); @oci_close($conn); return false;
    }

    oci_free_statement($stmt);
    oci_free_descriptor($clob);
    @oci_close($conn);

    return true;
}

/**
 * Mengambil daftar user yang BELUM menjadi anggota group ini.
 * Digunakan untuk fitur "Tambah Anggota".
 */
function getUsersAvailableForGroup($group_id, $search = '') {
    require __DIR__ . '/../../config/koneksi.php';

    // FIX: foto_profil -> avatar_url
    $sql = "SELECT user_id, username, nama_lengkap, avatar_url 
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

    if (!@oci_execute($stmt)) return [];

    $users = [];
    while ($row = oci_fetch_assoc($stmt)) {
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

function processJoinRequest($user_id, $group_id, $action) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return false;
    
    $clean_uid = (int)$user_id;
    $clean_fid = (int)$group_id;
    $clean_action = trim($action);

    // Tentukan Query
    if ($clean_action === 'approve') {
        // UPDATE status jadi active
        // Perhatikan nama bind variable: :p_request_uid dan :p_request_gid
        $sql = "UPDATE group_members SET status = 'active' 
                WHERE user_id = :p_request_uid AND group_id = :p_request_gid";
    } elseif ($clean_action === 'reject') {
        // DELETE data
        $sql = "DELETE FROM group_members 
                WHERE user_id = :p_request_uid AND group_id = :p_request_gid";
    } else {
        return false;
    }

    $stmt = oci_parse($conn, $sql);
    
    // BINDING AMAN (Anti ORA-01745)
    oci_bind_by_name($stmt, ':p_request_uid', $clean_uid, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':p_request_gid', $clean_fid, -1, SQLT_INT);
    
    // Eksekusi + Auto Commit
    // Kita gunakan OCI_COMMIT_ON_SUCCESS agar langsung tersimpan permanen
    if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
        $e = oci_error($stmt);
        error_log("SQL Error processJoinRequest: " . $e['message']);
        oci_free_statement($stmt);
        @oci_close($conn);
        return false;
    }

    // Cek apakah ada baris yang berubah
    // (Opsional, tapi kita return true aja kalau tidak error SQL biar tidak redirect ke 'failed')
    $rows = oci_num_rows($stmt);
    
    oci_free_statement($stmt);
    @oci_close($conn);
    
    return true;
}

function inviteUserToGroup($admin_id, $target_user_id, $group_id, $group_name) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return false;
    
    // 1. Cek apakah user sudah ada di grup (status apapun)
    $sqlCheck = "SELECT count(*) as hitung FROM group_members 
                 WHERE user_id = :p_uid AND group_id = :p_gid";
    $stmtCheck = oci_parse($conn, $sqlCheck);
    
    $clean_uid = (int)$target_user_id;
    $clean_gid = (int)$group_id;
    
    oci_bind_by_name($stmtCheck, ':p_uid', $clean_uid);
    oci_bind_by_name($stmtCheck, ':p_gid', $clean_gid);
    oci_execute($stmtCheck);
    $row = oci_fetch_assoc($stmtCheck);
    
    if ($row['HITUNG'] > 0) {
        return false; // User sudah ada
    }

    // 2. Insert ke Group Members dengan status 'invited'
    $sqlInsert = "INSERT INTO group_members (user_id, group_id, status, joined_at) 
                  VALUES (:p_uid, :p_gid, 'invited', SYSTIMESTAMP)";
    $stmt = oci_parse($conn, $sqlInsert);
    oci_bind_by_name($stmt, ':p_uid', $clean_uid);
    oci_bind_by_name($stmt, ':p_gid', $clean_gid);
    
    // Eksekusi Insert Member
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($conn); return false;
    }

    // 3. Commit Member Dulu
    oci_commit($conn); 

    // 4. KIRIM NOTIFIKASI (Pakai Fungsi dari NotificationModel.php)
    // createNotification($penerima, $pelaku, $tipe, $pesan, $post_id, $group_id)
    $msg = "Mengundang Anda bergabung ke grup: " . $group_name;
    createNotification($clean_uid, $admin_id, 'group_invite', $msg, null, $clean_gid);

    oci_free_statement($stmt);
    @oci_close($conn);
    return true;
}

function isGroupMember($user_id, $group_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) return false;

    // Cek status harus 'active'
    $sql = "SELECT count(*) as hitung FROM group_members 
            WHERE user_id = :p_uid AND group_id = :p_gid AND status = 'active'";
            
    $stmt = oci_parse($conn, $sql);
    
    $clean_uid = (int)$user_id;
    $clean_gid = (int)$group_id;
    
    oci_bind_by_name($stmt, ':p_uid', $clean_uid);
    oci_bind_by_name($stmt, ':p_gid', $clean_gid);
    
    if (!oci_execute($stmt)) {
        return false;
    }
    
    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    @oci_close($conn);

    return ($row['HITUNG'] > 0);
}
?>