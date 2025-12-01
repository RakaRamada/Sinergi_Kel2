<?php
// app/controllers/profileController.php

require_once __DIR__ . '/../../config/koneksi.php';

class ProfileController {

    private $conn;
    private $defaultAvatar;
    private $defaultHeader;

    public function __construct($dbConnection) {
        // Cek sesi saat controller dimulai
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Simpan koneksi database ke dalam property class
        $this->conn = $dbConnection;

        // Set default paths
        $this->defaultAvatar = '/Sinergi/public/assets/images/user.png';
        $this->defaultHeader = '/Sinergi/public/assets/images/default-header.jpg';
    }

    // ------------------------------
    // TAMPILKAN PROFIL
    // ------------------------------
    public function showProfile() {
        // Cek Login
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login&error=Harap login terlebih dahulu');
            exit;
        }

        $current_user_id = (int)$_SESSION['user_id'];
        $profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $current_user_id;
        $is_my_profile = ($profile_user_id === $current_user_id);

        // PERBAIKAN DISINI: Menghapus u.followers dan u.following dari SELECT
        $sql = "
            SELECT 
                u.user_id, u.username, u.nama_lengkap, u.email,
                u.avatar_url, u.header_url, u.bio, u.nim, r.role_name,
                (SELECT COUNT(*) FROM postingan p WHERE p.user_id = u.user_id) AS total_postingan
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE u.user_id = :id_bv
        ";

