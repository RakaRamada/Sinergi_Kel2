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

/**
 * Menangani permintaan user untuk bergabung ke sebuah forum.
 * Dipanggil oleh router 'page=join-forum'.
 */
function handleJoinForum() {
    // 0. Pastikan session aktif untuk mendapatkan ID user
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN forum_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['forum_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_GET['forum_id'];

        // 2. Panggil fungsi Model 'joinForum' (yang sudah kita buat)
        $success = joinForum($user_id, $forum_id);

        if ($success) {
            // 3. Berhasil! Arahkan user ke halaman forum yang baru dia ikuti
            header('Location: index.php?page=messages&forum_id=' . $forum_id);
            exit();
        } else {
            // 4. Gagal (mungkin karena error DB)
            // Kembali ke halaman search dengan pesan error
            header('Location: index.php?page=search&tab=forum&error=join_failed');
            exit();
        }

    } else {
        // 5. Jika tidak login atau tidak ada forum_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menampilkan halaman detail forum (info, anggota, dll.)
 */
function showForumDetails() {
    // 0. Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN forum_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['forum_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_GET['forum_id'];

        // 2. Panggil Model untuk data dasar forum
        // (Fungsi getForumById() sudah kita buat sebelumnya)
        $forum_info = getForumById($forum_id);

        // 3. Panggil Model untuk daftar anggota
        // (Fungsi getForumMembers() akan kita BUAT DI LANGKAH SELANJUTNYA)
        $forum_members = getForumMembers($forum_id); 

        // 4. Cek apakah user ini adalah pembuat forum (untuk tombol 'Edit')
        $is_creator = false;
        if ($forum_info && isset($forum_info['created_by_user_id'])) {
            $is_creator = ($forum_info['created_by_user_id'] == $user_id);
        }

        // 5. Muat file view (yang akan kita BUAT DI LANGKAH SELANJUTNYA)
        // dan kirimkan semua data yang kita kumpulkan
        require 'app/views/forum_details.php';

    } else {
        // Jika tidak login atau tidak ada forum_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}
?>