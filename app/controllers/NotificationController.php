<?php
// File: app/controllers/NotificationController.php

require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../models/ForumModel.php';

/**
 * Class NotificationController
 * Menangani semua proses yang berhubungan dengan Notifikasi.
 */
class NotificationController
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function showNotifications()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit();
        }

        $user_id = (int)$_SESSION['user_id'];
        // Pastikan model functions menggunakan $this->conn
        $data = getUserNotifications($user_id, $this->conn);

        $unread_list = $data['unread'];
        $read_list   = $data['read'];

        require 'app/views/notification.php';
    }

    public function processReadNotification()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $notif_id = $_GET['id'] ?? 0;

        // Pastikan model functions menggunakan $this->conn
        markNotificationAsRead($notif_id, $this->conn);

        $redirect_url = $_GET['redirect'] ?? 'index.php?page=notification';
        header("Location: " . $redirect_url);
        exit();
    }

    /**
     * LOGIKA: TERIMA UNDANGAN
     */
    public function handleAcceptInvite()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $notif_id = (int)($_GET['notif_id'] ?? 0);
        $user_id = $_SESSION['user_id'] ?? 0;

        // 1. Ambil data notifikasi
        $notif = getNotificationById($notif_id, $this->conn);

        if ($notif && $notif['type'] == 'group_invite' && $notif['user_id'] == $user_id) {
            $forum_id = $notif['related_forum_id'];

            // 2. Masukkan user ke forum
            $user_nama = $_SESSION['nama_lengkap'] ?? 'Seseorang';
            // Pastikan joinForum menggunakan $this->conn
            $joinSuccess = joinForum($user_id, $forum_id, $user_nama, $this->conn);

            if ($joinSuccess) {
                // 3. Tandai notifikasi sebagai SUDAH DIBACA
                markNotificationAsRead($notif_id, $this->conn);

                // 4. Redirect dengan pesan sukses
                header("Location: index.php?page=notification&success=joined");
                exit();
            }
        }

        // Jika gagal
        header("Location: index.php?page=notification&error=failed_join");
        exit();
    }

    /**
     * LOGIKA: TOLAK UNDANGAN
     */
    public function handleRejectInvite()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $notif_id = (int)($_GET['notif_id'] ?? 0);
        $user_id = $_SESSION['user_id'] ?? 0;

        $notif = getNotificationById($notif_id, $this->conn);

        // Validasi kepemilikan
        if ($notif && $notif['user_id'] == $user_id) {
            // Cukup tandai sebagai READ
            markNotificationAsRead($notif_id, $this->conn);
        }

        header("Location: index.php?page=notification&msg=rejected");
        exit();
    }
}
?>