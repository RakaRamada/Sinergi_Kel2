<?php
// File: app/models/MessageModel.php

/**
 * Menyimpan pesan baru ke database.
 * --- VERSI BARU: Bisa menangani Teks, Gambar, Dokumen, dan Reply ---
 *
 * @param int $forum_id ID forum
 * @param int $sender_id ID pengirim
 * @param string $isi_pesan Teks pesan (atau caption)
 * @param string $message_type 'text', 'image', atau 'document'
 * @param string|null $file_path Nama file unik di server
 * @param string|null $original_filename Nama file asli dari user
 * @param int|null $reply_to_message_id ID pesan yang dibalas (BARU)
 * @return int|false ID pesan baru jika berhasil, false jika gagal.
 */
function createMessage($forum_id, $sender_id, $isi_pesan, $message_type, $file_path, $original_filename, $reply_to_message_id) { // <-- 1. Parameter baru
    require __DIR__ . '/../../config/koneksi.php';

    
    if (!$conn) {
        error_log("Koneksi DB tidak tersedia di createMessage.");
        return false; 
    }

    $new_message_id = 0; 

    // 2. Query INSERT sekarang menyertakan 'reply_to_message_id'
    $sql = "INSERT INTO messages (
                forum_id, sender_id, isi_pesan, message_type, 
                file_path, original_filename, reply_to_message_id, created_at
            )
            VALUES (
                :fid, :sid, :isi_pesan, :msg_type, 
                :file_path, :orig_filename, :reply_id, SYSDATE
            )
            RETURNING message_id INTO :new_id";

    $stmt = oci_parse($conn, $sql);

    $clean_forum_id = (int)$forum_id;
    $clean_sender_id = (int)$sender_id;

    // Bind parameter dasar
    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':sid', $clean_sender_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':msg_type', $message_type);
    
    // Solusi ORA-01400 (dari error sebelumnya, JANGAN DIHAPUS)
    $isi_pesan_safe = ($isi_pesan === '') ? ' ' : $isi_pesan;
    oci_bind_by_name($stmt, ':isi_pesan', $isi_pesan_safe, -1, SQLT_CHR); 

    // Bind parameter file
    oci_bind_by_name($stmt, ':file_path', $file_path);
    oci_bind_by_name($stmt, ':orig_filename', $original_filename);

    // --- 3. BARU: Bind parameter Reply ---
    // Cek jika $reply_to_message_id valid (lebih dari 0), jika tidak, kirim NULL
    $clean_reply_id = ($reply_to_message_id > 0) ? (int)$reply_to_message_id : null;
   oci_bind_by_name($stmt, ':reply_id', $clean_reply_id);
    // --- AKHIR BARU ---

    // Bind ID baru
    oci_bind_by_name($stmt, ':new_id', $new_message_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in createMessage execute: ". $e['message']);
         @oci_close($conn);
         return false;
    }
    
    oci_free_statement($stmt);
    @oci_close($conn);

    return $new_message_id;
}


/**
 * Mengambil semua pesan lama (saat halaman dimuat)
 * --- DIPERBARUI: Menambahkan data Reply ---
 */
function getMessagesByForumId($forum_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getMessagesByForumId.");
        return []; 
    }

    // Query SEKARANG JOIN ke dirinya sendiri untuk data reply
    $sql = "SELECT 
                m.message_id, m.forum_id, m.sender_id, m.isi_pesan, 
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
                m.forum_id = :fid 
            ORDER BY 
                m.created_at ASC";

    $stmt = oci_parse($conn, $sql);

    $clean_forum_id = (int)$forum_id;
    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getMessagesByForumId: " . $e['message']);
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
                m.message_id, m.forum_id, m.sender_id, m.isi_pesan, 
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
function getNewMessagesAfterId($forum_id, $last_message_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getNewMessagesAfterId.');
        return [];
    }

    // Query 
    $sql = "SELECT 
                m.message_id, m.forum_id, m.sender_id, m.isi_pesan, 
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
                m.forum_id = :fid AND m.message_id > :last_id 
            ORDER BY 
                m.created_at ASC";

    $stmt = oci_parse($conn, $sql);

    $clean_forum_id = (int)$forum_id;
    $clean_last_id = (int)$last_message_id;
    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':last_id', $clean_last_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
        error_log('Error getNewMessagesAfterId: ' . oci_error($stmt)['message']);
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
 * Membuat pesan sistem (join/leave) di dalam chat.
 *
 * @param int $forum_id ID forum.
 * @param int $user_id ID user yang join/leave.
 * @param string $type Tipe pesan ('join' or 'leave').
 * @param string $message_text Teks yang akan ditampilkan (misal: "telah bergabung")
 * @return bool
 */
function createSystemMessage($forum_id, $user_id, $type, $message_text) {
    require __DIR__ . '/../../config/koneksi.php';
    if(!$conn) {
        error_log("Koneksi DB Gagal di CreateSystemMessage.");
        return false;
    }

    // query insert (menggunakan kolom message_type) kita biarkan auto commit

    $sql = "INSERT INTO messages (forum_id, sender_id, isi_pesan, created_at, message_type) VALUES (:fid, :sid, :isi_pesan, SYSDATE, :msg_type)";

    $stmt = oci_parse($conn, $sql);

    // Bind Parameter
    $clean_forum_id = (int)$forum_id;
    $clean_sender_id = (int)$user_id;

    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);
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
?>