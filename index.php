<?php
// TAMBAHKAN DUA BARIS INI DI PALING ATAS
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'app/helpers/utils.php';

// ... sisa kode router Anda ...
?>

<?php
// Memulai sesi PHP di setiap halaman. Ini penting untuk mengelola status login pengguna.

// --------------------------------------------------------------------------
// ROUTER SEDERHANA
// --------------------------------------------------------------------------

// 1. Ambil parameter 'page' dari URL. 
// Contoh: http://localhost/Sinergi/?page=login
// Jika tidak ada parameter 'page', kita anggap pengguna ingin ke halaman 'home'.
$page = $_GET['page'] ?? 'home';

// 2. Gunakan switch statement untuk memanggil controller yang sesuai.
// Ini seperti resepsionis yang mengarahkan tamu ke ruangan yang benar.
switch ($page) {
    case 'login':
        // Jika URL-nya ?page=login, panggil AuthController untuk menampilkan form login.
        require 'app/controllers/AuthController.php';
        login_view(); // Panggil fungsi untuk menampilkan view login
        break;

    case 'process-login':
        // Jika pengguna men-submit form login, panggil fungsi untuk memprosesnya.
        require 'app/controllers/AuthController.php';
        process_login(); // Panggil fungsi untuk memproses data login
        break;

    case 'dashboard':
        // Jika URL-nya ?page=dashboard, panggil HomeController.
        require 'app/controllers/HomeController.php';
        dashboard_view(); // Panggil fungsi untuk menampilkan view dashboard
        break;

    // ... di dalam switch ($page) ...
    case 'post-detail':
    require 'app/controllers/PostController.php'; // Kita akan buat file ini nanti
    show(); // Panggil fungsi untuk menampilkan detail postingan
    break;

    // Anda bisa menambahkan case lain di sini, misalnya 'profile', 'logout', dll.
    case 'profile':
    // Kita akan buat PostController nanti, untuk sekarang cukup view
    require 'app/views/profile.php';
    break;

    case 'search':
    require 'app/views/search.php';
    break;

    case 'notification':
        require 'app/views/notification.php';
        break;

    // CASE BARU UNTUK HALAMAN PESAN
    case 'messages':
        require 'app/views/messages.php';
        break;

    default:
        // Jika parameter 'page' tidak ada atau tidak dikenali (misalnya halaman utama),
        // kita arahkan ke HomeController.
        require 'app/controllers/HomeController.php';
        index(); // Panggil fungsi default (misalnya, cek login lalu arahkan ke dashboard)
        break;
}

?>