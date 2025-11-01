<?php
// File: app/models/MessageModel.php

/**
 * Menyimpan pesan baru ke database.
 * (Fungsi ini sudah benar)
 */
function createMessage($forum_id, $sender_id, $isi_pesan) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB tidak tersedia di createMessage.");
        return false; 
    }

    $sql = "INSERT INTO messages (forum_id, sender_id, isi_pesan, created_at)
            VALUES (:fid, :sid, EMPTY_CLOB(), SYSDATE)
            RETURNING isi_pesan INTO :isi_pesan_clob"; 

    $stmt = oci_parse($conn, $sql);

    $clean_forum_id = (int)$forum_id;
    $clean_sender_id = (int)$sender_id;
    $clob = oci_new_descriptor($conn, OCI_D_LOB);

    oci_bind_by_name($stmt, ':fid', $clean_forum_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':sid', $clean_sender_id, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':isi_pesan_clob', $clob, -1, OCI_B_CLOB);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in createMessage execute: " . $e['message']);
         oci_rollback($conn);
         @oci_close($conn);
         return false;
    }

    if (!$clob->save($isi_pesan)) {
        oci_rollback($conn); 
        $e = oci_error($clob); 
        error_log("OCI8 Error saving CLOB: " . ($e ? $e['message'] : 'Unknown error'));
        @oci_close($conn);
        return false;
    }

    if (!oci_commit($conn)) {
        $e = oci_error($conn); 
        error_log("OCI8 Error committing transaction: " . ($e ? $e['message'] : 'Unknown error'));
        oci_rollback($conn); 
        @oci_close($conn);
        return false;
    }

    oci_free_statement($stmt);
    oci_free_descriptor($clob);
    @oci_close($conn);

    return true; 
}


/**
 * Mengambil semua pesan lama (saat halaman dimuat)
 * (Fungsi ini yang menyebabkan timestamp hilang di pesan lama)
 */
function getMessagesByForumId($forum_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di getMessagesByForumId.");
        return []; 
    }

    // --- PERBAIKAN SQL (Tambahkan TO_CHAR) ---
    $sql = "SELECT 
                m.message_id, 
                m.forum_id, 
                m.sender_id, 
                m.isi_pesan, 
                TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, -- Ambil jam:menit
                u.nama_lengkap AS sender_nama 
            FROM messages m 
            JOIN users u ON m.sender_id = u.user_id 
            WHERE m.forum_id = :fid 
            ORDER BY m.created_at ASC";
    // ------------------------------------

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
        // Proses CLOB
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
 * Mengambil pesan baru (untuk Long Polling)
 * (Fungsi ini yang menyebabkan timestamp hilang di pesan baru)
 */
function getNewMessagesAfterId($forum_id, $last_message_id) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getNewMessagesAfterId.');
        return [];
    }

    // --- PERBAIKAN SQL (Tambahkan TO_CHAR) ---
    $sql = "SELECT 
                m.message_id, 
                m.forum_id, 
                m.sender_id, 
                m.isi_pesan, 
                TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, -- Ambil jam:menit
                u.nama_lengkap AS sender_nama 
            FROM messages m 
            JOIN users u ON m.sender_id = u.user_id 
            WHERE m.forum_id = :fid AND m.message_id > :last_id 
            ORDER BY m.created_at ASC";
    // ------------------------------------

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
        // Proses CLOB
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

?>