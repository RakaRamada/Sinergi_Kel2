<?php
// PERBAIKAN: Pastikan session_start() ada di baris PALING ATAS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

require_once __DIR__ . '/../config/koneksi.php'; 
// HAPUS session_start(); dari sini

header('Content-Type: application/json');
$response = [];

// 1. Validasi Metode & Login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['status' => 'error', 'message' => 'Metode request tidak valid.'];
    ob_end_clean(); echo json_encode($response); exit;
}
if (!isset($_SESSION['user_id'])) {
    $response = ['status' => 'error', 'message' => 'Anda harus login untuk like.'];
    ob_end_clean(); echo json_encode($response); exit;
}

// 2. Ambil data
$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? 0;

if (empty($post_id)) {
    $response = ['status' => 'error', 'message' => 'ID postingan tidak valid.'];
    ob_end_clean(); echo json_encode($response); exit;
}

// 3. Cek apakah user sudah like postingan ini
$sql_check = "SELECT COUNT(*) AS TOTAL 
              FROM likes 
              WHERE user_id = :user_id_bv AND post_id = :post_id_bv";
$stmt_check = oci_parse($conn, $sql_check);
oci_bind_by_name($stmt_check, ':user_id_bv', $user_id);
oci_bind_by_name($stmt_check, ':post_id_bv', $post_id);
oci_execute($stmt_check);
$row = oci_fetch_assoc($stmt_check);
$sudah_like = ($row['TOTAL'] > 0);
oci_free_statement($stmt_check);

$action = '';
$sql_update_count = '';

if ($sudah_like) {
    // --- PROSES UNLIKE ---
    $sql_action = "DELETE FROM likes 
                   WHERE user_id = :user_id_bv AND post_id = :post_id_bv";
    $sql_update_count = "UPDATE postingan 
                         SET like_count = GREATEST(0, like_count - 1) 
                         WHERE post_id = :post_id_bv";
    $action = 'unliked';

} else {
    // --- PROSES LIKE ---
    $sql_action = "INSERT INTO likes (user_id, post_id) 
                   VALUES (:user_id_bv, :post_id_bv)";
    $sql_update_count = "UPDATE postingan 
                         SET like_count = like_count + 1 
                         WHERE post_id = :post_id_bv";
    $action = 'liked';
}

// 4. Eksekusi Aksi (Like/Unlike)
$stmt_action = oci_parse($conn, $sql_action);
oci_bind_by_name($stmt_action, ':user_id_bv', $user_id);
oci_bind_by_name($stmt_action, ':post_id_bv', $post_id);
$result_action = oci_execute($stmt_action);

if ($result_action) {
    // 5. Eksekusi Update Like Count
    $stmt_update = oci_parse($conn, $sql_update_count);
    oci_bind_by_name($stmt_update, ':post_id_bv', $post_id);
    oci_execute($stmt_update); 
    oci_free_statement($stmt_update);
    
    // 6. Ambil like count terbaru
    $sql_new_count = "SELECT like_count FROM postingan WHERE post_id = :post_id_bv";
    $stmt_new_count = oci_parse($conn, $sql_new_count);
    oci_bind_by_name($stmt_new_count, ':post_id_bv', $post_id);
    oci_execute($stmt_new_count);
    $count_row = oci_fetch_assoc($stmt_new_count);
    $new_like_count = $count_row['LIKE_COUNT'] ?? 0;
    oci_free_statement($stmt_new_count);

    $response = [
        'status' => 'success', 
        'action' => $action, 
        'new_like_count' => $new_like_count
    ];

} else {
    $e = oci_error($stmt_action);
    $response = ['status' => 'error', 'message' => 'Gagal memproses like: ' . $e['message']];
}

ob_end_clean();
echo json_encode($response);

oci_free_statement($stmt_action);
oci_close($conn);
?>