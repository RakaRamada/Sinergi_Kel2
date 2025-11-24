<!-- app/controllers/PostingController.php -->
<?php
require_once __DIR__ . '/../../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function showDashboard() {
    require __DIR__ . '/../views/dashboard.php';
}

function showPostDetail(){
    global $conn; 

    $post_id = isset($_GET['id']) ? $_GET['id'] : 0;
    $current_user_id = $_SESSION['user_id'] ?? 0;

    if ($post_id == 0) {
        echo "<div class='p-4 text-red-500'>Error: ID Postingan tidak valid.</div>";
        return;
    }
    
    // 1. QUERY POSTINGAN (DENGAN FIX TANGGAL)
    // Kita gunakan TO_CHAR untuk kolom created_at agar formatnya YYYY-MM-DD HH:MM:SS
    $sql_post = "
        SELECT 
            p.post_id, p.user_id, p.konten, p.post_image, 
            TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
            p.like_count, p.comment_count,
            u.username, u.nama_lengkap, u.avatar_url
        FROM postingan p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.post_id = :post_id_bv
    ";

    $stmt_post = oci_parse($conn, $sql_post);
    oci_bind_by_name($stmt_post, ':post_id_bv', $post_id);
    
    if (!oci_execute($stmt_post)) {
        echo "Query Error."; return;
    }

    $row = oci_fetch_assoc($stmt_post);
    
    if(!$row){
        echo "<div class='p-4'>Postingan tidak ditemukan.</div>";
        return;
    }

    $post = array_change_key_case($row, CASE_UPPER);

    // Avatar Logic
    $default_avatar = '/Sinergi/public/assets/images/user.png';
    $post['AVATAR_URL_FIXED'] = (!empty($post['AVATAR_URL']) && strlen($post['AVATAR_URL']) > 5) 
                                ? $post['AVATAR_URL'] : $default_avatar;

    $post['POST_IMAGE'] = $post['POST_IMAGE'] ?? '';

    // --- FIX TANGGAL (Gunakan WAKTU_FIX dari Query) ---
    if (!empty($post['WAKTU_FIX'])) {
        $timestamp = strtotime($post['WAKTU_FIX']);
        $post['WAKTU_POSTING'] = date('H:i \· d M Y', $timestamp); 
    } else {
        $post['WAKTU_POSTING'] = '-';
    }

    $post['TOTAL_LIKES'] = $post['LIKE_COUNT'] ?? 0;
    $post['TOTAL_COMMENTS'] = $post['COMMENT_COUNT'] ?? 0;

// 2. QUERY KOMENTAR PARENT (yang tidak punya parent)
$sql_comments = "
    SELECT c.comment_id, c.user_id, c.post_id, c.isi_komen, 
           c.parent_comment_id,
           TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
           u.username, u.nama_lengkap, u.avatar_url,
           (SELECT COUNT(*) FROM comments WHERE parent_comment_id = c.comment_id) as reply_count
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.post_id = :post_id_bv AND c.parent_comment_id IS NULL
    ORDER BY c.created_at DESC
";

$stmt_c = oci_parse($conn, $sql_comments);
oci_bind_by_name($stmt_c, ':post_id_bv', $post_id);
oci_execute($stmt_c);

$comments = [];
while ($row_c = oci_fetch_assoc($stmt_c)) {
    $c = array_change_key_case($row_c, CASE_UPPER);
    
    $c['AVATAR_URL_FIXED'] = (!empty($c['AVATAR_URL']) && strlen($c['AVATAR_URL']) > 5) 
                             ? $c['AVATAR_URL'] : $default_avatar;
    
    if (!empty($c['WAKTU_FIX'])) {
        $c_time = strtotime($c['WAKTU_FIX']);
        $c['WAKTU_KOMEN'] = date('d M H:i', $c_time);
    } else {
        $c['WAKTU_KOMEN'] = 'Baru saja';
    }

    // Load replies untuk comment ini
    $c['REPLIES'] = [];
    $sql_replies = "
        SELECT c.comment_id, c.user_id, c.post_id, c.isi_komen, 
               TO_CHAR(c.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
               u.username, u.nama_lengkap, u.avatar_url
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.parent_comment_id = :parent_id
        ORDER BY c.created_at ASC
    ";
    $stmt_r = oci_parse($conn, $sql_replies);
    $parent_id = $c['COMMENT_ID'];
    oci_bind_by_name($stmt_r, ':parent_id', $parent_id);
    oci_execute($stmt_r);
    
    while ($row_r = oci_fetch_assoc($stmt_r)) {
        $r = array_change_key_case($row_r, CASE_UPPER);
        $r['AVATAR_URL_FIXED'] = (!empty($r['AVATAR_URL']) && strlen($r['AVATAR_URL']) > 5) 
                                 ? $r['AVATAR_URL'] : $default_avatar;
        if (!empty($r['WAKTU_FIX'])) {
            $r_time = strtotime($r['WAKTU_FIX']);
            $r['WAKTU_KOMEN'] = date('d M H:i', $r_time);
        } else {
            $r['WAKTU_KOMEN'] = 'Baru saja';
        }
        $c['REPLIES'][] = $r;
    }
    oci_free_statement($stmt_r);

    $comments[] = $c;
}
    require __DIR__ . '/../views/post_detail.php';

    oci_free_statement($stmt_post);
    oci_free_statement($stmt_c);
    oci_close($conn);
}
?>