<?php
// File: app/models/MessageModel.php

/**
 * Menyimpan pesan baru ke database.
 * --- VERSI BARU: Bisa menangani Teks, Gambar, Dokumen, dan Reply ---
 *
 * @param int $group_id ID group
 * @param int $sender_id ID pengirim
 * @param string $isi_pesan Teks pesan (atau caption)
 * @param string $message_type 'text', 'image', atau 'document'
 * @param string|null $file_path Nama file unik di server
 * @param string|null $original_filename Nama file asli dari user
 * @param int|null $reply_to_message_id ID pesan yang dibalas (BARU)
 * @return int|false ID pesan baru jika berhasil, false jika gagal.
 */
function createMessage($group_id, $sender_id, $isi_pesan, $message_type, $file_path, $original_filename, $reply_to_id = 0) {
    global $conn;

    // REVISI: Ganti nama bind variable agar tidak bentrok dengan keyword Oracle (:uid, :rid, dll)
    // :uid -> :p_sender_id
    // :gid -> :p_group_id
    // :rid -> :p_reply_id
    
    $sql = "INSERT INTO messages (
                group_id, sender_id, isi_pesan, message_type, 
                file_path, original_filename, reply_to_message_id, created_at
            ) VALUES (
                :p_group_id, :p_sender_id, EMPTY_CLOB(), :p_msg_type, 
                :p_file_path, :p_orig_name, :p_reply_id, SYSTIMESTAMP
            ) RETURNING message_id, isi_pesan INTO :p_new_id, :p_clob_loc";

    $stmt = oci_parse($conn, $sql);
    
    $clob = oci_new_descriptor($conn, OCI_D_LOB);
    
    // Casting
    $safe_gid = (int)$group_id;
    $safe_uid = (int)$sender_id;
    $safe_rid = ($reply_to_id > 0) ? (int)$reply_to_id : null;

    // Binding dengan Nama Baru (AMAN)
    oci_bind_by_name($stmt, ':p_group_id', $safe_gid);
    oci_bind_by_name($stmt, ':p_sender_id', $safe_uid);
    oci_bind_by_name($stmt, ':p_msg_type', $message_type);
    oci_bind_by_name($stmt, ':p_file_path', $file_path);
    oci_bind_by_name($stmt, ':p_orig_name', $original_filename);
    oci_bind_by_name($stmt, ':p_reply_id', $safe_rid);
    
    // Output Bind
    $new_id = 0;
    oci_bind_by_name($stmt, ':p_new_id', $new_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':p_clob_loc', $clob, -1, OCI_B_CLOB);

    // Eksekusi
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        // Tampilkan JSON Error biar kebaca di Network Tab
        die(json_encode(['error' => 'Database Error: ' . $e['message']]));
    }

    // Simpan CLOB
    if (!empty($isi_pesan)) {
        $clob->save($isi_pesan);
    } else {
        $clob->save(' '); 
    }

    oci_commit($conn);
    oci_free_statement($stmt);
    
    return $new_id;
}


/**
 * Mengambil semua pesan lama (saat halaman dimuat)
 * --- DIPERBARUI: Menambahkan data Reply ---
 */
