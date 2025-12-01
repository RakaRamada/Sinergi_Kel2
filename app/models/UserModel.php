<?php
// app/models/UserModel.php

class UserModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // --- FUNGSI SEARCH (TAMBAHAN BARU) ---
    public function searchUsers($keyword) {
        $keyword = "%" . strtolower($keyword) . "%";
        
        // Query Oracle case-insensitive
        $sql = "SELECT user_id, username, nama_lengkap, avatar_url, role_id, bio 
                FROM users 
                WHERE LOWER(username) LIKE :q OR LOWER(nama_lengkap) LIKE :q
                ORDER BY username ASC";
                
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':q', $keyword);
        
        if (!oci_execute($stmt)) {
            return [];
        }
        
        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = array_change_key_case($row, CASE_LOWER); // Biar mudah diakses ($row['username'])
            
            // Fix Avatar
            if (empty($row['avatar_url'])) {
                $row['avatar_url'] = '/Sinergi/public/assets/images/default_avatar.png';
            }
            
            $results[] = $row;
        }
        oci_free_statement($stmt);
        return $results;
    }

    // --- FITUR ANALITIK ---
    public function getUserGrowthAnalytics() {
        $sql = "SELECT 
                    TO_CHAR(created_at, 'YYYY-MM') AS bulan, 
                    role_id, 
                    COUNT(*) as total 
                FROM users 
                WHERE created_at >= ADD_MONTHS(SYSDATE, -12)
                GROUP BY TO_CHAR(created_at, 'YYYY-MM'), role_id
                ORDER BY bulan ASC";
                
        $stid = oci_parse($this->conn, $sql);
        oci_execute($stid);
        
        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    // --- FUNGSI EXISTING ---
    public function getUserByEmail($email) {
        $sql = "SELECT u.*, r.role_name 
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                WHERE u.email = :email";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':email', $email);

        if (!oci_execute($stmt)) return null;

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function getUserByUsername($username) {
        $sql = "SELECT u.*, r.role_name 
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                WHERE u.username = :username";
                
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':username', $username);

        if (!oci_execute($stmt)) return null;

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function createUser($data) {
        if ($this->getUserByEmail($data['email'])) return 'email_exists';
        if ($this->getUserByUsername($data['username'])) return 'username_exists';

        if (!empty($data['nim'])) {
            $sql_cek_nim = "SELECT COUNT(*) AS CNT FROM users WHERE nim = :nim";
            $stmt_nim = oci_parse($this->conn, $sql_cek_nim);
            oci_bind_by_name($stmt_nim, ":nim", $data['nim']);
            oci_execute($stmt_nim);
            $row_nim = oci_fetch_assoc($stmt_nim);
            if ($row_nim['CNT'] > 0) return 'nim_exists';
        }

        $sql_insert = "INSERT INTO users 
                       (username, nama_lengkap, email, password, role_id, verifikasi_kode, is_verif, created_at, nim) 
                       VALUES 
                       (:username, :nama, :email, :pass, :role_id, :token, 0, SYSTIMESTAMP, :nim)";
        
        $stmt = oci_parse($this->conn, $sql_insert);
        
        oci_bind_by_name($stmt, ":username", $data['username']);
        oci_bind_by_name($stmt, ":nama", $data['nama']);
        oci_bind_by_name($stmt, ":email", $data['email']);
        oci_bind_by_name($stmt, ":pass", $data['pass']);
        oci_bind_by_name($stmt, ":role_id", $data['role']);
        oci_bind_by_name($stmt, ":token", $data['token']);
        oci_bind_by_name($stmt, ":nim", $data['nim']);

        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            return 'success';
        } else {
            return 'db_error';
        }
    }
}
?>