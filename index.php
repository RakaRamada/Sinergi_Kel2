<?php
// File: index.php
// INTEGRATED & FULL OOP VERSION

// 1. CONFIG & BUFFER
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';
global $conn;

// 2. PAGE ROUTING
$page = $_GET['page'] ?? '';
$is_logged_in = isset($_SESSION['user_id']);
$role_id = $_SESSION['role_id'] ?? 0; // 5 = Admin

if (empty($page)) {
    $page = $is_logged_in ? 'dashboard' : 'landing';
}

// 3. WHITELISTS
$public_pages = [
    'landing', 'login', 'login-process', 'register', 'register-process', 
    'verify', 'verify-otp', 'verify-otp-process', 'resend-otp',
    'captcha', 'process-login', 'forgot-password', 
    'forgot-password-process', 
    'reset-password', 
    'reset-password-process'
];

$admin_pages = [
    'admin-dashboard', 'admin-detail-report', 'admin-profile', 'admin-analytics', 'admin-blacklist',
    'admin-api-reports', 'admin-api-process', 'admin-api-chart-data', 'admin-api-banned-users',
    'admin-api-unban-user', 'admin-api-delete-report', 'admin-api-purge-reports'
];

$api_pages = [
    'post-api', 'delete-post', 'api-like-forum-post', 
    'store-forum-post', 'api-delete-forum-post', 
    'api-get-forum-comments', 'api-store-forum-comment', 
    'api-delete-forum-comment',
    'store-message', 'check-new-messages', 'delete-message',
    'store-group', 'join-group', 'exit-group', 'update-group',
    'get-sidebar-updates', 'api-search-candidates',
    'admin-api-reports', 'admin-api-process', 'admin-api-chart-data',
    'captcha', 'accept-invite', 'reject-invite', 'read-notif', 'update_profile',
    'process-add-member', 'kick-member', 'process-request', 'process-invite-member',
    'api-delete-notif','api-clear-all-notif'
];

