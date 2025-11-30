<?php
// File: app/controllers/MessageController.php

// Pastikan file model dipanggil agar fungsinya bisa digunakan
require_once __DIR__ . '/../models/MessageModel.php'; 
require_once __DIR__ . '/../models/GroupModel.php'; 

/**
 * Fungsi untuk menampilkan halaman pesan (daftar group/chat group).
 */
function showMessages() {
    // --- PERBAIKAN PRG (POST-REDIRECT-GET) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $group_id = $_GET['group_id'] ?? null; 
    $messages = [];
    $groupInfo = null;
    $groups = []; 

    // Pastikan user sudah login
    if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id']; // <-- Ambil user_id
        
        // Panggil fungsi V2 yang baru (sudah ada notif/snippet)
        $groups = getGroupsByUserId($user_id);
        
    } else {
        // Jika tidak login, $groups akan kosong
        // Auth Guard di index.php akan menangani
    }

    // Jika ada group_id di URL, ambil pesan untuk group tersebut
    if ($group_id && isset($user_id)) { // <-- Pastikan user_id ada
        
        $messages = getMessagesByGroupId((int)$group_id);
        $groupInfo = getGroupById((int)$group_id);

        // --- INI DIA PERUBAHAN UTAMANYA ---
        // Saat user membuka chat, kita update "terakhir dibaca"
        // Ini akan otomatis menghapus notif di sidebar
        updateLastReadMessage($user_id, (int)$group_id);
        // --- AKHIR PERUBAHAN ---
    }
    
    require 'app/views/messages.php'; 
}

/**
 * Menyimpan pesan baru yang dikirim dari form (via AJAX).
 * -- VERSI BARU: Bisa menangani File Upload dan Reply --
 */
function storeMessage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Akses ditolak']);
        exit();
    }

    // 2. Ambil Data
    // Note: JS mengirim 'forum_id' (sesuai form chat_app.js), tapi kita anggap itu group_id
    $group_id = (int)$_POST['forum_id']; 
    $sender_id = (int)$_SESSION['user_id'];
    $isi_pesan = trim($_POST['isi_pesan'] ?? ''); 
    $file = $_FILES['file_upload'] ?? null;
    $reply_to_message_id = (int)($_POST['reply_to_message_id'] ?? 0);

    // 3. Cek Kekosongan
    if (empty($isi_pesan) && (empty($file) || $file['error'] !== UPLOAD_ERR_OK)) {
        header('Content-Type: application/json');
        http_response_code(400); 
        echo json_encode(['error' => 'Pesan kosong']);
        exit();
    }

    $message_type = 'text';
    $file_path_to_db = null;
    $original_filename = null;

    // 4. Proses Upload File (FIX FOLDER PATH)
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        
        // GANTI FOLDER KE 'group_files'
        $upload_dir = 'public/uploads/group_files/'; 
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $original_filename = basename($file['name']);
        $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $file_path_to_db = uniqid('chat_', true) . '.' . $extension;
        $destination = $upload_dir . $file_path_to_db;

        $image_types = ['jpg', 'jpeg', 'png', 'gif'];
        $doc_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

        if (in_array($extension, $image_types)) {
            $message_type = 'image';
        } elseif (in_array($extension, $doc_types)) {
            $message_type = 'document';
        } else {
            // Default text kalau tipe file aneh, atau return error
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Gagal upload file.']);
            exit();
        }
    }
    
    // 5. Panggil Model (Kirim group_id)
    $newMessageId = createMessage(
        $group_id, 
        $sender_id, 
        $isi_pesan, 
        $message_type, 
        $file_path_to_db, 
        $original_filename,
        $reply_to_message_id
    );
    
    if ($newMessageId) {
        // Sukses! Ambil data lengkap
        $newMessageData = getMessageById($newMessageId);
        header('Content-Type: application/json');
        echo json_encode($newMessageData);
        exit();
    }
    
    header('Content-Type: application/json');
    http_response_code(500); 
    echo json_encode(['error' => 'Gagal simpan DB']);
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
    $group_id = (int)($_GET['group_id'] ?? 0);
    $last_message_id = (int)($_GET['last_message_id'] ?? 0);

    if (empty($group_id)) {
        echo json_encode(['error' => 'Group ID tidak valid']);
        exit;
    }

    // Set batas waktu eksekusi skrip (misal 30 detik)
    set_time_limit(35); // Sedikit lebih lama dari loop

    // Loop untuk menunggu pesan baru (maksimal ~30 detik)
    $startTime = time();
    while (time() - $startTime < 30) {
        
        $newMessages = getNewMessagesAfterId($group_id, $last_message_id);

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

/**
 * Menghapus pesan (dipanggil oleh AJAX).
 */
function deleteMessageController() {
    // 1. Pastikan session aktif untuk cek user
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 2. Validasi Keamanan:
    // - Harus request POST
    // - User harus login
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'Akses ditolak']);
        exit();
    }

    // 3. Ambil data JSON yang dikirim oleh fetch()
    // Ini BUKAN dari $_POST, tapi dari "body" request
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Ambil ID pesan dari data JSON itu
    $message_id = $data['message_id'] ?? 0;
    // Ambil ID user yang sedang login (untuk keamanan)
    $user_id = (int)$_SESSION['user_id'];

    if (empty($message_id)) {
        header('Content-Type: application/json');
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Message ID tidak valid']);
        exit();
    }

    // 4. Panggil Model (Fungsi ini akan kita buat di langkah terakhir)
    // Kita kirim KEDUA ID agar Model bisa cek "apakah user ini pemilik pesan?")
    $success = deleteMessage($message_id, $user_id);
    
    // 5. Kirim balasan ke JavaScript
    header('Content-Type: application/json');
    if ($success) {
        // Jika model bilang sukses
        echo json_encode(['success' => true]);
    } else {
        // Jika model bilang gagal (misal: bukan pemilik pesan)
        http_response_code(500); 
        echo json_encode(['error' => 'Gagal menghapus pesan atau Anda tidak punya izin.']);
    }
    exit();
}

/**
 * Endpoint API untuk sidebar poller.
 * Hanya mengembalikan data JSON.
 */
function getSidebarUpdates() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $groups = [];
    if (isset($_SESSION['user_id'])) {
        // Panggil fungsi model kita yang sudah canggih
        $groups = getGroupsByUserId((int)$_SESSION['user_id']);
    }

    // Matikan error reporting agar tidak merusak JSON
    error_reporting(0); 
    ini_set('display_errors', 0);

    // Kirim sebagai JSON
    header('Content-Type: application/json');
    echo json_encode($groups);
    exit(); // Wajib ada exit()
}

?>