<?php
require_once __DIR__. '/../config/koneksi.php';
session_start();

header('Content-Type: application/json');
$current_user_id = $_SESSION['id_users']?? 0;

// --- PERBAIKAN DI SINI ---
// Kita gunakan TO_CHAR() untuk mengubah 'p.created_at'
// menjadi format string universal yang dimengerti strtotime()
$sql = "
    SELECT 
        p.id_postingan,
        p.id_user,
        p.konten,
        p.post_image,
        
        -- INI PERBAIKANNYA: 
        TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT,
        
        u.username,
        u.nama_lengkap,
        u.avatar_url,
        
        (SELECT COUNT(*) 
         FROM likes l 
         WHERE l.id_postingan = p.id_postingan) AS total_likes,
        
        (SELECT COUNT(*) 
         FROM comments c 
         WHERE c.id_postingan = p.id_postingan) AS total_comments,
        
        (SELECT COUNT(*) 
         FROM likes l 
         WHERE l.id_postingan = p.id_postingan AND l.id_user = :current_user_id_bv) AS user_sudah_like
        
    FROM 
        postingan p
    JOIN 
        users u ON p.id_user = u.id_users
    ORDER BY 
        p.id_postingan DESC -- Kita urutkan berdasarkan ID agar lebih konsisten
";
// Catatan: Saya mengubah ORDER BY ke 'p.id_postingan DESC' karena 'created_at'
// yang sudah di-TO_CHAR tidak bisa diurutkan dengan benar sebagai tanggal.
// Mengurutkan berdasarkan ID postingan (yang auto-increment) akan
// memberikan hasil yang sama (postingan terbaru di atas).

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
    $default_avatar_dir = '/Sinergi/public/assets/images/';
    $default_avatar_file = '/Sinergi/public/assets/images/default_avatar.png'; // Pastikan ada file default
    
    if (empty($row['AVATAR_URL']) || $row['AVATAR_URL'] == $default_avatar_dir) {
        $row['AVATAR_URL_FIXED'] = $default_avatar_file;
    } else {
        $row['AVATAR_URL_FIXED'] = $row['AVATAR_URL'];
    }

    // 4. Format Waktu
    // SEKARANG $row['CREATED_AT'] berisi string "YYYY-MM-DD HH:MM:SS"
    // yang pasti berhasil diproses strtotime()
    $timestamp = strtotime($row['CREATED_AT']); 
    
    if ($timestamp === false) {
        // Jika masih gagal (seharusnya tidak), beri nilai aman
        $row['WAKTU_POSTING'] = 'Beberapa saat lalu';
    } else {
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
    }
    
    $feed_data[] = $row;
}

// 5. Kirim semua data sebagai JSON
echo json_encode($feed_data);

oci_free_statement($stmt);
oci_close($conn);
?>