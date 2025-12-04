<?php
// File: app/models/PostModel.php
// MODEL FINAL: Terintegrasi dengan Notifikasi, Kode Rapih & Aman (Anti ORA-01745)

require_once __DIR__ . '/NotificationModel.php'; 

class PostModel {

    private $conn;
    private $notifModel;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        // Inisialisasi Model Notifikasi untuk fitur alert
        $this->notifModel = new NotificationModel($dbConnection);
    }

    // ==================================================================
    // 1. PENGAMBILAN DATA POSTINGAN (READ)
    // ==================================================================

    /**
     * Mengambil semua postingan untuk Dashboard
     */
    public function getAllPosts($current_user_id) {
        $sql = "SELECT 
                    p.post_id, p.user_id, p.konten, p.post_image,
                    NVL(p.like_count, 0) as LIKE_COUNT,       
                    NVL(p.comment_count, 0) as COMMENT_COUNT, 
                    TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
                    u.username, u.nama_lengkap, u.avatar_url,
                    (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.post_id AND l.user_id = :p_curr_uid) AS USER_SUDAH_LIKE
                FROM postingan p
                JOIN users u ON p.user_id = u.user_id
                ORDER BY p.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        $clean_uid = (int)$current_user_id;
        oci_bind_by_name($stmt, ':p_curr_uid', $clean_uid);

        if (!oci_execute($stmt)) return [];
        
        $posts = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $posts[] = $this->processRowData($row);
        }
        return $posts;
    }

    /**
     * Mengambil postingan spesifik milik satu user (Halaman Profil)
     */
    public function getPostsByUserId($target_user_id, $current_user_id) {
        $sql = "SELECT 
                    p.post_id, p.user_id, p.konten, p.post_image,
                    NVL(p.like_count, 0) as LIKE_COUNT,       
                    NVL(p.comment_count, 0) as COMMENT_COUNT, 
                    TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
                    u.username, u.nama_lengkap, u.avatar_url,
                    (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.post_id AND l.user_id = :p_curr_uid) AS USER_SUDAH_LIKE
                FROM postingan p
                JOIN users u ON p.user_id = u.user_id
                WHERE p.user_id = :p_target_uid
                ORDER BY p.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        $clean_target = (int)$target_user_id;
        $clean_curr   = (int)$current_user_id;
        
        oci_bind_by_name($stmt, ':p_target_uid', $clean_target);
        oci_bind_by_name($stmt, ':p_curr_uid', $clean_curr);

        if (!oci_execute($stmt)) return [];
        
        $posts = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $posts[] = $this->processRowData($row);
        }
        return $posts;
    }

    /**
     * Mengambil Detail satu postingan
     */
    public function getPostById($post_id) {
        $sql = "SELECT p.*, 
                       TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
                       u.username, u.nama_lengkap, u.avatar_url,
                       NVL(p.like_count, 0) as LIKE_COUNT,
                       NVL(p.comment_count, 0) as COMMENT_COUNT
                FROM postingan p
                JOIN users u ON p.user_id = u.user_id
                WHERE p.post_id = :p_pid";

        $stmt = oci_parse($this->conn, $sql);
        $clean_pid = (int)$post_id;
        oci_bind_by_name($stmt, ':p_pid', $clean_pid);
        
        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            if ($row) return $this->processRowData($row, true);
        }
        return null;
    }

    // ==================================================================
    // 2. AKSI UTAMA (CREATE, LIKE, COMMENT)
    // ==================================================================

    /**
     * Membuat Postingan Baru
     */
    public function createPost($user_id, $konten, $post_image_db) {
        $sql = "INSERT INTO postingan (user_id, konten, post_image, created_at, like_count, comment_count) 
                VALUES (:p_uid, EMPTY_CLOB(), :p_img, SYSTIMESTAMP, 0, 0)
                RETURNING konten INTO :p_clob_loc";
        
        $stmt = oci_parse($this->conn, $sql);
        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);
        $uid = (int)$user_id;
        
        oci_bind_by_name($stmt, ':p_uid', $uid);
        oci_bind_by_name($stmt, ':p_img', $post_image_db);
        oci_bind_by_name($stmt, ':p_clob_loc', $clob, -1, OCI_B_CLOB);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt);
            throw new Exception("DB Error: " . $e['message']);
        }

        if (!empty($konten)) { $clob->save($konten); } else { $clob->save(' '); }

        oci_commit($this->conn);
        $clob->free();
        oci_free_statement($stmt);
        return true;
    }
    /**
     * Toggle Like (Like / Unlike) + Kirim Notifikasi
     */
    public function toggleLike($user_id, $post_id) {
        $checkSql = "SELECT COUNT(*) as hitung FROM likes WHERE post_id = :p_pid AND user_id = :p_uid";
        $stmtCheck = oci_parse($this->conn, $checkSql);
        oci_bind_by_name($stmtCheck, ':p_pid', $post_id);
        oci_bind_by_name($stmtCheck, ':p_uid', $user_id);
        oci_execute($stmtCheck);
        $row = oci_fetch_assoc($stmtCheck);
        $alreadyLiked = ($row['HITUNG'] > 0);
        oci_free_statement($stmtCheck);
        
        if ($alreadyLiked) {
            $sql = "DELETE FROM likes WHERE post_id = :p_pid AND user_id = :p_uid";
            $sqlUpdate = "UPDATE postingan SET like_count = GREATEST(like_count - 1, 0) WHERE post_id = :p_pid";
            $status = 'unliked';
        } else {
            $sql = "INSERT INTO likes (post_id, user_id, created_at) VALUES (:p_pid, :p_uid, SYSTIMESTAMP)";
            $sqlUpdate = "UPDATE postingan SET like_count = like_count + 1 WHERE post_id = :p_pid";
            $status = 'liked';
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_pid', $post_id);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;

        $stmtUp = oci_parse($this->conn, $sqlUpdate);
        oci_bind_by_name($stmtUp, ':p_pid', $post_id);
        oci_execute($stmtUp, OCI_NO_AUTO_COMMIT);

        // NOTIFIKASI LIKE DASHBOARD
        if ($status === 'liked') {
            $sqlOwner = "SELECT user_id FROM postingan WHERE post_id = :p_pid";
            $stmtOwner = oci_parse($this->conn, $sqlOwner);
            oci_bind_by_name($stmtOwner, ':p_pid', $post_id);
            oci_execute($stmtOwner);
            $rowOwner = oci_fetch_assoc($stmtOwner);
            
            if ($rowOwner && $rowOwner['USER_ID'] != $user_id) {
                $this->notifModel->createNotification(
                    $rowOwner['USER_ID'], 
                    $user_id, 
                    'like', 
                    'menyukai postingan Anda.', 
                    ['post_id' => $post_id] // Array Baru
                );
            }
            oci_free_statement($stmtOwner);
        }

        oci_commit($this->conn);

        // Get New Count
        $sqlCount = "SELECT like_count FROM postingan WHERE post_id = :p_pid";
        $stmtCount = oci_parse($this->conn, $sqlCount);
        oci_bind_by_name($stmtCount, ':p_pid', $post_id);
        oci_execute($stmtCount);
        $rowCount = oci_fetch_assoc($stmtCount);
        
        return ['action' => $status, 'new_count' => $rowCount['LIKE_COUNT']];
    }

    /**
     * Tambah Komentar + Kirim Notifikasi
     */
    public function addComment($user_id, $post_id, $isi, $parent_id = null) {
        $sql = "INSERT INTO comments (post_id, user_id, isi_komen, parent_comment_id, created_at) 
                VALUES (:p_pid, :p_uid, :p_isi, :p_parent, SYSTIMESTAMP)
                RETURNING comment_id INTO :new_id";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_pid', $post_id);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        oci_bind_by_name($stmt, ':p_isi', $isi);
        oci_bind_by_name($stmt, ':p_parent', $parent_id);
        
        $new_id = 0;
        oci_bind_by_name($stmt, ':new_id', $new_id, -1, SQLT_INT);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;

        $sqlUp = "UPDATE postingan SET comment_count = comment_count + 1 WHERE post_id = :p_pid";
        $stmtUp = oci_parse($this->conn, $sqlUp);
        oci_bind_by_name($stmtUp, ':p_pid', $post_id);
        oci_execute($stmtUp, OCI_NO_AUTO_COMMIT);

        // NOTIFIKASI KOMEN DASHBOARD
        $sqlOwner = "SELECT user_id FROM postingan WHERE post_id = :p_pid";
        $stmtOwner = oci_parse($this->conn, $sqlOwner);
        oci_bind_by_name($stmtOwner, ':p_pid', $post_id);
        oci_execute($stmtOwner);
        $rowOwner = oci_fetch_assoc($stmtOwner);
        
        if ($rowOwner && $rowOwner['USER_ID'] != $user_id) {
            $this->notifModel->createNotification(
                $rowOwner['USER_ID'], 
                $user_id, 
                'comment', 
                'mengomentari postingan Anda.', 
                [
                    'post_id' => $post_id,
                    'comment_id' => $new_id // ID Komentar Baru
                ]
            );
        }
        oci_free_statement($stmtOwner);

        oci_commit($this->conn);
        return $new_id;
    }

    // ==================================================================
    // 3. PENGAMBILAN KOMENTAR & REPLY
    // ==================================================================

    public function getParentComments($post_id) {
        $sql = "SELECT c.comment_id, c.user_id, c.post_id, c.isi_komen, c.parent_comment_id,
                       TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
                       u.username, u.nama_lengkap, u.avatar_url
                FROM comments c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.post_id = :p_pid AND c.parent_comment_id IS NULL
                ORDER BY c.created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_pid', $post_id);
        oci_execute($stmt);

        $comments = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $comments[] = $this->processRowData($row);
        }
        return $comments;
    }

    public function getReplies($parent_id) {
        $sql = "SELECT c.comment_id, c.user_id, c.post_id, c.isi_komen, 
                       TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
                       u.username, u.nama_lengkap, u.avatar_url
                FROM comments c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.parent_comment_id = :p_parent
                ORDER BY c.created_at ASC";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_parent', $parent_id);
        oci_execute($stmt);
        
        $replies = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $replies[] = $this->processRowData($row);
        }
        return $replies;
    }

    // ==================================================================
    // 4. HAPUS DATA (POST & COMMENT)
    // ==================================================================

    /**
     * Hapus Postingan (Beserta Like & Komentar)
     */
    public function deletePost($post_id, $user_id) {
        $sqlCheck = "SELECT post_image FROM postingan WHERE post_id = :pid AND user_id = :p_uid";
        $stmtCheck = oci_parse($this->conn, $sqlCheck);
        oci_bind_by_name($stmtCheck, ':pid', $post_id);
        oci_bind_by_name($stmtCheck, ':p_uid', $user_id);
        oci_execute($stmtCheck);
        $row = oci_fetch_assoc($stmtCheck);

        if (!$row) return ['status' => false, 'message' => 'Postingan tidak ditemukan atau bukan milik Anda'];
        $image_path = $row['POST_IMAGE'] ?? null;

        // Hapus Notifikasi Terkait Dashboard Post Ini
        $sqlNotif = "DELETE FROM notifications WHERE related_post_id = :pid";
        $stmtN = oci_parse($this->conn, $sqlNotif);
        oci_bind_by_name($stmtN, ':pid', $post_id);
        oci_execute($stmtN, OCI_NO_AUTO_COMMIT);

        // Hapus Likes & Comments (Dependency)
        $sqlL = "DELETE FROM likes WHERE post_id = :pid"; 
        $stmtL = oci_parse($this->conn, $sqlL);
        oci_bind_by_name($stmtL, ':pid', $post_id);
        oci_execute($stmtL, OCI_NO_AUTO_COMMIT);

        $sqlC = "DELETE FROM comments WHERE post_id = :pid"; 
        $stmtC = oci_parse($this->conn, $sqlC);
        oci_bind_by_name($stmtC, ':pid', $post_id);
        oci_execute($stmtC, OCI_NO_AUTO_COMMIT);

        // Hapus Post Utama
        $sql = "DELETE FROM postingan WHERE post_id = :pid AND user_id = :p_uid";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':pid', $post_id);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            return ['status' => true, 'image_path' => $image_path];
        } else {
            return ['status' => false, 'message' => 'Gagal menghapus dari DB'];
        }
    }

    /**
     * Hapus Komentar (Beserta Balasannya & Update Counter)
     */
    public function deleteComment($comment_id, $user_id) {
        // 1. Cek Kepemilikan & Ambil Post ID
        $sqlCheck = "SELECT post_id FROM comments WHERE comment_id = :p_cid AND user_id = :p_uid";
        $stmtCheck = oci_parse($this->conn, $sqlCheck);
        
        $clean_cid = (int)$comment_id;
        $clean_uid = (int)$user_id;
        
        oci_bind_by_name($stmtCheck, ':p_cid', $clean_cid);
        oci_bind_by_name($stmtCheck, ':p_uid', $clean_uid);
        
        if (!oci_execute($stmtCheck)) return false;
        
        $row = oci_fetch_assoc($stmtCheck);
        if (!$row) return false; // Komentar tidak ditemukan atau bukan milik user
        
        $post_id = $row['POST_ID'];
        oci_free_statement($stmtCheck);

        // 2. Hapus BALASANNYA Dulu (Anak-anaknya)
        // Ini penting! Kalau tidak dihapus, Induk gak bisa dihapus (Constraint Error)
        $sqlDelChild = "DELETE FROM comments WHERE parent_comment_id = :p_parent_id";
        $stmtChild = oci_parse($this->conn, $sqlDelChild);
        oci_bind_by_name($stmtChild, ':p_parent_id', $clean_cid);
        oci_execute($stmtChild, OCI_NO_AUTO_COMMIT);
        
        // Hitung berapa anak yang dihapus (untuk update counter)
        $deleted_children = oci_num_rows($stmtChild);
        oci_free_statement($stmtChild);

        // 3. Hapus KOMENTAR UTAMA (Bapaknya)
        $sqlDel = "DELETE FROM comments WHERE comment_id = :p_cid";
        $stmtDel = oci_parse($this->conn, $sqlDel);
        oci_bind_by_name($stmtDel, ':p_cid', $clean_cid);

        if (!oci_execute($stmtDel, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return false;
        }
        
        // 4. Kurangi Counter di Tabel Postingan
        // Total yang berkurang = 1 (Induk) + Jumlah Anak
        $total_deleted = 1 + $deleted_children;
        
        $sqlUp = "UPDATE postingan SET comment_count = comment_count - :p_total WHERE post_id = :p_pid";
        $stmtUp = oci_parse($this->conn, $sqlUp);
        oci_bind_by_name($stmtUp, ':p_total', $total_deleted);
        oci_bind_by_name($stmtUp, ':p_pid', $post_id);
        oci_execute($stmtUp, OCI_NO_AUTO_COMMIT);

        // 5. Commit Semuanya
        oci_commit($this->conn);
        return true;
    }

    // ==================================================================
    // 5. HELPER DATA
    // ==================================================================
    private function processRowData($row, $isDetail = false) {
        $data = array_change_key_case($row, CASE_UPPER);
        
        // Baca CLOB
        if (isset($data['KONTEN']) && is_object($data['KONTEN'])) {
            $data['KONTEN'] = $data['KONTEN']->load();
        }
        
        // Fix Path Gambar
        if (!empty($data['POST_IMAGE']) && strpos($data['POST_IMAGE'], '/') === false) {
            $data['POST_IMAGE'] = '/Sinergi/public/assets/uploads/' . $data['POST_IMAGE'];
        }
        
        // Fix Avatar
        if (empty($data['AVATAR_URL'])) {
             $data['AVATAR_URL'] = '/sinergi/public/assets/images/user.png';
        }
        
        return $data;
    }
}
?>