<?php
// ==========================
// File: index.php (Merged)
// ==========================
require_once __DIR__ . '/config/koneksi.php';
global $conn;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// --- ERROR HANDLER ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- SESSION HANDLER ---


// --------------------------------------------------------------------------
// AUTHENTICATION GUARD
// --------------------------------------------------------------------------
$is_logged_in = isset($_SESSION['user_id']);
$page = $_GET['page'] ?? ($is_logged_in ? 'dashboard' : 'login');

// Halaman publik
$public_pages = ['login', 'login-process', 'register', 'register-process', 'verify', 'captcha', 'process-login'];

// --- PERBAIKAN: Daftar halaman API (JSON) ---
// Halaman ini TIDAK BOLEH memuat HTML (header/footer)
$api_pages = [
    'store-message', 'check-new-messages', 'delete-message', 
    'store-group', 'join-group', 'exit-group', 'update-group',
    'captcha', 'get-sidebar-updates', 'accept-invite', 'reject-invite', 
    'read-notif','api-search-candidates', 'api-like-forum-post', 'store-forum-post', 'api-delete-forum-post','api-get-forum-comments',
    'api-store-forum-comment','api-delete-forum-comment',
    'update_profile'
];

if (!$is_logged_in && !in_array($page, $public_pages)) {
    header("Location: index.php?page=login");
    exit();
}
if ($is_logged_in && ($page === 'login' || $page === 'register')) {
    header("Location: index.php?page=dashboard");
    exit();
}

// --------------------------------------------------------------------------
// HEADER (tidak ditampilkan untuk halaman publik & API)
// --------------------------------------------------------------------------
if (!in_array($page, $public_pages) && !in_array($page, $api_pages)) {
    require_once 'app/views/partials/header.php';
}

