<?php
// File: app/models/ForumModel.php
// MODEL FINAL FIX: Sequence & Time Elapsed Fixed

require_once __DIR__ . '/../../config/koneksi.php';

/**
 * 1. MENGAMBIL DAFTAR POSTINGAN DISKUSI
 */
function getForumPostsByGroupId($group_id, $current_user_id) {
    global $conn;

    $sql = "SELECT 
                p.post_id, p.group_id, p.user_id, p.konten, p.image_path, 
                p.like_count, p.comment_count,
                TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_str,
                u.nama_lengkap, u.username, u.avatar_url, 
                CASE WHEN l.user_id IS NOT NULL THEN 1 ELSE 0 END as user_has_liked
            FROM forum_posts p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN forum_post_likes l ON (p.post_id = l.post_id AND l.user_id = :p_user_id)
            WHERE p.group_id = :p_group_id
            ORDER BY p.created_at ASC";

    $stmt = oci_parse($conn, $sql);
    
    $safe_gid = (int)$group_id;
    $safe_uid = (int)$current_user_id;
    
    oci_bind_by_name($stmt, ':p_group_id', $safe_gid);
    oci_bind_by_name($stmt, ':p_user_id', $safe_uid);

    if (!oci_execute($stmt)) return [];

    $posts = [];
    while ($row = oci_fetch_assoc($stmt)) {
        if (isset($row['KONTEN']) && $row['KONTEN'] instanceof OCILob) {
            $row['KONTEN'] = $row['KONTEN']->read($row['KONTEN']->size());
        }
        
        $clean_row = array_change_key_case($row, CASE_LOWER);
        
        $def_avatar = '/Sinergi/public/assets/images/user.png';
        if (!empty($clean_row['avatar_url'])) {
             $clean_row['avatar_url_fixed'] = '/sinergi/public/uploads/avatars/' . $clean_row['avatar_url'];
        } else {
             $clean_row['avatar_url_fixed'] = $def_avatar;
        }

        $clean_row['waktu_lalu'] = time_elapsed_string($clean_row['created_at_str']);
        $posts[] = $clean_row;
    }
    oci_free_statement($stmt);
    return $posts;
}

/**
 * 2. MEMBUAT POSTINGAN BARU
 */
function createForumPost($group_id, $user_id, $konten, $image_path = null) {
    global $conn;

    $sqlSeq = "SELECT forum_posts_seq.NEXTVAL as NEXT_ID FROM dual";
    $stmtSeq = oci_parse($conn, $sqlSeq);
    if (!oci_execute($stmtSeq)) return false;
    $rowSeq = oci_fetch_assoc($stmtSeq);
    $new_id = $rowSeq['NEXT_ID']; 
    oci_free_statement($stmtSeq);

    $sql = "INSERT INTO forum_posts (post_id, group_id, user_id, konten, image_path, created_at) 
            VALUES (:p_post_id, :p_group_id, :p_user_id, EMPTY_CLOB(), :p_image, SYSTIMESTAMP)
            RETURNING konten INTO :p_clob_loc";

    $stmt = oci_parse($conn, $sql);
    $clob = oci_new_descriptor($conn, OCI_D_LOB);
    $safe_gid = (int)$group_id;
    $safe_uid = (int)$user_id;

    oci_bind_by_name($stmt, ':p_post_id', $new_id);
    oci_bind_by_name($stmt, ':p_group_id', $safe_gid);
    oci_bind_by_name($stmt, ':p_user_id', $safe_uid);
    oci_bind_by_name($stmt, ':p_image', $image_path);
    oci_bind_by_name($stmt, ':p_clob_loc', $clob, -1, OCI_B_CLOB);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;

    if ($konten) $clob->save($konten);
    oci_commit($conn);
    oci_free_statement($stmt);
    
    return $new_id;
}

/**
 * 3. TOGGLE LIKE
 */
function toggleForumLike($post_id, $user_id) {
    global $conn;
    
    $checkSql = "SELECT COUNT(*) as hitung FROM forum_post_likes WHERE post_id = :p_post_id AND user_id = :p_user_id";
    $stmtCheck = oci_parse($conn, $checkSql);
    oci_bind_by_name($stmtCheck, ':p_post_id', $post_id);
    oci_bind_by_name($stmtCheck, ':p_user_id', $user_id);
    
    if (!oci_execute($stmtCheck)) return false;
    
    $row = oci_fetch_assoc($stmtCheck);
    $alreadyLiked = ($row['HITUNG'] > 0);
    oci_free_statement($stmtCheck);
    
    if ($alreadyLiked) {
        $sql = "DELETE FROM forum_post_likes WHERE post_id = :p_post_id AND user_id = :p_user_id";
        $sqlUpdate = "UPDATE forum_posts SET like_count = like_count - 1 WHERE post_id = :p_post_id";
        $status = 'unliked';
    } else {
        $sql = "INSERT INTO forum_post_likes (post_id, user_id, liked_at) VALUES (:p_post_id, :p_user_id, SYSTIMESTAMP)";
        $sqlUpdate = "UPDATE forum_posts SET like_count = like_count + 1 WHERE post_id = :p_post_id";
        $status = 'liked';
    }

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':p_post_id', $post_id);
    oci_bind_by_name($stmt, ':p_user_id', $user_id);
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;

    $stmtUp = oci_parse($conn, $sqlUpdate);
    oci_bind_by_name($stmtUp, ':p_post_id', $post_id);
    if (!oci_execute($stmtUp, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($conn); 
        return false;
    }

    oci_commit($conn);
    return $status;
}

