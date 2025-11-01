<?php
// File: app/controllers/ForumController.php

require_once __DIR__ . '/../models/ForumModel.php';

/**
 * Menampilkan halaman/view 'Buat Forum'
 */
function showCreateForm() {
    // Memuat file view (form) yang kita buat di Langkah 3
    require 'app/views/create_forum.php';
}

/**
 * Menyimpan data forum baru dari form
 */
function storeForum() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        $nama_forum = trim($_POST['nama_forum']);
        $deskripsi = trim($_POST['deskripsi']);
        $creator_user_id = (int)$_SESSION['user_id'];
        $forum_image_file = $_FILES['forum_image'];
        $image_name_to_db = null; 

        if (empty($nama_forum) || empty($deskripsi)) {
            header('Location: index.php?page=create-forum&error=empty');
            exit();
        }

        // Proses Upload Gambar (Jika ada)
        if (isset($forum_image_file) && $forum_image_file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/uploads/forum_profiles/'; 
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // Coba buat folder jika tidak ada
            }
            
            $image_extension = strtolower(pathinfo($forum_image_file['name'], PATHINFO_EXTENSION));
            $image_name_to_db = 'forum_' . uniqid() . '.' . $image_extension;
            $upload_path = $upload_dir . $image_name_to_db;

            if (!move_uploaded_file($forum_image_file['tmp_name'], $upload_path)) {
                header('Location: index.php?page=create-forum&error=upload_failed');
                exit();
            }
        }

        // Panggil Model (Langkah 6)
        $new_forum_id = createForum($nama_forum, $deskripsi, $creator_user_id, $image_name_to_db);

        if ($new_forum_id) {
            // Otomatis join ke forum yang baru dibuat
            joinForum($creator_user_id, $new_forum_id);
            
            // Berhasil! Arahkan ke halaman pesan
            header('Location: index.php?page=messages&success=forum_created');
            exit();
        } else {
            header('Location: index.php?page=create-forum&error=db_error');
            exit();
        }
    } else {
        header('Location: index.php?page=login');
        exit();
    }
}
?>