<?php
// File: app/controllers/UserController.php

// Pastikan UserModel dipanggil
require_once __DIR__ . '/../models/UserModel.php';

/**
 * Fungsi untuk menampilkan halaman profil pengguna.
 */
function show_profile() {
    // Pastikan session sudah aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // 1. Tentukan user mana yang ingin dilihat
    // Prioritas: 1. Dari URL (?username=...), 2. Dari session pengguna yang sedang login
    $username_to_show = $_GET['username'] ?? ($_SESSION['username'] ?? null); 

    $user_profile = null; // Siapkan variabel untuk data profil

    if ($username_to_show) {
        // 2. Panggil fungsi dari UserModel untuk mendapatkan data user
        // Kita menggunakan getUserByUsername() karena kita mencarinya berdasarkan username
        $user_profile = getUserByUsername($username_to_show);
    }

    // 3. Jika user tidak ditemukan, tampilkan halaman error atau redirect
    if (!$user_profile) {
        // Jika tidak ada user_profile yang ditemukan, dan user tidak sedang login, 
        // arahkan ke login. Jika user sedang login tapi profilnya tidak ada (aneh), 
        // arahkan ke dashboard.
        if (!isset($_SESSION['user_id'])) {
             header('Location: index.php?page=login');
        } else {
             header('Location: index.php?page=dashboard&error=usernotfound');
        }
        exit();
    }

    // 4. Jika user ditemukan, muat view profile.php dan kirimkan data $user_profile
    // Variabel $user_profile akan bisa diakses di dalam profile.php
    require 'app/views/profile.php';
}

?>