<?php
// api/tambah_komentar.php (FINAL FIX IDENTITY COLUMN)
session_start();
require_once __DIR__ . '/../config/koneksi.php';

header('Content-Type: application/json');

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// 2. Ambil Data Input
$post_id = $_POST['post_id'] ?? 0;
$isi_komen = trim($_POST['isi_komen'] ?? '');
$parent_comment_id = isset($_POST['parent_comment_id']) && !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
$user_id = $_SESSION['user_id'];

// 3. Validasi
if (empty($isi_komen)) {
    echo json_encode(['status' => 'error', 'message' => 'Komentar kosong']);
    exit;
}

if ($post_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Post ID invalid']);
    exit;
}

// --- PERBAIKAN UTAMA DI SINI ---
// Kita tidak perlu menghitung ID manual. Biarkan Oracle yang buat.
// Kita siapkan variabel untuk menangkap ID baru dari Oracle.
$new_comment_id = 0;

// 4. INSERT Comment
if ($parent_comment_id !== null && $parent_comment_id > 0) {
    // === KASUS REPLY ===
    // Hapus 'comment_id' dari INSERT, tambahkan RETURNING INTO
    $sql = "INSERT INTO comments (post_id, user_id, isi_komen, parent_comment_id, created_at) 
            VALUES (:param_pid, :param_uid, :param_isi, :param_parent, SYSTIMESTAMP)
            RETURNING comment_id INTO :out_cid";
            
    $stmt = oci_parse($conn, $sql);
    
    // Binding Input
    oci_bind_by_name($stmt, ':param_pid', $post_id);
    oci_bind_by_name($stmt, ':param_uid', $user_id);
    oci_bind_by_name($stmt, ':param_isi', $isi_komen);
    oci_bind_by_name($stmt, ':param_parent', $parent_comment_id);
    
    // Binding Output (Untuk menangkap ID baru)
    oci_bind_by_name($stmt, ':out_cid', $new_comment_id, -1, OCI_B_INT);

} else {
    // === KASUS KOMENTAR UTAMA ===
    // Hapus 'comment_id' dari INSERT, tambahkan RETURNING INTO
    $sql = "INSERT INTO comments (post_id, user_id, isi_komen, parent_comment_id, created_at) 
            VALUES (:param_pid, :param_uid, :param_isi, NULL, SYSTIMESTAMP)
            RETURNING comment_id INTO :out_cid";
            
    $stmt = oci_parse($conn, $sql);
    
    // Binding Input
    oci_bind_by_name($stmt, ':param_pid', $post_id);
    oci_bind_by_name($stmt, ':param_uid', $user_id);
    oci_bind_by_name($stmt, ':param_isi', $isi_komen);
    
    // Binding Output (Untuk menangkap ID baru)
    oci_bind_by_name($stmt, ':out_cid', $new_comment_id, -1, OCI_B_INT);
}

// Eksekusi Query
if (!oci_execute($stmt)) {
    $error = oci_error($stmt);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $error['message']]);
    exit;
}
oci_free_statement($stmt);

// 5. Update comment_count (Update jumlah komentar di tabel postingan)
$sql_update = "UPDATE postingan SET comment_count = comment_count + 1 WHERE post_id = :param_pid_upd";
$stmt_update = oci_parse($conn, $sql_update);
oci_bind_by_name($stmt_update, ':param_pid_upd', $post_id);
oci_execute($stmt_update);
oci_free_statement($stmt_update);

// 6. Ambil data user untuk respons JSON (Agar avatar/nama langsung muncul)
$sql_user = "SELECT username, nama_lengkap, avatar_url FROM users WHERE user_id = :param_uid_get";
$stmt_user = oci_parse($conn, $sql_user);
oci_bind_by_name($stmt_user, ':param_uid_get', $user_id);

if (!oci_execute($stmt_user)) {
    // Kalau gagal ambil user, kita tetap return success karena komen sudah masuk
    echo json_encode([
        'status' => 'success',
        'message' => 'Komentar masuk, tapi gagal load user',
        'data' => [] 
    ]);
    exit;
}

$user_data = oci_fetch_assoc($stmt_user);

// Handle jika user tidak ditemukan
if (!$user_data) {
    $user_data = [
        'username' => 'Unknown',
        'nama_lengkap' => 'Pengguna',
        'avatar_url' => ''
    ];
}

$user_data = array_change_key_case($user_data, CASE_LOWER);
oci_free_statement($stmt_user);

// 7. Prepare Response
$default_avatar = '/Sinergi/public/assets/images/user.png';
$avatar_url = (!empty($user_data['avatar_url']) && strlen($user_data['avatar_url']) > 5) 
              ? $user_data['avatar_url'] : $default_avatar;

echo json_encode([
    'status' => 'success',
    'message' => 'Komentar berhasil ditambahkan',
    'data' => [
        'comment_id' => $new_comment_id, // Gunakan ID yang dikasih Oracle
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