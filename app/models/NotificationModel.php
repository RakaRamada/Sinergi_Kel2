<?php
// File: app/models/NotificationModel.php

function getUserNotifications($user_id) {
    require __DIR__ . '/../../config/koneksi.php';

    // Ambil notifikasi (Unread & Read < 7 hari)
    // FIX: Gunakan :p_user_id (Aman)
    $sql = "SELECT 
                n.notif_id, n.type, n.message, n.is_read, n.created_at, 
                n.related_post_id, n.related_group_id, n.read_at,
                n.related_user_id AS actor_id,
                u.username AS actor_name, 
                u.nama_lengkap AS actor_fullname,
                ROUND((SYSDATE - CAST(n.created_at AS DATE)) * 24 * 60) AS minutes_ago
            FROM notifications n
            LEFT JOIN users u ON n.related_user_id = u.user_id
            WHERE n.user_id = :p_user_id
            AND (
                n.is_read = 0 
                OR 
                (n.is_read = 1 AND n.read_at > SYSDATE - 7)
            )
            ORDER BY n.created_at DESC";

    $stmt = oci_parse($conn, $sql);
    $uid = (int)$user_id;
    oci_bind_by_name($stmt, ':p_user_id', $uid);

    if (!oci_execute($stmt)) {
        return ['unread' => [], 'read' => []];
    }

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
    @oci_close($conn);
    return $data;
}

function markNotificationAsRead($notif_id) {
    require __DIR__ . '/../../config/koneksi.php';

    $sql = "UPDATE notifications SET is_read = 1, read_at = SYSDATE WHERE notif_id = :p_nid";
    $stmt = oci_parse($conn, $sql);
    $nid = (int)$notif_id;
    oci_bind_by_name($stmt, ':p_nid', $nid); // Aman
    
    // Auto commit
    oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    @oci_close($conn);
}

function getUnreadCount($user_id) {
    require __DIR__ . '/../../config/koneksi.php';
    
    $sql = "SELECT COUNT(*) AS total FROM notifications WHERE user_id = :p_uid AND is_read = 0";
    $stmt = oci_parse($conn, $sql);
    $clean_uid = (int)$user_id;
    oci_bind_by_name($stmt, ':p_uid', $clean_uid); // Aman
    
    if (!oci_execute($stmt)) return 0;
    
    $row = oci_fetch_assoc($stmt);
    $count = $row['TOTAL'] ?? 0;
    
    oci_free_statement($stmt);
    @oci_close($conn);
    return (int)$count;
}

function getNotificationById($notif_id) {
    require __DIR__ . '/../../config/koneksi.php';

    $sql = "SELECT * FROM notifications WHERE notif_id = :p_nid";
    $stmt = oci_parse($conn, $sql);
    $nid = (int)$notif_id;
    oci_bind_by_name($stmt, ':p_nid', $nid); // Aman
    
    if (!oci_execute($stmt)) return null;
    
    $row = oci_fetch_assoc($stmt);
    @oci_close($conn);
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}

/**
 * FUNGSI UTAMA: MEMBUAT NOTIFIKASI
 * UPDATE: Mengganti nama bind variable agar tidak konflik di Oracle
 */
function createNotification($user_id, $actor_id, $type, $message, $related_post_id = null, $related_group_id = null) {
    require __DIR__ . '/../../config/koneksi.php';

    // FIX: Hapus 'notif_id' dan 'notifications_seq.NEXTVAL'
    // Biarkan Oracle yang mengenerate ID-nya otomatis
    $sql = "INSERT INTO notifications 
            (user_id, related_user_id, type, message, is_read, related_post_id, related_group_id, created_at)
            VALUES 
            (:p_target_id, :p_actor_id, :p_type, :p_msg, 0, :p_post_id, :p_group_id, SYSTIMESTAMP)";
            
    $stmt = oci_parse($conn, $sql);
    
    // Binding yang Aman
    $b_uid = (int)$user_id;
    $b_actor = (int)$actor_id;
    $b_pid = $related_post_id ? (int)$related_post_id : null;
    $b_fid = $related_group_id ? (int)$related_group_id : null;
    
    oci_bind_by_name($stmt, ':p_target_id', $b_uid);
    oci_bind_by_name($stmt, ':p_actor_id', $b_actor);
    oci_bind_by_name($stmt, ':p_type', $type);
    oci_bind_by_name($stmt, ':p_msg', $message);
    oci_bind_by_name($stmt, ':p_post_id', $b_pid);
    oci_bind_by_name($stmt, ':p_group_id', $b_fid);

    // Eksekusi dengan Auto Commit
    $res = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    
    if (!$res) {
        $e = oci_error($stmt);
        error_log("Gagal Buat Notifikasi: " . $e['message']);
    }

    @oci_close($conn);
    return $res;
}
?>