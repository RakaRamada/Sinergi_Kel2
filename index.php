<?php
// --------------------------------------------------------------------------
// KONFIGURASI ERROR & SESSION
// --------------------------------------------------------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'app/helpers/utils.php';

// --------------------------------------------------------------------------
// PENGATURAN HALAMAN & AKSES LOGIN
// --------------------------------------------------------------------------

// Ambil parameter "page" dari URL, default ke "home" jika tidak ada
$page = $_GET['page'] ?? 'home';

// Halaman publik yang bisa diakses tanpa login
$public_pages = ['login', 'register', 'process-login'];

// Jika user belum login dan mencoba akses halaman non-publik, arahkan ke login
if (!isset($_SESSION['id_users']) && !in_array($page, $public_pages)) {
    header('Location: index.php?page=login');
    exit;
}

// --------------------------------------------------------------------------
// ROUTER UTAMA
// --------------------------------------------------------------------------

// Router berfungsi seperti pengarah lalu lintas: 
// menentukan controller/view mana yang harus dipanggil berdasarkan nilai $page.
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

    // === PERBAIKAN: Gunakan require_once dan nama file yang benar (huruf besar 'P') ===
    case 'profile':
        require_once 'app/controllers/ProfileController.php';
        showProfile();
        break;
    // === AKHIR PERBAIKAN ===

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