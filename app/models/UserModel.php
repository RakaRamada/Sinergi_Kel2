<?php
// File: app/models/UserModel.php
// VERSI FINAL: FULL OOP

class UserModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // --- FUNGSI PENCARIAN (FIX ERROR SEARCH) ---
    public function searchUsers($searchTerm) {
        $sql = "SELECT user_id, username, nama_lengkap, role_id, avatar_url 
                FROM users 
                WHERE UPPER(username) LIKE :term OR UPPER(nama_lengkap) LIKE :term";
                
        $termWildcard = '%' . strtoupper($searchTerm) . '%';
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':term', $termWildcard);

        if (!oci_execute($stmt)) return [];

        $users = [];
        while ($row = oci_fetch_assoc($stmt)) {
             $users[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $users;
    }

    // --- FUNGSI AUTH & USER DATA ---
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

    public function getUserById($user_id) {
        $sql = "SELECT u.*, r.role_name 
                FROM users u
                JOIN roles r ON u.role_id = r.role_id
                WHERE u.user_id = :uid";
        $stmt = oci_parse($this->conn, $sql);
        $cid = (int)$user_id;
        oci_bind_by_name($stmt, ':uid', $cid); 

        if (!oci_execute($stmt)) return null;
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function createUser($data) {
        // Unpack data
        $username = $data['username'];
        $nama     = $data['nama'];
        $email    = $data['email'];
        $pass     = $data['pass'];
        $role     = $data['role'];
        $token    = $data['token'];

        // Cek Email & Username Duplikat dulu...
        if ($this->checkExists('email', $email)) return 'email_exists';
        if ($this->checkExists('username', $username)) return 'username_exists';

        $sql = "INSERT INTO users (username, nama_lengkap, email, password, role_id, verifikasi_kode, is_verif) 
                VALUES (:username, :nama, :email, :pass, :role, :token, 0)";
        
        $stmt = oci_parse($this->conn, $sql);
        $role_int = (int)$role;

        oci_bind_by_name($stmt, ":username", $username);
        oci_bind_by_name($stmt, ':nama', $nama); 
        oci_bind_by_name($stmt, ':email', $email);
        oci_bind_by_name($stmt, ':pass', $pass);
        oci_bind_by_name($stmt, ':role', $role_int);
        oci_bind_by_name($stmt, ':token', $token);

        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) return 'success';
        return 'db_error';
    }

    public function verifyUserByToken($token) {
        $sql = "SELECT user_id, is_verif FROM users WHERE verifikasi_kode = :code";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":code", $token);
        oci_execute($stmt);
        $user = oci_fetch_assoc($stmt);
        
        if ($user) {
            if ($user['IS_VERIF'] == 1) return 'already_verified';
            
            $sqlUp = "UPDATE users SET is_verif = 1, verifikasi_kode = NULL WHERE verifikasi_kode = :code";
            $stmtUp = oci_parse($this->conn, $sqlUp);
            oci_bind_by_name($stmtUp, ":code", $token);
            if (oci_execute($stmtUp, OCI_COMMIT_ON_SUCCESS)) return 'success';
        }
        return 'invalid_or_expired';
    }

    // Helper Private
    private function checkExists($field, $value) {
        $sql = "SELECT COUNT(*) AS CNT FROM users WHERE UPPER($field) = UPPER(:val)";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':val', $value);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        return ($row['CNT'] > 0);
    }
}
?>