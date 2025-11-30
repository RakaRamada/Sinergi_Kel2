<?php
// File: api/ambil_postingan.php (FINAL FIX CLOB)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. KONEKSI KE DATABASE
$path_koneksi = __DIR__ . '/../config/koneksi.php';
if (!file_exists($path_koneksi)) {
    $path_koneksi = __DIR__ . '/../../config/koneksi.php';
}

if (!file_exists($path_koneksi)) {
    echo json_encode(['status' => 'error', 'message' => 'File koneksi.php tidak ditemukan']);
    exit;
}

require_once $path_koneksi;

if (!isset($conn)) {
    echo json_encode(['status' => 'error', 'message' => 'Variabel $conn tidak ada']);
    exit;
}

$current_user_id = $_SESSION['user_id'] ?? 0;

// 2. QUERY SQL
// Menggunakan bind variable :b_user_id untuk menghindari error reserved word
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
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.post_id AND l.user_id = :b_user_id) AS USER_SUDAH_LIKE
    FROM postingan p
    JOIN users u ON p.user_id = u.user_id
    ORDER BY p.post_id DESC
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':b_user_id', $current_user_id);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e['message']]);
    exit;
}

$feed_data = [];
$default_avatar = '/Sinergi/public/assets/images/user.png';

while ($row = oci_fetch_assoc($stmt)) {
    
    // === [FIX PENTING] KONVERSI OCILob KE STRING ===
    // Jika KONTEN berupa Object (CLOB), kita ekstrak isinya.
    if (isset($row['KONTEN']) && is_object($row['KONTEN'])) {
        $row['KONTEN'] = $row['KONTEN']->load();
    }
    // Lakukan hal yang sama untuk kolom lain jika ada yang tipe CLOB
    // ===============================================

    // Fix Avatar
    $row['AVATAR_URL_FIXED'] = !empty($row['AVATAR_URL']) ? $row['AVATAR_URL'] : $default_avatar;
    
    // Fix Image Path
    if (!empty($row['POST_IMAGE']) && strpos($row['POST_IMAGE'], '/') === false) {
        $row['POST_IMAGE'] = '/Sinergi/public/uploads/posts/' . $row['POST_IMAGE'];
    }

    // Fix Waktu
    $timestamp = strtotime($row['CREATED_AT_STR']); 
    if ($timestamp) {
        $diff = time() - $timestamp;
        if ($diff < 60) { $row['WAKTU_POSTING'] = 'Baru saja'; }
        else if ($diff < 3600) { $row['WAKTU_POSTING'] = floor($diff / 60) . 'm'; }
        else if ($diff < 86400) { $row['WAKTU_POSTING'] = floor($diff / 3600) . 'j'; }
        else { $row['WAKTU_POSTING'] = date('d M Y', $timestamp); }
    } else {
        $row['WAKTU_POSTING'] = '-';
    }

    $feed_data[] = $row;
}

echo json_encode($feed_data);

oci_free_statement($stmt);
oci_close($conn);
?>