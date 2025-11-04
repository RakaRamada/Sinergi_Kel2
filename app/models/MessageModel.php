<?php
// File: app/models/MessageModel.php

/**
 * Menyimpan pesan baru ke database.
 * --- VERSI DIPERBARUI (Tanpa EMPTY_CLOB) ---
 *
 * @param int $forum_id ID forum tujuan.
 * @param int $sender_id ID pengguna yang mengirim.
 * @param string $isi_pesan Teks pesan.
 * @return int|false ID pesan baru jika berhasil, false jika gagal.
 */
function createMessage($forum_id, $sender_id, $isi_pesan) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB tidak tersedia di createMessage.");
        return false; 
    }

    $new_message_id = 0; // Siapkan variabel untuk menampung ID

    // --- PERBAIKAN SQL ---
    // Kita tidak pakai EMPTY_CLOB(). Kita insert string langsung.
    // Ini adalah satu query sederhana yang auto-commit.
    $sql = "INSERT INTO messages (forum_id, sender_id, isi_pesan, created_at)
            VALUES (:fid, :sid, :isi_pesan, SYSDATE)
            RETURNING message_id INTO :new_id";

    $stmt = oci_parse($conn, $sql);

    $clean_forum_id = (int)$forum_id;
    $clean_sender_id = (int)$sender_id;

    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':sid', $clean_sender_id, -1, SQLT_INT);
    // Bind isi_pesan sebagai string. Oracle akan otomatis konversi ke CLOB.
    oci_bind_by_name($stmt, ':isi_pesan', $isi_pesan, -1, SQLT_CHR);
    oci_bind_by_name($stmt, ':new_id', $new_message_id, -1, SQLT_INT);

    // Eksekusi (dengan auto-commit standar, BUKAN OCI_NO_AUTO_COMMIT)
    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in createMessage execute: " . $e['message']);
         @oci_close($conn);
         return false;
    }

    // Tidak perlu save CLOB atau commit manual
    
    oci_free_statement($stmt);
    @oci_close($conn);

    // Kembalikan ID baru
    return $new_message_id;
}


/**
 * Mengambil semua pesan lama (saat halaman dimuat)
 * (Fungsi ini sudah ada)
 */
function getMessagesByForumId($forum_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getMessagesByForumId.");
        return []; 
    }

    // Query 
// MENJADI:
$sql = "SELECT m.message_id, m.forum_id, m.sender_id, m.isi_pesan, 
               TO_CHAR(m.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at_iso, 
               TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, 
               m.message_type, u.nama_lengkap AS sender_nama 
        FROM messages m 
        JOIN users u ON m.sender_id = u.user_id 
        WHERE m.forum_id = :fid 
        ORDER BY m.created_at ASC";

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
        $isi_pesan_string = '';
        if (isset($row['ISI_PESAN']) && $row['ISI_PESAN'] instanceof OCILob) {
            $isi_pesan_string = $row['ISI_PESAN']->read($row['ISI_PESAN']->size());
        } elseif (isset($row['ISI_PESAN']) && is_string($row['ISI_PESAN'])) {
            $isi_pesan_string = $row['ISI_PESAN'];
        }
        $row['ISI_PESAN'] = $isi_pesan_string; 
        
        $messages[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $messages;
}


/**
 * --- FUNGSI BARU YANG HILANG (UNTUK AJAX SUBMIT) ---
 * Mengambil SATU pesan berdasarkan ID-nya.
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

    // MENJADI:
$sql = "SELECT m.message_id, m.forum_id, m.sender_id, m.isi_pesan, 
               TO_CHAR(m.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at_iso, 
               TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, 
               u.nama_lengkap AS sender_nama 
        FROM messages m 
        JOIN users u ON m.sender_id = u.user_id 
        WHERE m.message_id = :mid";
    $stmt = oci_parse($conn, $sql);
    
    $clean_id = (int)$message_id;
    oci_bind_by_name($stmt, ':mid', $clean_id, -1, SQLT_INT);

    if (!oci_execute($stmt)) {
        error_log('Error getMessageById: ' . oci_error($stmt)['message']);
        @oci_close($conn);
        return null;
    }

    $row = oci_fetch_assoc($stmt);
    
    // Proses CLOB
    if ($row && isset($row['ISI_PESAN']) && $row['ISI_PESAN'] instanceof OCILob) {
        $row['ISI_PESAN'] = $row['ISI_PESAN']->read($row['ISI_PESAN']->size());
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}


/**
 * Mengambil pesan baru (untuk Long Polling)
 * (Fungsi ini sudah ada)
 */
function getNewMessagesAfterId($forum_id, $last_message_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getNewMessagesAfterId.');
        return [];
    }

    // Query 
    // MENJADI:
$sql = "SELECT m.message_id, m.forum_id, m.sender_id, m.isi_pesan, 
               TO_CHAR(m.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at_iso, 
               TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, 
               m.message_type, u.nama_lengkap AS sender_nama 
        FROM messages m 
        JOIN users u ON m.sender_id = u.user_id 
        WHERE m.forum_id = :fid AND m.message_id > :last_id 
        ORDER BY m.created_at ASC";
        
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
        $isi_pesan_string = '';
        if (isset($row['ISI_PESAN']) && $row['ISI_PESAN'] instanceof OCILob) {
            $isi_pesan_string = $row['ISI_PESAN']->read($row['ISI_PESAN']->size());
        } elseif (isset($row['ISI_PESAN']) && is_string($row['ISI_PESAN'])) {
            $isi_pesan_string = $row['ISI_PESAN'];
        }
        $row['ISI_PESAN'] = $isi_pesan_string; 
        
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
?>