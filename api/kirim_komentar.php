<!-- api/kirim_postingan.php -->
<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login dulu.']); exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? 0;
$isi = trim($_POST['isi_komen'] ?? '');

if (empty($post_id) || empty($isi)) {
    echo json_encode(['status' => 'error', 'message' => 'Komentar kosong.']); exit;
}

// Insert
$sql = "INSERT INTO comments (USER_ID, POST_ID, ISI_KOMEN, CREATED_AT) VALUES (:uid, :pid, :isi, SYSTIMESTAMP) RETURNING COMMENT_ID INTO :cid";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':uid', $user_id);
oci_bind_by_name($stmt, ':pid', $post_id);
oci_bind_by_name($stmt, ':isi', $isi);
$new_id = 0;
oci_bind_by_name($stmt, ':cid', $new_id, -1, SQLT_INT);

if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
    // Update Counter di Postingan
    $up = oci_parse($conn, "UPDATE postingan SET comment_count = comment_count + 1 WHERE post_id = :pid");
    oci_bind_by_name($up, ':pid', $post_id);
    oci_execute($up, OCI_COMMIT_ON_SUCCESS);

    // Return Data Baru
    echo json_encode(['status' => 'success', 'data' => [
        'comment_id' => $new_id,
        'isi_komen' => htmlspecialchars($isi),
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'User',
        'username' => $_SESSION['username'] ?? 'user',
        'avatar_url' => $_SESSION['avatar_url'] ?? '/Sinergi/public/assets/images/user.png',
        'waktu' => 'Baru saja'
    ]]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'DB Error']);
}
oci_close($conn);
?>