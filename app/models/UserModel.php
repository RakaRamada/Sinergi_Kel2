<?php
// File: app/models/UserModel.php (VERSI PERBAIKAN FINAL)

/**
 * Ambil data user berdasarkan email
 */
function getUserByEmail($email)
{
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getUserByEmail.');
        return null;
    }

    // Perbaikan: Ubah 'u.is_verified' menjadi 'u.is_verif'
    $sql = "SELECT u.*, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.email = :email";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':email', $email);

    if (!oci_execute($stmt)) {
        error_log('Error getUserByEmail: ' . oci_error($stmt)['message']);
        @oci_close($conn);
        return null;
    }

    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    @oci_close($conn);
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}

/**
 * Ambil data user berdasarkan username
 */
function getUserByUsername($username)
{
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getUserByUsername.');
        return null;
    }

    $sql = "SELECT u.*, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = :uname";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':uname', $username);

    if (!oci_execute($stmt)) {
        error_log('Error getUserByUsername: ' . oci_error($stmt)['message']);
        @oci_close($conn);
        return null;
    }

    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    @oci_close($conn);
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}

/**
 * Ambil data user berdasarkan ID
 */
function getUserById($user_id)
{
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getUserById.');
        return null;
    }

    $sql = "SELECT u.user_id, u.nama_lengkap, u.username, r.role_name 
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = :uid";
            
    $stmt = oci_parse($conn, $sql);
    
    $clean_id = (int)$user_id;
    oci_bind_by_name($stmt, ':uid', $clean_id, -1, SQLT_INT); 

    if (!oci_execute($stmt)) {
        error_log('Error getUserById: ' . oci_error($stmt)['message']);
        @oci_close($conn);
        return null;
    }

    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    @oci_close($conn);
    return $row ? array_change_key_case($row, CASE_LOWER) : null;
}

/**
 * Tambahkan user baru ke database
 */
function createUser($username, $nama_lengkap, $email, $password_hash, $role_id, $token) 
{
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di createUser.');
        return 'db_error';
    }

    // 1. Cek Email (Case-Insensitive)
    $sql_check_email = "SELECT COUNT(*) AS CNT FROM users WHERE UPPER(email) = :email_upper";
    $stmt_check_email = oci_parse($conn, $sql_check_email);
    $email_upper = strtoupper($email);
    oci_bind_by_name($stmt_check_email, ':email_upper', $email_upper);
    
    if (!oci_execute($stmt_check_email)) {
         error_log('Error createUser check email: ' . oci_error($stmt_check_email)['message']);
         @oci_close($conn);
         return 'db_error';
    }
    $row_email = oci_fetch_assoc($stmt_check_email);
    oci_free_statement($stmt_check_email);

    if ($row_email && $row_email['CNT'] > 0) {
        @oci_close($conn);
        return 'email_exists';
    }

    // 2. Cek Username (Case-Insensitive)
    $sql_check_uname = "SELECT COUNT(*) AS CNT FROM users WHERE UPPER(username) = :uname_upper";
    $stmt_check_uname = oci_parse($conn, $sql_check_uname);
    $uname_upper = strtoupper($username);
    oci_bind_by_name($stmt_check_uname, ':uname_upper', $uname_upper);

    if (!oci_execute($stmt_check_uname)) {
         error_log('Error createUser check username: ' . oci_error($stmt_check_uname)['message']);
         @oci_close($conn);
         return 'db_error';
    }
    $row_uname = oci_fetch_assoc($stmt_check_uname);
    oci_free_statement($stmt_check_uname);
    
    if ($row_uname && $row_uname['CNT'] > 0) {
        @oci_close($conn);
        return 'username_exists';
    }
    
    // 3. Jika lolos, baru lakukan INSERT
    // === PERBAIKAN NAMA KOLOM DI SINI ===
    $sql = "INSERT INTO users (
        username, nama_lengkap, email, password, role_id,
        verifikasi_kode, is_verif
    ) VALUES (
        :username, :nama, :email, :pass, :role_id,
        :token, 0
    )";

    $stmt = oci_parse($conn, $sql);

    $role_id_int = (int)$role_id;
    oci_bind_by_name($stmt, ":username", $username);
    oci_bind_by_name($stmt, ':nama', $nama_lengkap); 
    oci_bind_by_name($stmt, ':email', $email);
    oci_bind_by_name($stmt, ':pass', $password_hash); // Variable $password_hash (dari controller) di-bind ke :pass
    oci_bind_by_name($stmt, ':role_id', $role_id_int, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':token', $token); // Variable $token (dari controller) di-bind ke :token

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        error_log('Error createUser execute: ' . oci_error($stmt)['message']);
        oci_rollback($conn);
        @oci_close($conn);
        return 'db_error';
    }

    if (!oci_commit($conn)) {
        error_log('Error createUser commit: ' . oci_error($conn)['message']);
        oci_rollback($conn);
        @oci_close($conn);
        return 'db_error';
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return 'success';
}

