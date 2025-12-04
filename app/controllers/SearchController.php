<?php
// File: app/controllers/SearchController.php

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/UserModel.php'; 

class SearchController {
    
    private $conn;
    private $groupModel;
    private $userModel; 

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        
        // Inisialisasi Model sebagai Object
        $this->groupModel = new GroupModel($dbConnection);
        $this->userModel  = new UserModel($dbConnection);
    }

    public function showSearchPage() {
        $currentTab = $_GET['tab'] ?? 'group'; 
        $query = $_GET['q'] ?? ''; 
        
        // Ambil Data User Session
        $user_id = (int)($_SESSION['user_id'] ?? 0); 
        $role_id = (int)($_SESSION['role_id'] ?? 0); // <-- TAMBAHKAN INI

        $searchTerm = empty($query) ? '' : $query;

        $group_results = [];
        $user_results = [];

        if ($currentTab === 'group') {
            $group_results = $this->groupModel->searchGroups($searchTerm, $user_id); 
        } elseif ($currentTab === 'orang') {
            $user_results = $this->userModel->searchUsers($searchTerm); 
        }

        // Variabel $role_id akan otomatis terkirim ke view karena scope function
        require __DIR__ . '/../views/search.php';
    }
}
?>