<?php
// api/hapus_postingan.php
error_reporting(0); 
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../app/models/PostModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Login dulu.']); 
    exit; 
}

$post_id = $_POST['post_id'] ?? 0;
$uid = $_SESSION['user_id'];

if (empty($post_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Postingan tidak valid']); 
    exit;
}

$postModel = new PostModel($conn);
$result = $postModel->deletePost($post_id, $uid);

if ($result['status']) {
    // Hapus file gambar jika ada
    if (!empty($result['image_path'])) {
        $file_path = __DIR__ . '/..' . $result['image_path'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }
    echo json_encode(['status' => 'success', 'message' => 'Postingan dihapus']);
} else {
    echo json_encode(['status' => 'error', 'message' => $result['message']]);
}

oci_close($conn);
?>