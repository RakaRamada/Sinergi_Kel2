<?php
// File: app/controllers/SearchController.php

require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/UserModel.php';

function showSearchPage() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    $currentTab = $_GET['tab'] ?? 'group'; 
    $query = $_GET['q'] ?? ''; 
    $user_id = (int)($_SESSION['user_id'] ?? 0); 

    // MODIFIKASI: Default search (%%) jika query kosong
    // Agar user langsung melihat daftar grup saat membuka halaman
    $searchTerm = empty($query) ? '' : $query;

    $group_results = [];
    $user_results = [];

    if ($currentTab === 'group') {
        // Cari grup (Kalau kosong, dia akan menampilkan semua karena LIKE '%%')
        $group_results = searchGroups($searchTerm, $user_id); 
    } elseif ($currentTab === 'orang') {
        // Cari user
        $user_results = searchUsers($searchTerm); 
    }

    require 'app/views/search.php';
}
?>