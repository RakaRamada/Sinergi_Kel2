<?php
// File: app/controllers/MessageController.php

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../models/MessageModel.php'; 
require_once __DIR__ . '/../models/GroupModel.php'; 
// LOAD FORUM MODEL (Wajib agar tidak error di View)
require_once __DIR__ . '/../models/ForumModel.php'; 

class MessageController {
    
    private $conn;
    private $messageModel;
    private $groupModel;
    private $forumModel;

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        
        // Inisialisasi semua Model
        $this->messageModel = new MessageModel($dbConnection);
        $this->groupModel   = new GroupModel($dbConnection);
        $this->forumModel   = new ForumModel($dbConnection);
    }

    // Helper: Kirim JSON bersih
    private function sendJson($data, $code = 200) {
        while (ob_get_level()) ob_end_clean();
        ini_set('display_errors', 0);
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    /**
     * Menampilkan Halaman Pesan (Chat Room)
     */
    public function showMessages() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
        
        $group_id = $_GET['group_id'] ?? null; 
        $messages = [];
        $groupInfo = null;
        $groups = []; 
        $forumData = []; 

        if (isset($_SESSION['user_id'])) {
            $user_id = (int)$_SESSION['user_id'];
            
            // Ambil daftar grup user
            $groups = $this->groupModel->getGroupsByUserId($user_id);
            
            if ($group_id) {
                // Cek Membership
                $isMember = $this->groupModel->isGroupMember($user_id, $group_id);
                if (!$isMember) {
                    header("Location: index.php?page=messages&error=access_denied");
                    exit();
                }

                // Ambil Data Pesan
                $messages = $this->messageModel->getMessagesByGroupId((int)$group_id);
                $groupInfo = $this->groupModel->getGroupById((int)$group_id);
                
                // Update status terbaca
                $this->messageModel->updateLastReadMessage($user_id, (int)$group_id);

                // FIX ERROR: Ambil Data Forum menggunakan Model OOP
                $forumData = $this->forumModel->getForumPostsByGroupId((int)$group_id, $user_id);
            }
        } else {
            header('Location: index.php?page=login'); exit();
        }
        
        // Load View (Variable $forumData, $messages, dll akan tersedia di View)
        require __DIR__ . '/../views/messages.php'; 
    }

    public function storeMessage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            $this->sendJson(['error' => 'Akses ditolak'], 403);
        }

        $group_id = (int)$_POST['group_id']; 
        $sender_id = (int)$_SESSION['user_id'];
        
        // Cek Member
        if (!$this->groupModel->isGroupMember($sender_id, $group_id)) {
            $this->sendJson(['error' => 'Anda bukan anggota grup ini.']);
        }

        $isi_pesan = trim($_POST['isi_pesan'] ?? '');
        $reply_to_message_id = (int)($_POST['reply_to_message_id'] ?? 0);

        // --- PERBAIKAN VALIDASI MULTI FILE ---
        // Cek apakah ada file yang dipilih di array upload
        $has_file = (isset($_FILES['file_upload']) && !empty($_FILES['file_upload']['name'][0]));

        // Jika Teks Kosong DAN Tidak Ada File -> Error
        if (empty($isi_pesan) && !$has_file) {
            $this->sendJson(['error' => 'Pesan atau gambar tidak boleh kosong'], 400);
        }

        $full_paths_arr = []; 
        $original_filenames_arr = [];
        $message_type = 'text';

        // Base URL sesuai request Dosen
        $web_base_path = '/Sinergi/public/uploads/group_files/';

        if (isset($_FILES['file_upload']) && !empty($_FILES['file_upload']['name'][0])) {
            $upload_dir = __DIR__ . '/../../public/uploads/group_files/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

            $count_files = count($_FILES['file_upload']['name']);
            
            // Validasi Max 5
            if ($count_files > 5) {
                $this->sendJson(['error' => 'Maksimal 5 gambar'], 400);
            }

            for ($i = 0; $i < $count_files; $i++) {
                if ($_FILES['file_upload']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES['file_upload']['tmp_name'][$i];
                    $name = $_FILES['file_upload']['name'][$i];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    // Nama file unik di folder
                    $disk_filename = uniqid('chat_', true) . $i . '.' . $ext;
                    
                    // Cek Tipe
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $message_type = 'image';
                    } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
                        $message_type = 'document';
                    }

                    if (move_uploaded_file($tmp, $upload_dir . $disk_filename)) {
                        // REQ DOSEN: Simpan FULL PATH ke array
                        // Contoh: /Sinergi/public/uploads/group_files/chat_123.jpg
                        $full_paths_arr[] = $web_base_path . $disk_filename;
                        
                        $original_filenames_arr[] = $name;
                    }
                }
            }
        }

        // GABUNGKAN JADI SATU STRING DIPISAH KOMA
        // Hasil: "/Sinergi/.../img1.jpg,/Sinergi/.../img2.jpg"
        $file_path_string = !empty($full_paths_arr) ? implode(',', $full_paths_arr) : null;
        $original_name_string = !empty($original_filenames_arr) ? implode(',', $original_filenames_arr) : null;

        // SIMPAN KE DB (Pake Model Lama Aja, Gak Perlu Ubah Model!)
        $newMessageId = $this->messageModel->createMessage(
            $group_id, $sender_id, $isi_pesan, $message_type, 
            $file_path_string, $original_name_string, $reply_to_message_id
        );
        
        if ($newMessageId) {
            $newMessageData = $this->messageModel->getMessageById($newMessageId);
            $this->sendJson($newMessageData);
        } else {
            $this->sendJson(['error' => 'Gagal simpan DB'], 500);
        }
    }

    public function checkNewMessages() {
        // Penting untuk Long Polling: Tutup session biar gak nge-lock request lain
        session_write_close(); 

        $group_id = (int)($_GET['group_id'] ?? 0);
        $last_message_id = (int)($_GET['last_message_id'] ?? 0);

        if (empty($group_id)) $this->sendJson(['error' => 'Group ID Invalid']);

        set_time_limit(35);
        $startTime = time();
        
        while (time() - $startTime < 30) {
            $newMessages = $this->messageModel->getNewMessagesAfterId($group_id, $last_message_id); 
            if (!empty($newMessages)) {
                $this->sendJson($newMessages);
            }
            sleep(1); 
        }

        $this->sendJson([]);
    }

    public function deleteMessageController() {
        // 1. BERSIHKAN BUFFER (Hapus spasi/enter bandel)
        while (ob_get_level()) ob_end_clean();
        
        header('Content-Type: application/json');
        ini_set('display_errors', 0);
        error_reporting(0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak']);
            exit;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $message_id = $data['message_id'] ?? 0;
        $user_id = (int)$_SESSION['user_id'];

        if (empty($message_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }

        $isDeleted = $this->messageModel->deleteMessage($message_id, $user_id);
        
        // 2. KIRIM SINYAL GANDA
        if ($isDeleted) {
            echo json_encode([
                'status' => 'success', 
                'success' => true,
                'message' => 'Berhasil dihapus'
            ]);
        } else {
            echo json_encode([
                'status' => 'error', 
                'success' => false,
                'message' => 'Gagal menghapus (DB Error)'
            ]);
        }
        exit; // Pastikan berhenti disini
    }

    public function getSidebarUpdates() {
        $groups = [];
        if (isset($_SESSION['user_id'])) {
            $groups = $this->groupModel->getGroupsByUserId((int)$_SESSION['user_id']);
        }
        $this->sendJson($groups);
    }
}