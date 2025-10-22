<?php

// Fungsi ini akan dipanggil saat pengguna mengakses halaman utama atau ?page=dashboard
function dashboard_view() {
    // Di sini kita bisa menambahkan logika untuk memeriksa apakah user sudah login.
    // Jika belum, kita bisa redirect ke halaman login.
    // if (!isset($_SESSION['user_id'])) {
    //     header('Location: index.php?page=login');
    //     exit();
    // }

    // Jika sudah login, muat file view dashboard.php
    require 'app/views/dashboard.php';
}

// Fungsi default yang bisa kita gunakan untuk halaman utama
function index() {
    // Untuk saat ini, kita anggap halaman utama langsung menampilkan dashboard.
    dashboard_view();
}

?>