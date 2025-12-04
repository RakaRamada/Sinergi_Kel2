<?php
// File: app/models/MessageModel.php
// UPDATE: Menambahkan pengambilan tipe dan filename untuk fitur Reply

class MessageModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    public function createMessage($group_id, $sender_id, $isi_pesan, $message_type, $file_path, $original_filename, $reply_to_id = 0) {
        $sql = "INSERT INTO messages (
                    group_id, sender_id, isi_pesan, message_type, 
                    file_path, original_filename, reply_to_message_id, created_at
                ) VALUES (
                    :p_group_id, :p_sender_id, EMPTY_CLOB(), :p_msg_type, 
                    :p_file_path, :p_orig_name, :p_reply_id, SYSTIMESTAMP
                ) RETURNING message_id, isi_pesan INTO :p_new_id, :p_clob_loc";

        $stmt = oci_parse($this->conn, $sql);
        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);
        
        $safe_gid = (int)$group_id;
        $safe_uid = (int)$sender_id;
        $safe_rid = ($reply_to_id > 0) ? (int)$reply_to_id : null;

        oci_bind_by_name($stmt, ':p_group_id', $safe_gid);
        oci_bind_by_name($stmt, ':p_sender_id', $safe_uid);
        oci_bind_by_name($stmt, ':p_msg_type', $message_type);
        oci_bind_by_name($stmt, ':p_file_path', $file_path);
        oci_bind_by_name($stmt, ':p_orig_name', $original_filename);
        oci_bind_by_name($stmt, ':p_reply_id', $safe_rid);
        
        $new_id = 0;
        oci_bind_by_name($stmt, ':p_new_id', $new_id, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_clob_loc', $clob, -1, OCI_B_CLOB);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt);
            throw new Exception('Database Error: ' . $e['message']);
        }

        if (!empty($isi_pesan)) {
            $clob->save($isi_pesan);
        } else {
            $clob->save(' '); 
        }

        oci_commit($this->conn);
        oci_free_statement($stmt);
        
        return $new_id;
    }

    public function getMessagesByGroupId($group_id) {
        // QUERY UPDATE: Ambil r.message_type dan r.original_filename
        $sql = "SELECT 
                    m.message_id, m.group_id, m.sender_id, m.isi_pesan, 
                    m.message_type, m.file_path, m.original_filename,
                    m.reply_to_message_id, 
                    TO_CHAR(m.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at_iso, 
                    TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, 
                    u.nama_lengkap AS sender_nama, u.avatar_url,
                    
                    r.isi_pesan AS replied_message_text,
                    r.message_type AS replied_message_type, 
                    r.original_filename AS replied_filename,
                    ru.nama_lengkap AS replied_sender_nama

                FROM messages m 
                JOIN users u ON m.sender_id = u.user_id
                LEFT JOIN messages r ON m.reply_to_message_id = r.message_id
                LEFT JOIN users ru ON r.sender_id = ru.user_id
                WHERE m.group_id = :p_group_id 
                ORDER BY m.created_at ASC";

        $stmt = oci_parse($this->conn, $sql);
        $clean_group_id = (int)$group_id;
        oci_bind_by_name($stmt, ':p_group_id', $clean_group_id);

        if (!oci_execute($stmt)) return [];

        $messages = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $this->processClob($row, 'ISI_PESAN');
            $this->processClob($row, 'REPLIED_MESSAGE_TEXT');
            $messages[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $messages;
    }

    public function getMessageById($message_id) {
        // QUERY UPDATE
        $sql = "SELECT 
                    m.message_id, m.group_id, m.sender_id, m.isi_pesan, 
                    m.message_type, m.file_path, m.original_filename,
                    m.reply_to_message_id,
                    TO_CHAR(m.created_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') AS created_at_iso, 
                    TO_CHAR(m.created_at, 'HH24:MI') AS created_at_time, 
                    u.nama_lengkap AS sender_nama, u.avatar_url,
                    
                    r.isi_pesan AS replied_message_text,
                    r.message_type AS replied_message_type,
                    r.original_filename AS replied_filename,
                    ru.nama_lengkap AS replied_sender_nama

                FROM messages m 
                JOIN users u ON m.sender_id = u.user_id
                LEFT JOIN messages r ON m.reply_to_message_id = r.message_id
                LEFT JOIN users ru ON r.sender_id = ru.user_id
                WHERE m.message_id = :p_msg_id";

        $stmt = oci_parse($this->conn, $sql);
        $clean_id = (int)$message_id;
        oci_bind_by_name($stmt, ':p_msg_id', $clean_id);

        if (!oci_execute($stmt)) return null;

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row) {
            $this->processClob($row, 'ISI_PESAN');
            $this->processClob($row, 'REPLIED_MESSAGE_TEXT');
            return array_change_key_case($row, CASE_LOWER);
        }
        return null;
    }

    public function getNewMessagesAfterId($group_id, $last_message_id) {
        // QUERY UPDATE
        $sql = "SELECT 
                    m.message_id, m.group_id, m.sender_id, m.isi_pesan, 
                    m.message_type, m.file_path, m.original_filename,
                    m.reply_to_message_id,
                    TO_CHAR(m.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_iso,
                    TO_CHAR(m.created_at, 'HH24:MI') as created_at_time,
                    u.nama_lengkap as sender_nama, u.avatar_url,
                    
                    r.isi_pesan as replied_message_text,
                    r.message_type AS replied_message_type,
                    r.original_filename AS replied_filename,
                    ru.nama_lengkap as replied_sender_nama

                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                LEFT JOIN messages r ON m.reply_to_message_id = r.message_id
                LEFT JOIN users ru ON r.sender_id = ru.user_id
                WHERE m.group_id = :p_group_id 
                AND m.message_id > :p_last_id
                ORDER BY m.created_at ASC";

        $stmt = oci_parse($this->conn, $sql);
        $safe_gid = (int)$group_id;
        $safe_lid = (int)$last_message_id;

        oci_bind_by_name($stmt, ':p_group_id', $safe_gid);
        oci_bind_by_name($stmt, ':p_last_id', $safe_lid);

        if (!oci_execute($stmt)) return [];

        $messages = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $this->processClob($row, 'ISI_PESAN');
            $this->processClob($row, 'REPLIED_MESSAGE_TEXT');
            $messages[] = array_change_key_case($row, CASE_LOWER);
        }
        return $messages;
    }

    public function createSystemMessage($group_id, $user_id, $type, $message_text) {
        // Query Insert dengan RETURNING INTO untuk CLOB
        $sql = "INSERT INTO messages (group_id, sender_id, isi_pesan, created_at, message_type) 
                VALUES (:p_group_id, :p_sender_id, EMPTY_CLOB(), SYSTIMESTAMP, :p_type)
                RETURNING isi_pesan INTO :p_clob_loc";

        $stmt = oci_parse($this->conn, $sql);
        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);

        $clean_group_id = (int)$group_id;
        $clean_sender_id = (int)$user_id;

        oci_bind_by_name($stmt, ':p_group_id', $clean_group_id);
        oci_bind_by_name($stmt, ':p_sender_id', $clean_sender_id);
        oci_bind_by_name($stmt, ':p_type', $type);
        // Bind Descriptor CLOB
        oci_bind_by_name($stmt, ':p_clob_loc', $clob, -1, OCI_B_CLOB);

        // Eksekusi tanpa auto commit
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt);
            error_log("Gagal Create System Message: " . $e['message']);
            return false;
        }

        // Simpan Teks ke CLOB
        if (!empty($message_text)) {
            $clob->save($message_text);
        } else {
            $clob->save(' '); // Spasi biar gak null
        }

        // Commit Transaksi
        oci_commit($this->conn);
        $clob->free();
        oci_free_statement($stmt);
        return true;
    }

    public function deleteMessage($message_id, $user_id) {
        // 1. Putuskan hubungan Reply dulu (Set NULL pada pesan yang membalas pesan ini)
        // Ini mencegah error ORA-02292 (Integrity constraint violated)
        $sqlUnlink = "UPDATE messages SET reply_to_message_id = NULL WHERE reply_to_message_id = :p_target_id";
        $stmtUnlink = oci_parse($this->conn, $sqlUnlink);
        $clean_mid = (int)$message_id;
        oci_bind_by_name($stmtUnlink, ':p_target_id', $clean_mid);
        oci_execute($stmtUnlink, OCI_NO_AUTO_COMMIT); // Jangan commit dulu
        oci_free_statement($stmtUnlink);

        // 2. Baru Hapus Pesan Utamanya
        $sql = "DELETE FROM messages WHERE message_id = :p_msg_id AND sender_id = :p_sender_id";
        $stmt = oci_parse($this->conn, $sql);

        $clean_sid = (int)$user_id;
        
        oci_bind_by_name($stmt, ':p_msg_id', $clean_mid);
        oci_bind_by_name($stmt, ':p_sender_id', $clean_sid);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            // Jika gagal, rollback perubahan unlink tadi
            oci_rollback($this->conn);
            return false;
        }
        
        $rows = oci_num_rows($stmt);
        
        // 3. Commit Transaksi jika ada baris terhapus
        if ($rows > 0) {
            oci_commit($this->conn);
            oci_free_statement($stmt);
            return true;
        } else {
            // Gagal hapus (mungkin bukan pemilik pesan)
            oci_rollback($this->conn);
            oci_free_statement($stmt);
            return false;
        }
    }

    public function getMediaByGroupId($group_id) {
        $sql = "SELECT message_id, file_path, original_filename FROM messages
                WHERE group_id = :p_group_id AND message_type = 'image' ORDER BY created_at DESC";
        $stmt = oci_parse($this->conn, $sql);
        $gid = (int)$group_id;
        oci_bind_by_name($stmt, ':p_group_id', $gid);
        oci_execute($stmt);
        $res = [];
        while ($row = oci_fetch_assoc($stmt)) { $res[] = array_change_key_case($row, CASE_LOWER); }
        oci_free_statement($stmt);
        return $res;
    }

    public function getDocumentsByGroupId($group_id) {
        $sql = "SELECT m.message_id, m.file_path, m.original_filename, TO_CHAR(m.created_at, 'DD Mon YYYY') AS created_at_formatted, u.nama_lengkap AS sender_nama
                FROM messages m JOIN users u ON m.sender_id = u.user_id
                WHERE m.group_id = :p_group_id AND m.message_type = 'document' ORDER BY m.created_at DESC";
        $stmt = oci_parse($this->conn, $sql);
        $gid = (int)$group_id;
        oci_bind_by_name($stmt, ':p_group_id', $gid);
        oci_execute($stmt);
        $res = [];
        while ($row = oci_fetch_assoc($stmt)) { $res[] = array_change_key_case($row, CASE_LOWER); }
        oci_free_statement($stmt);
        return $res;
    }

    public function updateLastReadMessage($user_id, $group_id) {
        $sql_max = "SELECT MAX(message_id) AS max_id FROM messages WHERE group_id = :p_fid_max";
        $stmt_max = oci_parse($this->conn, $sql_max);
        $gid = (int)$group_id;
        oci_bind_by_name($stmt_max, ':p_fid_max', $gid);
        oci_execute($stmt_max);
        $row = oci_fetch_assoc($stmt_max);
        $max_id = $row ? (int)$row['MAX_ID'] : 0;
        oci_free_statement($stmt_max);

        if ($max_id == 0) return true;

        $sql_upd = "UPDATE group_members SET last_read_message_id = :p_new_mid 
                    WHERE user_id = :p_uid AND group_id = :p_gid";
        $stmt_upd = oci_parse($this->conn, $sql_upd);
        $uid = (int)$user_id;
        
        oci_bind_by_name($stmt_upd, ':p_new_mid', $max_id);
        oci_bind_by_name($stmt_upd, ':p_uid', $uid);
        oci_bind_by_name($stmt_upd, ':p_gid', $gid);
        
        $res = oci_execute($stmt_upd, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt_upd);
        return $res;
    }

    private function processClob(&$row, $field) {
        if (isset($row[$field]) && $row[$field] instanceof OCILob) {
            $row[$field] = $row[$field]->read($row[$field]->size());
        }
    }
}
?>