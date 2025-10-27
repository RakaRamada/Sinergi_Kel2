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
if (!isset($_SESSION['user']) && !in_array($page, $public_pages)) {
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

    // --- PERBAIKAN: TAMBAHKAN CASE UNTUK 'REGISTER' ---
    case 'register':
        // Langsung panggil view register, sesuai permintaan Anda
        // bahwa register.php sudah benar.
        require 'app/views/register.php'; 
        break;
    // --- AKHIR PERBAIKAN ---

    case 'process-login':
        require 'app/controllers/HomeController.php';
        process_login();
        break;

    case 'dashboard':
        require 'app/controllers/HomeController.php';
        dashboard_view();
        break;

    case 'post-detail':
        require 'app/controllers/PostController.php';
        show();
        break;

    case 'profile':
        require 'app/views/profile.php';
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