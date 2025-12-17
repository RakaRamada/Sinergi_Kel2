<?php
// File: app/controllers/profileController.php
// VERSI FINAL FIXED: Mengatasi ORA-01745 (Ganti nama bind variable)

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../models/PostModel.php';

class ProfileController {
    
    private $conn;
    private $postModel;
    private $baseUrlUploads = '/Sinergi/public/uploads/avatars/'; 
    private $baseUrlAssets  = '/Sinergi/public/assets/images/';

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        $this->postModel = new PostModel($dbConnection);
    }

    private function fixUrl($url, $default) {
        if (empty($url)) return $this->baseUrlAssets . $default;
        if (strpos($url, '/') !== false) return $url; 
        return $this->baseUrlUploads . $url;
    }

    public function showProfile() {
        if (!isset($_SESSION['user_id'])) { header('Location: index.php?page=login'); exit; }
        
        $current_user_id = (int)$_SESSION['user_id'];
        require_once __DIR__ . '/../models/UserModel.php';
        require_once __DIR__ . '/../models/GroupModel.php';
        
        $uModel = new UserModel($this->conn);
        $gModel = new GroupModel($this->conn);

        $recommendedUsers = $uModel->getTopActiveUsers(5, $current_user_id);
        // Fix Path Avatar
        foreach ($recommendedUsers as &$u) {
            $u['avatar_url'] = $this->fixUrl($u['avatar_url'], 'user.png'); // Pakai helper fixUrl yg sudah ada di class ini
        }
        unset($u);

        $recommendedGroups = $gModel->getPopularGroups(5);
        // Fix Path Group
        foreach ($recommendedGroups as &$g) {
            $g['group_image'] = !empty($g['group_image']) 
                 ? '/Sinergi/public/uploads/group_profiles/' . $g['group_image'] 
                 : '/Sinergi/public/assets/images/user.png';
        }
        unset($g);
        
        $profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $current_user_id;
        $is_my_profile = ($profile_user_id === $current_user_id);

        // Ganti bind :p_uid biar aman
        $sql = "SELECT u.*, r.role_name, 
                (SELECT COUNT(*) FROM postingan p WHERE p.user_id = u.user_id) AS total_postingan 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                WHERE u.user_id = :p_target_id";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_target_id', $profile_user_id);
        oci_execute($stmt);
        $profile_data = oci_fetch_assoc($stmt);
        
        if (!$profile_data) { echo "Profil tidak ditemukan."; exit; }

        $profile_data = array_change_key_case($profile_data, CASE_UPPER);
        $profile_data['AVATAR_URL_FIXED'] = $this->fixUrl($profile_data['AVATAR_URL'], 'user.png');
        $profile_data['HEADER_URL_FIXED'] = $this->fixUrl($profile_data['HEADER_URL'], 'default-header.jpg');
        
        $user_posts = $this->postModel->getPostsByUserId($profile_user_id, $current_user_id);
        require __DIR__ . '/../views/profile.php';
    }

    public function showEditProfileForm() {
        if (!isset($_SESSION['user_id'])) { header('Location: index.php?page=login'); exit; }
        $current_user_id = (int)$_SESSION['user_id'];

        // --- SIDEBAR DATA (Supaya Sidebar Kanan Tetap Muncul) ---
        require_once __DIR__ . '/../models/UserModel.php';
        require_once __DIR__ . '/../models/GroupModel.php';
        
        $uModel = new UserModel($this->conn);
        $gModel = new GroupModel($this->conn);

        $recommendedUsers = $uModel->getTopActiveUsers(5, $current_user_id);
        foreach ($recommendedUsers as &$u) {
            $u['avatar_url'] = $this->fixUrl($u['avatar_url'], 'user.png');
        }
        unset($u);

        $recommendedGroups = $gModel->getPopularGroups(5);
        foreach ($recommendedGroups as &$g) {
            $g['group_image'] = !empty($g['group_image']) 
                 ? '/Sinergi/public/uploads/group_profiles/' . $g['group_image'] 
                 : '/Sinergi/public/assets/images/user.png';
        }
        unset($g);
        // --- END SIDEBAR DATA ---

        $sql = "SELECT * FROM users WHERE user_id = :p_curr_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_curr_id', $current_user_id);
        oci_execute($stmt);
        $user_data = oci_fetch_assoc($stmt);
        
        if($user_data) {
            $user_data = array_change_key_case($user_data, CASE_UPPER);
            $user_data['AVATAR_URL_FIXED'] = $this->fixUrl($user_data['AVATAR_URL'], 'user.png');
            $user_data['HEADER_URL_FIXED'] = $this->fixUrl($user_data['HEADER_URL'], 'default-header.jpg');
        }
        require __DIR__ . '/../views/edit_profile.php';
    }

    public function processProfileUpdate() {
        if (!isset($_SESSION['user_id'])) { header('Location: index.php?page=login'); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = (int)$_SESSION['user_id'];
            $nama_lengkap = trim($_POST['nama_lengkap']);
            $username = trim($_POST['username']); // Tambahan update username jika perlu
            $bio = trim($_POST['bio'] ?? '');
            
            // --- LOGIKA BARU: ROLE & TAHUN MASUK ---
            // Ambil role lama dulu dari session/db untuk pengecekan hak akses
            $current_role = (int)$_SESSION['role_id'];
            
            $new_role = isset($_POST['role_id']) ? (int)$_POST['role_id'] : $current_role;
            $tahun_masuk = isset($_POST['tahun_masuk']) && !empty($_POST['tahun_masuk']) ? (int)$_POST['tahun_masuk'] : null;

            // VALIDASI LOGIKA "MASUK AKAL" (Hanya jika mengubah ke Alumni)
            if ($current_role == 1 && $new_role == 3) {
                if (empty($tahun_masuk)) {
                    header('Location: index.php?page=edit_profile&error=tahun_required'); 
                    exit();
                }
                
                $tahun_sekarang = (int)date('Y');
                $masa_studi = $tahun_sekarang - $tahun_masuk;

                // Syarat: Minimal 3 tahun selisih
                if ($masa_studi < 4) {
                    header('Location: index.php?page=edit_profile&error=belum_cukup_umur'); 
                    exit();
                }
            }
            // ----------------------------------------

            $file_avatar = $_FILES['avatar'] ?? null;
            $file_header = $_FILES['header'] ?? null;
            $avatar_baru = null;
            $header_baru = null;

            $upload_dir = __DIR__ . '/../../public/uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            // Upload Logic
            if ($file_avatar && $file_avatar['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file_avatar['name'], PATHINFO_EXTENSION));
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file_avatar['tmp_name'], $upload_dir . $filename)) $avatar_baru = $filename;
            }
            if ($file_header && $file_header['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file_header['name'], PATHINFO_EXTENSION));
                $filename = 'header_' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file_header['tmp_name'], $upload_dir . $filename)) $header_baru = $filename;
            }
            
            // UPDATE QUERY
            $sql = "UPDATE users SET 
                    nama_lengkap = :b_nama, 
                    username = :b_username,
                    bio = :b_bio,
                    role_id = :b_role,
                    tahun_masuk = :b_tahun"; // Tambah Tahun Masuk

            if ($avatar_baru) $sql .= ", avatar_url = :b_avatar";
            if ($header_baru) $sql .= ", header_url = :b_header";
            $sql .= " WHERE user_id = :b_id";

            $stmt = oci_parse($this->conn, $sql);
            
            // Bind
            oci_bind_by_name($stmt, ':b_nama', $nama_lengkap);
            oci_bind_by_name($stmt, ':b_username', $username);
            oci_bind_by_name($stmt, ':b_bio', $bio);
            oci_bind_by_name($stmt, ':b_role', $new_role);
            oci_bind_by_name($stmt, ':b_tahun', $tahun_masuk);
            oci_bind_by_name($stmt, ':b_id', $user_id);
            
            if ($avatar_baru) oci_bind_by_name($stmt, ':b_avatar', $avatar_baru);
            if ($header_baru) oci_bind_by_name($stmt, ':b_header', $header_baru);

            if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
                // Update Session Data
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['username'] = $username;
                $_SESSION['role_id'] = $new_role; // Update Role di Session
                
                // Update Role Name di Session (Opsional, biar UI langsung berubah)
                if ($new_role == 3) $_SESSION['role_name'] = 'Alumni';
                if ($new_role == 1) $_SESSION['role_name'] = 'Mahasiswa';

                if ($avatar_baru) $_SESSION['avatar_url'] = $this->fixUrl($avatar_baru, 'user.png');
                
                header('Location: index.php?page=profile&success=updated');
                exit();
            } else {
                $e = oci_error($stmt);
                echo "<h1>Gagal update database</h1>";
                echo "<p>Pesan Error Oracle: <strong>" . $e['message'] . "</strong></p>";
                echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";
                exit;
            }
        }
    }
}
?>