function getMessagesByGroupId($group_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getMessagesByGroupId.");
        return []; 
    }

    // Query SEKARANG JOIN ke dirinya sendiri untuk data reply
    $sql = "SELECT 
                m.message_id, m.group_id, m.sender_id, m.isi_pesan, 
                m.message_type, m.file_path, m.original_filename,
                m.reply_to_message_id, 
                TO_CHAR(m.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at_iso, 
                TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, 
                u.nama_lengkap AS sender_nama,
                r.isi_pesan AS replied_message_text,
                ru.nama_lengkap AS replied_sender_nama
            FROM 
                messages m 
            JOIN 
                users u ON m.sender_id = u.user_id
            LEFT JOIN 
                messages r ON m.reply_to_message_id = r.message_id
            LEFT JOIN 
                users ru ON r.sender_id = ru.user_id
            WHERE 
                m.group_id = :fid 
            ORDER BY 
                m.created_at ASC";

    $stmt = oci_parse($conn, $sql);

    $clean_group_id = (int)$group_id;
    oci_bind_by_name($stmt, ':fid', $clean_group_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getMessagesByGroupId: " . $e['message']);
         @oci_close($conn);
         return []; 
    }

    $messages = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Proses CLOB untuk pesan utama
        $isi_pesan_string = '';
        if (isset($row['ISI_PESAN']) && $row['ISI_PESAN'] instanceof OCILob) {
            $isi_pesan_string = $row['ISI_PESAN']->read($row['ISI_PESAN']->size());
        } elseif (isset($row['ISI_PESAN']) && is_string($row['ISI_PESAN'])) {
            $isi_pesan_string = $row['ISI_PESAN'];
        }
        $row['ISI_PESAN'] = $isi_pesan_string; 
        
        // --- BARU: Proses CLOB untuk pesan yang dibalas ---
        $replied_text_string = '';
        if (isset($row['REPLIED_MESSAGE_TEXT']) && $row['REPLIED_MESSAGE_TEXT'] instanceof OCILob) {
            $replied_text_string = $row['REPLIED_MESSAGE_TEXT']->read($row['REPLIED_MESSAGE_TEXT']->size());
        } elseif (isset($row['REPLIED_MESSAGE_TEXT']) && is_string($row['REPLIED_MESSAGE_TEXT'])) {
            $replied_text_string = $row['REPLIED_MESSAGE_TEXT'];
        }
        $row['REPLIED_MESSAGE_TEXT'] = $replied_text_string;
        // --- AKHIR BARU ---

        $messages[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $messages;
}


/**
 * Mengambil SATU pesan berdasarkan ID-nya.
 * --- DIPERBARUI: Menambahkan data Reply ---
 *
 * @param int $message_id ID pesan yang baru dibuat.
 * @return array|null Array data pesan, atau null.
 */
function getMessageById($message_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getMessageById.');
        return null;
    }

    $sql = "SELECT 
                m.message_id, m.group_id, m.sender_id, m.isi_pesan, 
                m.message_type, m.file_path, m.original_filename,
                m.reply_to_message_id,
                TO_CHAR(m.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at_iso, 
                TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, 
                u.nama_lengkap AS sender_nama,
                r.isi_pesan AS replied_message_text,
                ru.nama_lengkap AS replied_sender_nama
            FROM 
                messages m 
            JOIN 
                users u ON m.sender_id = u.user_id
            LEFT JOIN 
                messages r ON m.reply_to_message_id = r.message_id
            LEFT JOIN 
                users ru ON r.sender_id = ru.user_id
            WHERE 
                m.message_id = :mid";

    $stmt = oci_parse($conn, $sql);
    
    $clean_id = (int)$message_id;
    oci_bind_by_name($stmt, ':mid', $clean_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
        error_log('Error getMessageById: ' . oci_error($stmt)['message']);
        @oci_close($conn);
        return null;
    }

    $row = oci_fetch_assoc($stmt);
    
    // Proses CLOB pesan utama
    if ($row && isset($row['ISI_PESAN']) && $row['ISI_PESAN'] instanceof OCILob) {
        $row['ISI_PESAN'] = $row['ISI_PESAN']->read($row['ISI_PESAN']->size());
    }

    // --- BARU: Proses CLOB pesan yang dibalas ---
    if ($row && isset($row['REPLIED_MESSAGE_TEXT']) && $row['REPLIED_MESSAGE_TEXT'] instanceof OCILob) {
        $row['REPLIED_MESSAGE_TEXT'] = $row['REPLIED_MESSAGE_TEXT']->read($row['REPLIED_MESSAGE_TEXT']->size());
    }
    // --- AKHIR BARU ---

    oci_free_statement($stmt);
    @oci_close($conn);
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}


/**
 * Mengambil pesan baru (untuk Long Polling)
 * --- DIPERBARUI: Menambahkan data Reply ---
 */
function getNewMessagesAfterId($group_id, $last_message_id) {
    global $conn;

    // Pastikan nama kolom 'group_id' benar (dulu forum_id)
    $sql = "SELECT 
                m.message_id, 
                m.group_id, 
                m.sender_id, 
                m.isi_pesan, 
                m.message_type, 
                m.file_path,
                m.original_filename,
                m.reply_to_message_id, -- Tambahan untuk fitur reply
                TO_CHAR(m.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_iso,
                TO_CHAR(m.created_at, 'HH24:MI') as created_at_time,
                u.nama_lengkap as sender_nama,
                
                -- Ambil info pesan yang dibalas (Reply Context)
                r.isi_pesan as replied_message_text,
                ru.nama_lengkap as replied_sender_nama

            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            -- Join Self untuk ambil data reply
            LEFT JOIN messages r ON m.reply_to_message_id = r.message_id
            LEFT JOIN users ru ON r.sender_id = ru.user_id
            
            WHERE m.group_id = :gid 
            AND m.message_id > :lid
            ORDER BY m.created_at ASC";

    $stmt = oci_parse($conn, $sql);
    
    $safe_gid = (int)$group_id;
    $safe_lid = (int)$last_message_id;

    oci_bind_by_name($stmt, ':gid', $safe_gid);
    oci_bind_by_name($stmt, ':lid', $safe_lid);

    if (!oci_execute($stmt)) {
        return [];
    }

    $messages = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Proses CLOB jika ada
        if (isset($row['ISI_PESAN']) && $row['ISI_PESAN'] instanceof OCILob) {
            $row['ISI_PESAN'] = $row['ISI_PESAN']->read($row['ISI_PESAN']->size());
        }
        if (isset($row['REPLIED_MESSAGE_TEXT']) && $row['REPLIED_MESSAGE_TEXT'] instanceof OCILob) {
            $row['REPLIED_MESSAGE_TEXT'] = $row['REPLIED_MESSAGE_TEXT']->read($row['REPLIED_MESSAGE_TEXT']->size());
        }

        $messages[] = array_change_key_case($row, CASE_LOWER);
    }
    
    return $messages;
}

/**
 * Membuat pesan sistem (join/leave) di dalam chat.
 *
 * @param int $group_id ID group.
 * @param int $user_id ID user yang join/leave.
 * @param string $type Tipe pesan ('join' or 'leave').
 * @param string $message_text Teks yang akan ditampilkan (misal: "telah bergabung")
 * @return bool
 */
function createSystemMessage($group_id, $user_id, $type, $message_text) {
    require __DIR__ . '/../../config/koneksi.php';
    if(!$conn) {
        error_log("Koneksi DB Gagal di CreateSystemMessage.");
        return false;
    }

    // query insert (menggunakan kolom message_type) kita biarkan auto commit

    $sql = "INSERT INTO messages (group_id, sender_id, isi_pesan, created_at, message_type) VALUES (:fid, :sid, :isi_pesan, SYSDATE, :msg_type)";

    $stmt = oci_parse($conn, $sql);

    // Bind Parameter
    $clean_group_id = (int)$group_id;
    $clean_sender_id = (int)$user_id;

    oci_bind_by_name($stmt, ':fid', $clean_group_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':sid', $clean_sender_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':isi_pesan', $message_text, -1, SQLT_CHR); //bind sebagai string biasa
    oci_bind_by_name($stmt, ':msg_type', $type);

    // eksekusi
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        error_log("OCI8 Error in createSystemMessage : " . $e['message']);
        @oci_close($conn);
        return false;
    }
    // bebaskan resource
    oci_free_statement($stmt);
    @oci_close($conn);
    return true;
}


