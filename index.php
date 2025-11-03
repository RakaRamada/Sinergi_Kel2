<?php
// File: index.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------------
// AUTHENTICATION GUARD
// --------------------------------------------------------------------------
$is_logged_in = isset($_SESSION['user_id']);
$page = $_GET['page'] ?? ($is_logged_in ? 'dashboard' : 'login');
$public_pages = ['login', 'login-process', 'register', 'register-process', 'verify'];

// --- PERBAIKAN: Daftar halaman API (JSON) ---
// Halaman ini TIDAK BOLEH memuat HTML (header/footer)
$api_pages = ['store-message', 'check-new-messages', 'store-forum', 'join-forum'];
// ----------------------------------------

if (!$is_logged_in && !in_array($page, $public_pages)) {
    header("Location: index.php?page=login");
    exit();
}
if ($is_logged_in && ($page === 'login' || $page === 'register')) {
    header("Location: index.php?page=dashboard");
    exit();
}

// --------------------------------------------------------------------------
// --- PEROMBAKAN LAYOUT ---
// --------------------------------------------------------------------------

// 1. Panggil HEADER (HANYA jika bukan halaman publik DAN bukan halaman API)
if (!in_array($page, $public_pages) && !in_array($page, $api_pages)) {
    require_once 'app/views/partials/header.php';
}

// 2. ROUTER
switch ($page) {
    // --- Kasus Halaman Penuh (Login/Register) ---
    case 'login':
        require_once 'app/controllers/AuthController.php';
        showLogin(); 
        break;
    case 'login-process':
        require_once 'app/controllers/AuthController.php';
        doLogin(); 
        break;
    case 'register':
        require_once 'app/controllers/AuthController.php';
        showRegister(); 
        break;
    case 'register-process':
        require_once 'app/controllers/AuthController.php';
        doRegister();
        break;
    case 'verify':
        require_once 'app/controllers/VerifController.php';
        verify_email();
        break;
    case 'logout':
        require_once 'app/controllers/AuthController.php';
        logout();
        break;
        
    // --- Kasus Halaman API (HANYA JSON) ---
    case 'store-message':
        require_once 'app/controllers/MessageController.php';
        storeMessage();
        break;
    case 'check-new-messages':
        require_once 'app/controllers/MessageController.php';
        checkNewMessages();
        break;
    case 'store-forum':
        require_once 'app/controllers/ForumController.php';
        storeForum();
        break;
    case 'join-forum':
        require_once 'app/controllers/ForumController.php';
        handleJoinForum();
        break;
    case 'exit-forum':
        require_once 'app/controllers/ForumController.php';
        handleExitForum(); 
        break;
    case 'edit-forum': 
        require_once 'app/controllers/ForumController.php';
        showEditForm(); 
        break;
    case 'update-forum':
        require_once 'app/controllers/ForumController.php';
        handleUpdateForum(); 
        break;

    // --- Kasus Halaman Konten (Dashboard, Pesan, dll.) ---
    case 'dashboard':
        require_once 'app/controllers/HomeController.php';
        dashboard_view(); 
        break;
    case 'profile':
        require_once 'app/controllers/UserController.php';
        show_profile();
        break;
    case 'messages':
        require_once 'app/controllers/MessageController.php';
        showMessages();
        break;
    case 'settings':
        require_once 'app/views/settings.php';
        break;
    case 'create-forum':
        require_once 'app/controllers/ForumController.php';
        showCreateForm();
        break;
    case 'forum-details':
        require_once 'app/controllers/ForumController.php';
        showForumDetails(); // Kita akan buat fungsi ini
        break;
    case 'search':
        require_once 'app/controllers/SearchController.php';
        showSearchPage();
        break;
    case 'notification':
        require_once 'app/views/notification.php';
        break;
        
    default:
        if ($is_logged_in) {
            require_once 'app/controllers/HomeController.php';
            dashboard_view(); 
        } else {
            require_once 'app/controllers/AuthController.php';
            showLogin();
        }
        break;
}

// 3. Panggil FOOTER (HANYA jika bukan halaman publik DAN bukan halaman API)
if (!in_array($page, $public_pages) && !in_array($page, $api_pages)) {
    require_once 'app/views/partials/footer.php';
}
?>