/**
 * 4. MENGAMBIL KOMENTAR
 */
function getForumPostComments($post_id) {
    global $conn;
    
    // Select Parent Author Name juga
    $sql = "SELECT c.comment_id, c.post_id, c.user_id, c.isi_komentar, c.parent_comment_id,
                   TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_str,
                   u.nama_lengkap, u.username, u.avatar_url,
                   pu.nama_lengkap as parent_author
            FROM forum_post_comments c
            JOIN users u ON c.user_id = u.user_id
            -- Join untuk ambil nama user yang dibalas (Reply context)
            LEFT JOIN forum_post_comments pc ON c.parent_comment_id = pc.comment_id
            LEFT JOIN users pu ON pc.user_id = pu.user_id
            WHERE c.post_id = :p_post_id
            ORDER BY c.created_at ASC";
            
    $stmt = oci_parse($conn, $sql);
    $safe_pid = (int)$post_id;
    oci_bind_by_name($stmt, ':p_post_id', $safe_pid);
    
    if (!oci_execute($stmt)) return [];
    
    $comments = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $c = array_change_key_case($row, CASE_LOWER);
        
        $def = '/Sinergi/public/assets/images/user.png';
        if (!empty($c['avatar_url'])) {
             $c['avatar_url_fixed'] = '/Sinergi/public/uploads/avatars/' . $c['avatar_url'];
        } else {
             $c['avatar_url_fixed'] = $def;
        }
        
        $c['waktu_lalu'] = time_elapsed_string($c['created_at_str']);
        $comments[] = $c;
    }
    return $comments;
}

/**
 * 5. MEMBUAT KOMENTAR BARU
 */
function createForumComment($post_id, $user_id, $isi, $parent_id = null) { // Tambah parameter parent_id
    global $conn;
    
    $sqlSeq = "SELECT forum_post_comments_seq.NEXTVAL as NEXT_ID FROM dual";
    $s = oci_parse($conn, $sqlSeq); 
    if (!oci_execute($s)) return false;
    $r = oci_fetch_assoc($s); 
    $new_id = $r['NEXT_ID'];
    oci_free_statement($s);

    // Insert Parent ID (bisa null)
    $sql = "INSERT INTO forum_post_comments (comment_id, post_id, user_id, isi_komentar, parent_comment_id, created_at)
            VALUES (:p_cid, :p_pid, :p_uid, :p_isi, :p_parent, SYSTIMESTAMP)";
            
    $stmt = oci_parse($conn, $sql);
    
    oci_bind_by_name($stmt, ':p_cid', $new_id);
    oci_bind_by_name($stmt, ':p_pid', $post_id);
    oci_bind_by_name($stmt, ':p_uid', $user_id);
    oci_bind_by_name($stmt, ':p_isi', $isi);
    oci_bind_by_name($stmt, ':p_parent', $parent_id); // Bind parent
    
    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        $e = oci_error($stmt);
        die(json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e['message']]));
    }
    
    $sqlCount = "UPDATE forum_posts SET comment_count = comment_count + 1 WHERE post_id = :p_pid";
    $stmt2 = oci_parse($conn, $sqlCount);
    oci_bind_by_name($stmt2, ':p_pid', $post_id);
    oci_execute($stmt2, OCI_NO_AUTO_COMMIT);
    
    oci_commit($conn);
    return $new_id;
}

/**
 * 6. MENGAMBIL SATU POSTINGAN
 */
function getForumPostById($post_id, $current_user_id) {
    global $conn;

    $sql = "SELECT p.*, TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at_str,
                u.nama_lengkap, u.username, u.avatar_url, 
                CASE WHEN l.user_id IS NOT NULL THEN 1 ELSE 0 END as user_has_liked
            FROM forum_posts p
            JOIN users u ON p.user_id = u.user_id
            LEFT JOIN forum_post_likes l ON (p.post_id = l.post_id AND l.user_id = :p_user_id)
            WHERE p.post_id = :p_post_id";

    $stmt = oci_parse($conn, $sql);
    $safe_pid = (int)$post_id;
    $safe_uid = (int)$current_user_id;
    oci_bind_by_name($stmt, ':p_post_id', $safe_pid);
    oci_bind_by_name($stmt, ':p_user_id', $safe_uid);

    if (!oci_execute($stmt)) return null;
    $row = oci_fetch_assoc($stmt);
    if (!$row) return null;

    if (isset($row['KONTEN']) && $row['KONTEN'] instanceof OCILob) {
        $row['KONTEN'] = $row['KONTEN']->read($row['KONTEN']->size());
    }
    
    $clean_row = array_change_key_case($row, CASE_LOWER);
    
    $def_avatar = '/Sinergi/public/assets/images/user.png';
    if (!empty($clean_row['avatar_url'])) {
            $clean_row['avatar_url_fixed'] = '/sinergi/public/uploads/avatars/' . $clean_row['avatar_url'];
    } else {
            $clean_row['avatar_url_fixed'] = $def_avatar;
    }
    
    $clean_row['waktu_lalu'] = time_elapsed_string($clean_row['created_at_str']);
    return $clean_row;
}

