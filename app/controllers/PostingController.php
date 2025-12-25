<?php
// File: app/controllers/PostingController.php
// VERSI FULL RECOVERY: SHOW POST DETAIL KEMBALI + FITUR BARU

require_once __DIR__ . '/../../config/koneksi.php'; 
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/ReportModel.php'; 

class PostingController {
    
    private $conn; 
    private $postModel;
    private $reportModel; 
    
    private $defaultAvatar = '/sinergi/public/assets/images/user.png';
    private $avatarUploadPath = '/sinergi/public/uploads/avatars/';

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        $this->postModel = new PostModel($dbConnection);
        $this->reportModel = new ReportModel($dbConnection);
    }

    // --- HELPER ---
    private function fixAvatarPath($url) {
        if (empty($url)) return $this->defaultAvatar;
        if (strpos($url, '/') !== false) return $url; 
        return $this->avatarUploadPath . $url;
    }

    private function sendJson($data) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    // ==================================================================
    // 1. VIEW METHODS (HALAMAN)
    // ==================================================================

    public function showDashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) { header('Location: index.php?page=login'); exit(); }

        require_once __DIR__ . '/../models/UserModel.php';
        require_once __DIR__ . '/../models/GroupModel.php';

        $userModel = new UserModel($this->conn);
        $groupModel = new GroupModel($this->conn);

        // Sidebar Data
        $recommendedUsers = $userModel->getTopActiveUsers(5, $user_id);
        foreach ($recommendedUsers as &$u) {
            $u['avatar_url'] = $this->fixAvatarPath($u['avatar_url']);
        }
        
        $recommendedGroups = $groupModel->getPopularGroups(5);
        foreach ($recommendedGroups as &$g) {
            $g['group_image'] = !empty($g['group_image']) ? '/Sinergi/public/uploads/group_profiles/' . $g['group_image'] : '/Sinergi/public/assets/images/user.png';
        }

        require 'app/views/dashboard.php';
    }

    // --- [RESTORED] FUNGSI INI TADI HILANG ---
    public function showPostDetail() {
        $post_id = isset($_GET['id']) ? $_GET['id'] : 0;
        
        if ($post_id == 0) {
            echo "<div class='p-4 text-red-500 font-bold'>Error: ID Postingan tidak valid.</div>"; return;
        }
        
        $post = $this->postModel->getPostById($post_id);
        
        if(!$post){
            // Fallback jika post dihapus/tidak ketemu
            require 'app/views/dashboard.php'; 
            return;
        }

        // --- SIDEBAR DATA (Supaya Sidebar Kanan Tetap Muncul) ---
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? 0;

        require_once __DIR__ . '/../models/UserModel.php';
        require_once __DIR__ . '/../models/GroupModel.php';

        $userModel = new UserModel($this->conn);
        $groupModel = new GroupModel($this->conn);

        $recommendedUsers = $userModel->getTopActiveUsers(5, $user_id);
        foreach ($recommendedUsers as &$u) {
            $u['avatar_url'] = $this->fixAvatarPath($u['avatar_url']);
        }

        $recommendedGroups = $groupModel->getPopularGroups(5);
        foreach ($recommendedGroups as &$g) {
            $g['group_image'] = !empty($g['group_image']) ? '/Sinergi/public/uploads/group_profiles/' . $g['group_image'] : '/Sinergi/public/assets/images/user.png';
        }
        // -------------------------------------------------------

        // Data Processing untuk View
        $post['AVATAR_URL_FIXED'] = $this->fixAvatarPath($post['AVATAR_URL']);
        $post['POST_IMAGE'] = $post['POST_IMAGE'] ?? '';

        if (!empty($post['WAKTU_FIX'])) {
            $post['WAKTU_POSTING'] = date('H:i | d M Y', strtotime($post['WAKTU_FIX'])); 
        } else {
            $post['WAKTU_POSTING'] = '-';
        }

        $post['TOTAL_LIKES'] = $post['LIKE_COUNT'] ?? 0;
        $post['TOTAL_COMMENTS'] = $post['COMMENT_COUNT'] ?? 0;

        // Ambil Komentar & Reply
        $rawComments = $this->postModel->getParentComments($post_id);
        $comments = [];

        foreach ($rawComments as $c) {
            $c['AVATAR_URL_FIXED'] = $this->fixAvatarPath($c['AVATAR_URL']);
            $c['WAKTU_KOMEN'] = !empty($c['WAKTU_FIX']) ? date('d M H:i', strtotime($c['WAKTU_FIX'])) : 'Baru saja';
            
            $rawReplies = $this->postModel->getReplies($c['COMMENT_ID']);
            $c['REPLIES'] = [];
            foreach ($rawReplies as $r) {
                $r['AVATAR_URL_FIXED'] = $this->fixAvatarPath($r['AVATAR_URL']);
                $r['WAKTU_KOMEN'] = !empty($r['WAKTU_FIX']) ? date('d M H:i', strtotime($r['WAKTU_FIX'])) : 'Baru saja';
                $c['REPLIES'][] = $r;
            }
            $comments[] = $c;
        }

        require __DIR__ . '/../views/post_detail.php';
    }

    // ==================================================================
    // 2. API METHODS (AJAX)
    // ==================================================================

    public function getPostings() {
        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) $this->sendJson(['status' => 'error', 'message' => 'Session expired']);

        date_default_timezone_set('Asia/Jakarta');

        try {
            $posts = $this->postModel->getAllPosts($userId);
            $formatted = [];
            $now = time();

            foreach ($posts as $row) {
                $row['AVATAR_URL_FIXED'] = $this->fixAvatarPath($row['AVATAR_URL']);
                
                // PERBAIKAN: Coba multiple possible field names untuk timestamp
                $rawTimestamp = null;
                if (!empty($row['CREATED_AT_STR'])) {
                    $rawTimestamp = $row['CREATED_AT_STR'];
                } elseif (!empty($row['CREATED_AT'])) {
                    // Handle jika CREATED_AT adalah object (Oracle Date) atau string
                    if (is_object($row['CREATED_AT'])) {
                        $rawTimestamp = $row['CREATED_AT']->format('Y-m-d H:i:s');
                    } else {
                        $rawTimestamp = $row['CREATED_AT'];
                    }
                } elseif (!empty($row['WAKTU_FIX'])) {
                    $rawTimestamp = $row['WAKTU_FIX'];
                }
                
                $timestamp = $rawTimestamp ? strtotime($rawTimestamp) : false;
                
                if ($timestamp && $timestamp > 0) {
                    $diff = $now - $timestamp;
                    if ($diff < 0) $diff = 0;

                    if ($diff < 60) $row['WAKTU_POSTING'] = 'Baru saja';
                    else if ($diff < 3600) $row['WAKTU_POSTING'] = floor($diff / 60) . ' menit yang lalu';
                    else if ($diff < 86400) $row['WAKTU_POSTING'] = floor($diff / 3600) . ' jam yang lalu';
                    else $row['WAKTU_POSTING'] = date('d M', $timestamp);
                } else {
                    $row['WAKTU_POSTING'] = '-';
                }
                $formatted[] = $row;
            }
            $this->sendJson($formatted);
        } catch (Exception $e) {
            $this->sendJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // FITUR UPLOAD MULTI GAMBAR
    public function createPost() {
        if (!isset($_SESSION['user_id'])) $this->sendJson(['status' => 'error', 'message' => 'Belum login']);
        
        $user_id = $_SESSION['user_id'];
        $konten = isset($_POST['konten']) ? trim($_POST['konten']) : '';
        
        $post_image_db = null;
        $uploaded_paths = [];
        
        if (isset($_FILES['post_image'])) {
            $files = $_FILES['post_image'];
            
            // Normalisasi $_FILES
            $file_list = [];
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $file_list[] = [
                            'name' => $files['name'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i]
                        ];
                    }
                }
            } else {
                if ($files['error'] === UPLOAD_ERR_OK) $file_list[] = $files;
            }

            if (!empty($file_list)) {
                $upload_dir = __DIR__ . '/../../public/assets/uploads/'; 
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

                foreach ($file_list as $file) {
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
                        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_file_name)) {
                            $uploaded_paths[] = '/Sinergi/public/assets/uploads/' . $new_file_name;
                        }
                    }
                }
                if (!empty($uploaded_paths)) $post_image_db = implode(',', $uploaded_paths);
            }
        }

        if (empty($konten) && $post_image_db === null) {
            $this->sendJson(['status' => 'error', 'message' => 'Konten atau Gambar harus diisi.']);
        }

        try {
            $this->postModel->createPost($user_id, $konten, $post_image_db);
            $this->sendJson(['status' => 'success', 'message' => 'Terposting!']);
        } catch (Exception $e) {
            $this->sendJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function toggleLike() {
        if (!isset($_SESSION['user_id'])) $this->sendJson(['status' => 'error', 'message' => 'Login required']);
        $post_id = $_POST['post_id'] ?? 0;
        $result = $this->postModel->toggleLike($_SESSION['user_id'], $post_id);
        if ($result) {
            $this->sendJson([
                'status' => ($result['action'] === 'error') ? 'error' : 'success', 
                'action' => $result['action'], 
                'new_count' => $result['new_count']
            ]);
        } else {
            $this->sendJson(['status' => 'error', 'message' => 'Failed to like']);
        }
    }

    public function addComment() {
        if (!isset($_SESSION['user_id'])) $this->sendJson(['status' => 'error', 'message' => 'Login required']);
        $post_id = $_POST['post_id'] ?? 0;
        $isi = trim($_POST['isi_komen'] ?? '');
        $parent_id = !empty($_POST['parent_comment_id']) ? intval($_POST['parent_comment_id']) : null;
        if (empty($isi) || $post_id == 0) $this->sendJson(['status' => 'error', 'message' => 'Data tidak lengkap']);
        $newId = $this->postModel->addComment($_SESSION['user_id'], $post_id, $isi, $parent_id);
        if ($newId) $this->sendJson(['status' => 'success']);
        else $this->sendJson(['status' => 'error', 'message' => 'Gagal simpan']);
    }

    public function deleteComment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $comment_id = (int)$_POST['comment_id'];
            $user_id = (int)$_SESSION['user_id'];
            $success = $this->postModel->deleteComment($comment_id, $user_id);
            if ($success) $this->sendJson(['status' => 'success']);
            else $this->sendJson(['status' => 'error', 'message' => 'Gagal menghapus atau bukan milik Anda']);
        } else {
            $this->sendJson(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
    }

    public function deletePost() {
        if (!isset($_SESSION['user_id'])) $this->sendJson(['status' => 'error', 'message' => 'Login required']);
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $uid = intval($_SESSION['user_id']);
        if (empty($post_id)) $this->sendJson(['status' => 'error', 'message' => 'Invalid ID']);

        try {
            $result = $this->postModel->deletePost($post_id, $uid);
            if ($result['status']) {
                if (!empty($result['image_path'])) {
                    $paths = explode(',', $result['image_path']);
                    $target_dir = __DIR__ . '/../../public/assets/uploads/';
                    foreach($paths as $p) {
                        $p = trim($p);
                        if(empty($p)) continue;
                        $filename = basename($p);
                        $file_path = $target_dir . $filename;
                        if (file_exists($file_path)) @unlink($file_path);
                    }
                }
                $this->sendJson(['status' => 'success', 'message' => 'Postingan dihapus']);
            } else {
                $this->sendJson(['status' => 'error', 'message' => $result['message']]);
            }
        } catch (Exception $e) {
            $this->sendJson(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
        }
    }
    
    public function addReport() {
        if (!isset($_SESSION['user_id'])) $this->sendJson(['status' => 'error', 'message' => 'Login required']);
        try {
            $post_id = $_POST['post_id'] ?? null;
            $reason = $_POST['reason'] ?? null;
            if (!$post_id || !$reason) throw new Exception("Data tidak lengkap");
            $result = $this->reportModel->createReport($post_id, $_SESSION['user_id'], $reason);
            $this->sendJson($result); 
        } catch (Exception $e) {
            $this->sendJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
?>