<?php
// api/tambah_komentar.php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

header('Content-Type: application/json');

// Cek Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Ambil Data Input
$post_id = $_POST['post_id'] ?? 0;
$isi_komen = trim($_POST['isi_komen'] ?? '');
$parent_comment_id = isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
$user_id = $_SESSION['user_id'];

// Validasi
if (empty($isi_komen)) {
    echo json_encode(['status' => 'error', 'message' => 'Komentar kosong']);
    exit;
}

if ($post_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Post ID invalid']);
    exit;
}

// 1. Generate Comment ID
$sql_get_id = "SELECT NVL(MAX(comment_id), 0) + 1 as next_id FROM comments";
$stmt_id = oci_parse($conn, $sql_get_id);
oci_execute($stmt_id);
$row_id = oci_fetch_assoc($stmt_id);
$comment_id = $row_id['NEXT_ID'];
oci_free_statement($stmt_id);

// 2. INSERT Comment (Menggunakan nama variabel unik: param_)
if ($parent_comment_id !== null && $parent_comment_id > 0) {
    // Reply
    $sql = "INSERT INTO comments (comment_id, post_id, user_id, isi_komen, parent_comment_id, created_at) 
            VALUES (:param_cid, :param_pid, :param_uid, :param_isi, :param_parent, SYSTIMESTAMP)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':param_cid', $comment_id);
    oci_bind_by_name($stmt, ':param_pid', $post_id);
    oci_bind_by_name($stmt, ':param_uid', $user_id);
    oci_bind_by_name($stmt, ':param_isi', $isi_komen);
    oci_bind_by_name($stmt, ':param_parent', $parent_comment_id);
} else {
    // Komentar Utama
    $sql = "INSERT INTO comments (comment_id, post_id, user_id, isi_komen, parent_comment_id, created_at) 
            VALUES (:param_cid, :param_pid, :param_uid, :param_isi, NULL, SYSTIMESTAMP)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':param_cid', $comment_id);
    oci_bind_by_name($stmt, ':param_pid', $post_id);
    oci_bind_by_name($stmt, ':param_uid', $user_id);
    oci_bind_by_name($stmt, ':param_isi', $isi_komen);
}

if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $error['message']]);
    exit;
}
oci_free_statement($stmt);

// 3. Update comment_count (PERBAIKAN: Ganti :pid jadi :param_pid_upd)
$sql_update = "UPDATE postingan SET comment_count = comment_count + 1 WHERE post_id = :param_pid_upd";
$stmt_update = oci_parse($conn, $sql_update);
oci_bind_by_name($stmt_update, ':param_pid_upd', $post_id);
oci_execute($stmt_update);
oci_free_statement($stmt_update);

// 4. Ambil data user (PERBAIKAN: Ganti :uid jadi :param_uid_get)
$sql_user = "SELECT username, nama_lengkap, avatar_url FROM users WHERE user_id = :param_uid_get";
$stmt_user = oci_parse($conn, $sql_user);
oci_bind_by_name($stmt_user, ':param_uid_get', $user_id);

if (!oci_execute($stmt_user)) {
    // Error handling untuk select
    echo json_encode([
        'status' => 'success', // Tetap success karena komen sudah masuk
        'message' => 'Komentar masuk, tapi gagal load user',
        'data' => [] 
    ]);
    exit;
}

$user_data = oci_fetch_assoc($stmt_user);

// Handle jika fetch gagal atau user tidak ditemukan
if (!$user_data) {
    $user_data = [
        'username' => 'Unknown',
        'nama_lengkap' => 'Pengguna',
        'avatar_url' => ''
    ];
}

$user_data = array_change_key_case($user_data, CASE_LOWER);
oci_free_statement($stmt_user);

// Prepare Response
$default_avatar = '/Sinergi/public/assets/images/user.png';
$avatar_url = (!empty($user_data['avatar_url']) && strlen($user_data['avatar_url']) > 5) 
              ? $user_data['avatar_url'] : $default_avatar;

echo json_encode([
    'status' => 'success',
    'message' => 'Komentar berhasil ditambahkan',
    'data' => [
        'comment_id' => $comment_id,
        'post_id' => $post_id,
        'user_id' => $user_id,
        'isi_komen' => htmlspecialchars($isi_komen), // Safety XSS
        'parent_comment_id' => $parent_comment_id,
        'username' => $user_data['username'],
        'nama_lengkap' => $user_data['nama_lengkap'],
        'avatar_url' => $avatar_url,
        'waktu' => 'Baru saja'
    ]
], JSON_UNESCAPED_UNICODE);

oci_close($conn);
?>