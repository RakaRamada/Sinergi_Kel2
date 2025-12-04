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
        
        if (!$this->groupModel->isGroupMember($sender_id, $group_id)) {
            $this->sendJson(['error' => 'Anda bukan anggota grup ini.']);
        }

        $isi_pesan = trim($_POST['isi_pesan'] ?? ''); 
        $file = $_FILES['file_upload'] ?? null;
        $reply_to_message_id = (int)($_POST['reply_to_message_id'] ?? 0);

        if (empty($isi_pesan) && (empty($file) || $file['error'] !== UPLOAD_ERR_OK)) {
            $this->sendJson(['error' => 'Pesan kosong'], 400);
        }

        $message_type = 'text';
        $file_path_to_db = null;
        $original_filename = null;

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../public/uploads/group_files/'; 
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

            $original_filename = basename($file['name']);
            $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
            $file_path_to_db = uniqid('chat_', true) . '.' . $extension;
            
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $message_type = 'image';
            } elseif (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
                $message_type = 'document';
            }

            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $file_path_to_db)) {
                $this->sendJson(['error' => 'Gagal upload file.'], 500);
            }
        }
        
        $newMessageId = $this->messageModel->createMessage(
            $group_id, $sender_id, $isi_pesan, $message_type, 
            $file_path_to_db, $original_filename, $reply_to_message_id
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            $this->sendJson(['error' => 'Akses ditolak'], 403);
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $message_id = $data['message_id'] ?? 0;
        $user_id = (int)$_SESSION['user_id'];

        if (empty($message_id)) $this->sendJson(['error' => 'Invalid ID'], 400);

        $success = $this->messageModel->deleteMessage($message_id, $user_id);
        
        if ($success) {
            $this->sendJson(['success' => true]);
        } else {
            $this->sendJson(['error' => 'Gagal hapus'], 500);
        }
    }

    public function getSidebarUpdates() {
        $groups = [];
        if (isset($_SESSION['user_id'])) {
            $groups = $this->groupModel->getGroupsByUserId((int)$_SESSION['user_id']);
        }
        $this->sendJson($groups);
    }
}
?>