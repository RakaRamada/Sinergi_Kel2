<?php
// File: app/models/PostModel.php

// Otomatis cari root folder agar path dinamis
$root_path = dirname(__DIR__, 2); 
if (file_exists($root_path . '/config/koneksi.php')) {
    require_once $root_path . '/config/koneksi.php';
} else {
    require_once __DIR__ . '/../../config/koneksi.php';
}

class PostModel {

    public static function getAllPosts($current_user_id) {
        global $conn;

        if (!$conn) return [];

        // Query untuk mengambil postingan + data user
        // Menggunakan NVL pada LIKE_COUNT dan COMMENT_COUNT agar jika null tetap keluar angka 0
        $sql = "SELECT 
                    p.post_id,    
                    p.user_id,
                    p.konten,
                    p.post_image,
                    NVL(p.like_count, 0) as LIKE_COUNT,       
                    NVL(p.comment_count, 0) as COMMENT_COUNT, 
                    TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
                    u.username,
                    u.nama_lengkap,
                    u.avatar_url,
                    0 AS USER_SUDAH_LIKE -- Nanti bisa diupdate logicnya jika fitur like sudah full
                FROM postingan p
                JOIN users u ON p.user_id = u.user_id
                ORDER BY p.created_at DESC";

        $stmt = oci_parse($conn, $sql);
        
        if (!$stmt || !oci_execute($stmt)) {
            return []; 
        }

        $posts = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $posts[] = self::processRowData($row);
        }
        return $posts;
    }

    public static function createPost($user_id, $konten, $post_image_db) {
        global $conn;
        if (!$conn) return false;

        // PERBAIKAN UTAMA DI SINI:
        // Hapus POST_ID dan Sequence-nya. Biarkan Database mengisi otomatis (Identity Column).
        $sql = "INSERT INTO postingan 
                (USER_ID, KONTEN, POST_IMAGE, CREATED_AT, LIKE_COUNT, COMMENT_COUNT) 
                VALUES (:uid, :konten, :img, SYSTIMESTAMP, 0, 0)";
        
        $stmt = oci_parse($conn, $sql);
        
        // Binding variable
        oci_bind_by_name($stmt, ':uid', $user_id);
        oci_bind_by_name($stmt, ':konten', $konten); // Oracle otomatis anggap string kosong sebagai NULL
        oci_bind_by_name($stmt, ':img', $post_image_db);

        // Execute dengan Commit
        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if (!$result) {
            // Untuk debugging: tangkap error jika execute gagal
            $e = oci_error($stmt);
            error_log("Oracle Insert Error: " . $e['message']);
            return false;
        }

        return true;
    }

    public static function getPostById($post_id) {
        global $conn;
        if (!$conn) return null;

        $sql = "SELECT p.*, TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') as WAKTU_FIX,
                    u.username, u.nama_lengkap, u.avatar_url
                FROM postingan p
                JOIN users u ON p.user_id = u.user_id
                WHERE p.post_id = :pid";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':pid', $post_id);
        
        if (oci_execute($stmt)) {
            $row = oci_fetch_assoc($stmt);
            if ($row) return self::processRowData($row, true);
        }
        return null;
    }

    // Helper untuk memproses data row (Waktu & Avatar)
    private static function processRowData($row, $isDetail = false) {
        $data = array_change_key_case($row, CASE_UPPER);
        
        // Fix path avatar
        $default_avatar = '/Sinergi/public/assets/images/user.png'; 
        $data['AVATAR_URL_FIXED'] = (!empty($data['AVATAR_URL']) && strlen($data['AVATAR_URL']) > 5) 
                                    ? $data['AVATAR_URL'] : $default_avatar;

        // Fix waktu
        $timeKey = isset($data['CREATED_AT_STR']) ? 'CREATED_AT_STR' : 'WAKTU_FIX';
        if (!empty($data[$timeKey])) {
            $timestamp = strtotime($data[$timeKey]);
            if ($isDetail) {
                $data['WAKTU_POSTING'] = date('H:i \· d M Y', $timestamp);
            } else {
                $diff = time() - $timestamp;
                if ($diff < 60) $data['WAKTU_POSTING'] = 'Baru saja';
                else if ($diff < 3600) $data['WAKTU_POSTING'] = floor($diff / 60) . 'm';
                else if ($diff < 86400) $data['WAKTU_POSTING'] = floor($diff / 3600) . 'j';
                else $data['WAKTU_POSTING'] = date('d M', $timestamp);
            }
        } else {
            $data['WAKTU_POSTING'] = '';
        }
        return $data;
    }
}
?>