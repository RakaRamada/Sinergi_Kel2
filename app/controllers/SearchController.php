<?php
// File: app/controllers/SearchController.php

// Panggil Model yang dibutuhkan
require_once __DIR__ . '/../models/ForumModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class SearchController
{
    private $conn;
    private $userModel;

    public function __construct($conn)
    {
        $this->conn = $conn;
        // Inisialisasi UserModel sebagai Object
        $this->userModel = new UserModel($this->conn);
    }

    /**
     * Fungsi utama untuk menampilkan halaman pencarian.
     */
    public function showSearchPage()
    {
        // 1. Ambil parameter dari URL
        $currentTab = $_GET['tab'] ?? 'forum';
        $query = $_GET['q'] ?? '';

        // 2. Siapkan variabel penampung hasil
        $forum_results = [];
        $user_results = [];

        // 3. Hanya jalankan pencarian jika query TIDAK kosong
        if (!empty($query)) {

            // --- TAB FORUM ---
            if ($currentTab === 'forum') {
                $raw_forums = [];

                // Cek ketersediaan fungsi searchForums (Baik Procedural maupun OOP)
                if (function_exists('searchForums')) {
                    $raw_forums = searchForums($query, $this->conn);
                } 
                elseif (class_exists('ForumModel')) {
                    $forumModel = new ForumModel($this->conn);
                    if (method_exists($forumModel, 'searchForums')) {
                        $raw_forums = $forumModel->searchForums($query);
                    }
                }

                // PERBAIKAN UTAMA DISINI: Mapping NAMA_FORUM -> JUDUL
                foreach ($raw_forums as $f) {
                    // Ubah semua key jadi huruf kecil (NAMA_FORUM -> nama_forum)
                    $item = array_change_key_case($f, CASE_LOWER);
                    
                    // Buat key 'judul' yang mengambil isi dari 'nama_forum'
                    // Ini mengatasi error "Undefined array key 'judul'"
                    $item['judul'] = $item['nama_forum'] ?? $item['judul'] ?? '(Tanpa Judul)';
                    
                    // Pastikan deskripsi juga aman
                    $item['deskripsi'] = $item['deskripsi'] ?? '';

                    $forum_results[] = $item;
                }
            } 
            
            // --- TAB ORANG ---
            elseif ($currentTab === 'orang') {
                // Panggil method searchUsers dari Object userModel
                // (Ini memperbaiki error undefined function searchUsers sebelumnya)
                $user_results = $this->userModel->searchUsers($query);
            }

        }

        // 4. Load View
        require 'app/views/search.php';
    }
}
?>