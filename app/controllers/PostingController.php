<?php
require_once __DIR__ . '/../../config/koneksi.php';
// Load Model Baru
require_once __DIR__ . '/../models/PostModel.php';

class PostingController {
    
    private $postModel; // Property untuk menyimpan objek Model
    private $defaultAvatar;

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Instansiasi Model dengan mengirim koneksi DB
        $this->postModel = new PostModel($dbConnection);
        $this->defaultAvatar = '/Sinergi/public/assets/images/user.png';
    }

    public function showDashboard() {
        require __DIR__ . '/../views/dashboard.php';
    }

    public function showPostDetail() {
        $post_id = isset($_GET['id']) ? $_GET['id'] : 0;
        
        if ($post_id == 0) {
            echo "<div class='p-4 text-red-500'>Error: ID Postingan tidak valid.</div>";
            return;
        }
        
        // 1. PANGGIL DATA DARI MODEL
        $post = $this->postModel->getPostById($post_id);
        
        if(!$post){
            echo "<div class='p-4'>Postingan tidak ditemukan.</div>";
            return;
        }

        // 2. FORMAT DATA (Logika Tampilan tetap di Controller/View Helper)
        $post['AVATAR_URL_FIXED'] = (!empty($post['AVATAR_URL']) && strlen($post['AVATAR_URL']) > 5) 
                                    ? $post['AVATAR_URL'] : $this->defaultAvatar;

        $post['POST_IMAGE'] = $post['POST_IMAGE'] ?? '';

        if (!empty($post['WAKTU_FIX'])) {
            $timestamp = strtotime($post['WAKTU_FIX']);
            $post['WAKTU_POSTING'] = date('H:i \· d M Y', $timestamp); 
        } else {
            $post['WAKTU_POSTING'] = '-';
        }

        $post['TOTAL_LIKES'] = $post['LIKE_COUNT'] ?? 0;
        $post['TOTAL_COMMENTS'] = $post['COMMENT_COUNT'] ?? 0;

        // 3. AMBIL KOMENTAR DARI MODEL
        $rawComments = $this->postModel->getParentComments($post_id);
        $comments = [];

        foreach ($rawComments as $c) {
            // Format Avatar & Waktu Komentar
            $c['AVATAR_URL_FIXED'] = (!empty($c['AVATAR_URL']) && strlen($c['AVATAR_URL']) > 5) 
                                     ? $c['AVATAR_URL'] : $this->defaultAvatar;
            
            if (!empty($c['WAKTU_FIX'])) {
                $c_time = strtotime($c['WAKTU_FIX']);
                $c['WAKTU_KOMEN'] = date('d M H:i', $c_time);
            } else {
                $c['WAKTU_KOMEN'] = 'Baru saja';
            }

            // Ambil Replies dari Model
            $rawReplies = $this->postModel->getReplies($c['COMMENT_ID']);
            $c['REPLIES'] = [];

            foreach ($rawReplies as $r) {
                $r['AVATAR_URL_FIXED'] = (!empty($r['AVATAR_URL']) && strlen($r['AVATAR_URL']) > 5) 
                                         ? $r['AVATAR_URL'] : $this->defaultAvatar;
                
                if (!empty($r['WAKTU_FIX'])) {
                    $r_time = strtotime($r['WAKTU_FIX']);
                    $r['WAKTU_KOMEN'] = date('d M H:i', $r_time);
                } else {
                    $r['WAKTU_KOMEN'] = 'Baru saja';
                }
                $c['REPLIES'][] = $r;
            }

            $comments[] = $c;
        }

        // 4. LEMPAR KE VIEW
        require __DIR__ . '/../views/post_detail.php';
    }
}
?>