/**
 * Menghapus sebuah pesan dari database.
 * PENTING: Pesan hanya akan terhapus JIKA user_id cocok dengan sender_id.
 *
 * @param int $message_id ID pesan yang akan dihapus.
 * @param int $user_id ID user yang meminta (untuk verifikasi kepemilikan).
 * @return bool True jika berhasil menghapus, false jika gagal.
 */
function deleteMessage($message_id, $user_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB Gagal di deleteMessage.");
        return false;
    }

    // --- INI ADALAH QUERY KUNCINYA ---
    // Kita HANYA akan menghapus baris JIKA:
    // 1. ID pesannya cocok (message_id = :mid)
    // 2. DAN ID pengirimnya cocok (sender_id = :sid)
    $sql = "DELETE FROM messages 
            WHERE message_id = :mid AND sender_id = :sid";

    $stmt = oci_parse($conn, $sql);

    // Bind ID pesan yang akan dihapus
    $clean_mid = (int)$message_id;
    oci_bind_by_name($stmt, ':mid', $clean_mid, -1, SQLT_INT);
    
    // Bind ID user yang sedang login (sebagai 'kunci' keamanan)
    $clean_sid = (int)$user_id;
    oci_bind_by_name($stmt, ':sid', $clean_sid, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
        // Jika query-nya sendiri error
        $e = oci_error($stmt);
        error_log("OCI8 Error in deleteMessage : " . $e['message']);
        @oci_close($conn);
        return false;
    }
    
    // --- VERIFIKASI ---
    // Cek apakah ada baris yang benar-benar terhapus
    $rows_affected = oci_num_rows($stmt);
    
    oci_free_statement($stmt);
    @oci_close($conn);
    
    // Jika $rows_affected > 0, berarti hapus berhasil
    // Jika 0, berarti pesan itu tidak ada ATAU bukan milik user ini
    return ($rows_affected > 0);
}

