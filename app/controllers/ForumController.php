<?php
// File: app/controllers/ForumController.php
// UPDATE: Menambahkan error suppression pada API endpoint agar JSON tidak rusak

require_once __DIR__ . '/../models/ForumModel.php'; 
require_once __DIR__ . '/../models/GroupModel.php'; 

/**
 * Menangani penyimpanan postingan diskusi baru.
 * Route: index.php?page=store-forum-post
 */
function handleStoreForumPost() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        $group_id = (int)$_POST['group_id']; 
        $user_id  = (int)$_SESSION['user_id'];
        $konten   = trim($_POST['konten']);
        $image_file = $_FILES['post_image'] ?? null; 

        // Validasi Input: Minimal ada konten TEKS atau GAMBAR
        if (empty($konten) && (empty($image_file) || $image_file['error'] !== UPLOAD_ERR_OK)) {
            header("Location: index.php?page=messages&group_id=$group_id&tab=forum&error=empty_content");
            exit();
        }

        $image_path_db = null;

        // Proses Upload Gambar
        if (isset($image_file) && $image_file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/uploads/forum_posts/'; 
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $ext = strtolower(pathinfo($image_file['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed_ext)) {
                $new_filename = 'post_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
                $dest_path = $upload_dir . $new_filename;

                if (move_uploaded_file($image_file['tmp_name'], $dest_path)) {
                    $image_path_db = $new_filename;
                } else {
                    header("Location: index.php?page=messages&group_id=$group_id&tab=forum&error=upload_failed");
                    exit();
                }
            } else {
                header("Location: index.php?page=messages&group_id=$group_id&tab=forum&error=invalid_file");
                exit();
            }
        }

        // Simpan ke Database
        $new_post_id = createForumPost($group_id, $user_id, $konten, $image_path_db);

        if ($new_post_id) {
            header("Location: index.php?page=messages&group_id=$group_id&tab=forum&success=posted");
            exit();
        } else {
            header("Location: index.php?page=messages&group_id=$group_id&tab=forum&error=db_error");
            exit();
        }

    } else {
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menangani Like Postingan (AJAX).
 * Route: index.php?page=api-like-forum-post
 */
function handleLikeForumPostAPI() {
    // --- MATIKAN ERROR HTML AGAR JSON VALID ---
    error_reporting(0); 
    ini_set('display_errors', 0);
    // ------------------------------------------

    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        $post_id = (int)($_POST['post_id'] ?? 0);
        $user_id = (int)$_SESSION['user_id'];

        if ($post_id > 0) {
            $status = toggleForumLike($post_id, $user_id); 
            
            if ($status) {
                echo json_encode(['status' => 'success', 'action' => $status]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Post ID']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    }
    exit();
}

/**
 * Helper: Menyiapkan data untuk Tab Forum
 */
function getForumTabData($group_id) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $user_id = $_SESSION['user_id'] ?? 0;
    return getForumPostsByGroupId($group_id, $user_id);
}

/**
 * API: Ambil Komentar
 */
function handleGetForumCommentsAPI() {
    // --- MATIKAN ERROR HTML ---
    error_reporting(0); 
    ini_set('display_errors', 0);
    // --------------------------

    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');
    
    $post_id = (int)($_GET['post_id'] ?? 0);
    if ($post_id > 0) {
        $comments = getForumPostComments($post_id);
        echo json_encode(['status' => 'success', 'data' => $comments]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    }
    exit();
}

/**
 * API: Kirim komentar baru
 */
function handleStoreForumCommentAPI() {
    error_reporting(0); 
    ini_set('display_errors', 0);

    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        $post_id = (int)$_POST['post_id'];
        $isi = trim($_POST['isi_komentar']);
        // Tangkap Parent ID (kalau tidak ada, null)
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $user_id = $_SESSION['user_id'];
        
        if (empty($isi)) {
            echo json_encode(['status' => 'error', 'message' => 'Komentar kosong']);
            exit();
        }
        
        // Panggil fungsi model yang baru (4 parameter)
        $newId = createForumComment($post_id, $user_id, $isi, $parent_id);
        
        if ($newId) {
            echo json_encode([
                'status' => 'success', 
                'data' => [
                    'comment_id' => $newId,
                    'nama_lengkap' => $_SESSION['nama_lengkap'],
                    'avatar_url' => '/Sinergi/public/uploads/avatars/' . ($_SESSION['avatar_url'] ?? 'user.png'),
                    'isi_komentar' => htmlspecialchars($isi),
                    'waktu_lalu' => 'Baru saja',
                    // Kirim balik parent_id buat update UI (opsional)
                    'parent_comment_id' => $parent_id
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    }
    exit();
}

/**
 * API: Hapus Postingan Forum
 */
function handleDeleteForumPostAPI() {
    // --- MATIKAN ERROR HTML ---
    error_reporting(0); 
    ini_set('display_errors', 0);
    // --------------------------

    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        $post_id = (int)$_POST['post_id'];
        $user_id = (int)$_SESSION['user_id'];

        $success = deleteForumPostById($post_id, $user_id); 

        if ($success) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus atau bukan milik Anda']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    }
    exit();
}

/**
 * Menampilkan Halaman Detail Postingan Forum
 */
function showForumPostDetail() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $post_id = (int)($_GET['post_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    if ($post_id === 0) {
        header('Location: index.php?page=dashboard');
        exit();
    }

    $post = getForumPostById($post_id, $user_id);
    if (!$post) { echo "Postingan tidak ditemukan."; exit(); }

    $comments = getForumPostComments($post_id);
    $groups = getGroupsByUserId($user_id); 
    $current_group_id = $post['group_id']; 

    require 'app/views/forum_post_detail.php';
}

function handleDeleteForumCommentAPI() {
    error_reporting(0);
    ini_set('display_errors', 0);

    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        $comment_id = (int)$_POST['comment_id'];
        $user_id = (int)$_SESSION['user_id'];

        $success = deleteForumCommentById($comment_id, $user_id); // Fungsi di Model

        if ($success) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    }
    exit();
}
?>