/**
 * 7. HAPUS POSTINGAN
 */
function deleteForumPostById($post_id, $user_id) {
    global $conn;
    
    $sql = "DELETE FROM forum_posts WHERE post_id = :p_post_id AND user_id = :p_user_id";
    
    $stmt = oci_parse($conn, $sql);
    $safe_pid = (int)$post_id;
    $safe_uid = (int)$user_id;
    
    oci_bind_by_name($stmt, ':p_post_id', $safe_pid);
    oci_bind_by_name($stmt, ':p_user_id', $safe_uid);

    if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) return false;
    
    $deleted = oci_num_rows($stmt);
    if ($deleted > 0) {
        oci_commit($conn);
        return true;
    } else {
        oci_rollback($conn);
        return false;
    }
}

/**
 * Helper Waktu (PERBAIKAN: Tidak lagi inject properti $w ke DateInterval)
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta')); 
    $ago = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
    
    $diff = $now->diff($ago);

    // Hitung minggu dan sisa hari secara manual (Tanpa merusak objek $diff)
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    // Mapping nilai manual
    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );

    // Array nilai yang kita hitung sendiri
    $values = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks, // Pakai variabel lokal
        'd' => $days,  // Pakai variabel lokal
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );

    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yang lalu' : 'Baru saja';
}

/**
 * 8. HAPUS KOMENTAR
 */
function deleteForumCommentById($comment_id, $user_id) {
    global $conn;
    
    // 1. Ambil POST_ID dulu (Untuk update counter nanti)
    $sqlGet = "SELECT post_id FROM forum_post_comments WHERE comment_id = :p_cid AND user_id = :p_uid";
    $stmtGet = oci_parse($conn, $sqlGet);
    $safe_cid = (int)$comment_id;
    $safe_uid = (int)$user_id;
    
    oci_bind_by_name($stmtGet, ':p_cid', $safe_cid);
    oci_bind_by_name($stmtGet, ':p_uid', $safe_uid);
    
    if (!oci_execute($stmtGet)) return false;
    
    $row = oci_fetch_assoc($stmtGet);
    if (!$row) return false; // Tidak ditemukan / bukan milik user
    
    $post_id = $row['POST_ID'];
    oci_free_statement($stmtGet);

    // 2. HAPUS ANAK-ANAKNYA DULU (Manual Cascade Delete)
    // Kita hapus semua komentar yang parent_id-nya adalah komentar ini
    $sqlDelChild = "DELETE FROM forum_post_comments WHERE parent_comment_id = :p_parent_id";
    $stmtChild = oci_parse($conn, $sqlDelChild);
    oci_bind_by_name($stmtChild, ':p_parent_id', $safe_cid);
    
    // Eksekusi hapus anak (Jangan return false kalau 0 rows, karena bisa jadi dia tidak punya anak)
    oci_execute($stmtChild, OCI_NO_AUTO_COMMIT);
    oci_free_statement($stmtChild);

    // 3. HAPUS KOMENTAR UTAMA (BAPAKNYA)
    $sqlDel = "DELETE FROM forum_post_comments WHERE comment_id = :p_cid";
    $stmtDel = oci_parse($conn, $sqlDel);
    oci_bind_by_name($stmtDel, ':p_cid', $safe_cid);

    if (!oci_execute($stmtDel, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($conn);
        return false;
    }
    
    // 4. HITUNG ULANG (RECOUNT)
    // Sekarang anak-anaknya sudah bersih, jadi hitungan pasti akurat
    $sqlCount = "SELECT COUNT(*) AS total FROM forum_post_comments WHERE post_id = :p_pid";
    $stmtCount = oci_parse($conn, $sqlCount);
    oci_bind_by_name($stmtCount, ':p_pid', $post_id);
    
    if (!oci_execute($stmtCount)) {
        oci_rollback($conn);
        return false;
    }
    
    $rowCount = oci_fetch_assoc($stmtCount);
    $total_now = $rowCount['TOTAL'];
    oci_free_statement($stmtCount);

    // 5. Update Tabel Utama
    $sqlUpd = "UPDATE forum_posts SET comment_count = :p_total WHERE post_id = :p_pid";
    $stmtUpd = oci_parse($conn, $sqlUpd);
    oci_bind_by_name($stmtUpd, ':p_total', $total_now);
    oci_bind_by_name($stmtUpd, ':p_pid', $post_id);
    
    if (!oci_execute($stmtUpd, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($conn);
        return false;
    }
    
    // 6. Commit Semua Perubahan
    oci_commit($conn);
    
    return true;
}
?>