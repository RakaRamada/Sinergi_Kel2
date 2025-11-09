<?php
// File: app/controllers/MessageController.php

// Pastikan file model dipanggil agar fungsinya bisa digunakan
require_once __DIR__ . '/../models/MessageModel.php'; 
require_once __DIR__ . '/../models/ForumModel.php'; 

/**
 * Fungsi untuk menampilkan halaman pesan (daftar forum/chat forum).
 */
function showMessages() {
    // --- PERBAIKAN PRG (POST-REDIRECT-GET) ---
    // Jika ada yang mencoba POST ke halaman ini...
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //...tendang mereka kembali ke halaman ini via GET
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
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
 * -- VERSI BARU: Bisa menangani Teks dan File Upload --
 */
function storeMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi dasar
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'Akses ditolak']);
        exit();
    }

    // 2. Ambil data dasar
    $forum_id = (int)$_POST['forum_id'];
    $sender_id = (int)$_SESSION['user_id'];
    $isi_pesan = trim($_POST['isi_pesan'] ?? ''); // Ini adalah caption atau teks
    $file = $_FILES['file_upload'] ?? null;       // Ini adalah file yang di-upload

    // 3. Validasi: Pesan tidak boleh kosong JIKA tidak ada file
    if (empty($isi_pesan) && (empty($file) || $file['error'] !== UPLOAD_ERR_OK)) {
        header('Content-Type: application/json');
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Pesan atau file tidak boleh kosong']);
        exit();
    }

    // 4. Siapkan variabel untuk Model (default-nya adalah 'text')
    $message_type = 'text';
    $file_path_to_db = null;
    $original_filename = null;

    // 5. Logika jika ADA FILE yang di-upload
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        
        // Tentukan folder upload (PASTIKAN FOLDER INI ADA DAN BISA DITULIS)
        $upload_dir = 'public/uploads/forum_files/'; 
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true); // Coba buat folder jika tidak ada
        }

        $original_filename = basename($file['name']);
        $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        
        // Buat nama file yang unik untuk disimpan di server
        $file_path_to_db = uniqid('file_', true) . '.' . $extension;
        $destination = $upload_dir . $file_path_to_db;

        // Tentukan message_type berdasarkan ekstensi
        $image_types = ['jpg', 'jpeg', 'png', 'gif'];
        $doc_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

        if (in_array($extension, $image_types)) {
            $message_type = 'image';
        } elseif (in_array($extension, $doc_types)) {
            $message_type = 'document';
        } else {
            // Jika tipe file tidak dikenal (tidak sesuai 'accept' di HTML/JS)
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => 'Tipe file tidak diizinkan.']);
            exit();
        }

        // Pindahkan file dari 'tmp' ke folder 'forum_files'
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Gagal menyimpan file di server.']);
            exit();
        }
    }
    
    // 6. Panggil Model (Langkah 5)
    // Kita akan ubah createMessage() agar menerima parameter baru
    $newMessageId = createMessage(
        $forum_id, 
        $sender_id, 
        $isi_pesan, 
        $message_type, 
        $file_path_to_db, 
        $original_filename
    );
    
    if ($newMessageId) {
        // 7. Berhasil! Ambil data lengkap (Model getMessageById juga akan kita update)
        $newMessageData = getMessageById($newMessageId);

        // 8. Kirim data lengkap itu kembali ke JavaScript sebagai JSON
        header('Content-Type: application/json');
        echo json_encode($newMessageData);
        exit();
    }
    
    // Jika gagal (misal $newMessageId = false)
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Gagal menyimpan pesan ke database']);
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