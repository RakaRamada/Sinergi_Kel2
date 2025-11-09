<?php
// ==========================
// File: index.php (Merged)
// ==========================

// --- ERROR HANDLER ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- SESSION HANDLER ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------------
// AUTHENTICATION GUARD
// --------------------------------------------------------------------------
$is_logged_in = isset($_SESSION['user_id']) || isset($_SESSION['id_users']);
$page = $_GET['page'] ?? ($is_logged_in ? 'dashboard' : 'login');

// Halaman publik
$public_pages = ['login', 'login-process', 'register', 'register-process', 'verify', 'captcha', 'process-login'];

// Halaman API (tanpa header/footer)
$api_pages = ['store-message', 'check-new-messages', 'store-forum', 'join-forum', 'exit-forum', 'edit-forum', 'update-forum', 'captcha'];

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
    case 'process-login': // dari versi kamu
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


    // ------------------------
    // DASHBOARD & PROFILE
    // ------------------------
    case 'dashboard':
        require_once 'app/controllers/HomeController.php';
        dashboard_view();
        break;

    case 'profile':
        // gabungkan dua kemungkinan controller
        if (file_exists('app/controllers/profileController.php')) {
            require_once 'app/controllers/profileController.php';
            showProfile();
        } else {
            require_once 'app/controllers/UserController.php';
            show_profile();
        }
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

    case 'create-forum':
        require_once 'app/controllers/ForumController.php';
        showCreateForm();
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

    case 'forum-details':
        require_once 'app/controllers/ForumController.php';
        showForumDetails();
        break;


    // ------------------------
    // POSTING & SEARCH
    // ------------------------
    case 'post-detail':
        require_once 'app/controllers/PostingController.php';
        showPostDetail();
        break;

    case 'search':
        if (file_exists('app/controllers/SearchController.php')) {
            require_once 'app/controllers/SearchController.php';
            showSearchPage();
        } else {
            require_once 'app/views/search.php';
        }
        break;

    case 'notification':
        require_once 'app/views/notification.php';
        break;

    case 'settings':
        require_once 'app/views/settings.php';
        break;


    // ------------------------
    // DEFAULT
    // ------------------------
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

// --------------------------------------------------------------------------
// FOOTER (tidak ditampilkan untuk halaman publik & API)
// --------------------------------------------------------------------------
if (!in_array($page, $public_pages) && !in_array($page, $api_pages)) {
    require_once 'app/views/partials/footer.php';
}

?>