        // Gunakan $this->conn, bukan global $conn
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':id_bv', $profile_user_id);

        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            echo "Error Query: " . $e['message'];
            exit;
        }

        $profile_data = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$profile_data) {
            echo "Profil tidak ditemukan (ID: $profile_user_id)";
            exit;
        }

        // Fix keys and defaults
        $profile_data['AVATAR_URL_FIXED'] = !empty($profile_data['AVATAR_URL']) 
            ? $profile_data['AVATAR_URL'] 
            : $this->defaultAvatar; 

        $profile_data['HEADER_URL_FIXED'] = !empty($profile_data['HEADER_URL']) 
            ? $profile_data['HEADER_URL'] 
            : $this->defaultHeader; 

        // Set Followers/Following ke 0 karena kolom sudah dihapus
        $profile_data['FOLLOWERS'] = 0;
        $profile_data['FOLLOWING'] = 0;
        
        $profile_data['BIO'] = isset($profile_data['BIO']) ? $profile_data['BIO'] : '';
        $profile_data['NIM'] = isset($profile_data['NIM']) ? $profile_data['NIM'] : '';

        // --- AMBIL POSTINGAN USER ---
        $user_posts = [];
        $sql_posts = "
            SELECT 
                p.post_id, p.user_id, p.konten, p.post_image,
                p.like_count, p.comment_count, 
                TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
                u.username, u.nama_lengkap, u.avatar_url,
                (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.post_id AND l.user_id = :current_user_bv) AS USER_SUDAH_LIKE
            FROM postingan p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.user_id = :target_user_bv
            ORDER BY p.post_id DESC
        ";

        $stmt_posts = oci_parse($this->conn, $sql_posts);
        oci_bind_by_name($stmt_posts, ':target_user_bv', $profile_user_id);
        oci_bind_by_name($stmt_posts, ':current_user_bv', $current_user_id);
        
        oci_execute($stmt_posts);

        while ($row = oci_fetch_assoc($stmt_posts)) {
            $row = array_change_key_case($row, CASE_UPPER);

            // Fix Avatar Post
            if (empty($row['AVATAR_URL']) || (strpos($row['AVATAR_URL'], '/assets/images/') !== false && strlen($row['AVATAR_URL']) < 30)) {
                 $row['AVATAR_URL_FIXED'] = $this->defaultAvatar;
            } else {
                 $row['AVATAR_URL_FIXED'] = $row['AVATAR_URL'];
            }

            // Fix Waktu
            $timestamp = strtotime($row['CREATED_AT_STR']); 
            if ($timestamp === false) {
                $row['WAKTU_POSTING'] = '-';
            } else {
                $diff = time() - $timestamp;
                if ($diff < 60) { $row['WAKTU_POSTING'] = 'Baru saja'; }
                else if ($diff < 3600) { $row['WAKTU_POSTING'] = floor($diff / 60) . 'm'; }
                else if ($diff < 86400) { $row['WAKTU_POSTING'] = floor($diff / 3600) . 'j'; }
                else { $row['WAKTU_POSTING'] = date('d M Y', $timestamp); }
            }

            $user_posts[] = $row;
        }
        oci_free_statement($stmt_posts);

        // Panggil View
        require __DIR__ . '/../views/profile.php';
    }

    // ------------------------------
    // FORM EDIT PROFIL
    // ------------------------------
    public function showEditProfileForm() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $current_user_id = (int)$_SESSION['user_id'];

        $sql = "SELECT user_id, username, nama_lengkap, email, avatar_url, header_url, bio, nim FROM users WHERE user_id = :id_bv";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':id_bv', $current_user_id);
        oci_execute($stmt);
        $user_data = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$user_data) {
            echo "Data user tidak ditemukan.";
            exit;
        }

        $user_data['AVATAR_URL_FIXED'] = !empty($user_data['AVATAR_URL']) ? $user_data['AVATAR_URL'] : $this->defaultAvatar;
        $user_data['HEADER_URL_FIXED'] = !empty($user_data['HEADER_URL']) ? $user_data['HEADER_URL'] : $this->defaultHeader;
        $user_data['BIO'] = isset($user_data['BIO']) ? $user_data['BIO'] : '';
        $user_data['NIM'] = isset($user_data['NIM']) ? $user_data['NIM'] : '';

        require __DIR__ . '/../views/edit_profile.php';
    }

    // ------------------------------
    // PROSES UPDATE PROFIL
    // ------------------------------
    public function processProfileUpdate() {
        if (!isset($_SESSION['user_id'])) {
            die("Aksi tidak diizinkan. Silakan login terlebih dahulu.");
        }

        $current_user_id = (int)$_SESSION['user_id'];
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if (empty($nama_lengkap) || empty($username)) {
            die("Nama lengkap dan username tidak boleh kosong.");
        }

        $target_dir_server_avatars = $_SERVER['DOCUMENT_ROOT'] . '/Sinergi/public/uploads/avatars/';
        $target_dir_db_avatars = '/Sinergi/public/uploads/avatars/';

        $target_dir_server_headers = $_SERVER['DOCUMENT_ROOT'] . '/Sinergi/public/uploads/headers/';
        $target_dir_db_headers = '/Sinergi/public/uploads/headers/';

        if (!is_dir($target_dir_server_avatars)) mkdir($target_dir_server_avatars, 0777, true);
        if (!is_dir($target_dir_server_headers)) mkdir($target_dir_server_headers, 0777, true);

        // Upload Avatar
        $new_avatar_db_path = null;
        if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $new_filename = "user_" . $current_user_id . "_" . time() . "." . $ext;
            $target_file_server = $target_dir_server_avatars . $new_filename;
            
            if (in_array($ext, ['jpg', 'jpeg', 'png']) && $_FILES['avatar']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file_server)) {
                    $new_avatar_db_path = $target_dir_db_avatars . $new_filename;
                }
            }
        }

        // Upload Header
        $new_header_db_path = null;
        if (!empty($_FILES['header']) && $_FILES['header']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['header']['name'], PATHINFO_EXTENSION));
            $new_filename = "header_" . $current_user_id . "_" . time() . "." . $ext;
            $target_file_server = $target_dir_server_headers . $new_filename;
            
            if (in_array($ext, ['jpg', 'jpeg', 'png']) && $_FILES['header']['size'] <= 5 * 1024 * 1024) {
                if (move_uploaded_file($_FILES['header']['tmp_name'], $target_file_server)) {
                    $new_header_db_path = $target_dir_db_headers . $new_filename;
                }
            }
        }

        // Build SQL
        $fields = "nama_lengkap = :nama, username = :uname, bio = :bio";
        if ($new_avatar_db_path) $fields .= ", avatar_url = :avar";
        if ($new_header_db_path) $fields .= ", header_url = :hvar";

        $sql = "UPDATE users SET $fields WHERE user_id = :id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':nama', $nama_lengkap);
        oci_bind_by_name($stmt, ':uname', $username);
        oci_bind_by_name($stmt, ':bio', $bio);
        if ($new_avatar_db_path) oci_bind_by_name($stmt, ':avar', $new_avatar_db_path);
        if ($new_header_db_path) oci_bind_by_name($stmt, ':hvar', $new_header_db_path);
        oci_bind_by_name($stmt, ':id', $current_user_id);

        if (oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_commit($this->conn);
        } else {
            oci_rollback($this->conn);
            $e = oci_error($stmt);
            die("Error update: " . $e['message']);
        }
        oci_free_statement($stmt);

        header("Location: index.php?page=profile&status=update");
        exit;
    }
}
?>