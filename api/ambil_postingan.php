<?php
require_once __DIR__. '/../config/koneksi.php';
session_start();

header('Content-Type: application/json');

$current_user_id = $_SESSION['id_users']?? 0;

$sql = "
    SELECT 
        p.id_postingan,
        p.id_user,
        p.konten,
        p.post_image,
        p.created_at,
        u.username,
        u.nama_lengkap,
        u.avatar_url,
        
        -- Hitung total likes
        (SELECT COUNT(*) 
         FROM likes l 
         WHERE l.id_postingan = p.id_postingan) AS total_likes,
        
        -- Hitung total comments
        (SELECT COUNT(*) 
         FROM comments c 
         WHERE c.id_postingan = p.id_postingan) AS total_comments,
        
        -- Cek apakah user yang sedang login sudah like
        (SELECT COUNT(*) 
         FROM likes l 
         WHERE l.id_postingan = p.id_postingan AND l.id_user = :current_user_id_bv) AS user_sudah_like
         
    FROM 
        postingan p
    JOIN 
        users u ON p.id_user = u.id_users -- Cocok: postingan.id_user -> users.id_users
    ORDER BY 
        p.created_at DESC
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':current_user_id_bv', $current_user_id);

if (!oci_execute($stmt)) {
    echo json_encode(['status' => 'error', 'message' => oci_error($stmt)['message']]);
    exit;
}

$feed_data = [];
while ($row = oci_fetch_assoc($stmt)) {
    // 3. Proses data mentah sebelum dikirim
    
    // Fallback untuk avatar:
    // Jika avatar_url di DB null ATAU sama dengan path default, gunakan gambar default
    $default_avatar_dir = '/Sinergi/public/assets/images/';
    $default_avatar_file = '/Sinergi/public/assets/images/';
    
    if (empty($row['AVATAR_URL']) || $row['AVATAR_URL'] == $default_avatar_dir) {
        $row['AVATAR_URL_FIXED'] = $default_avatar_file;
    } else {
        // Asumsi DB menyimpan path lengkap, misal: /Sinergi/public/assets/images/user_123.jpg
        $row['AVATAR_URL_FIXED'] = $row['AVATAR_URL'];
    }

    // 4. Format Waktu
    $timestamp = strtotime($row['CREATED_AT']);
    $diff = time() - $timestamp;
    if ($diff < 60) {
        $row['WAKTU_POSTING'] = 'Baru saja';
    } else if ($diff < 3600) {
        $row['WAKTU_POSTING'] = floor($diff / 60) . ' menit yang lalu';
    } else if ($diff < 86400) {
        $row['WAKTU_POSTING'] = floor($diff / 3600) . ' jam yang lalu';
    } else {
        $row['WAKTU_POSTING'] = date('d M Y', $timestamp);
    }
    
    $feed_data[] = $row;
}

// 5. Kirim semua data sebagai JSON
echo json_encode($feed_data);

oci_free_statement($stmt);
oci_close($conn);
?>