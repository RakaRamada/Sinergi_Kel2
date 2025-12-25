<?php
// File: app/models/ForumModel.php
// UPDATE: Integrasi Notifikasi untuk Like & Comment Forum

require_once __DIR__ . '/NotificationModel.php'; // Panggil Model Notifikasi

class ForumModel {
    
    private $conn;
    private $notifModel; // Property baru

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        // Inisialisasi Model Notifikasi
        $this->notifModel = new NotificationModel($dbConnection);
    }

    /**
     *  Ambil Daftar Postingan Forum
     */
    public function getForumPostsByGroupId($group_id, $current_user_id) {
        $sql = "SELECT 
                    p.post_id, p.group_id, p.user_id, p.konten, p.image_path, 
                    p.like_count, p.comment_count,
                    TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_str,
                    u.nama_lengkap, u.username, u.avatar_url, 
                    CASE WHEN l.user_id IS NOT NULL THEN 1 ELSE 0 END as user_has_liked
                FROM forum_posts p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN forum_post_likes l ON (p.post_id = l.post_id AND l.user_id = :p_curr_uid)
                WHERE p.group_id = :p_group_id
                ORDER BY p.created_at ASC";

        $stmt = oci_parse($this->conn, $sql);
        
        $safe_gid = (int)$group_id;
        $safe_uid = (int)$current_user_id;
        
        oci_bind_by_name($stmt, ':p_group_id', $safe_gid);
        oci_bind_by_name($stmt, ':p_curr_uid', $safe_uid);

        if (!oci_execute($stmt)) return [];

        $posts = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $posts[] = $this->processRowData($row);
        }
        oci_free_statement($stmt);
        return $posts;
    }

    // ambil komen
    public function getForumPostComments($post_id) {
        $sql = "SELECT c.comment_id, c.post_id, c.user_id, c.isi_komentar, c.parent_comment_id,
                       TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_str,
                       u.nama_lengkap, u.username, u.avatar_url,
                       pu.nama_lengkap as parent_author
                FROM forum_post_comments c
                JOIN users u ON c.user_id = u.user_id
                LEFT JOIN forum_post_comments pc ON c.parent_comment_id = pc.comment_id
                LEFT JOIN users pu ON pc.user_id = pu.user_id
                WHERE c.post_id = :p_pid
                ORDER BY c.created_at ASC";
                
        $stmt = oci_parse($this->conn, $sql);
        $safe_pid = (int)$post_id;
        oci_bind_by_name($stmt, ':p_pid', $safe_pid);
        
        if (!oci_execute($stmt)) return [];
        
        $comments = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $comments[] = $this->processRowData($row);
        }
        return $comments;
    }

    /**
     * 2. Buat Postingan Forum Baru
     */
    public function createForumPost($group_id, $user_id, $konten, $image_path = null) {
        $sqlSeq = "SELECT forum_posts_seq.NEXTVAL as NEXT_ID FROM dual";
        $stmtSeq = oci_parse($this->conn, $sqlSeq);
        if (!oci_execute($stmtSeq)) return false;
        $rowSeq = oci_fetch_assoc($stmtSeq);
        $new_id = $rowSeq['NEXT_ID']; 
        oci_free_statement($stmtSeq);

        $sql = "INSERT INTO forum_posts (post_id, group_id, user_id, konten, image_path, created_at) 
                VALUES (:p_post_id, :p_group_id, :p_user_id, EMPTY_CLOB(), :p_image, SYSTIMESTAMP)
                RETURNING konten INTO :p_clob_loc";

        $stmt = oci_parse($this->conn, $sql);
        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);
        
        $safe_gid = (int)$group_id;
        $safe_uid = (int)$user_id;

        oci_bind_by_name($stmt, ':p_post_id', $new_id);
        oci_bind_by_name($stmt, ':p_group_id', $safe_gid);
        oci_bind_by_name($stmt, ':p_user_id', $safe_uid);
        oci_bind_by_name($stmt, ':p_image', $image_path);
        oci_bind_by_name($stmt, ':p_clob_loc', $clob, -1, OCI_B_CLOB);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;

        if ($konten) $clob->save($konten);
        
        oci_commit($this->conn); 
        oci_free_statement($stmt);
        
        return $new_id;
    }

    /**
     * 3. Toggle Like (UPDATE: NOTIFIKASI)
     */
    public function toggleForumLike($post_id, $user_id) {
        $checkSql = "SELECT COUNT(*) as hitung FROM forum_post_likes WHERE post_id = :p_pid AND user_id = :p_uid";
        $stmtCheck = oci_parse($this->conn, $checkSql);
        oci_bind_by_name($stmtCheck, ':p_pid', $post_id);
        oci_bind_by_name($stmtCheck, ':p_uid', $user_id);
        oci_execute($stmtCheck);
        $row = oci_fetch_assoc($stmtCheck);
        $alreadyLiked = ($row['HITUNG'] > 0);
        oci_free_statement($stmtCheck);
        
        if ($alreadyLiked) {
            $sql = "DELETE FROM forum_post_likes WHERE post_id = :p_pid AND user_id = :p_uid";
            $sqlUpdate = "UPDATE forum_posts SET like_count = like_count - 1 WHERE post_id = :p_pid";
            $status = 'unliked';
        } else {
            $sql = "INSERT INTO forum_post_likes (post_id, user_id, liked_at) VALUES (:p_pid, :p_uid, SYSTIMESTAMP)";
            $sqlUpdate = "UPDATE forum_posts SET like_count = like_count + 1 WHERE post_id = :p_pid";
            $status = 'liked';
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_pid', $post_id);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;

        $stmtUp = oci_parse($this->conn, $sqlUpdate);
        oci_bind_by_name($stmtUp, ':p_pid', $post_id);
        oci_execute($stmtUp, OCI_NO_AUTO_COMMIT);

        // --- NOTIFIKASI LIKE FORUM ---
        if ($status === 'liked') {
            $sqlOwner = "SELECT user_id, group_id FROM forum_posts WHERE post_id = :p_pid";
            $stmtOwner = oci_parse($this->conn, $sqlOwner);
            oci_bind_by_name($stmtOwner, ':p_pid', $post_id);
            oci_execute($stmtOwner);
            $rowOwner = oci_fetch_assoc($stmtOwner);
            
            if ($rowOwner && $rowOwner['USER_ID'] != $user_id) {
                $this->notifModel->createNotification(
                    $rowOwner['USER_ID'], $user_id, 'like', 
                    'menyukai diskusi Anda di forum.',
                    [
                        'forum_post_id' => $post_id,
                        'group_id' => $rowOwner['GROUP_ID']
                    ]
                );
            }
            oci_free_statement($stmtOwner);
        }
        
        oci_commit($this->conn);
        return $status;
    }

    

    /**
     * 5. Buat Komentar Baru (UPDATE: NOTIFIKASI)
     */
    public function createForumComment($post_id, $user_id, $isi, $parent_id = null) {
        $sqlSeq = "SELECT forum_post_comments_seq.NEXTVAL as NEXT_ID FROM dual";
        $s = oci_parse($this->conn, $sqlSeq); 
        if (!oci_execute($s)) return false;
        $r = oci_fetch_assoc($s); 
        $new_id = $r['NEXT_ID'];
        oci_free_statement($s);

        $sql = "INSERT INTO forum_post_comments (comment_id, post_id, user_id, isi_komentar, parent_comment_id, created_at)
                VALUES (:p_cid, :p_pid, :p_uid, :p_isi, :p_parent, SYSTIMESTAMP)";
                
        $stmt = oci_parse($this->conn, $sql);
        
        oci_bind_by_name($stmt, ':p_cid', $new_id);
        oci_bind_by_name($stmt, ':p_pid', $post_id);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        oci_bind_by_name($stmt, ':p_isi', $isi);
        oci_bind_by_name($stmt, ':p_parent', $parent_id);
        
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;
        
        $sqlCount = "UPDATE forum_posts SET comment_count = comment_count + 1 WHERE post_id = :p_pid";
        $stmt2 = oci_parse($this->conn, $sqlCount);
        oci_bind_by_name($stmt2, ':p_pid', $post_id);
        oci_execute($stmt2, OCI_NO_AUTO_COMMIT);
        
        // --- NOTIFIKASI KOMEN FORUM ---
        // 1. Cari Pemilik Postingan Utama
        $sqlOwner = "SELECT user_id, group_id FROM forum_posts WHERE post_id = :p_pid";
        $stmtOwner = oci_parse($this->conn, $sqlOwner);
        oci_bind_by_name($stmtOwner, ':p_pid', $post_id);
        oci_execute($stmtOwner);
        $rowOwner = oci_fetch_assoc($stmtOwner);
        
        if ($rowOwner) {
            $owner_id = $rowOwner['USER_ID'];
            $group_id = $rowOwner['GROUP_ID'];

            // 1. Notif ke Pemilik Post
            if ($owner_id != $user_id) {
                $this->notifModel->createNotification(
                    $owner_id, $user_id, 'comment', 
                    'mengomentari diskusi Anda.', 
                    [
                        'forum_post_id' => $post_id,
                        'group_id' => $group_id,
                        'forum_comment_id' => $new_id
                    ]
                );
            }
            
            // 2. Notif Reply (Jika ada parent)
            if ($parent_id) {
                $sqlParent = "SELECT user_id FROM forum_post_comments WHERE comment_id = :p_cid";
                $stmtP = oci_parse($this->conn, $sqlParent);
                oci_bind_by_name($stmtP, ':p_cid', $parent_id);
                oci_execute($stmtP);
                $rowP = oci_fetch_assoc($stmtP);
                
                if ($rowP && $rowP['USER_ID'] != $user_id && $rowP['USER_ID'] != $owner_id) {
                    $this->notifModel->createNotification(
                        $rowP['USER_ID'], $user_id, 'comment', 
                        'membalas komentar Anda.', 
                        [
                            'forum_post_id' => $post_id,
                            'group_id' => $group_id,
                            'forum_comment_id' => $new_id
                        ]
                    );
                }
                oci_free_statement($stmtP);
            }
        }
        oci_free_statement($stmtOwner);

        oci_commit($this->conn);
        return $new_id;
    }

    /**
     * 6. Ambil Detail Satu Postingan
     */
    public function getForumPostById($post_id, $current_user_id) {
        $sql = "SELECT p.*, TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_str,
                    u.nama_lengkap, u.username, u.avatar_url, 
                    CASE WHEN l.user_id IS NOT NULL THEN 1 ELSE 0 END as user_has_liked
                FROM forum_posts p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN forum_post_likes l ON (p.post_id = l.post_id AND l.user_id = :p_curr_uid)
                WHERE p.post_id = :p_pid";

        $stmt = oci_parse($this->conn, $sql);
        $safe_pid = (int)$post_id;
        $safe_uid = (int)$current_user_id;
        oci_bind_by_name($stmt, ':p_pid', $safe_pid);
        oci_bind_by_name($stmt, ':p_curr_uid', $safe_uid);

        if (!oci_execute($stmt)) return null;
        $row = oci_fetch_assoc($stmt);
        if (!$row) return null;

        return $this->processRowData($row);
    }

    /**
     * 7. Hapus Postingan
     */
    public function deleteForumPostById($post_id, $user_id) {
        // Hapus Notifikasi Terkait dulu (Opsional tapi bersih)
        $sqlNotif = "DELETE FROM notifications WHERE related_post_id = :p_pid AND related_group_id IS NOT NULL";
        $stmtN = oci_parse($this->conn, $sqlNotif);
        oci_bind_by_name($stmtN, ':p_pid', $post_id);
        oci_execute($stmtN, OCI_NO_AUTO_COMMIT);

        $sql = "DELETE FROM forum_posts WHERE post_id = :p_pid AND user_id = :p_uid";
        
        $stmt = oci_parse($this->conn, $sql);
        $safe_pid = (int)$post_id;
        $safe_uid = (int)$user_id;
        
        oci_bind_by_name($stmt, ':p_pid', $safe_pid);
        oci_bind_by_name($stmt, ':p_uid', $safe_uid);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;
        
        if (oci_num_rows($stmt) > 0) {
            oci_commit($this->conn);
            return true;
        } else {
            oci_rollback($this->conn);
            return false;
        }
    }

    /**
     * 8. Hapus Komentar
     */
    public function deleteForumCommentById($comment_id, $user_id) {
        $sqlGet = "SELECT post_id FROM forum_post_comments WHERE comment_id = :p_cid AND user_id = :p_uid";
        $stmtGet = oci_parse($this->conn, $sqlGet);
        $safe_cid = (int)$comment_id;
        $safe_uid = (int)$user_id;
        oci_bind_by_name($stmtGet, ':p_cid', $safe_cid);
        oci_bind_by_name($stmtGet, ':p_uid', $safe_uid);
        if (!oci_execute($stmtGet)) return false;
        
        $row = oci_fetch_assoc($stmtGet);
        if (!$row) return false;
        $post_id = $row['POST_ID'];
        oci_free_statement($stmtGet);

        $sqlDelChild = "DELETE FROM forum_post_comments WHERE parent_comment_id = :p_parent_id";
        $stmtChild = oci_parse($this->conn, $sqlDelChild);
        oci_bind_by_name($stmtChild, ':p_parent_id', $safe_cid);
        oci_execute($stmtChild, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmtChild);

        $sqlDel = "DELETE FROM forum_post_comments WHERE comment_id = :p_cid";
        $stmtDel = oci_parse($this->conn, $sqlDel);
        oci_bind_by_name($stmtDel, ':p_cid', $safe_cid);
        if (!oci_execute($stmtDel, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn); return false;
        }

        $sqlCount = "SELECT COUNT(*) AS total FROM forum_post_comments WHERE post_id = :p_pid";
        $stmtCount = oci_parse($this->conn, $sqlCount);
        oci_bind_by_name($stmtCount, ':p_pid', $post_id);
        oci_execute($stmtCount);
        $rowCount = oci_fetch_assoc($stmtCount);
        $total_now = $rowCount['TOTAL'];

        $sqlUpd = "UPDATE forum_posts SET comment_count = :p_total WHERE post_id = :p_pid";
        $stmtUpd = oci_parse($this->conn, $sqlUpd);
        oci_bind_by_name($stmtUpd, ':p_total', $total_now);
        oci_bind_by_name($stmtUpd, ':p_pid', $post_id);
        oci_execute($stmtUpd, OCI_NO_AUTO_COMMIT);

        oci_commit($this->conn);
        return true;
    }

    // --- HELPER PRIVATE ---
    private function processRowData($row) {
        $data = array_change_key_case($row, CASE_LOWER);
        
        // Handle CLOB Postingan
        if (isset($data['konten']) && is_object($data['konten'])) {
            $size = $data['konten']->size();
            if ($size > 0) {
                $data['konten'] = $data['konten']->read($size);
            } else {
                $data['konten'] = '';
            }
        }
        
        // Handle CLOB Komentar
        if (isset($data['isi_komentar']) && is_object($data['isi_komentar'])) {
            $size = $data['isi_komentar']->size();
            if ($size > 0) {
                $data['isi_komentar'] = $data['isi_komentar']->read($size);
            } else {
                $data['isi_komentar'] = '';
            }
        }

        // Fix Avatar
        $def_avatar = '/Sinergi/public/assets/images/user.png';
        if (!empty($data['avatar_url'])) {
             $data['avatar_url_fixed'] = '/Sinergi/public/uploads/avatars/' . $data['avatar_url'];
        } else {
             $data['avatar_url_fixed'] = $def_avatar;
        }

        // Waktu
        $timeKey = isset($data['created_at_str']) ? 'created_at_str' : '';
        if ($timeKey) {
            $data['waktu_lalu'] = $this->timeElapsedString($data[$timeKey]);
        }

        return $data;
    }

    private function timeElapsedString($datetime) {
        try {
            $now = new DateTime('now', new DateTimeZone('Asia/Jakarta')); 
            $ago = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
            $diff = $now->diff($ago);

            if ($diff->d == 0 && $diff->h == 0 && $diff->i == 0) return 'Baru saja';
            if ($diff->d == 0 && $diff->h == 0) return $diff->i . ' menit yang lalu';
            if ($diff->d == 0) return $diff->h . ' jam yang lalu';
            if ($diff->d < 7) return $diff->d . ' hari yang lalu';
            return $ago->format('d M Y');
        } catch (Exception $e) { return '-'; }
    }
}
?>