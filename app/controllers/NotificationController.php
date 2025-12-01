<?php
// File: app/controllers/NotificationController.php

require_once __DIR__ . '/../models/NotificationModel.php';
// KITA BUTUH INI untuk fungsi joinGroup()
require_once __DIR__ . '/../models/GroupModel.php'; 

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

    // 1. Ambil data notifikasi
    $notif = getNotificationById($notif_id);

    if ($notif && $notif['type'] == 'group_invite' && $notif['user_id'] == $user_id) {
        $group_id = $notif['related_group_id'];
        
        // 2. UPDATE STATUS MEMBER JADI 'ACTIVE'
        // Kita tidak perlu insert baru, cukup update status 'invited' -> 'active'
        // Kita bisa pakai fungsi joinGroup() yang sudah kita buat sebelumnya (karena dia punya logika update/upsert)
        
        $user_nama = $_SESSION['nama_lengkap'] ?? 'Seseorang';
        
        // Panggil fungsi joinGroup dengan status 'active'
        $joinSuccess = joinGroup($user_id, $group_id, $user_nama, null, 'active');

        if ($joinSuccess) {
            // 3. Tandai Notif Sudah Dibaca (Selesai)
            markNotificationAsRead($notif_id);
            
            // 4. Redirect ke chat grup
            header("Location: index.php?page=messages&group_id=$group_id&success=joined");
            exit();
        }
    }

    header("Location: index.php?page=notification&error=failed_join");
    exit();
}

function handleRejectInvite() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $notif_id = (int)($_GET['notif_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? 0;
    
    $notif = getNotificationById($notif_id);

    if ($notif && $notif['user_id'] == $user_id) {
        $group_id = $notif['related_group_id'];

        // 1. HAPUS DATA DARI GROUP_MEMBERS (Status Invited dihapus)
        // Kita perlu query delete manual atau pakai fungsi processJoinRequest('reject')
        // Mari kita panggil fungsi processJoinRequest di GroupModel biar praktis
        require_once __DIR__ . '/../models/GroupModel.php';
        processJoinRequest($user_id, $group_id, 'reject');

        // 2. Tandai Notif Read
        markNotificationAsRead($notif_id);
    }

    header("Location: index.php?page=notification&msg=rejected");
    exit();
}
?>