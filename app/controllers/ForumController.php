<?php
// File: app/controllers/ForumController.php

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../models/ForumModel.php';
require_once __DIR__ . '/../models/GroupModel.php'; 

class ForumController {

    private $conn;
    private $forumModel;
    private $groupModel; 

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        $this->forumModel = new ForumModel($dbConnection);
        $this->groupModel = new GroupModel($dbConnection);
    }

    // Helper JSON Clean
    private function sendJson($data, $code = 200) {
        while (ob_get_level()) ob_end_clean();
        ini_set('display_errors', 0);
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    /**
     * Menyimpan Postingan Forum Baru
     */
    public function storeForumPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            
            $group_id = (int)$_POST['group_id']; 
            $user_id  = (int)$_SESSION['user_id'];
            $konten   = trim($_POST['konten']);
            $image_file = $_FILES['post_image'] ?? null; 

            // Validasi
            if (empty($konten) && (empty($image_file) || $image_file['error'] !== UPLOAD_ERR_OK)) {
                header("Location: index.php?page=messages&group_id=$group_id&tab=forum&error=empty_content");
                exit();
            }

            $image_path_db = null;

            // Upload Gambar
            if (isset($image_file) && $image_file['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../public/uploads/forum_posts/'; 
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $ext = strtolower(pathinfo($image_file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $new_filename = 'post_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($image_file['tmp_name'], $upload_dir . $new_filename)) {
                        $image_path_db = $new_filename;
                    }
                } else {
                    header("Location: index.php?page=messages&group_id=$group_id&tab=forum&error=invalid_file");
                    exit();
                }
            }

            // Panggil Model
            $new_post_id = $this->forumModel->createForumPost($group_id, $user_id, $konten, $image_path_db);

            if ($new_post_id) {
                header("Location: index.php?page=messages&group_id=$group_id&tab=forum&success=posted");
            } else {
                header("Location: index.php?page=messages&group_id=$group_id&tab=forum&error=db_error");
            }
            exit();
        } else {
            header('Location: index.php?page=login');
            exit();
        }
    }

    /**
     * Menampilkan Detail Postingan Forum
     */
    public function showForumPostDetail() {
        $post_id = (int)($_GET['post_id'] ?? 0);
        $user_id = (int)($_SESSION['user_id'] ?? 0);

        if ($post_id === 0) {
            header('Location: index.php?page=dashboard'); exit();
        }

        // Ambil data post
        $post = $this->forumModel->getForumPostById($post_id, $user_id);
        if (!$post) { echo "Postingan tidak ditemukan."; exit(); }

        // Ambil data komentar
        $comments = $this->forumModel->getForumPostComments($post_id);
        
        // Ambil data groups untuk sidebar
        $groups = $this->groupModel->getGroupsByUserId($user_id);
        $current_group_id = $post['group_id']; 

        require __DIR__ . '/../views/forum_post_detail.php';
    }

    // --- API ENDPOINTS ---

    public function apiLikeForumPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $post_id = (int)$_POST['post_id'] ?? 0;
            $user_id = (int)$_SESSION['user_id'];

            if ($post_id > 0) {
                $status = $this->forumModel->toggleForumLike($post_id, $user_id);
                if ($status) {
                    $this->sendJson(['status' => 'success', 'action' => $status]);
                } else {
                    $this->sendJson(['status' => 'error', 'message' => 'Database error'], 500);
                }
            } else {
                $this->sendJson(['status' => 'error', 'message' => 'Invalid ID'], 400);
            }
        } else {
            $this->sendJson(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
    }

    public function apiGetForumComments() {
        $post_id = (int)($_GET['post_id'] ?? 0);
        if ($post_id > 0) {
            $comments = $this->forumModel->getForumPostComments($post_id);
            $this->sendJson(['status' => 'success', 'data' => $comments]);
        } else {
            $this->sendJson(['status' => 'error', 'message' => 'Invalid ID'], 400);
        }
    }

    public function apiStoreForumComment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $post_id = (int)$_POST['post_id'];
            $isi = trim($_POST['isi_komentar']);
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $user_id = $_SESSION['user_id'];
            
            if (empty($isi)) {
                $this->sendJson(['status' => 'error', 'message' => 'Komentar kosong'], 400);
            }
            
            $newId = $this->forumModel->createForumComment($post_id, $user_id, $isi, $parent_id);
            
            if ($newId) {
                $responseData = [
                    'comment_id' => $newId,
                    'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'User',
                    'avatar_url' => '/Sinergi/public/uploads/avatars/' . ($_SESSION['avatar_url'] ?? 'user.png'),
                    'isi_komentar' => htmlspecialchars($isi),
                    'waktu_lalu' => 'Baru saja',
                    'parent_comment_id' => $parent_id
                ];
                $this->sendJson(['status' => 'success', 'data' => $responseData]);
            } else {
                $this->sendJson(['status' => 'error', 'message' => 'Gagal menyimpan'], 500);
            }
        } else {
            $this->sendJson(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
    }

    public function apiDeleteForumPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $post_id = (int)$_POST['post_id'];
            $user_id = (int)$_SESSION['user_id'];

            $success = $this->forumModel->deleteForumPostById($post_id, $user_id); 

            if ($success) {
                $this->sendJson(['status' => 'success']);
            } else {
                $this->sendJson(['status' => 'error', 'message' => 'Gagal atau bukan milik Anda'], 500);
            }
        } else {
            $this->sendJson(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
    }

    public function apiDeleteForumComment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $comment_id = (int)$_POST['comment_id'];
            $user_id = (int)$_SESSION['user_id'];

            $success = $this->forumModel->deleteForumCommentById($comment_id, $user_id);

            if ($success) {
                $this->sendJson(['status' => 'success']);
            } else {
                $this->sendJson(['status' => 'error', 'message' => 'Gagal hapus'], 500);
            }
        } else {
            $this->sendJson(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
    }
}
?>