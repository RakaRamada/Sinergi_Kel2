<?php
// 1. Matikan Error Reporting agar JSON bersih saat di-return ke Javascript
error_reporting(0);
ini_set('display_errors', 0);

// 2. Start Session & Koneksi
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/koneksi.php';
ob_clean(); // Bersihkan output buffer agar tidak ada spasi/enter liar

header('Content-Type: application/json');

// 3. Cek Login
if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']); 
    exit; 
}

// 4. Ambil POST_ID (Perhatikan: disini kita ambil post_id, BUKAN comment_id)
$pid = $_POST['post_id'] ?? 0;
$uid = $_SESSION['user_id'];

if (empty($pid)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Postingan tidak valid.']); 
    exit;
}

// --- DATABASE OPERATION ---

// Langkah 1: Cek apakah postingan ada DAN apakah milik user yang login
$sql_cek = "SELECT USER_ID, POST_IMAGE FROM postingan WHERE POST_ID = :p_id";
$stmt_cek = oci_parse($conn, $sql_cek);
oci_bind_by_name($stmt_cek, ':p_id', $pid);

if (!oci_execute($stmt_cek)) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengecek data postingan.']); 
    exit;
}

$row = oci_fetch_assoc($stmt_cek);

if (!$row) { 
    echo json_encode(['status' => 'error', 'message' => 'Postingan tidak ditemukan.']); 
    exit; 
}

// Validasi Kepemilikan
if ($row['USER_ID'] != $uid) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak berhak menghapus postingan orang lain.']); 
    exit;
}

// Langkah 2: Hapus Postingan dari Database
$sql_del = "DELETE FROM postingan WHERE POST_ID = :p_id";
$stmt_del = oci_parse($conn, $sql_del);
oci_bind_by_name($stmt_del, ':p_id', $pid);

if (oci_execute($stmt_del, OCI_COMMIT_ON_SUCCESS)) {
    
    // Langkah 3: Hapus File Gambar Fisik (Jika ada gambarnya)
    if (!empty($row['POST_IMAGE'])) {
        // Logika untuk menghapus file fisik di folder
        // Asumsi path di DB: /Sinergi/public/uploads/gambar.jpg
        // Kita ubah jadi path sistem: C:/xampp/htdocs/Sinergi/public/uploads/gambar.jpg
        
        $relativePath = str_replace('/Sinergi/public', '', $row['POST_IMAGE']);
        $fullPath = __DIR__ . '/../public' . $relativePath;
        
        // Cek file ada atau tidak, lalu hapus
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Postingan berhasil dihapus.']);

} else {
    $e = oci_error($stmt_del);
    
    // Jika error ORA-02292 (Integrity constraint violation)
    // Artinya ada komentar/like yang terhubung dan Foreign Key di database belum ON DELETE CASCADE
    if ($e['code'] == 2292) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal: Postingan ini memiliki komentar. Hapus komentar dulu atau atur database (Cascade).']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e['message']]);
    }
}

oci_free_statement($stmt_cek);
oci_free_statement($stmt_del);
oci_close($conn);
?>