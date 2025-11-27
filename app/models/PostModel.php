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
        // PERBAIKAN: Gunakan bind dengan variable yang lebih jelas
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
                (SELECT COUNT(*) 
                 FROM likes l 
                 WHERE l.post_id = p.post_id AND l.user_id = :current_user_id) AS USER_SUDAH_LIKE
            FROM postingan p
            JOIN users u ON p.user_id = u.user_id
            ORDER BY p.post_id DESC
        ";

        $stmt = oci_parse($this->conn, $sql);
        
        if (!$stmt) {
            $e = oci_error($this->conn);
            throw new Exception("Parse Error: " . $e['message']);
        }
        
        // Bind dengan variable lokal
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
                u.avatar_url
            FROM postingan p
            JOIN users u ON p.user_id = u.user_id
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
    public function deletePost($postId, $userId) {
        // Cek data dulu
        $sql_cek = "SELECT USER_ID, POST_IMAGE FROM postingan WHERE POST_ID = :post_id";
        $stmt_cek = oci_parse($this->conn, $sql_cek);
        
        $bind_post_id = $postId;
        oci_bind_by_name($stmt_cek, ':post_id', $bind_post_id);
        oci_execute($stmt_cek);
        $row = oci_fetch_assoc($stmt_cek);
        oci_free_statement($stmt_cek);

        if (!$row) return ['status' => false, 'message' => 'Postingan tidak ditemukan'];
        if ($row['USER_ID'] != $userId) return ['status' => false, 'message' => 'Bukan milik Anda'];

        // Hapus Data
        $sql_del = "DELETE FROM postingan WHERE POST_ID = :post_id";
        $stmt_del = oci_parse($this->conn, $sql_del);
        
        $bind_post_id2 = $postId;
        oci_bind_by_name($stmt_del, ':post_id', $bind_post_id2);
        
        if (!oci_execute($stmt_del, OCI_COMMIT_ON_SUCCESS)) {
            $e = oci_error($stmt_del);
            if ($e['code'] == 2292) return ['status' => false, 'message' => 'Gagal: Hapus komentar dulu'];
            return ['status' => false, 'message' => $e['message']];
        }
        oci_free_statement($stmt_del);

        return ['status' => true, 'image_path' => $row['POST_IMAGE'], 'message' => 'Berhasil'];
    }

    // ==========================================================
    // BAGIAN: LIKE
    // ==========================================================
    public function toggleLike($userId, $postId) {
        // Cek Like
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

        // Eksekusi Action
        $stmt_act = oci_parse($this->conn, $sql_act);
        
        $bind_user_id2 = $userId;
        $bind_post_id2 = $postId;
        
        oci_bind_by_name($stmt_act, ':user_id', $bind_user_id2);
        oci_bind_by_name($stmt_act, ':post_id', $bind_post_id2);
        
        if(!oci_execute($stmt_act, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return false;
        }

        // Eksekusi Update Count
        $stmt_upd = oci_parse($this->conn, $sql_upd);
        
        $bind_post_id3 = $postId;
        oci_bind_by_name($stmt_upd, ':post_id', $bind_post_id3);
        
        if(!oci_execute($stmt_upd, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return false;
        }

        oci_commit($this->conn);
        oci_free_statement($stmt_act);
        oci_free_statement($stmt_upd);

        // Ambil Count Terbaru
        $sql_cnt = "SELECT like_count FROM postingan WHERE post_id = :post_id";
        $stmt_cnt = oci_parse($this->conn, $sql_cnt);
        
        $bind_post_id4 = $postId;
        oci_bind_by_name($stmt_cnt, ':post_id', $bind_post_id4);
        oci_execute($stmt_cnt);
        $rCount = oci_fetch_assoc($stmt_cnt);
        oci_free_statement($stmt_cnt);

        return ['action' => $action, 'new_count' => $rCount['LIKE_COUNT']];
    }

    // ==========================================================
    // BAGIAN: KOMENTAR
    // ==========================================================
    
    // 1. Ambil Komentar Parent
    public function getParentComments($postId) {
        $sql = "SELECT 
                    c.comment_id,
                    c.post_id,
                    c.user_id,
                    c.isi_komen,
                    c.parent_comment_id,
                    TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
                    u.username, 
                    u.nama_lengkap, 
                    u.avatar_url,
                    (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as reply_count
                FROM comments c 
                JOIN users u ON c.user_id = u.user_id
                WHERE c.post_id = :post_id AND c.parent_comment_id IS NULL 
                ORDER BY c.created_at DESC";
        
        $stmt = oci_parse($this->conn, $sql);
        
        $bind_post_id = $postId;
        oci_bind_by_name($stmt, ':post_id', $bind_post_id);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception("Execute Error getParentComments: " . $e['message']);
        }
        
        $res = [];
        while($r = oci_fetch_assoc($stmt)) { 
            $res[] = array_change_key_case($r, CASE_UPPER); 
        }
        oci_free_statement($stmt);
        return $res;
    }

    // 2. Ambil Replies
    public function getReplies($parentCommentId) {
        $sql = "SELECT 
                    c.comment_id,
                    c.post_id,
                    c.user_id,
                    c.isi_komen,
                    c.parent_comment_id,
                    TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
                    u.username, 
                    u.nama_lengkap, 
                    u.avatar_url
                FROM comments c 
                JOIN users u ON c.user_id = u.user_id
                WHERE c.parent_comment_id = :parent_id 
                ORDER BY c.created_at ASC";
        
        $stmt = oci_parse($this->conn, $sql);
        
        $bind_parent_id = $parentCommentId;
        oci_bind_by_name($stmt, ':parent_id', $bind_parent_id);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception("Execute Error getReplies: " . $e['message']);
        }
        
        $res = [];
        while($r = oci_fetch_assoc($stmt)) { 
            $res[] = array_change_key_case($r, CASE_UPPER); 
        }
        oci_free_statement($stmt);
        return $res;
    }

    // 3. Ambil Komentar (Generic)
    public function getComments($postId, $parentId = null) {
        if ($parentId === null) {
            return $this->getParentComments($postId);
        } else {
            return $this->getReplies($parentId);
        }
    }

    // 4. Tambah Komentar
    public function addComment($userId, $postId, $content, $parentId = null) {
        // Generate ID Manual
        $sql_id = "SELECT NVL(MAX(comment_id), 0) + 1 as next_id FROM comments";
        $stmt_id = oci_parse($this->conn, $sql_id);
        oci_execute($stmt_id);
        $row_id = oci_fetch_assoc($stmt_id);
        $newId = $row_id['NEXT_ID'];
        oci_free_statement($stmt_id);

        $sql = "INSERT INTO comments (comment_id, post_id, user_id, isi_komen, parent_comment_id, created_at) 
                VALUES (:comment_id, :post_id, :user_id, :isi_komen, :parent_id, SYSTIMESTAMP)";
        
        $stmt = oci_parse($this->conn, $sql);
        
        $bind_cid = $newId;
        $bind_pid = $postId;
        $bind_uid = $userId;
        $bind_isi = $content;
        $bind_parent = $parentId;
        
        oci_bind_by_name($stmt, ':comment_id', $bind_cid);
        oci_bind_by_name($stmt, ':post_id', $bind_pid);
        oci_bind_by_name($stmt, ':user_id', $bind_uid);
        oci_bind_by_name($stmt, ':isi_komen', $bind_isi);
        oci_bind_by_name($stmt, ':parent_id', $bind_parent);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return false;
        }

        // Update Count di Postingan
        $sql_upd = "UPDATE postingan SET comment_count = comment_count + 1 WHERE post_id = :post_id";
        $stmt_upd = oci_parse($this->conn, $sql_upd);
        
        $bind_pid2 = $postId;
        oci_bind_by_name($stmt_upd, ':post_id', $bind_pid2);
        oci_execute($stmt_upd, OCI_NO_AUTO_COMMIT);
        
        oci_commit($this->conn);
        oci_free_statement($stmt);
        oci_free_statement($stmt_upd);

        return $newId;
    }

    // 5. Hapus Komentar
    public function deleteComment($commentId, $userId) {
        // Cek Kepemilikan & Ambil PostID
        $sql_cek = "SELECT POST_ID FROM comments WHERE COMMENT_ID = :comment_id AND USER_ID = :user_id";
        $stmt_cek = oci_parse($this->conn, $sql_cek);
        
        $bind_cid = $commentId;
        $bind_uid = $userId;
        
        oci_bind_by_name($stmt_cek, ':comment_id', $bind_cid);
        oci_bind_by_name($stmt_cek, ':user_id', $bind_uid);
        oci_execute($stmt_cek);
        $row = oci_fetch_assoc($stmt_cek);
        oci_free_statement($stmt_cek);

        if (!$row) return false;

        $postId = $row['POST_ID'];

        // Hapus
        $sql_del = "DELETE FROM comments WHERE COMMENT_ID = :comment_id";
        $stmt_del = oci_parse($this->conn, $sql_del);
        
        $bind_cid2 = $commentId;
        oci_bind_by_name($stmt_del, ':comment_id', $bind_cid2);
        
        if (!oci_execute($stmt_del, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return false;
        }

        // Kurangi Count
        $sql_dec = "UPDATE postingan SET comment_count = GREATEST(0, comment_count - 1) WHERE post_id = :post_id";
        $stmt_dec = oci_parse($this->conn, $sql_dec);
        
        $bind_pid = $postId;
        oci_bind_by_name($stmt_dec, ':post_id', $bind_pid);
        oci_execute($stmt_dec, OCI_NO_AUTO_COMMIT);

        oci_commit($this->conn);
        oci_free_statement($stmt_del);
        oci_free_statement($stmt_dec);
        
        return true;
    }
}
?>