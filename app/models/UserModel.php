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
    $username    = $data['username'];
    $nama        = $data['nama'];
    $email       = $data['email'];
    $pass        = $data['pass'];
    $role        = $data['role'];
    $token       = $data['token'];
    $nomor_induk = isset($data['nomor_induk']) ? $data['nomor_induk'] : null;

    // Cek Email & Username Duplikat...
    if ($this->checkExists('email', $email)) return 'email_exists';
    if ($this->checkExists('username', $username)) return 'username_exists';
    // Opsional: Cek Nomor Induk Duplikat jika tidak null
    if ($nomor_induk && $this->checkExists('nomor_induk', $nomor_induk)) return 'nim_exists';

    // QUERY UPDATE: Tambahkan kolom nomor_induk
    $sql = "INSERT INTO users (
                username, nama_lengkap, email, password, role_id, verifikasi_kode, is_verif, nomor_induk
            ) VALUES (
                :p_username, :p_nama, :p_email, :p_pass, :p_role, :p_token, 0, :p_nomor_induk
            )";
    
    $stmt = oci_parse($this->conn, $sql);
    $role_int = (int)$role;

    // BIND VARIABLE (Sesuai request: pakai nama unik)
    oci_bind_by_name($stmt, ":p_username", $username);
    oci_bind_by_name($stmt, ':p_nama', $nama); 
    oci_bind_by_name($stmt, ':p_email', $email);
    oci_bind_by_name($stmt, ':p_pass', $pass);
    oci_bind_by_name($stmt, ':p_role', $role_int);
    oci_bind_by_name($stmt, ':p_token', $token);
    oci_bind_by_name($stmt, ':p_nomor_induk', $nomor_induk);

    // Eksekusi dengan COMMIT
    if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) return 'success';
    
    // Debugging error jika gagal (opsional)
    // $e = oci_error($stmt); var_dump($e); die();
    
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

    /**
     * Mengambil 5 user paling aktif berdasarkan jumlah postingan + komentar
     * Kecuali user yang sedang login
     */
    public function getTopActiveUsers($limit = 5, $excludeUserId = 0) {
        // [BARU] Pakai Function f_get_user_score
        // Gak perlu subquery (SELECT COUNT...) yang bikin pusing
        $sql = "SELECT 
                    user_id, username, nama_lengkap, avatar_url,
                    f_get_user_score(user_id) as activity_score
                FROM users 
                WHERE user_id != :p_exclude_id
                ORDER BY activity_score DESC
                FETCH FIRST :p_limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        
        $clean_exclude = (int)$excludeUserId;
        $clean_limit = (int)$limit;
        
        oci_bind_by_name($stmt, ':p_exclude_id', $clean_exclude);
        oci_bind_by_name($stmt, ':p_limit', $clean_limit);
        
        if (!oci_execute($stmt)) return [];

        $users = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $users[] = array_change_key_case($row, CASE_LOWER);
        }
        return $users;
    }

    public function setResetToken($email, $token) {
        $sql = "UPDATE users 
                SET reset_token = :token, 
                    token_expiry = SYSTIMESTAMP + INTERVAL '1' HOUR 
                WHERE UPPER(email) = UPPER(:email)";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":token", $token);
        oci_bind_by_name($stmt, ":email", $email);
        
        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);
        
        return $result;
    }

    public function getUserByToken($token) {
        $sql = "SELECT user_id, email, nama_lengkap 
                FROM users 
                WHERE reset_token = :token 
                AND token_expiry > SYSTIMESTAMP";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":token", $token);
        
        if (!oci_execute($stmt)) {
            oci_free_statement($stmt);
            return null;
        }
        
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function updateNewPassword($token, $newHash) {
        $sql = "UPDATE users 
                SET password = :pass, 
                    reset_token = NULL, 
                    token_expiry = NULL 
                WHERE reset_token = :token";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":pass", $newHash);
        oci_bind_by_name($stmt, ":token", $token);
        
        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);
        
        return $result;
    }
}


?>