/**
 * Mengambil semua media (gambar) dari sebuah group.
 *
 * @param int $group_id ID group.
 * @return array Array berisi daftar media.
 */
function getMediaByGroupId($group_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getMediaByGroupId.");
        return [];
    }

    $sql = "SELECT message_id, file_path, original_filename
            FROM messages
            WHERE group_id = :fid 
            AND message_type = 'image'
            ORDER BY created_at DESC";
    
    $stmt = oci_parse($conn, $sql);
    
    $clean_group_id = (int)$group_id;
    oci_bind_by_name($stmt, ':fid', $clean_group_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getMediaByGroupId: " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $media = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $media[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $media;
}

/**
 * Mengambil semua dokumen dari sebuah group.
 *
 * @param int $group_id ID group.
 * @return array Array berisi daftar dokumen.
 */
function getDocumentsByGroupId($group_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getDocumentsByGroupId.");
        return [];
    }

    // Kita JOIN dengan USERS untuk dapat nama pengirim
    $sql = "SELECT 
                m.message_id, 
                m.file_path, 
                m.original_filename, 
                TO_CHAR(m.created_at, 'DD Mon YYYY') AS created_at_formatted,
                u.nama_lengkap AS sender_nama
            FROM 
                messages m
            JOIN 
                users u ON m.sender_id = u.user_id
            WHERE 
                m.group_id = :fid 
            AND 
                m.message_type = 'document'
            ORDER BY 
                m.created_at DESC";
    
    $stmt = oci_parse($conn, $sql);
    
    $clean_group_id = (int)$group_id;
    oci_bind_by_name($stmt, ':fid', $clean_group_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getDocumentsByGroupId: " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $documents = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $documents[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $documents;
}

/**
 * Memperbarui 'pesan terakhir dibaca' oleh user di sebuah group.
 * VERSI BARU: Meng-UPDATE tabel group_members
 * --- PERBAIKAN ORA-01745 (FINAL V6 - GANTI NAMA BIND) ---
 *
 * @param int $user_id ID user yang sedang membaca
 * @param int $group_id ID group yang sedang dibuka
 * @return bool
 */
function updateLastReadMessage($user_id, $group_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di updateLastReadMessage.");
        return false;
    }

    $clean_user_id = (int)$user_id;
    $clean_group_id = (int)$group_id;

    // 1. Cari ID pesan terakhir
    $sql_max_id = "SELECT MAX(message_id) AS max_id FROM messages WHERE group_id = :fid_max"; // Nama unik
    $stmt_max = oci_parse($conn, $sql_max_id);
    oci_bind_by_name($stmt_max, ':fid_max', $clean_group_id, -1, SQLT_INT); // Nama unik
    
    if (!oci_execute($stmt_max)) {
        error_log("OCI8 Error get MAX_ID in updateLastReadMessage: " . oci_error($stmt_max)['message']);
        @oci_close($conn);
        return false;
    }
    
    $row = oci_fetch_assoc($stmt_max);
    $max_message_id = $row ? (int)$row['MAX_ID'] : 0;
    oci_free_statement($stmt_max);

    if ($max_message_id == 0) {
        @oci_close($conn);
        return true; 
    }

    // 2. Query UPDATE (DENGAN NAMA BARU YANG AMAN)
    // =======================================================
    // PERBAIKAN DI SINI:
    // :max_id -> :newmessageid
    // :uid -> :currentuserid
    // :fid -> :currentgroupid
    // =======================================================
    $sql_update = "UPDATE group_members 
                   SET last_read_message_id = :newmessageid
                   WHERE user_id = :currentuserid AND group_id = :currentgroupid"; 
    
    $stmt_update = oci_parse($conn, $sql_update);

    // Bind semua parameter dengan NAMA BARU
    oci_bind_by_name($stmt_update, ':newmessageid', $max_message_id, -1, SQLT_INT);
    oci_bind_by_name($stmt_update, ':currentuserid', $clean_user_id, -1, SQLT_INT);
    oci_bind_by_name($stmt_update, ':currentgroupid', $clean_group_id, -1, SQLT_INT);

    // Ini adalah line 544 kamu (sekarang)
    if (!oci_execute($stmt_update)) {
        $e = oci_error($stmt_update);
        error_log("OCI8 Error UPDATE in updateLastReadMessage: " . $e['message']);
        @oci_close($conn);
        return false;
    }

    oci_free_statement($stmt_update);
    @oci_close($conn);
    return true;
}
?>