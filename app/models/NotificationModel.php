<?php
// File: app/models/NotificationModel.php

class NotificationModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * AMBIL NOTIFIKASI (LENGKAP DENGAN SEMUA ID BARU)
     */
    public function getUserNotifications($user_id) {
        $sql = "SELECT 
                    n.notif_id, n.type, n.message, n.is_read, 
                    TO_CHAR(n.created_at, 'YYYY-MM-DD HH24:MI:SS') as time_str,
                    
                    -- ID DASHBOARD
                    n.related_post_id,      
                    n.related_comment_id,   
                    
                    -- ID GRUP & FORUM
                    n.related_group_id,     
                    n.related_forum_post_id,    
                    n.related_forum_comment_id, 
                    
                    n.read_at,
                    n.related_user_id AS actor_id,
                    u.username AS actor_name, 
                    u.nama_lengkap AS actor_fullname
                FROM notifications n
                LEFT JOIN users u ON n.related_user_id = u.user_id
                WHERE n.user_id = :p_user_id
                AND (
                    n.is_read = 0 OR (n.is_read = 1 AND n.read_at > SYSDATE - 7)
                )
                ORDER BY n.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        $uid = (int)$user_id;
        oci_bind_by_name($stmt, ':p_user_id', $uid);

        if (!oci_execute($stmt)) return ['unread' => [], 'read' => []];

        $data = ['unread' => [], 'read' => []];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = array_change_key_case($row, CASE_LOWER);
            if ($row['is_read'] == 0) {
                $data['unread'][] = $row;
            } else {
                $data['read'][] = $row;
            }
        }
        oci_free_statement($stmt);
        return $data;
    }

    /**
     * CREATE NOTIFICATION (VERSI FLEXIBLE)
     * @param array $refs Array asosiatif berisi ID referensi (post_id, group_id, dll)
     */
    public function createNotification($user_id, $actor_id, $type, $message, $refs = []) {
        // Siapkan variabel bind dari array $refs
        // Jika key tidak ada di array, otomatis NULL
        $b_pid   = isset($refs['post_id']) ? (int)$refs['post_id'] : null;          // Dashboard Post
        $b_cid   = isset($refs['comment_id']) ? (int)$refs['comment_id'] : null;    // Dashboard Comment
        $b_gid   = isset($refs['group_id']) ? (int)$refs['group_id'] : null;        // Group ID
        $b_fpid  = isset($refs['forum_post_id']) ? (int)$refs['forum_post_id'] : null; // Forum Post
        $b_fcid  = isset($refs['forum_comment_id']) ? (int)$refs['forum_comment_id'] : null; // Forum Comment

        $sql = "INSERT INTO notifications 
                (user_id, related_user_id, type, message, is_read, 
                 related_post_id, related_comment_id, related_group_id, 
                 related_forum_post_id, related_forum_comment_id, created_at)
                VALUES 
                (:p_target, :p_actor, :p_type, :p_msg, 0, 
                 :p_pid, :p_cid, :p_gid, 
                 :p_fpid, :p_fcid, SYSTIMESTAMP)";
            
        $stmt = oci_parse($this->conn, $sql);
        
        $b_uid   = (int)$user_id;
        $b_actor = (int)$actor_id;
        
        oci_bind_by_name($stmt, ':p_target', $b_uid);
        oci_bind_by_name($stmt, ':p_actor', $b_actor);
        oci_bind_by_name($stmt, ':p_type', $type);
        oci_bind_by_name($stmt, ':p_msg', $message);
        
        // Bind ID Spesifik
        oci_bind_by_name($stmt, ':p_pid', $b_pid);
        oci_bind_by_name($stmt, ':p_cid', $b_cid);
        oci_bind_by_name($stmt, ':p_gid', $b_gid);
        oci_bind_by_name($stmt, ':p_fpid', $b_fpid);
        oci_bind_by_name($stmt, ':p_fcid', $b_fcid);

        // Eksekusi & Auto Commit
        $res = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        
        if (!$res) {
            $e = oci_error($stmt);
            error_log("Gagal Buat Notifikasi: " . $e['message']);
        }

        oci_free_statement($stmt);
        return $res;
    }

    // --- FUNGSI PENDUKUNG (Tetap Sama) ---
    public function markNotificationAsRead($notif_id) {
        $sql = "UPDATE notifications SET is_read = 1, read_at = SYSDATE WHERE notif_id = :p_nid";
        $stmt = oci_parse($this->conn, $sql);
        $nid = (int)$notif_id;
        oci_bind_by_name($stmt, ':p_nid', $nid);
        oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);
    }
    public function getUnreadCount($user_id) {
        $sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = :p_uid AND is_read = 0";
        $stmt = oci_parse($this->conn, $sql);
        $clean_uid = (int)$user_id;
        oci_bind_by_name($stmt, ':p_uid', $clean_uid);
        if (!oci_execute($stmt)) return 0;
        $row = oci_fetch_assoc($stmt);
        $count = $row['TOTAL'] ?? 0;
        oci_free_statement($stmt);
        return (int)$count;
    }
    public function getNotificationById($notif_id) {
        $sql = "SELECT * FROM notifications WHERE notif_id = :p_nid";
        $stmt = oci_parse($this->conn, $sql);
        $nid = (int)$notif_id;
        oci_bind_by_name($stmt, ':p_nid', $nid);
        if (!oci_execute($stmt)) return null;
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function deleteNotification($notif_id, $user_id) {
        // Ganti bind variable jadi :p_del_nid dan :p_del_uid biar beda
        $sql = "DELETE FROM notifications WHERE notif_id = :p_del_nid AND user_id = :p_del_uid";
        $stmt = oci_parse($this->conn, $sql);
        
        $nid = (int)$notif_id;
        $uid = (int)$user_id;
        
        oci_bind_by_name($stmt, ':p_del_nid', $nid);
        oci_bind_by_name($stmt, ':p_del_uid', $uid);
        
        // Eksekusi Tanpa Auto Commit dulu
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            return false;
        }
        
        // Commit Manual
        oci_commit($this->conn);
        return true;
    }

    // --- 6. HAPUS SEMUA NOTIFIKASI USER (VERSI AMAN) ---
    public function deleteAllNotifications($user_id) {
        // PERBAIKAN: Tambahkan "AND is_read = 1"
        $sql = "DELETE FROM notifications WHERE user_id = :p_del_all_uid AND is_read = 1";
        
        $stmt = oci_parse($this->conn, $sql);
        
        $uid = (int)$user_id;
        oci_bind_by_name($stmt, ':p_del_all_uid', $uid);
        
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            return false;
        }

        // Commit Manual
        oci_commit($this->conn);
        return true;
    }
}


?>