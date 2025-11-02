<?php
// File: app/models/UserModel.php (VERSI ROMBAKAN STABIL)

/**
 * Ambil data user berdasarkan email
 * (untuk proses login)
 */
function getUserByEmail($email)
{
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di getUserByEmail.');
        return null;
    }

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
 * (untuk halaman profil)
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
 * (Dibutuhkan oleh ForumModel untuk pesan sistem join/leave)
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
 * (untuk proses registrasi)
 */
function createUser($username, $nama_lengkap, $email, $password_hash, $role_id, $token) 
{
    require __DIR__ . '/../../config/koneksi.php';
    if (!$conn) {
        error_log('Koneksi DB gagal di createUser.');
        return 'db_error';
    }

    $sql_check = "SELECT COUNT(*) AS CNT FROM users WHERE email = :email OR username = :uname";
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ':email', $email);
    oci_bind_by_name($stmt_check, ':uname', $username);
    oci_execute($stmt_check);
    $row = oci_fetch_assoc($stmt_check);
    $email_exists = ($row && $row['CNT'] > 0);
    oci_free_statement($stmt_check);

    if ($email_exists) {
        @oci_close($conn);
        return 'email_exists'; // (atau 'username_exists')
    }

    $sql = "INSERT INTO users (
        username, nama_lengkap, email, password_hash, role_id,
        verification_token, is_verified
    ) VALUES (
        :username, :nama, :email, :pass, :role_id,
        :token, 0
    )";

    $stmt = oci_parse($conn, $sql);

    $role_id_int = (int)$role_id;
    oci_bind_by_name($stmt, ":username", $username);
    oci_bind_by_name($stmt, ':nama', $nama_lengkap); 
    oci_bind_by_name($stmt, ':email', $email);
    oci_bind_by_name($stmt, ':pass', $password_hash);
    oci_bind_by_name($stmt, ':role_id', $role_id_int, -1, SQLT_INT);
    oci_bind_by_name($stmt, ':token', $token);

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
 
    $sql_find = "SELECT user_id FROM users WHERE verification_token = :token";
    
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
    
    $sql_update = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE user_id = " . $user_id;
    
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
}
?>