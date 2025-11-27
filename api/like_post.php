<?php
// api/like_post.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../app/models/PostModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login dulu.']); exit;
}
$post_id = $_POST['post_id'] ?? 0;
if (empty($post_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID Invalid.']); exit;
}

$postModel = new PostModel($conn);
$result = $postModel->toggleLike($_SESSION['user_id'], $post_id);

if ($result) {
    echo json_encode([
        'status' => 'success', 
        'action' => $result['action'], 
        'new_like_count' => $result['new_count']
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal like.']);
}
oci_close($conn);
?>