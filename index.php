<?php
// ==========================
// File: index.php (FIXED)
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
$is_logged_in = isset($_SESSION['user_id']);
$role_id = $_SESSION['role_id'] ?? 0;
$page = $_GET['page'] ?? ($is_logged_in ? 'dashboard' : 'login');

// Halaman publik
$public_pages = ['login', 'login-process', 'register', 'register-process', 'verify', 'captcha'];

// Halaman API (JSON response only)
$api_pages = [
    'store-message', 'check-new-messages', 'delete-message', 
    'store-forum', 'join-forum', 'exit-forum', 'update-forum',
    'captcha', 'get-sidebar-updates', 'accept-invite', 'reject-invite', 
    'read-notif', 'api-search-candidates',
    'admin-api-reports', 'admin-api-process' // ADMIN API
];

// Halaman khusus admin
$admin_pages = ['admin-dashboard', 'admin-detail-report', 'admin-profile'];

// --------------------------------------------------------------------------
// AUTHENTICATION GUARD
// --------------------------------------------------------------------------
if (!$is_logged_in && !in_array($page, $public_pages)) {
    header("Location: index.php?page=login");
    exit();
}

// Redirect jika sudah login tapi akses halaman login/register
if ($is_logged_in && ($page === 'login' || $page === 'register')) {
    if ($role_id == 5) {
        header("Location: index.php?page=admin-dashboard");
    } else {
        header("Location: index.php?page=dashboard");
    }
    exit();
}

// PENTING: Cek akses admin pages
if (in_array($page, $admin_pages) && $role_id != 5) {
    header("Location: index.php?page=dashboard");
    exit();
}

// PENTING: Redirect admin jika akses halaman user
if ($role_id == 5 && !in_array($page, $admin_pages) && !in_array($page, $api_pages) && !in_array($page, $public_pages) && $page !== 'logout') {
    header("Location: index.php?page=admin-dashboard");
    exit();
}

// --------------------------------------------------------------------------
// HEADER (tidak ditampilkan untuk halaman publik, API & admin)
// --------------------------------------------------------------------------
if (!in_array($page, $public_pages) && !in_array($page, $api_pages) && !in_array($page, $admin_pages)) {
    require_once 'app/views/partials/header.php';
}

// --------------------------------------------------------------------------
// ROUTER UTAMA
// --------------------------------------------------------------------------

// Load AdminController untuk halaman admin
if (in_array($page, $admin_pages) || in_array($page, ['admin-api-reports', 'admin-api-process'])) {
    require_once 'app/admin/controllers/adminController.php';
    $adminController = new AdminController($conn);
}

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

    // ========================================
    // ADMIN ROUTES
    // ========================================
    case 'admin-dashboard':
        $adminController->dashboard();
        break;
    
    case 'admin-detail-report':
        $adminController->detailReport();
        break;
    
    case 'admin-profile':
        $adminController->profile();
        break;
    
    // ========== ADMIN API ROUTES ==========
    case 'admin-api-reports':
        $adminController->apiGetReports();
        break;
    
    case 'admin-api-process':
        $adminController->apiProcessReport();
        break;

    // ------------------------
    // DASHBOARD & PROFILE (USER)
    // ------------------------
    case 'home':
    case 'dashboard':
        require_once 'app/controllers/PostingController.php';        
        $postingController = new PostingController($conn);
        $postingController->showDashboard();
        break;

    case 'post-detail':
    case 'post':
        require_once 'app/controllers/PostingController.php';        
        $postingController = new PostingController($conn);
        $postingController->showPostDetail();
        break;

    case 'profile':
        require_once 'app/controllers/profileController.php';
        $profileController = new ProfileController($conn);
        $profileController->showProfile();
        break;

    case 'edit_profile':
        require_once 'app/controllers/profileController.php';
        $profileController = new ProfileController($conn);
        $profileController->showEditProfileForm();
        break;

    case 'update_profile':
        require_once 'app/controllers/profileController.php';
        $profileController = new ProfileController($conn);
        $profileController->processProfileUpdate();
        break;

    // ------------------------
    // FORUM & MESSAGE
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

    case 'get-sidebar-updates':
        require_once 'app/controllers/MessageController.php';
        getSidebarUpdates();
        break;

    case 'add-member':
        require_once 'app/controllers/ForumController.php';
        showAddMemberForm();
        break;

    case 'process-add-member':
        require_once 'app/controllers/ForumController.php';
        handleAddMemberProcess();
        break;

    case 'kick-member':
        require_once 'app/controllers/ForumController.php';
        handleKickMember();
        break;

    case 'api-search-candidates':
        require_once 'app/controllers/ForumController.php';
        searchCandidatesAPI();
        break;
        
    case 'create-forum':
        require_once 'app/controllers/ForumController.php';
        showCreateForm();
        break;

    case 'forum-details':
        require_once 'app/controllers/ForumController.php';
        showForumDetails();
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
        showNotifications();
        break;

    case 'accept-invite':
        require_once 'app/controllers/NotificationController.php';
        handleAcceptInvite();
        break;

    case 'reject-invite':
        require_once 'app/controllers/NotificationController.php';
        handleRejectInvite();
        break;

    case 'read-notif':
        require_once 'app/controllers/NotificationController.php';
        processReadNotification();
        break;

    case 'settings':
        require_once 'app/views/settings.php';
        break;

    default:
        if ($is_logged_in) {
            if ($role_id == 5) {
                header("Location: index.php?page=admin-dashboard");
            } else {
                header("Location: index.php?page=dashboard");
            }
        } else {
            header("Location: index.php?page=login");
        }
        exit();
}

// --------------------------------------------------------------------------
// FOOTER (tidak ditampilkan untuk halaman publik, API & admin)
// --------------------------------------------------------------------------
if (!in_array($page, $public_pages) && !in_array($page, $api_pages) && !in_array($page, $admin_pages)) {
    require_once 'app/views/partials/postingan.php';
}
?>