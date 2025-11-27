<?php
// api/tambah_komentar.php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../app/models/PostModel.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); exit;
}

$post_id = $_POST['post_id'] ?? 0;
$isi = trim($_POST['isi_komen'] ?? '');
$parent_id = !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
$user_id = $_SESSION['user_id'];

if (empty($isi) || $post_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']); exit;
}

$postModel = new PostModel($conn);
$newCommentId = $postModel->addComment($user_id, $post_id, $isi, $parent_id);

if ($newCommentId) {
    // Siapkan data response (Hardcode avatar session untuk kecepatan UI)
    $avatar = $_SESSION['avatar_url'] ?? '/Sinergi/public/assets/images/user.png';
    echo json_encode([
        'status' => 'success',
        'message' => 'Berhasil',
        'data' => [
            'comment_id' => $newCommentId,
            'post_id' => $post_id,
            'user_id' => $user_id,
            'isi_komen' => htmlspecialchars($isi),
            'parent_comment_id' => $parent_id,
            'username' => $_SESSION['username'] ?? 'User',
            'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'User',
            'avatar_url' => $avatar,
            'waktu' => 'Baru saja'
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal simpan DB']);
}
oci_close($conn);
?>