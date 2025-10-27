<?php
// INI WAJIB ADA DI ATAS (GLOBAL SCOPE)
require_once __DIR__ . '/../../config/koneksi.php';

function login_view() {
    // INI WAJIB ADA DI DALAM FUNGSI (FUNCTION SCOPE)
    global $conn;

    // Baris ini sekarang akan aman karena $conn sudah ada
    require_once __DIR__ . '/../views/login.php';
}

function process_login() {
    // Logika MySQL Anda yang belum terpakai
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        global $koneksi; // Ini seharusnya $conn jika Anda mau pakai OCI

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Error: Ini mysqli, bukan oci
        $query = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email' LIMIT 1"); 
        $user = mysqli_fetch_assoc($query);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header('Location: index.php?page=dashboard');
            exit;
        } else {
            $_SESSION['error'] = 'Email atau password salah';
            header('Location: index.php?page=login');
            exit;
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function dashboard_view() {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php?page=login');
        exit();
    }
    require_once __DIR__ . '/../views/dashboard.php';
}

function index() {
    dashboard_view();
}

?>