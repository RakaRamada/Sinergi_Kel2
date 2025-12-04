<?php
// File: app/controllers/PostingController.php
// VERSI FINAL: FITUR DELETE COMMENT SUDAH AKTIF

require_once __DIR__ . '/../../config/koneksi.php'; 
require_once __DIR__ . '/../models/PostModel.php';
require_once __DIR__ . '/../models/ReportModel.php'; 

class PostingController {
    
    private $conn; 
    private $postModel;
    private $reportModel; 
    
    // Path Default
    private $defaultAvatar = '/sinergi/public/assets/images/user.png';
    private $avatarUploadPath = '/sinergi/public/uploads/avatars/';

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        $this->postModel = new PostModel($dbConnection);
        $this->reportModel = new ReportModel($dbConnection);
    }

    private function fixAvatarPath($url) {
        if (empty($url)) return $this->defaultAvatar;
        if (strpos($url, '/') !== false) return $url; 
        return $this->avatarUploadPath . $url;
    }

    private function sendJson($data) {
        while (ob_get_level()) ob_end_clean();
        ini_set('display_errors', 0);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    // --- VIEW METHODS ---
    public function showDashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) { header('Location: index.php?page=login'); exit(); }
        require 'app/views/dashboard.php';
    }

    public function showPostDetail() {
        $post_id = isset($_GET['id']) ? $_GET['id'] : 0;
        
        if ($post_id == 0) {
            echo "<div class='p-4 text-red-500 font-bold'>Error: ID Postingan tidak valid.</div>"; return;
        }
        
        $post = $this->postModel->getPostById($post_id);
        
        if(!$post){
            require 'app/views/dashboard.php';
            echo "<div class='container mx-auto p-4 mt-4 bg-white rounded shadow text-center'>Postingan tidak ditemukan.</div>"; return;
        }

        $post['AVATAR_URL_FIXED'] = $this->fixAvatarPath($post['AVATAR_URL']);
        $post['POST_IMAGE'] = $post['POST_IMAGE'] ?? '';

        if (!empty($post['WAKTU_FIX'])) {
            $post['WAKTU_POSTING'] = date('H:i | d M Y', strtotime($post['WAKTU_FIX'])); 
        } else {
            $post['WAKTU_POSTING'] = '-';
        }

        $post['TOTAL_LIKES'] = $post['LIKE_COUNT'] ?? 0;
        $post['TOTAL_COMMENTS'] = $post['COMMENT_COUNT'] ?? 0;

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

    // --- API METHODS ---

    public function getPostings() {
        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId === 0) $this->sendJson(['status' => 'error', 'message' => 'Session expired']);

        try {
            $posts = $this->postModel->getAllPosts($userId);
            $formatted = [];
            foreach ($posts as $row) {
                $row['AVATAR_URL_FIXED'] = $this->fixAvatarPath($row['AVATAR_URL']);
                $timestamp = strtotime($row['CREATED_AT_STR']); 
                if ($timestamp) {
                    $diff = time() - $timestamp;
                    if ($diff < 60) $row['WAKTU_POSTING'] = 'Baru saja';
                    else if ($diff < 3600) $row['WAKTU_POSTING'] = floor($diff / 60) . 'm';
                    else if ($diff < 86400) $row['WAKTU_POSTING'] = floor($diff / 3600) . 'j';
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

    public function createPost() {
        if (!isset($_SESSION['user_id'])) $this->sendJson(['status' => 'error', 'message' => 'Belum login']);
        $user_id = $_SESSION['user_id'];
        $konten = isset($_POST['konten']) ? trim($_POST['konten']) : '';
        $has_image = (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK);

        if (empty($konten) && !$has_image) $this->sendJson(['status' => 'error', 'message' => 'Konten kosong']);

        $post_image_db = null;
        if ($has_image) {
            $upload_dir = __DIR__ . '/../../public/assets/uploads/'; 
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) $this->sendJson(['status' => 'error', 'message' => 'Format salah']);
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $upload_dir . $new_file_name)) {
                $post_image_db = '/Sinergi/public/assets/uploads/' . $new_file_name;
            }
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
            $this->sendJson(['status' => 'success', 'action' => $result['action'], 'new_count' => $result['new_count']]);
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

    // --- FITUR YANG SEBELUMNYA KOSONG, SEKARANG DIAKTIFKAN ---
    public function deleteComment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            
            $comment_id = (int)$_POST['comment_id'];
            $user_id = (int)$_SESSION['user_id'];

            // Panggil Model
            $success = $this->postModel->deleteComment($comment_id, $user_id);

            if ($success) {
                $this->sendJson(['status' => 'success']);
            } else {
                $this->sendJson(['status' => 'error', 'message' => 'Gagal menghapus atau bukan milik Anda']);
            }
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
                    $target_dir = __DIR__ . '/../../public/assets/uploads/';
                    $filename = basename($result['image_path']);
                    $file_path = $target_dir . $filename;
                    if (file_exists($file_path)) @unlink($file_path);
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