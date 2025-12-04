<?php
// File: app/controllers/NotificationController.php

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../models/GroupModel.php'; 

class NotificationController {
    
    private $conn;
    private $notifModel;
    private $groupModel;

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        $this->notifModel = new NotificationModel($dbConnection);
        $this->groupModel = new GroupModel($dbConnection);
    }

    public function showNotifications() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit();
        }

        $user_id = (int)$_SESSION['user_id'];
        $data = $this->notifModel->getUserNotifications($user_id);
        
        $unread_list = $data['unread'];
        $read_list   = $data['read'];

        require __DIR__ . '/../views/notification.php';
    }

    public function processReadNotification() {
        $notif_id = $_GET['id'] ?? 0;
        $this->notifModel->markNotificationAsRead($notif_id);
        
        $redirect_url = $_GET['redirect'] ?? 'index.php?page=notifications';
        header("Location: " . $redirect_url);
        exit();
    }

    public function handleAcceptInvite() {
        $notif_id = (int)($_GET['notif_id'] ?? 0);
        $user_id = $_SESSION['user_id'] ?? 0;

        // 1. Ambil Data Notifikasi
        $notif = $this->notifModel->getNotificationById($notif_id);

        if ($notif && $notif['type'] == 'group_invite' && $notif['user_id'] == $user_id) {
            $group_id = (int)$notif['related_group_id'];
            
            // --- LOGIKA CERDAS: CEK DULU BARU EKSEKUSI ---
            
            // A. Cek apakah user ini pernah ada di tabel group_members? (Entah invited, kicked, atau left)
            $sql_cek = "SELECT COUNT(*) AS ADA FROM group_members WHERE group_id = :p_cek_gid AND user_id = :p_cek_uid";
            $stmt_cek = oci_parse($this->conn, $sql_cek);
            oci_bind_by_name($stmt_cek, ':p_cek_gid', $group_id);
            oci_bind_by_name($stmt_cek, ':p_cek_uid', $user_id);
            oci_execute($stmt_cek);
            $row_cek = oci_fetch_assoc($stmt_cek);
            $sudah_ada = ($row_cek['ADA'] > 0);
            oci_free_statement($stmt_cek);

            $sukses = false;

            if ($sudah_ada) {
                // SKENARIO 1: Data sudah ada (misal status 'invited' atau bekas 'left') -> Kita UPDATE
                $sql = "UPDATE group_members 
                        SET status = 'active', 
                            role = 'member', 
                            joined_at = SYSTIMESTAMP 
                        WHERE group_id = :p_gid AND user_id = :p_uid";
            } else {
                // SKENARIO 2: Data belum ada sama sekali -> Kita INSERT
                $sql = "INSERT INTO group_members (group_id, user_id, status, role, joined_at) 
                        VALUES (:p_gid, :p_uid, 'active', 'member', SYSTIMESTAMP)";
            }

            // Eksekusi Query yang terpilih
            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ':p_gid', $group_id);
            oci_bind_by_name($stmt, ':p_uid', $user_id);
            
            // Jalankan dan Commit
            if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
                $sukses = true;
            } else {
                // Debugging: Jika gagal, intip errornya (bisa dihapus nanti)
                $e = oci_error($stmt);
                error_log("Gagal Join Grup: " . $e['message']);
            }
            oci_free_statement($stmt);

            if ($sukses) {
                // Tandai notifikasi sudah dibaca
                $this->notifModel->markNotificationAsRead($notif_id);
                
                // Redirect ke Halaman Grup
                header("Location: index.php?page=messages&group_id=$group_id&success=joined");
                exit();
            }
        }

        // Jika gagal total
        header("Location: index.php?page=notification&error=failed_join_logic");
        exit();
    }   

    public function handleRejectInvite() {
        $notif_id = (int)($_GET['notif_id'] ?? 0);
        $user_id = $_SESSION['user_id'] ?? 0;
        
        $notif = $this->notifModel->getNotificationById($notif_id);

        if ($notif && $notif['user_id'] == $user_id) {
            $group_id = $notif['related_group_id'];
            
            // Reject di DB Group
            $this->groupModel->processJoinRequest($user_id, $group_id, 'reject');
            $this->notifModel->markNotificationAsRead($notif_id);
        }

        header("Location: index.php?page=notification&msg=rejected");
        exit();
    }

    public function apiDeleteNotification() {
        // 1. MATIKAN ERROR DISPLAY (Agar teks error tidak merusak format JSON)
        ini_set('display_errors', 0);
        error_reporting(0);

        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // 2. Set Header JSON
        header('Content-Type: application/json'); 

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Request method salah");
            }

            if (!isset($_SESSION['user_id'])) {
                throw new Exception("User belum login");
            }

            $notif_id = $_POST['notif_id'] ?? null;
            $user_id = $_SESSION['user_id'];

            if (empty($notif_id)) {
                throw new Exception("ID Notifikasi tidak valid");
            }

            // Eksekusi Hapus via Model
            $result = $this->notifModel->deleteNotification($notif_id, $user_id);
            
            if ($result) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus database']);
            }

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }

    // --- API: HAPUS SEMUA NOTIFIKASI ---
    public function apiClearAllNotifications() {
        // 1. MATIKAN ERROR REPORTING
        error_reporting(0);
        ini_set('display_errors', 0);

        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];

            if ($this->notifModel->deleteAllNotifications($user_id)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        }
        exit();
    }
}


?>