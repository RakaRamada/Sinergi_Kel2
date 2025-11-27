<?php
// File: app/models/NotificationModel.php

function getUserNotifications($user_id) {
    require __DIR__ . '/../../config/koneksi.php';

    // LOGIKA BARU:
    // 1. Unread: is_read = 0
    // 2. Read: is_read = 1 DAN (waktu baca < 7 hari yang lalu)
    
    $sql = "SELECT 
                n.notif_id, n.type, n.message, n.is_read, n.created_at, 
                n.related_post_id, n.related_forum_id, n.read_at,
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
                (n.is_read = 1 AND n.read_at > SYSDATE - 7) -- Hapus otomatis setelah 7 hari DIBACA
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

/**
 * Fungsi ini dipanggil saat User KLIK notifikasi.
 * Mengubah status jadi read DAN mencatat waktu baca (SYSDATE).
 */
function markNotificationAsRead($notif_id) {
    require __DIR__ . '/../../config/koneksi.php';

    // Kita update IS_READ jadi 1 DAN READ_AT jadi SYSDATE
    $sql = "UPDATE notifications 
            SET is_read = 1, read_at = SYSDATE 
            WHERE notif_id = :nid";
            
    $stmt = oci_parse($conn, $sql);
    $nid = (int)$notif_id;
    oci_bind_by_name($stmt, ':nid', $nid);
    
    oci_execute($stmt);
    @oci_close($conn);
}

/**
 * Menghitung jumlah notifikasi yang belum dibaca (Unread).
 * Digunakan untuk badge merah di sidebar/header.
 */
function getUnreadCount($user_id) {
    require __DIR__ . '/../../config/koneksi.php';
    
    // Perhatikan: saya ganti :uid menjadi :p_uid
    $sql = "SELECT COUNT(*) AS total FROM notifications 
            WHERE user_id = :p_uid AND is_read = 0";
            
    $stmt = oci_parse($conn, $sql);
    
    $clean_uid = (int)$user_id;
    
    // Bind dengan nama variabel yang aman
    oci_bind_by_name($stmt, ':p_uid', $clean_uid);
    
    if (!oci_execute($stmt)) {
        // Jika error, kembalikan 0 biar tampilan gak rusak
        return 0;
    }
    
    $row = oci_fetch_assoc($stmt);
    // Oracle biasanya mengembalikan nama kolom dengan HURUF BESAR ('TOTAL')
    $count = $row['TOTAL'] ?? 0;
    
    oci_free_statement($stmt);
    @oci_close($conn);
    
    return (int)$count;
}

/**
 * Mengambil detail satu notifikasi.
 * Penting untuk tombol 'Terima' invite, agar kita tahu invite ke forum mana.
 */
function getNotificationById($notif_id) {
    require __DIR__ . '/../../config/koneksi.php';

    $sql = "SELECT * FROM notifications WHERE notif_id = :nid";
    $stmt = oci_parse($conn, $sql);
    $nid = (int)$notif_id;
    oci_bind_by_name($stmt, ':nid', $nid);
    
    if (!oci_execute($stmt)) {
        return null;
    }
    
    $row = oci_fetch_assoc($stmt);
    @oci_close($conn);
    
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}

/**
 * Fungsi Global untuk MENGIRIM notifikasi baru.
 * Teman setimmu nanti tinggal panggil fungsi ini:
 * createNotification($penerima_id, $pelaku_id, 'like', 'menyukai...', $post_id, null);
 */
function createNotification($user_id, $actor_id, $type, $message, $related_post_id = null, $related_forum_id = null) {
    require __DIR__ . '/../../config/koneksi.php';

    $sql = "INSERT INTO notifications 
            (user_id, related_user_id, type, message, is_read, related_post_id, related_forum_id, created_at)
            VALUES 
            (:uid, :actor, :type, :msg, 0, :pid, :fid, SYSDATE)";
            
    $stmt = oci_parse($conn, $sql);
    
    // Binding
    $b_uid = (int)$user_id;
    $b_actor = (int)$actor_id;
    $b_pid = $related_post_id ? (int)$related_post_id : null;
    $b_fid = $related_forum_id ? (int)$related_forum_id : null;
    
    oci_bind_by_name($stmt, ':uid', $b_uid);
    oci_bind_by_name($stmt, ':actor', $b_actor);
    oci_bind_by_name($stmt, ':type', $type);
    oci_bind_by_name($stmt, ':msg', $message);
    oci_bind_by_name($stmt, ':pid', $b_pid);
    oci_bind_by_name($stmt, ':fid', $b_fid);

    $res = oci_execute($stmt); // Auto commit
    @oci_close($conn);
    return $res;
}
?>