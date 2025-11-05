<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'app/helpers/utils.php';

$page = $_GET['page'] ?? 'home';

// Halaman publik yang bisa diakses tanpa login
$public_pages = ['login', 'register', 'process-login'];

// Jika user belum login dan mencoba akses halaman non-publik, arahkan ke login
if (!isset($_SESSION['id_users']) && !in_array($page, $public_pages)) {
    header('Location: index.php?page=login');
    exit;
}

// ROUTER UTAMA
switch ($page) {
    case 'login':
        require 'app/controllers/HomeController.php';
        login_view();
        break;

    case 'register':
        require 'app/views/register.php'; 
        break;

    case 'process-login':
        require 'app/controllers/HomeController.php';
        process_login();
        break;

    case 'dashboard':
        require 'app/controllers/HomeController.php';
        dashboard_view();
        break;

    case 'post-detail':
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
        
    case 'search':
        require 'app/views/search.php';
        break;

    case 'notification':
        require 'app/views/notification.php';
        break;

    case 'messages':
        require 'app/views/messages.php';
        break;

    case 'logout':
        // Hapus semua data sesi & arahkan ke login page
        session_destroy();
        header('Location: index.php?page=login');
        exit;

    default:
        // Default: arahkan ke halaman utama (HomeController)
        require 'app/controllers/HomeController.php';
        index();
        break;
}
?>