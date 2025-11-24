<?php
// File: api/ambil_postingan.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__. '/../config/koneksi.php';

header('Content-Type: application/json');
$current_user_id = $_SESSION['user_id'] ?? 0;

// === QUERY DIPERBAIKI: SINKRONISASI NAMA KOLOM ===
$sql = "
    SELECT 
        p.post_id AS POST_ID,    
        p.user_id AS USER_ID,
        p.konten AS KONTEN,
        p.post_image AS POST_IMAGE,
        
        p.like_count AS TOTAL_LIKES,       
        p.comment_count AS TOTAL_COMMENTS, 
        
        TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
        
        u.username AS USERNAME,
        u.nama_lengkap AS NAMA_LENGKAP,
        u.avatar_url AS AVATAR_URL,
        
        (SELECT COUNT(*) 
         FROM likes l 
         WHERE l.post_id = p.post_id AND l.user_id = :current_user_id_bv) AS USER_SUDAH_LIKE
    FROM 
        postingan p
    JOIN 
        users u ON p.user_id = u.user_id
    ORDER BY 
        p.post_id DESC
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':current_user_id_bv', $current_user_id);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    echo json_encode(['status' => 'error', 'message' => $e['message']]);
    exit;
}

$feed_data = [];
$default_avatar_file = '/Sinergi/public/assets/images/user.png';
$default_avatar_dir = '/Sinergi/public/assets/images/';

while ($row = oci_fetch_assoc($stmt)) {
    // Perbaiki Avatar
    if (empty($row['AVATAR_URL']) || $row['AVATAR_URL'] == $default_avatar_dir) {
        $row['AVATAR_URL_FIXED'] = $default_avatar_file;
    } else {
        $row['AVATAR_URL_FIXED'] = $row['AVATAR_URL'];
    }

    // Perbaiki Waktu
    $timestamp = strtotime($row['CREATED_AT_STR']); 
    if ($timestamp === false) {
        $row['WAKTU_POSTING'] = '-';
    } else {
        $diff = time() - $timestamp;
        if ($diff < 60) { $row['WAKTU_POSTING'] = 'Baru saja'; }
        else if ($diff < 3600) { $row['WAKTU_POSTING'] = floor($diff / 60) . 'm'; }
        else if ($diff < 86400) { $row['WAKTU_POSTING'] = floor($diff / 3600) . 'j'; }
        else { $row['WAKTU_POSTING'] = date('d M', $timestamp); }
    }
    
    $feed_data[] = $row;
}

echo json_encode($feed_data);

oci_free_statement($stmt);
oci_close($conn);
?>