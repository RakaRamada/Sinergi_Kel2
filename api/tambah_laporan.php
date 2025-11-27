<?php
// File: app/api/tambah_laporan.php

// Error Handling untuk debug
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        ob_clean(); // Bersihkan output error HTML
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $error['message']]);
        exit;
    }
});

ob_start();
session_start();
header('Content-Type: application/json');

// 1. ATUR PATH (Sesuaikan dengan struktur folder Anda)
// Mundur 2 langkah: api -> app -> config
require_once __DIR__ . '/../config/koneksi.php'; 

// PENTING: Arahkan ke file ReportModel.php yang sudah kita buat sebelumnya.
// Jika ReportModel ada di folder admin, gunakan path ini:
// require_once __DIR__ . '/../admin/models/ReportModel.php';
// TAPI, lebih baik pindahkan ReportModel.php ke folder 'app/models/' agar bisa dipakai User & Admin.
// Asumsi saat ini file ada di app/models/:
require_once __DIR__ . '/../app/models/ReportModel.php'; // Atau sesuaikan path-nya

// 2. CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

try {
    if (!$conn) throw new Exception("Koneksi database gagal");

    // 3. AMBIL DATA
    $post_id = $_POST['post_id'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$post_id || !$reason) {
        throw new Exception("Data laporan tidak lengkap");
    }

    // 4. PROSES SIMPAN
    // Pastikan class ReportModel sudah Anda pindahkan/copy agar bisa diakses di sini
    $reportModel = new ReportModel($conn);
    
    // Fungsi createReport ini ada di ReportModel yang saya berikan sebelumnya
    $result = $reportModel->createReport($post_id, $user_id, $reason);

    ob_clean();
    echo json_encode($result); // Hasilnya: {status: true, message: ...}

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

if (isset($conn) && $conn) oci_close($conn);
?>