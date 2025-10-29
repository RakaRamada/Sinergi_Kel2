<?php
require_once __DIR__ . '/../../config/koneksi.php';

/**
 * Fungsi ini akan dipanggil oleh router Anda (index.php)
 * saat ?page=post-detail
 *
 * Pastikan Anda memanggil fungsi ini di router Anda.
 * Misal di index.php:
 *
 * if ($page == 'post-detail') {
 * require 'app/controllers/PostingController.php';
 * showPostDetail(); // Panggil fungsi ini
 * }
 *
 */

function showsPostDetail(){
    global $conn;
    session_start();

    //1. ambil ID post dari URL
    $post_id = $_GET['id']?? 0;
    $current_user_id = $_SESSION['id_users']?? 0;

    if ($post_id == 0) {
        echo "Postingan tidak ditemukan.";
        exit;
    }

    //2. query ngambil data postingan
    $sql_post = "
        SELECT 
            p.id_postingan, p.id_user, p.konten, p.post_image, p.created_at,
            u.username, u.nama_lengkap, u.avatar_url,
            (SELECT COUNT(*) FROM likes l WHERE l.id_postingan = p.id_postingan) AS total_likes,
            (SELECT COUNT(*) FROM comments c WHERE c.id_postingan = p.id_postingan) AS total_comments,
            (SELECT COUNT(*) 
             FROM likes l 
             WHERE l.id_postingan = p.id_postingan AND l.id_user = :current_user_id_bv) AS user_sudah_like
        FROM 
            postingan p
        JOIN 
            users u ON p.id_user = u.id_users
        WHERE
            p.id_postingan = :post_id_bv
    ";
    $stmt_post = oci_parse($conn, $sql_post);
    oci_bind_by_name($stmt_post, ':post_id_bv', $post_id);
    oci_bind_by_name($stmt_post, ':current_user_id_bv', $current_user_id);
    oci_execute($stmt_post);

    //ambil 1 baris data postingan
    $post = oci_fetch_assoc($stmt_post);

    if(!$post){
        echo "Postingan tidak ditemukan.";
        exit;
    }

    //proses data tambahan
    $default_avatar_dir = '/Sinergi/public/assets/images/';
    $default_avatar_file = '/Sinergi/public/assets/images/user.png';
    if (empty($post['AVATAR_URL']) || $post['AVATAR_URL'] == $default_avatar_dir) {
        $post['AVATAR_URL_FIXED'] = $default_avatar_file;
    } else {
        $post['AVATAR_URL_FIXED'] = $post['AVATAR_URL'];
    }

    $timestamp = strtotime($post['CREATED_AT']);
    $post['WAKTU_POSTING'] = date('h:i A \· d M Y', $timestamp); 

    $sql_comments = "
        SELECT 
            c.id_comments, c.isi_komen, c.created_at,
            u.id_users, u.username, u.nama_lengkap, u.avatar_url
        FROM 
            comments c
        JOIN 
            users u ON c.id_user = u.id_users
        WHERE 
            c.id_postingan = :post_id_bv
        ORDER BY 
            c.created_at ASC
    ";

    $stmt_comments = oci_parse($conn, $sql_comments);
    oci_bind_by_name($stmt_comments, ':post_id_bv', $post_id);
    oci_execute($stmt_comments);

    $comments = [];
    while ($comment_row = oci_fetch_assoc($stmt_comments)) {
        // Proses data tambahan untuk setiap komentar
        if (empty($comment_row['AVATAR_URL']) || $comment_row['AVATAR_URL'] == $default_avatar_dir) {
            $comment_row['AVATAR_URL_FIXED'] = $default_avatar_file;
        } else {
            $comment_row['AVATAR_URL_FIXED'] = $comment_row['AVATAR_URL'];
        }
        
        $c_timestamp = strtotime($comment_row['CREATED_AT']);
        $c_diff = time() - $c_timestamp;
        if ($c_diff < 3600) {
            $comment_row['WAKTU_KOMEN'] = floor($c_diff / 60) . 'm';
        } else if ($c_diff < 86400) {
            $comment_row['WAKTU_KOMEN'] = floor($c_diff / 3600) . 'j';
        } else {
            $comment_row['WAKTU_KOMEN'] = date('d M', $c_timestamp);
        }
        
        $comments[] = $comment_row;
    }

    // 4. Panggil file View
    // Variabel $post dan $comments sekarang tersedia di dalam post_detail.php
    require __DIR__ . '/../views/post_detail.php';

    // 5. Bersihkan statement
    oci_free_statement($stmt_post);
    oci_free_statement($stmt_comments);
    oci_close($conn);
}
?>