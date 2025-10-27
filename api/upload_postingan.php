<?php

require_once __DIR__ . '/../config/koneksi.php';
session_start();

header('Content-Type: application/json');
$response = [];

// 1. Validasi: Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['status' => 'error', 'message' => 'Metode request tidak valid.'];
    echo json_encode($response);
    exit;
}

// 2. Validasi: Pastikan pengguna sudah login
// Menggunakan 'id_users' sesuai skema tabel Anda
if (!isset($_SESSION['id_users'])) {
    $response = ['status' => 'error', 'message' => 'Anda harus login untuk memposting.'];
    echo json_encode($response);
    exit;
}

// 3. Ambil data dari form
$id_user = $_SESSION['id_users'];
$konten = $_POST['post_content'] ?? null;
$image_path_db = null; // Path gambar yang akan disimpan ke DB

// 4. Logika Handle Upload Gambar (Jika ada)
if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == UPLOAD_ERR_OK) {
    
    // Path ke folder upload (pastikan /public/uploads/posts/ sudah ada dan writable)
    $upload_dir = __DIR__ . '/../public/uploads/posts/'; 
    
    $file_name = time() . '_' . basename($_FILES['post_image']['name']);
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_file)) {
        // Path yang akan diakses oleh browser
        $image_path_db = '/Sinergi/public/uploads/posts/' . $file_name;
    } else {
        $response = ['status' => 'error', 'message' => 'Gagal mengunggah gambar.'];
        echo json_encode($response);
        exit;
    }
}

// 5. Validasi: Postingan tidak boleh kosong sama sekali
if (empty($konten) && empty($image_path_db)) {
    $response = ['status' => 'error', 'message' => 'Postingan tidak boleh kosong.'];
    echo json_encode($response);
    exit;
}

// 6. Simpan ke Database (sesuai skema Anda)
// Tabel: postingan, Kolom: id_user, konten, post_image
$sql = "INSERT INTO postingan (id_user, konten, post_image) 
        VALUES (:id_user_bv, :konten_bv, :image_bv)";

$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt, ':id_user_bv', $id_user);
oci_bind_by_name($stmt, ':konten_bv', $konten);
oci_bind_by_name($stmt, ':image_bv', $image_path_db);

$result = oci_execute($stmt);

if ($result) {
    $response = ['status' => 'success', 'message' => 'Postingan berhasil diunggah!'];
} else {
    $e = oci_error($stmt);
    $response = ['status' => 'error', 'message' => 'Gagal menyimpan ke DB: ' . $e['message']];
}

echo json_encode($response);

oci_free_statement($stmt);
oci_close($conn);
?>