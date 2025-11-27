<?php
// File: api/upload_postingan.php

// 1. Matikan Error HTML agar JSON tidak rusak
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Session & Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// 3. DEFINE PATHS (Absolut agar aman)
$path_koneksi = __DIR__ . '/../config/koneksi.php';
$path_model   = __DIR__ . '/../app/models/PostModel.php';

// Cek keberadaan file (Safety check)
if (!file_exists($path_koneksi)) {
    echo json_encode(['status' => 'error', 'message' => 'Config DB tidak ditemukan.']); exit;
}
if (!file_exists($path_model)) {
    echo json_encode(['status' => 'error', 'message' => 'Model Post tidak ditemukan.']); exit;
}

// 4. Require File
require_once $path_koneksi;
require_once $path_model;

global $conn; // Pastikan variabel koneksi terbaca

// Cek Login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda belum login.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$konten = isset($_POST['konten']) ? trim($_POST['konten']) : '';
$has_image = (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK);

// Validasi Input
if (empty($konten) && !$has_image) {
    echo json_encode(['status' => 'error', 'message' => 'Postingan tidak boleh kosong.']);
    exit;
}

// 5. Proses Upload Gambar
$post_image_db = null;

if ($has_image) {
    // Path folder fisik di server
    $upload_dir = __DIR__ . '/../public/assets/uploads/';
    
    // Buat folder jika belum ada
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($file_ext, $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'Format gambar tidak didukung (JPG/PNG/GIF).']);
        exit;
    }

    $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
    
    // Pindahkan file
    if (move_uploaded_file($_FILES['post_image']['tmp_name'], $upload_dir . $new_file_name)) {
        // Path untuk disimpan di DB
        $post_image_db = '/Sinergi/public/assets/uploads/' . $new_file_name;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal upload gambar ke server.']);
        exit;
    }
}

// 6. Panggil Model untuk Simpan ke DB
try {
    $postModel = new PostModel($conn);
    $postModel->createPost($user_id, $konten, $post_image_db);
    
    echo json_encode(['status' => 'success', 'message' => 'Berhasil memposting!']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}

// Tutup koneksi (Opsional di Oracle, tapi baik dilakukan)
if ($conn) {
    oci_close($conn);
}
?>