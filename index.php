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