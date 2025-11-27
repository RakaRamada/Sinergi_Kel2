<?php
// File: api/ambil_postingan.php

// 1. Matikan tampilan error HTML agar tidak merusak JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Mulai Session & Header JSON
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// 3. DEFINISI PATH
$path_koneksi = __DIR__ . '/../config/koneksi.php';
$path_model   = __DIR__ . '/../app/models/PostModel.php';

// --- DEBUGGING PATH ---
if (!file_exists($path_koneksi)) {
    echo json_encode(['status' => 'error', 'message' => 'File Koneksi tidak ditemukan di: ' . $path_koneksi]);
    exit;
}

if (!file_exists($path_model)) {
    echo json_encode(['status' => 'error', 'message' => 'File Model tidak ditemukan di: ' . $path_model]);
    exit;
}

// 4. Require File
require_once $path_koneksi;
require_once $path_model;

// Pastikan variabel koneksi database tersedia
global $conn; 

if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal atau variabel $conn tidak ditemukan.']);
    exit;
}

// 5. Eksekusi Model
try {
    $userId = $_SESSION['user_id'] ?? 0;
    
    // DEBUGGING: Cek User ID
    if ($userId === 0) {
        echo json_encode(['status' => 'error', 'message' => 'User belum login atau session hilang.']);
        exit;
    }
    
    // Instansiasi Model
    $postModel = new PostModel($conn);
    
    // Ambil Data dengan error handling
    try {
        $posts = $postModel->getAllPosts($userId);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error saat mengambil postingan: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        exit;
    }

    // Cek apakah ada data
    if (!is_array($posts)) {
        echo json_encode(['status' => 'error', 'message' => 'Format data tidak valid dari database.']);
        exit;
    }

    // Format Data (Avatar & Waktu)
    $formatted = [];
    $default_avatar_file = '/Sinergi/public/assets/images/user.png';
    $default_avatar_dir = '/Sinergi/public/assets/images/';

    foreach ($posts as $row) {
        // Fix Avatar
        if (empty($row['AVATAR_URL']) || $row['AVATAR_URL'] == $default_avatar_dir) {
            $row['AVATAR_URL_FIXED'] = $default_avatar_file;
        } else {
            $row['AVATAR_URL_FIXED'] = $row['AVATAR_URL'];
        }

        // Fix Waktu
        $timestamp = strtotime($row['CREATED_AT_STR']); 
        if ($timestamp === false) {
            $row['WAKTU_POSTING'] = '-';
        } else {
            $diff = time() - $timestamp;
            if ($diff < 60) { 
                $row['WAKTU_POSTING'] = 'Baru saja'; 
            } else if ($diff < 3600) { 
                $row['WAKTU_POSTING'] = floor($diff / 60) . 'm'; 
            } else if ($diff < 86400) { 
                $row['WAKTU_POSTING'] = floor($diff / 3600) . 'j'; 
            } else { 
                $row['WAKTU_POSTING'] = date('d M', $timestamp); 
            }
        }
        $formatted[] = $row;
    }

    echo json_encode($formatted);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Server Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Tutup koneksi
if ($conn) {
    oci_close($conn);
}
?>