/**
 * Verifikasi akun user berdasarkan token
 */
function verifyUserByToken($token)
{
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di verifyUserByToken.');
        return 'db_error';
    }
 
    // === PERBAIKAN NAMA KOLOM DI SINI ===
    $sql_find = "SELECT user_id FROM users WHERE verifikasi_kode = :token";
    
    $stmt_find = oci_parse($conn, $sql_find);
    oci_bind_by_name($stmt_find, ':token', $token);
    
    if (!oci_execute($stmt_find)) {
        error_log('Error verifyUserByToken find: ' . oci_error($stmt_find)['message']);
        @oci_close($conn);
        return 'db_error';
    }

    $row = oci_fetch_assoc($stmt_find);
    oci_free_statement($stmt_find);
    if (!$row) {
        @oci_close($conn);
        return 'invalid_or_expired';
    }
 
    $user_id = (int)$row['USER_ID']; 
    
    // === PERBAIKAN NAMA KOLOM DI SINI ===
    $sql_update = "UPDATE users SET is_verif = 1, verifikasi_kode = NULL WHERE user_id = " . $user_id;
    
    $stmt_update = oci_parse($conn, $sql_update);

    if (!oci_execute($stmt_update, OCI_NO_AUTO_COMMIT)) {
        error_log('Error verifyUserByToken update: ' . oci_error($stmt_update)['message']);
        oci_rollback($conn);
        @oci_close($conn);
        return 'db_error';
    }

    if (!oci_commit($conn)) {
        error_log('Error verifyUserByToken commit: ' . oci_error($conn)['message']);
        oci_rollback($conn);
        @oci_close($conn);
        return 'db_error';
    }

    oci_free_statement($stmt_update);
    @oci_close($conn);
    return 'success';
}


/**
 * Mengambil data profil lengkap
 */
function getUserProfileData($username) {
    require __DIR__ . '/../../config/koneksi.php'; 
    if (!$conn) {
        error_log('Koneksi DB gagal di getUserProfileData.');
        return null; 
    }

    $sql = "SELECT u.user_id, u.nama_lengkap, u.username, u.email, u.bio, r.role_name, (SELECT COUNT(*) FROM postingan p WHERE p.user_id = u.user_id) AS post_count, 0 AS followers_count, 0 AS following_count FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = :uname";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':uname', $username);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in getUserProfileData: " . $e['message']);
         @oci_close($conn);
         return null; 
    }

    $profile = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);
    @oci_close($conn);
    return $profile ? array_change_key_case($profile, CASE_LOWER) : null;
}

/**
 * Mencari pengguna berdasarkan username atau nama lengkap.
 */
function searchUsers($searchTerm) {
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log("Koneksi DB gagal di searchUsers.");
        return [];
    }

    $sql = "SELECT user_id, username, nama_lengkap, role_id 
            FROM users 
            WHERE UPPER(username) LIKE :term OR UPPER(nama_lengkap) LIKE :term";
            
    $searchTermWildcard = '%' . strtoupper($searchTerm) . '%';

    $stmt = oci_parse($conn, $sql);
    
    oci_bind_by_name($stmt, ':term', $searchTermWildcard);

    if (!oci_execute($stmt)) {
         $e = oci_error($stmt);
         error_log("OCI8 Error in searchUsers: " . $e['message']);
         @oci_close($conn);
         return [];
    }

    $users = [];
    while ($row = oci_fetch_assoc($stmt)) {
         $users[] = array_change_key_case($row, CASE_LOWER);
    }

    oci_free_statement($stmt);
    @oci_close($conn);
    return $users;
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