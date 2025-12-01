<?php
// ==========================
// File: index.php (UPDATED)
// ==========================

// CRITICAL: Aktifkan output buffering SEJAK AWAL
ob_start();

require_once __DIR__ . '/config/koneksi.php';
global $conn;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// --- ERROR HANDLER ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- SESSION HANDLER ---


// --- ERROR HANDLER ---
$page = $_GET['page'] ?? '';

// Tentukan apakah ini halaman API
$is_api_request = (
    $page === 'post-api' || 
    $page === 'delete-post' ||
    strpos($page, '-api') !== false || 
    (isset($_GET['method']) && !empty($_GET['method']))
);

// Matikan display error untuk API agar tidak merusak JSON
if ($is_api_request) {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// --- SESSION HANDLER ---
$is_logged_in = isset($_SESSION['user_id']);
$role_id = $_SESSION['role_id'] ?? 0;

// Default page
if (empty($page)) {
    $page = $is_logged_in ? 'dashboard' : 'login';
}

// Halaman publik
$public_pages = ['login', 'login-process', 'register', 'register-process', 'verify', 'captcha'];

// Halaman API (JSON response only)
$api_pages = [
    'post-api',
    'delete-post', 
    'store-message', 
    'check-new-messages', 
    'delete-message',
    'store-forum', 
    'join-forum', 
    'exit-forum', 
    'update-forum',
    'captcha', 
    'get-sidebar-updates', 
    'accept-invite', 
    'reject-invite',
    'read-notif', 
    'api-search-candidates',
    'admin-api-reports', 
    'admin-api-process',
    'admin-api-chart-data' // <--- ROUTE BARU
];

$admin_pages = ['admin-dashboard', 'admin-detail-report', 'admin-profile', 'admin-analytics'];

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
if (!$is_logged_in && !in_array($page, $public_pages) && !in_array($page, $api_pages)) {
    header("Location: index.php?page=login");
    exit();
}

if ($is_logged_in && ($page === 'login' || $page === 'register')) {
    header("Location: index.php?page=" . ($role_id == 5 ? "admin-dashboard" : "dashboard"));
    exit();
}

if (in_array($page, $admin_pages) && $role_id != 5) {
    header("Location: index.php?page=dashboard");
    exit();
}

// --------------------------------------------------------------------------
// HEADER (tidak ditampilkan untuk halaman publik & API)
// --------------------------------------------------------------------------
if (!in_array($page, $public_pages) && !in_array($page, $api_pages)) {
if ($role_id == 5 && !in_array($page, $admin_pages) && !in_array($page, $api_pages) && !in_array($page, $public_pages) && $page !== 'logout') {
    header("Location: index.php?page=admin-dashboard");
    exit();
}

// --------------------------------------------------------------------------
// LOAD CONTROLLERS
// --------------------------------------------------------------------------

require_once 'app/controllers/AuthController.php';
$authController = new AuthController($conn);

require_once 'app/controllers/VerifController.php';
$verifController = new VerifController($conn);

require_once 'app/controllers/NotificationController.php';
$notificationController = new NotificationController($conn);

require_once 'app/controllers/SearchController.php';
$searchController = new SearchController($conn);

// Load Admin Controller
if (in_array($page, $admin_pages) || in_array($page, ['admin-api-reports', 'admin-api-process', 'admin-api-chart-data'])) {
    require_once 'app/admin/controllers/adminController.php';
    $adminController = new AdminController($conn);
}

// Posting Controller
$postingController = null;
if (!in_array($page, $public_pages) && !in_array($page, $admin_pages)) {
    require_once 'app/controllers/PostingController.php';
    $postingController = new PostingController($conn);
}

// Profile Controller
if (strpos($page, 'profile') !== false) {
    require_once 'app/controllers/profileController.php';
    $profileController = new ProfileController($conn);
}

// --------------------------------------------------------------------------
// API ROUTES
// --------------------------------------------------------------------------

if ($page === 'delete-post' && $postingController) {
    $postingController->deletePost();
    exit; 
}

if ($page === 'post-api' && $postingController) {
    $method = $_GET['method'] ?? '';
    switch ($method) {
        case 'getPostings': $postingController->getPostings(); break;
        case 'createPost': $postingController->createPost(); break;
        case 'toggleLike': $postingController->toggleLike(); break;
        case 'addComment': $postingController->addComment(); break;
        case 'deleteComment': $postingController->deleteComment(); break;
        case 'deletePost': $postingController->deletePost(); break;
        case 'addReport': $postingController->addReport(); break;
        default: 
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Method not found']);
    }
    exit;
}

// --------------------------------------------------------------------------
// HEADER
// --------------------------------------------------------------------------
if (!in_array($page, $public_pages) && !in_array($page, $api_pages) && !in_array($page, $admin_pages)) {
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

switch ($page) {
    // AUTH
    case 'login': $authController->showLogin(); break;
    case 'login-process': $authController->doLogin(); break;
    case 'captcha': require_once 'app/helpers/captcha.php'; break;
    case 'register': $authController->showRegister(); break;
    case 'register-process': $authController->doRegister(); break;
    case 'verify': $verifController->verifyEmail(); break;
    case 'logout': $authController->logout(); break;

    // ADMIN
    case 'admin-dashboard': $adminController->dashboard(); break;
    case 'admin-analytics': $adminController->analytics(); break;
    case 'admin-detail-report': $adminController->detailReport(); break;
    case 'admin-profile': $adminController->profile(); break;

    // API ADMIN
    case 'admin-api-reports': $adminController->apiGetReports(); break;
    case 'admin-api-process': $adminController->apiProcessReport(); break;
    
    // NEW API CHART
    case 'admin-api-chart-data': $adminController->apiGetChartData(); break;

    // USER POSTING
    case 'home':
    case 'dashboard':
        $postingController->showDashboard();
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
        $postingController->showPostDetail(); 
        break;

    // PROFILE
    case 'profile':
        if ($profileController) $profileController->showProfile();
        break;
    case 'edit_profile':
        if ($profileController) $profileController->showEditProfileForm();
        break;
    case 'update_profile':
        if ($profileController) $profileController->processProfileUpdate();
        break;

    // NOTIFICATION & SEARCH
    case 'search': $searchController->showSearchPage(); break;
    case 'notification': $notificationController->showNotifications(); break;
    case 'accept-invite': $notificationController->handleAcceptInvite(); break;
    case 'reject-invite': $notificationController->handleRejectInvite(); break;
    case 'read-notif': $notificationController->processReadNotification(); break;
    case 'settings': require_once 'app/views/settings.php'; break;

    default:
        header("Location: index.php?page=" . ($is_logged_in ? "dashboard" : "login"));
        exit();
}

// --------------------------------------------------------------------------
// FOOTER SCRIPT
// --------------------------------------------------------------------------
$pages_with_posting_script = ['dashboard', 'home'];
if (in_array($page, $pages_with_posting_script)) {
    require_once 'app/views/partials/postingan.php';
}

if (!in_array($page, $api_pages)) {
    ob_end_flush();
}
?>