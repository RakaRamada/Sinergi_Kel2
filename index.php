<?php
// Menampilkan semua error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Memulai sesi PHP di setiap halaman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------------
// AUTHENTICATION GUARD (PENJAGA AUTENTIKASI)
// --------------------------------------------------------------------------

// 1. Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);

// 2. Tentukan halaman yang diminta.
$page = $_GET['page'] ?? ($is_logged_in ? 'dashboard' : 'login');

// 3. Daftar halaman "publik" (boleh diakses tanpa login)
$public_pages = ['login', 'login-process', 'register', 'register-process', 'verify'];

// 4. Logika Penjaga
if (!$is_logged_in && !in_array($page, $public_pages)) {
    header("Location: index.php?page=login");
    exit();
}

if ($is_logged_in && ($page === 'login' || $page === 'register')) {
    header("Location: index.php?page=dashboard");
    exit();
}

// --------------------------------------------------------------------------
// ROUTER SEDERHANA
// --------------------------------------------------------------------------

// 5. Panggil controller yang sesuai
switch ($page) {
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
        
    // --- HALAMAN PRIVAT (SETELAH LOGIN) ---

    case 'dashboard':
        require_once 'app/controllers/HomeController.php';
        dashboard_view(); 
        break;

    case 'post-detail':
        require_once 'app/controllers/PostController.php'; 
        show();
        break;

    case 'profile':
        require_once 'app/controllers/UserController.php';
        show_profile();
        break;

    case 'search':
        require_once 'app/views/search.php'; // Sebaiknya lewat controller
        break;

    case 'notification':
        require_once 'app/views/notification.php'; // Sebaiknya lewat controller
        break;

    case 'messages':
        require_once 'app/controllers/MessageController.php';
        showMessages();
        break;

    // === TAMBAHAN BARU DI SINI ===
    case 'settings':
        // Langsung panggil view karena belum ada logika kompleks
        require_once 'app/views/settings.php';
        break;
    // === AKHIR TAMBAHAN ===

    case 'store-message':
        require_once 'app/controllers/MessageController.php';
        storeMessage();
        break;

    // === TAMBAHAN BARU UNTUK LONG POLLING ===
    case 'check-new-messages':
        require_once 'app/controllers/MessageController.php';
        checkNewMessages(); // Kita akan buat fungsi ini
        break;
    // === AKHIR TAMBAHAN ===
        
    default:
        // Jika 'page' tidak dikenali, arahkan ke halaman default yang aman
        if ($is_logged_in) {
            require_once 'app/controllers/HomeController.php';
            dashboard_view(); 
        } else {
            require_once 'app/controllers/AuthController.php';
            showLogin();
        }
        break;
}

?>