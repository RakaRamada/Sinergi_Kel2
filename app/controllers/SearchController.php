<?php
// File: app/controllers/SearchController.php

// Panggil Model yang kita butuhkan
require_once __DIR__ . '/../models/ForumModel.php';
require_once __DIR__ . '/../models/UserModel.php';

/**
 * Fungsi utama untuk menampilkan halaman pencarian.
 */
function showSearchPage() {
    
    // 1. Ambil data dari URL
    $currentTab = $_GET['tab'] ?? 'forum'; 
    $query = $_GET['q'] ?? ''; 

    // 2. Siapkan variabel hasil
    $forum_results = [];
    $user_results = [];

    // 3. Hanya jalankan pencarian jika query TIDAK kosong
    if (!empty($query)) {
        
        // --- SEKARANG KITA AKTIFKAN ---
        // Cek tab mana yang aktif, lalu panggil fungsi Model yang sesuai
        if ($currentTab === 'forum') {
            $forum_results = searchForums($query); // Panggil dari ForumModel
        } elseif ($currentTab === 'orang') {
            $user_results = searchUsers($query); // Panggil dari UserModel
        }
        
    }

    // 4. Muat file view dan kirimkan semua data
    require 'app/views/search.php';
}
?>