<?php
// File: api/upload_postingan.php
// FIXED: Membolehkan posting gambar saja, teks saja, atau keduanya.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';

header('Content-Type: application/json');

// 1. Cek Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda belum login.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$konten = isset($_POST['konten']) ? trim($_POST['konten']) : '';

// 2. Cek Keberadaan File Gambar
$has_image = false;
if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
    $has_image = true;
}

// 3. VALIDASI UTAMA (PERBAIKAN DISINI)
// Error jika: Konten Kosong DAN Tidak ada Gambar
if (empty($konten) && !$has_image) {
    echo json_encode(['status' => 'error', 'message' => 'Postingan tidak boleh kosong (isi teks atau gambar).']);
    exit;
}

// 4. Proses Upload Gambar (Jika Ada)
$post_image_db = null; // Default null jika tidak ada gambar

if ($has_image) {
    $upload_dir = __DIR__ . '/../public/assets/uploads/';
    
    // Buat folder jika belum ada
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = $_FILES['post_image']['name'];
    $file_tmp = $_FILES['post_image']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validasi Ekstensi
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['status' => 'error', 'message' => 'Format gambar tidak didukung.']);
        exit;
    }

    // Generate Nama Unik
    $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
    $destination = $upload_dir . $new_file_name;

    if (move_uploaded_file($file_tmp, $destination)) {
        // Path untuk disimpan di database (Relative URL)
        $post_image_db = '/Sinergi/public/assets/uploads/' . $new_file_name;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload gambar ke folder server.']);
        exit;
    }
}

// 5. Simpan ke Database Oracle
try {
    // Query Insert
    $sql = "INSERT INTO postingan (USER_ID, KONTEN, POST_IMAGE, CREATED_AT, LIKE_COUNT, COMMENT_COUNT) 
            VALUES (:user_id, :konten, :post_image, SYSTIMESTAMP, 0, 0)";
    
    $stmt = oci_parse($conn, $sql);

    // Binding Variabel
    oci_bind_by_name($stmt, ':user_id', $user_id);
    
    // Handle Konten (Bisa Null/Empty)
    oci_bind_by_name($stmt, ':konten', $konten);
    
    // Handle Image (Bisa Null)
    oci_bind_by_name($stmt, ':post_image', $post_image_db);

    if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
        echo json_encode(['status' => 'success', 'message' => 'Berhasil memposting!']);
    } else {
        $e = oci_error($stmt);
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e['message']]);
    }

    oci_free_statement($stmt);
    oci_close($conn);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
}
?>