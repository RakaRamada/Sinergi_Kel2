<?php
// File: app/controllers/NotificationController.php

require_once __DIR__ . '/../models/NotificationModel.php';
// KITA BUTUH INI untuk fungsi joinForum()
require_once __DIR__ . '/../models/ForumModel.php'; 

function showNotifications() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $data = getUserNotifications($user_id);
    
    $unread_list = $data['unread'];
    $read_list   = $data['read'];

    require 'app/views/notification.php';
}

function processReadNotification() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $notif_id = $_GET['id'] ?? 0;
    
    markNotificationAsRead($notif_id);
    
    $redirect_url = $_GET['redirect'] ?? 'index.php?page=notifications';
    header("Location: " . $redirect_url);
    exit();
}

/**
 * LOGIKA: TERIMA UNDANGAN
 */
function handleAcceptInvite() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $notif_id = (int)($_GET['notif_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? 0;

    // 1. Ambil data notifikasi untuk tau ID Forum-nya
    $notif = getNotificationById($notif_id);

    if ($notif && $notif['type'] == 'group_invite' && $notif['user_id'] == $user_id) {
        $forum_id = $notif['related_forum_id'];
        
        // 2. Masukkan user ke forum (Pakai fungsi dari ForumModel)
        // Kita pakai nama dari session untuk pesan sistem join
        $user_nama = $_SESSION['nama_lengkap'] ?? 'Seseorang';
        $joinSuccess = joinForum($user_id, $forum_id, $user_nama);

        if ($joinSuccess) {
            // 3. Jika berhasil join, tandai notifikasi sebagai SUDAH DIBACA
            // supaya tombol Terima/Tolak hilang
            markNotificationAsRead($notif_id);
            
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
function handleRejectInvite() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $notif_id = (int)($_GET['notif_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? 0;
    
    $notif = getNotificationById($notif_id);

    // Validasi kepemilikan
    if ($notif && $notif['user_id'] == $user_id) {
        // Cukup tandai sebagai READ (atau bisa juga dihapus permanen)
        // Di sini kita tandai read saja agar masuk histori
        markNotificationAsRead($notif_id);
    }

    header("Location: index.php?page=notification&msg=rejected");
    exit();
}
?>