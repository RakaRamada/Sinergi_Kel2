<?php
// 1. Matikan Error Reporting agar JSON bersih
error_reporting(0);
ini_set('display_errors', 0);

// 2. Bersihkan Buffer Output
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/koneksi.php';
ob_clean(); // Hapus spasi/enter liar

header('Content-Type: application/json');

// Cek Login
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Login dulu.']); exit; 
}

$cid = $_POST['comment_id'] ?? 0;
$uid = $_SESSION['user_id'];

if (empty($cid)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Komentar tidak valid.']); exit;
}

// --- DATABASE OPERATION ---

// Langkah 1: Cek Kepemilikan (Apakah komentar ini milik user yang login?)
// Kita ambil POST_ID sekalian untuk update counter nanti
$sql_cek = "SELECT POST_ID FROM comments WHERE COMMENT_ID = :b_id AND USER_ID = :b_user";
$stmt_cek = oci_parse($conn, $sql_cek);
oci_bind_by_name($stmt_cek, ':b_id', $cid);
oci_bind_by_name($stmt_cek, ':b_user', $uid);

if (!oci_execute($stmt_cek)) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengecek data.']); exit;
}

$row = oci_fetch_assoc($stmt_cek);

if(!$row) { 
    // Jika tidak ada data, berarti bukan milik user ini atau sudah terhapus
    echo json_encode(['status' => 'error', 'message' => 'Gagal hapus (Bukan milikmu atau sudah hilang).']); exit; 
}

$pid = $row['POST_ID']; // Simpan ID Postingan

// Langkah 2: Hapus Komentar
$sql_del = "DELETE FROM comments WHERE COMMENT_ID = :b_id";
$stmt_del = oci_parse($conn, $sql_del);
oci_bind_by_name($stmt_del, ':b_id', $cid);

if(oci_execute($stmt_del, OCI_COMMIT_ON_SUCCESS)){
    
    // Langkah 3: Kurangi Counter Komentar di Postingan
    // (Kita gunakan try-catch silent agar jika ini gagal, hapus komentar tetap dianggap sukses)
    $sql_dec = "UPDATE postingan SET comment_count = comment_count - 1 WHERE post_id = :b_post";
    $stmt_dec = oci_parse($conn, $sql_dec);
    oci_bind_by_name($stmt_dec, ':b_post', $pid);
    oci_execute($stmt_dec, OCI_COMMIT_ON_SUCCESS);
    oci_free_statement($stmt_dec);
    
    echo json_encode(['status' => 'success', 'message' => 'Komentar dihapus.']);

} else {
    $e = oci_error($stmt_del);
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e['message']]);
}

oci_free_statement($stmt_cek);
oci_free_statement($stmt_del);
oci_close($conn);
?>