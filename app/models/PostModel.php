<?php
// app/models/PostModel.php

class PostModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // ==========================================================
    // BAGIAN: POSTINGAN
    // ==========================================================

    // 1. Ambil Semua Postingan (Untuk Feed)
    public function getAllPosts($currentUserId) {
        // UPDATE: Menambahkan r.role_name dan JOIN ke tabel roles
        $sql = "
            SELECT 
                p.post_id AS POST_ID,    
                p.user_id AS USER_ID,
                p.konten AS KONTEN,
                p.post_image AS POST_IMAGE,
                p.like_count AS TOTAL_LIKES,       
                p.comment_count AS TOTAL_COMMENTS, 
                TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
                u.username AS USERNAME,
                u.nama_lengkap AS NAMA_LENGKAP,
                u.avatar_url AS AVATAR_URL,
                r.role_name AS ROLE_NAME,
                (SELECT COUNT(*) 
                 FROM likes l 
                 WHERE l.post_id = p.post_id AND l.user_id = :current_user_id) AS USER_SUDAH_LIKE
            FROM postingan p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN roles r ON u.role_id = r.role_id
            ORDER BY p.post_id DESC
        ";

        $stmt = oci_parse($this->conn, $sql);
        
        if (!$stmt) {
            $e = oci_error($this->conn);
            throw new Exception("Parse Error: " . $e['message']);
        }
        
        $bind_user_id = $currentUserId;
        oci_bind_by_name($stmt, ':current_user_id', $bind_user_id);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception("Execute Error getAllPosts: " . $e['message']);
        }

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $row;
        }
        oci_free_statement($stmt);
        return $results;
    }

    // 2. Ambil Satu Postingan (Untuk Detail)
    public function getPostById($postId) {
        // UPDATE: Menambahkan role_name juga disini untuk konsistensi
        $sql = "
            SELECT 
                p.post_id, 
                p.user_id, 
                p.konten, 
                p.post_image, 
                TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
                p.like_count, 
                p.comment_count,
                u.username, 
                u.nama_lengkap, 
                u.avatar_url,
                r.role_name
            FROM postingan p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE p.post_id = :post_id
        ";
        
        $stmt = oci_parse($this->conn, $sql);
        
        if (!$stmt) {
            $e = oci_error($this->conn);
            throw new Exception("Parse Error: " . $e['message']);
        }
        
        $bind_post_id = $postId;
        oci_bind_by_name($stmt, ':post_id', $bind_post_id);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception("Execute Error getPostById: " . $e['message']);
        }
        
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $row ? array_change_key_case($row, CASE_UPPER) : false;
    }

    // 3. Buat Postingan Baru
    public function createPost($userId, $konten, $imagePath) {
        $sql = "INSERT INTO postingan (USER_ID, KONTEN, POST_IMAGE, CREATED_AT, LIKE_COUNT, COMMENT_COUNT) 
                VALUES (:user_id, :konten, :img_path, SYSTIMESTAMP, 0, 0)";
        
        $stmt = oci_parse($this->conn, $sql);
        
        if (!$stmt) {
            $e = oci_error($this->conn);
            throw new Exception("Parse Error: " . $e['message']);
        }
        
        $bind_user_id = $userId;
        $bind_konten = $konten;
        $bind_img = $imagePath;
        
        oci_bind_by_name($stmt, ':user_id', $bind_user_id);
        oci_bind_by_name($stmt, ':konten', $bind_konten);
        oci_bind_by_name($stmt, ':img_path', $bind_img);

        $res = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        
        if (!$res) {
            $e = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception("Execute Error createPost: " . $e['message']);
        }
        
        oci_free_statement($stmt);
        return true;
    }

    // 4. Hapus Postingan
    public function deletePost($post_id, $user_id) {
        try {
            // Konversi ke int agar aman
            $pid_clean = intval($post_id);
            $uid_clean = intval($user_id);

            // Ambil data postingan dulu untuk dapatkan image_path
            $sql_get = "SELECT POST_IMAGE FROM postingan WHERE post_id = :pid AND user_id = :uid";
            $stmt_get = oci_parse($this->conn, $sql_get);
            oci_bind_by_name($stmt_get, ':pid', $pid_clean);
            oci_bind_by_name($stmt_get, ':uid', $uid_clean);
            oci_execute($stmt_get);
            $row = oci_fetch_assoc($stmt_get);
            oci_free_statement($stmt_get);

            if (!$row) {
                return ['status' => false, 'message' => 'Postingan tidak ditemukan atau bukan milik Anda.'];
            }

            $image_path = $row['POST_IMAGE'] ?? null;

            // Hapus dari database
            $sql_del = "DELETE FROM postingan WHERE post_id = :pid AND user_id = :uid";
            $stmt_del = oci_parse($this->conn, $sql_del);
            oci_bind_by_name($stmt_del, ':pid', $pid_clean);
            oci_bind_by_name($stmt_del, ':uid', $uid_clean);

            if (oci_execute($stmt_del, OCI_COMMIT_ON_SUCCESS)) {
                oci_free_statement($stmt_del);
                return ['status' => true, 'image_path' => $image_path];
            } else {
                $e = oci_error($stmt_del);
                oci_free_statement($stmt_del);
                return ['status' => false, 'message' => 'Gagal hapus dari DB: ' . $e['message']];
            }

        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // ==========================================================
    // BAGIAN: LIKE
    // ==========================================================
    public function toggleLike($userId, $postId) {
        $sql_check = "SELECT COUNT(*) AS TOTAL FROM likes WHERE user_id = :user_id AND post_id = :post_id";
        $stmt_check = oci_parse($this->conn, $sql_check);
        
        $bind_user_id = $userId;
        $bind_post_id = $postId;
        
        oci_bind_by_name($stmt_check, ':user_id', $bind_user_id);
        oci_bind_by_name($stmt_check, ':post_id', $bind_post_id);
        oci_execute($stmt_check);
        $row = oci_fetch_assoc($stmt_check);
        $isLiked = ($row['TOTAL'] > 0);
        oci_free_statement($stmt_check);

        if ($isLiked) {
            $sql_act = "DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id";
            $sql_upd = "UPDATE postingan SET like_count = GREATEST(0, like_count - 1) WHERE post_id = :post_id";
            $action = 'unliked';
        } else {
            $sql_act = "INSERT INTO likes (user_id, post_id) VALUES (:user_id, :post_id)";
            $sql_upd = "UPDATE postingan SET like_count = like_count + 1 WHERE post_id = :post_id";
            $action = 'liked';
        }

        $stmt_act = oci_parse($this->conn, $sql_act);
        oci_bind_by_name($stmt_act, ':user_id', $bind_user_id);
        oci_bind_by_name($stmt_act, ':post_id', $bind_post_id);
        
        if(!oci_execute($stmt_act, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return false;
        }

        $stmt_upd = oci_parse($this->conn, $sql_upd);
        oci_bind_by_name($stmt_upd, ':post_id', $bind_post_id);
        
        if(!oci_execute($stmt_upd, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return false;
        }

        oci_commit($this->conn);
        oci_free_statement($stmt_act);
        oci_free_statement($stmt_upd);

        $sql_cnt = "SELECT like_count FROM postingan WHERE post_id = :post_id";
        $stmt_cnt = oci_parse($this->conn, $sql_cnt);
        oci_bind_by_name($stmt_cnt, ':post_id', $bind_post_id);
        oci_execute($stmt_cnt);
        $rCount = oci_fetch_assoc($stmt_cnt);
        oci_free_statement($stmt_cnt);

        return ['action' => $action, 'new_count' => $rCount['LIKE_COUNT']];
    }

    // ==========================================================
    // BAGIAN: KOMENTAR
    // ==========================================================
    
    public function getParentComments($postId) {
        $sql = "SELECT c.*, u.username, u.nama_lengkap, u.avatar_url,
                (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as reply_count
                FROM comments c JOIN users u ON c.user_id = u.user_id
                WHERE c.post_id = :post_id AND c.parent_comment_id IS NULL ORDER BY c.created_at DESC";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':post_id', $postId);
        oci_execute($stmt);
        $res = [];
        while($r = oci_fetch_assoc($stmt)) { $res[] = array_change_key_case($r, CASE_UPPER); }
        oci_free_statement($stmt);
        return $res;
    }

    public function getReplies($parentCommentId) {
        $sql = "SELECT c.*, u.username, u.nama_lengkap, u.avatar_url
                FROM comments c JOIN users u ON c.user_id = u.user_id
                WHERE c.parent_comment_id = :parent_id ORDER BY c.created_at ASC";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':parent_id', $parentCommentId);
        oci_execute($stmt);
        $res = [];
        while($r = oci_fetch_assoc($stmt)) { $res[] = array_change_key_case($r, CASE_UPPER); }
        oci_free_statement($stmt);
        return $res;
    }

    public function addComment($userId, $postId, $content, $parentId = null) {
        $sql_id = "SELECT NVL(MAX(comment_id), 0) + 1 as next_id FROM comments";
        $stmt_id = oci_parse($this->conn, $sql_id);
        oci_execute($stmt_id);
        $row_id = oci_fetch_assoc($stmt_id);
        $newId = $row_id['NEXT_ID'];
        oci_free_statement($stmt_id);

        $sql = "INSERT INTO comments (comment_id, post_id, user_id, isi_komen, parent_comment_id, created_at) 
                VALUES (:comment_id, :post_id, :user_id, :isi_komen, :parent_id, SYSTIMESTAMP)";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':comment_id', $newId);
        oci_bind_by_name($stmt, ':post_id', $postId);
        oci_bind_by_name($stmt, ':user_id', $userId);
        oci_bind_by_name($stmt, ':isi_komen', $content);
        oci_bind_by_name($stmt, ':parent_id', $parentId);
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) { oci_rollback($this->conn); return false; }

        $sql_upd = "UPDATE postingan SET comment_count = comment_count + 1 WHERE post_id = :post_id";
        $stmt_upd = oci_parse($this->conn, $sql_upd);
        oci_bind_by_name($stmt_upd, ':post_id', $postId);
        oci_execute($stmt_upd, OCI_NO_AUTO_COMMIT);
        
        oci_commit($this->conn);
        return $newId;
    }
}
?>