// Matikan error display untuk API
if (in_array($page, $api_pages) || strpos($page, 'api') !== false) {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// 4. AUTH GUARD
if (!$is_logged_in && !in_array($page, $public_pages)) {
    header("Location: index.php?page=login");
    exit();
}
if ($is_logged_in && in_array($page, ['login', 'register'])) {
    header("Location: index.php?page=" . ($role_id == 5 ? "admin-dashboard" : "dashboard"));
    exit();
}
if ($role_id != 5 && in_array($page, $admin_pages)) {
    header("Location: index.php?page=dashboard");
    exit();
}

// 5. LOAD & INSTANTIATE CONTROLLERS (FULL OOP)

// Auth & Verif
require_once 'app/controllers/AuthController.php';
$authController = new AuthController($conn);

require_once 'app/controllers/VerifController.php';
$verifController = new VerifController($conn);

// Posting & Profile
require_once 'app/controllers/PostingController.php';
$postingController = new PostingController($conn);

require_once 'app/controllers/profileController.php';
$profileController = new ProfileController($conn);

// --- NEW OOP CONTROLLERS (YANG KITA UBAH TADI) ---
require_once 'app/controllers/GroupController.php';
$groupController = new GroupController($conn);

require_once 'app/controllers/MessageController.php';
$msgController = new MessageController($conn);

require_once 'app/controllers/ForumController.php';
$forumController = new ForumController($conn);

require_once 'app/controllers/NotificationController.php';
$notifController = new NotificationController($conn);

require_once 'app/controllers/SearchController.php';
$searchController = new SearchController($conn);

require_once 'app/controllers/LandingController.php';
$landingController = new LandingController($conn);

// Admin (Jika Perlu)
if ($role_id == 5 || in_array($page, $admin_pages)) {
    if (file_exists('app/admin/controllers/adminController.php')) {
        require_once 'app/admin/controllers/adminController.php';
        $adminController = new AdminController($conn);
    }
}


// 6. HEADER VIEW
if (!in_array($page, $public_pages) && !in_array($page, $api_pages) && !in_array($page, $admin_pages)) {
    require_once 'app/views/partials/header.php';
}
// 7. ROUTING SWITCH
switch ($page) {

    // --- AUTHENTICATION ---
    case 'login':           $authController->showLogin(); break;
    case 'login-process':   $authController->doLogin(); break;
    case 'register':        $authController->showRegister(); break;
    case 'register-process':$authController->doRegister(); break;
    case 'logout':          $authController->logout(); break;
    case 'verify':          $verifController->verifyEmail(); break;
    case 'verify-otp':      $verifController->showOtpForm(); break;
    case 'verify-otp-process': $verifController->verifyOtp(); break;
    case 'resend-otp':      $verifController->resendOtp(); break;
    case 'landing':         $landingController->index(); break;

    // --- POSTING & DASHBOARD ---
    case 'home':
    case 'dashboard':       $postingController->showDashboard(); break;
    case 'post':
    case 'post-detail':     $postingController->showPostDetail(); break;
    case 'delete-post':     $postingController->deletePost(); break;

    // API Posting
    case 'post-api':
        $method = $_GET['method'] ?? '';
        switch ($method) {
            case 'getPostings': $postingController->getPostings(); break;
            case 'createPost':  $postingController->createPost(); break;
            case 'toggleLike':  $postingController->toggleLike(); break;
            case 'addComment':  $postingController->addComment(); break;
            case 'deleteComment': $postingController->deleteComment(); break;
            case 'deletePost':  $postingController->deletePost(); break;
            case 'addReport':   $postingController->addReport(); break;
            default: header('Content-Type: application/json'); echo json_encode(['status'=>'error']);
        }
        break;

    // --- PROFILE ---
    case 'profile':         $profileController->showProfile(); break;
    case 'edit_profile':    $profileController->showEditProfileForm(); break;
    case 'update_profile':  $profileController->processProfileUpdate(); break;

    // --- GROUPS (FULL OOP CALLS) ---
    case 'create-group':        $groupController->showCreateForm(); break;
    case 'store-group':         $groupController->storeGroup(); break;
    case 'join-group':          $groupController->handleJoinGroup(); break;
    case 'exit-group':          $groupController->handleExitGroup(); break;
    case 'edit-group':          $groupController->showEditForm(); break;
    case 'update-group':        $groupController->handleUpdateGroup(); break;
    case 'group-details':       $groupController->showGroupDetails(); break;
    
    // Group Members Management
    case 'add-member':          $groupController->showAddMemberForm(); break;
    case 'process-add-member':  $groupController->handleAddMemberProcess(); break;
    case 'kick-member':         $groupController->handleKickMember(); break;
    case 'process-request':     $groupController->handleGroupRequest(); break;
    case 'process-invite-member': $groupController->handleSendInvite(); break;
    case 'api-search-candidates': $groupController->searchCandidatesAPI(); break;
    case 'change-role': $groupController->handleRoleChange(); break;
    case 'delete-group-process': $groupController->handleDeleteGroup(); break;

    // --- MESSAGES / CHAT (FULL OOP CALLS) ---
    case 'messages':            $msgController->showMessages(); break;
    case 'store-message':       $msgController->storeMessage(); break;
    case 'check-new-messages':  $msgController->checkNewMessages(); break;
    case 'delete-message':      $msgController->deleteMessageController(); break;
    case 'get-sidebar-updates': $msgController->getSidebarUpdates(); break;

    // --- FORUM (FULL OOP CALLS) ---
    case 'forum-post-detail':       $forumController->showForumPostDetail(); break;
    case 'store-forum-post':        $forumController->storeForumPost(); break;
    case 'api-like-forum-post':     $forumController->apiLikeForumPost(); break;
    case 'api-delete-forum-post':   $forumController->apiDeleteForumPost(); break;
    case 'api-get-forum-comments':  $forumController->apiGetForumComments(); break;
    case 'api-store-forum-comment': $forumController->apiStoreForumComment(); break;
    case 'api-delete-forum-comment': $forumController->apiDeleteForumComment(); break;

    // --- NOTIFICATIONS (FULL OOP CALLS) ---
    case 'notification':    $notifController->showNotifications(); break;
    case 'accept-invite':   $notifController->handleAcceptInvite(); break;
    case 'reject-invite':   $notifController->handleRejectInvite(); break;
    case 'read-notif':      $notifController->processReadNotification(); break;
    case 'api-delete-notif': $notifController->apiDeleteNotification(); break;
    case 'api-clear-all-notif': $notifController->apiClearAllNotifications(); break;

    // --- SEARCH ---
    case 'search':          $searchController->showSearchPage(); break;
    
    // LUPA PASSWORD 
    case 'forgot-password':         $authController->showForgotPassword(); break;
    case 'forgot-password-process': $authController->doForgotPassword(); break;
    case 'reset-password':          $authController->showResetPassword(); break;
    case 'reset-password-process':  $authController->doResetPassword(); break;


    // --- ADMIN ---
    case 'admin-dashboard':     if(isset($adminController)) $adminController->dashboard(); break;
    case 'admin-analytics':     if(isset($adminController)) $adminController->analytics(); break;
    case 'admin-detail-report': if(isset($adminController)) $adminController->detailReport(); break;
    case 'admin-profile':       if(isset($adminController)) $adminController->profile(); break;
    case 'admin-api-reports':   if(isset($adminController)) $adminController->apiGetReports(); break;
    case 'admin-api-process':   if(isset($adminController)) $adminController->apiProcessReport(); break;
    case 'admin-api-chart-data':if(isset($adminController)) $adminController->apiGetChartData(); break;
    case 'admin-blacklist':     if(isset($adminController)) $adminController->blacklist(); break;
    case 'admin-api-banned-users': if(isset($adminController)) $adminController->apiGetBannedUsers(); break;
    case 'admin-api-unban-user':   if(isset($adminController)) $adminController->apiUnbanUser(); break;
    case 'admin-api-delete-report':if(isset($adminController)) $adminController->apiDeleteReport(); break;
    case 'admin-api-purge-reports':if(isset($adminController)) $adminController->apiPurgeReports(); break;

    // --- MISC ---
    case 'captcha':         
        require_once 'app/helpers/captcha.php'; 
        break;

    // --- DEFAULT ---
    default:
        header("Location: index.php?page=" . ($is_logged_in ? "dashboard" : "landing"));
        exit();
}

// 8. FOOTER VIEW
$pages_with_posting_script = ['dashboard', 'home'];
if (in_array($page, $pages_with_posting_script) && !in_array($page, $api_pages)) {
    require_once 'app/views/partials/postingan.php';
}

if (!in_array($page, $api_pages)) {
    ob_end_flush();
}
?>