<?php
// File: app/controllers/MessageController.php

// Pastikan file model dipanggil agar fungsinya bisa digunakan
require_once __DIR__ . '/../models/MessageModel.php'; 
require_once __DIR__ . '/../models/ForumModel.php'; 

/**
 * Fungsi untuk menampilkan halaman pesan (daftar forum/chat forum).
 */
function showMessages() {
    // 0. Mulai session (Sangat penting untuk mendapatkan $_SESSION['user_id'])
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 1. Ambil ID forum dari URL (?forum_id=...)
    $forum_id = $_GET['forum_id'] ?? null; 

    // 2. Siapkan variabel untuk data yang akan dikirim ke view
    $messages = [];
    $forumInfo = null; // Info forum yang sedang dibuka (jika ada)
    $forums = []; // Daftar semua forum (untuk sidebar)

    // 3. --- INI PERUBAHAN UTAMA ---
    // Pastikan user sudah login sebelum mencoba mengambil forum mereka
    if (isset($_SESSION['user_id'])) {
        // Panggil fungsi baru: getForumsByUserId
        // Bukan lagi getAllForums()
        $forums = getForumsByUserId((int)$_SESSION['user_id']);
    }
    // Jika user tidak login, $forums akan tetap [] (array kosong)
    // dan 'Auth Guard' di index.php akan mengarahkan mereka ke login.
    // ----------------------------

    // 4. Jika ada forum_id di URL, ambil pesan untuk forum tersebut
    if ($forum_id) {
        // Panggil fungsi dari MessageModel
        $messages = getMessagesByForumId((int)$forum_id);

        // Ambil info forum yang sedang dibuka
        $forumInfo = getForumById((int)$forum_id);
    }
    
    // 5. Muat file view dan kirimkan data
    require 'app/views/messages.php'; 
}

/**
 * Menyimpan pesan baru yang dikirim dari form (via AJAX).
 * Tidak lagi me-redirect, tapi mengembalikan JSON.
 */
function storeMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        $forum_id = (int)$_POST['forum_id'];
        $isi_pesan = $_POST['isi_pesan']; 
        $sender_id = (int)$_SESSION['user_id'];

        if (!empty($forum_id) && !empty($isi_pesan) && !empty($sender_id)) {
            
            // 2. Panggil Model untuk menyimpan (Model kita sekarang mengembalikan ID)
            $newMessageId = createMessage($forum_id, $sender_id, $isi_pesan);
            
            if ($newMessageId) {
                // 3. Berhasil disimpan! Ambil data lengkap pesan baru itu.
                $newMessageData = getMessageById($newMessageId);

                // 4. Kirim data lengkap itu kembali ke JavaScript sebagai JSON
                header('Content-Type: application/json');
                echo json_encode($newMessageData);
                exit();
            }
        }
    }
    
    // Jika gagal, kirim error JSON
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Gagal menyimpan pesan']);
    exit();
}

/**
 * Endpoint untuk Long Polling. Mengecek pesan baru.
 */
function checkNewMessages() {
    // Pastikan session sudah aktif tapi tutup segera agar tidak memblok request lain
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_write_close(); // Lepas session lock!

    // Ambil parameter dari request JavaScript
    $forum_id = (int)($_GET['forum_id'] ?? 0);
    $last_message_id = (int)($_GET['last_message_id'] ?? 0);

    if (empty($forum_id)) {
        echo json_encode(['error' => 'Forum ID tidak valid']);
        exit;
    }

    // Set batas waktu eksekusi skrip (misal 30 detik)
    set_time_limit(35); // Sedikit lebih lama dari loop

    // Loop untuk menunggu pesan baru (maksimal ~30 detik)
    $startTime = time();
    while (time() - $startTime < 30) {
        
        $newMessages = getNewMessagesAfterId($forum_id, $last_message_id);

        if (!empty($newMessages)) {
            // Jika ada pesan baru, kirim sebagai JSON dan keluar
            header('Content-Type: application/json');
            echo json_encode($newMessages);
            exit;
        }

        // Jika tidak ada, tunggu 1 detik sebelum cek lagi
        sleep(1); 
    }

    // Jika loop selesai (timeout) tanpa pesan baru, kirim array JSON kosong
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

?>