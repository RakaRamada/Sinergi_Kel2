<?php
// app/controllers/HomeController.php

// INI WAJIB ADA DI ATAS (GLOBAL SCOPE)
require_once __DIR__ . '/../../config/koneksi.php';

function login_view() {
    // === PERBAIKAN: Tambahkan 'global $conn' ===
    // Ini agar $conn 'terlihat' di dalam fungsi ini
    // dan juga di dalam file view yang dipanggilnya (login.php).
    global $conn;

    // Tampilkan halaman login
    // session_start() sudah dipanggil di index.php
    require_once __DIR__ . '/../views/login.php';
}

function process_login() {
    // 1. Ambil koneksi Oracle
    global $conn; 

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        $password_input = $_POST['password'] ?? ''; // Ganti nama var agar tidak bentrok

        // Validasi input
        if (empty($email) || empty($password_input)) {
             $_SESSION['error'] = 'Email dan password tidak boleh kosong';
             header('Location: index.php?page=login');
             exit;
        }

        // 2. Query menggunakan OCI (Oracle)
        // (Menggunakan nama kolom dari tabel 'users' Anda)
        $sql = "SELECT id_users, username, nama_lengkap, password FROM users WHERE email = :email_bv";
        
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':email_bv', $email);
        
        if (!oci_execute($stmt)) {
             $e = oci_error($stmt);
             $_SESSION['error'] = 'Error database: ' . $e['message'];
             header('Location: index.php?page=login');
             exit;
        }
        
        $user_data = oci_fetch_assoc($stmt); // Ambil data user

        // 3. Verifikasi password dan set session
        // $user_data akan 'false' jika email tidak ditemukan
        // password_verify akan 'true' jika password cocok
        if ($user_data && password_verify($password_input, $user_data['PASSWORD'])) {
            
            // === INI KUNCI PERBAIKANNYA ===
            // Set session menggunakan 'id_users' agar sinkron
            $_SESSION['id_users'] = $user_data['ID_USERS'];
            $_SESSION['username'] = $user_data['USERNAME']; // Opsional, tapi berguna
            $_SESSION['nama_lengkap'] = $user_data['NAMA_LENGKAP']; // Opsional

            // Hapus session lama jika ada
            unset($_SESSION['user']);
            unset($_SESSION['error']);
            
            // BERSIHKAN
            oci_free_statement($stmt);
            oci_close($conn);

            header('Location: index.php?page=dashboard');
            exit;
            
        } else {
            // Email tidak ditemukan atau password salah
            $_SESSION['error'] = 'Email atau password salah';
            
            if($stmt) oci_free_statement($stmt);
            if($conn) oci_close($conn);
            
            header('Location: index.php?page=login');
            exit;
        }
    }
}

// Hapus 'session_start()' ganda dari sini

function dashboard_view() {
    // === PERBAIKAN KEDUA ===
    // Cek 'id_users', BUKAN 'user', agar sinkron dengan index.php
    if (!isset($_SESSION['id_users'])) {
        header('Location: index.php?page=login');
        exit();
    }
    require_once __DIR__ . '/../views/dashboard.php';
}

function index() {
    // Jika user membuka halaman utama, langsung arahkan ke dashboard
    dashboard_view();
}

?>