// --------------------------------------------------------------------------
// ROUTER UTAMA
// --------------------------------------------------------------------------
switch ($page) {

    // ------------------------
    // AUTH & LOGIN REGISTER
    // ------------------------
    case 'login':
        require_once 'app/controllers/AuthController.php';
        showLogin();
        break;

    case 'login-process':
        require_once 'app/controllers/AuthController.php';
        doLogin();
        break;
        
    case 'captcha':
        require_once 'app/helpers/captcha.php';
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


    // ------------------------
    // DASHBOARD & PROFILE
    // ------------------------
    case 'home':
    case 'dashboard':
        require_once 'app/controllers/PostingController.php';        
        showDashboard();
        break;

    case 'post-detail':
    case 'post':
        require_once 'app/controllers/PostingController.php';        
        showPostDetail();
        break;

    case 'profile':
        require_once 'app/controllers/profileController.php';
        showProfile();
        break;

    case 'edit_profile':
        require_once 'app/controllers/profileController.php';
        showEditProfileForm();
        break;

    case 'update_profile':
        require_once 'app/controllers/profileController.php';
        processProfileUpdate();
        break;

    // ------------------------
    // GROUP & MESSAGE
    // ------------------------
    case 'messages':
        require_once 'app/controllers/MessageController.php';
        showMessages();
        break;

    case 'store-message':
        require_once 'app/controllers/MessageController.php';
        storeMessage();
        break;

    case 'check-new-messages':
        require_once 'app/controllers/MessageController.php';
        checkNewMessages();
        break;
    case 'delete-message':
        require_once 'app/controllers/MessageController.php';
        deleteMessageController(); 
        break;
    case 'store-group':
        require_once 'app/controllers/GroupController.php';
        storeGroup();
        break;
    case 'join-group':
        require_once 'app/controllers/GroupController.php';
        handleJoinGroup();
        break;
    case 'exit-group':
        require_once 'app/controllers/GroupController.php';
        handleExitGroup(); 
        break;
    case 'edit-group': 
        require_once 'app/controllers/GroupController.php';
        showEditForm(); 
        break;
    case 'update-group':
        require_once 'app/controllers/GroupController.php';
        handleUpdateGroup(); 
        break;
    case 'get-sidebar-updates':
        require_once 'app/controllers/MessageController.php';
        getSidebarUpdates();
        break;
    case 'add-member':
        require_once 'app/controllers/GroupController.php';
        showAddMemberForm();
        break;

    case 'process-add-member':
        require_once 'app/controllers/GroupController.php';
        handleAddMemberProcess();
        break;

    case 'kick-member':
        require_once 'app/controllers/GroupController.php';
        handleKickMember();
        break;
    case 'api-search-candidates':
        require_once 'app/controllers/GroupController.php';
        searchCandidatesAPI();
        break;
        
    case 'create-group':
        require_once 'app/controllers/GroupController.php';
        showCreateForm();
        break;

    case 'store-group':
        require_once 'app/controllers/GroupController.php';
        storeGroup();
        break;

    case 'join-group':
        require_once 'app/controllers/GroupController.php';
        handleJoinGroup();
        break;

    case 'exit-group':
        require_once 'app/controllers/GroupController.php';
        handleExitGroup();
        break;

    case 'edit-group':
        require_once 'app/controllers/GroupController.php';
        showEditForm();
        break;

    case 'update-group':
        require_once 'app/controllers/GroupController.php';
        handleUpdateGroup();
        break;

    case 'group-details':
        require_once 'app/controllers/GroupController.php';
        showGroupDetails();
        break;
    
    case 'process-request':
        require_once 'app/controllers/GroupController.php';
        handleGroupRequest();
        break;

        
        // 2. Tambahkan Case Baru
    case 'store-forum-post':
        require_once 'app/controllers/ForumController.php';
        handleStoreForumPost();
        break;
            
    case 'api-like-forum-post':
        require_once 'app/controllers/ForumController.php';
        handleLikeForumPostAPI();
        break;
        
    case 'api-delete-forum-post':
        require_once 'app/controllers/ForumController.php';
        handleDeleteForumPostAPI();
        break;
        
    case 'forum-post-detail':
        require_once 'app/controllers/ForumController.php';
        showForumPostDetail(); // Panggil fungsi controller baru
        break;
    
        case 'api-get-forum-comments':
        require_once 'app/controllers/ForumController.php';
        handleGetForumCommentsAPI();
        break;

    case 'api-store-forum-comment':
        require_once 'app/controllers/ForumController.php';
        handleStoreForumCommentAPI();
        break;

    case 'api-delete-forum-comment':
        require_once 'app/controllers/ForumController.php';
        handleDeleteForumCommentAPI();
        break;

    case 'process-invite-member': // Admin kirim invite (tetap di GroupController)
        require_once 'app/controllers/GroupController.php';
        handleSendInvite();
        break;

    case 'accept-invite': // User terima (Pindah ke NotificationController)
        require_once 'app/controllers/NotificationController.php';
        handleAcceptInvite();
        break;

    case 'reject-invite': // User tolak (Pindah ke NotificationController)
        require_once 'app/controllers/NotificationController.php';
        handleRejectInvite();
        break;


    // ------------------------
    // POSTING & SEARCH
    // ------------------------

    case 'search':
        if (file_exists('app/controllers/SearchController.php')) {
            require_once 'app/controllers/SearchController.php';
            showSearchPage();
        } else {
            require_once 'app/views/search.php';
        }
        break;
    case 'notification':
        require_once 'app/controllers/NotificationController.php';
        showNotifications(); // Panggil fungsi di Controller
        break;

    // Tambahan Rute untuk Aksi Tombol Invite
    case 'accept-invite':
        require_once 'app/controllers/NotificationController.php';
        handleAcceptInvite(); // Nanti kita buat fungsi ini
        break;

    case 'reject-invite':
        require_once 'app/controllers/NotificationController.php';
        handleRejectInvite(); // Nanti kita buat fungsi ini
        break;
    case 'read-notif':
        require_once 'app/controllers/NotificationController.php';
        processReadNotification(); // Fungsi baru
        break;

    case 'settings':
        require_once 'app/views/settings.php';
        break;


    // ------------------------
    // DEFAULT
    // ------------------------
    // default:
    //     if ($is_logged_in) {
    //         require_once 'app/controllers/PostingController.php';
    //         showDashboard();
    //     } else {
    //         require_once 'app/controllers/AuthController.php';
    //         showLogin();
    //     }
    //     break;
}

// --------------------------------------------------------------------------
// FOOTER (tidak ditampilkan untuk halaman publik & API)
// --------------------------------------------------------------------------
if (!in_array($page, $public_pages) && !in_array($page, $api_pages)) {
    require_once 'app/views/partials/postingan.php';
}
?>