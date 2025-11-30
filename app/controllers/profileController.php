<?php
// app/controllers/profileController.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/koneksi.php';

// Utility: buat path default (ubah nama file default jika ingin gambar lain)
$DEFAULT_AVATAR = '/Sinergi/public/assets/images/user.png';
$DEFAULT_HEADER = '/Sinergi/public/assets/images/default-header.jpg'; // <-- ganti file default header sesuai yang kamu sediakan

// -------------
// TAMPILKAN PROFIL
// ------------------------------
function showProfile() {
    global $conn, $DEFAULT_AVATAR, $DEFAULT_HEADER;

    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login&error=Harap login terlebih dahulu');
        exit;
    }

    $current_user_id = (int)$_SESSION['user_id'];
    $profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : $current_user_id;
    $is_my_profile = ($profile_user_id === $current_user_id);

    // Ambil data user termasuk role name, bio, header_url, followers, following, nim
    $sql = "
        SELECT 
            u.user_id,
            u.username,
            u.nama_lengkap,
            u.email,
            u.avatar_url,
            u.header_url,
            u.bio,
            u.nim,
            r.role_name,
            (SELECT COUNT(*) FROM postingan p WHERE p.user_id = u.user_id) AS total_postingan
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = :id_bv
    ";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_bv', $profile_user_id);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "Error Query: " . $e['message'];
        exit;
    }

    $profile_data = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$profile_data) {
        echo "Profil tidak ditemukan (ID: $profile_user_id)";
        exit;
    }

    // Fix keys and defaults (kembalikan sesuai nama yang dipakai di view)
    // Oracle returns uppercase keys by default, but fetch_assoc used as-is (we rely on view using uppercase)
    // Pastikan kita set AVATAR_URL_FIXED & HEADER_URL_FIXED
    $profile_data['AVATAR_URL_FIXED'] = !empty($profile_data['AVATAR_URL']) 
        ? $profile_data['AVATAR_URL'] 
        : $DEFAULT_AVATAR;

    $profile_data['HEADER_URL_FIXED'] = !empty($profile_data['HEADER_URL']) 
        ? $profile_data['HEADER_URL'] 
        : $DEFAULT_HEADER;

    // Provide default numeric values if null
    $profile_data['FOLLOWERS'] = isset($profile_data['FOLLOWERS']) ? (int)$profile_data['FOLLOWERS'] : 0;
    $profile_data['FOLLOWING'] = isset($profile_data['FOLLOWING']) ? (int)$profile_data['FOLLOWING'] : 0;
    $profile_data['BIO'] = isset($profile_data['BIO']) ? $profile_data['BIO'] : '';
    $profile_data['NIM'] = isset($profile_data['NIM']) ? $profile_data['NIM'] : '';

    // Keep variables for view
    $is_my_profile = $is_my_profile;
$user_posts = [];
    
    // Query mirip dengan api/ambil_postingan.php tapi difilter WHERE p.user_id = :uid
    $sql_posts = "
        SELECT 
            p.post_id,    
            p.user_id,
            p.konten,
            p.post_image,
            p.like_count,       
            p.comment_count, 
            TO_CHAR(p.created_at, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
            u.username,
            u.nama_lengkap,
            u.avatar_url,
            (SELECT COUNT(*) 
             FROM likes l 
             WHERE l.post_id = p.post_id AND l.user_id = :current_user_bv) AS USER_SUDAH_LIKE
        FROM 
            postingan p
        JOIN 
            users u ON p.user_id = u.user_id
        WHERE 
            p.user_id = :target_user_bv
        ORDER BY 
            p.post_id DESC
    ";

    $stmt_posts = oci_parse($conn, $sql_posts);
    oci_bind_by_name($stmt_posts, ':target_user_bv', $profile_user_id); // ID profil yang dilihat
    oci_bind_by_name($stmt_posts, ':current_user_bv', $current_user_id); // ID kita (untuk cek like)
    
    oci_execute($stmt_posts);

    while ($row = oci_fetch_assoc($stmt_posts)) {
        // Fix Uppercase Keys dari Oracle
        $row = array_change_key_case($row, CASE_UPPER);

        // 1. Fix Avatar
        if (empty($row['AVATAR_URL']) || strpos($row['AVATAR_URL'], '/assets/images/') !== false && strlen($row['AVATAR_URL']) < 30) {
             // Logika sederhana: jika kosong atau path default, pakai default
             $row['AVATAR_URL_FIXED'] = $DEFAULT_AVATAR;
        } else {
             $row['AVATAR_URL_FIXED'] = $row['AVATAR_URL'];
        }

        // 2. Fix Waktu (Time Ago)
        $timestamp = strtotime($row['CREATED_AT_STR']); 
        if ($timestamp === false) {
            $row['WAKTU_POSTING'] = '-';
        } else {
            $diff = time() - $timestamp;
            if ($diff < 60) { $row['WAKTU_POSTING'] = 'Baru saja'; }
            else if ($diff < 3600) { $row['WAKTU_POSTING'] = floor($diff / 60) . 'm'; }
            else if ($diff < 86400) { $row['WAKTU_POSTING'] = floor($diff / 3600) . 'j'; }
            else { $row['WAKTU_POSTING'] = date('d M Y', $timestamp); }
        }

        $user_posts[] = $row;
    }
    oci_free_statement($stmt_posts);

    // Keep variables for view
    $is_my_profile = $is_my_profile;
    require __DIR__ . '/../views/profile.php';
}

// ------------------------------
// FORM EDIT PROFIL
// ------------------------------
function showEditProfileForm() {
    global $conn, $DEFAULT_AVATAR, $DEFAULT_HEADER;

    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }

    $current_user_id = (int)$_SESSION['user_id'];

    $sql = "SELECT user_id, username, nama_lengkap, email, avatar_url, header_url, bio, nim FROM users WHERE user_id = :id_bv";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_bv', $current_user_id);
    oci_execute($stmt);
    $user_data = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if (!$user_data) {
        echo "Data user tidak ditemukan.";
        exit;
    }

    $user_data['AVATAR_URL_FIXED'] = !empty($user_data['AVATAR_URL']) ? $user_data['AVATAR_URL'] : $DEFAULT_AVATAR;
    $user_data['HEADER_URL_FIXED'] = !empty($user_data['HEADER_URL']) ? $user_data['HEADER_URL'] : $DEFAULT_HEADER;
    $user_data['BIO'] = isset($user_data['BIO']) ? $user_data['BIO'] : '';
    $user_data['NIM'] = isset($user_data['NIM']) ? $user_data['NIM'] : '';

    require __DIR__ . '/../views/edit_profile.php';
}

// ------------------------------
// PROSES UPDATE PROFIL
// ------------------------------
// File: app/controllers/profileController.php

// File: app/controllers/profileController.php

function processProfileUpdate() {
    global $conn;
    
    if (session_status() === PHP_SESSION_NONE) session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $bio = trim($_POST['bio'] ?? '');
        
        $file = $_FILES['foto_profil'] ?? null;
        $avatar_baru = null;

        // 1. PROSES UPLOAD FILE (Jika Ada)
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/uploads/avatars/'; 
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed)) {
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $avatar_baru = $filename;
                }
            }
        }

        // 2. SIAPKAN QUERY (JURUS ANTI ORA-01745)
        // Kita ganti :uid jadi :p_uid, :nama jadi :p_nama, dst biar aman 100%
        
        if ($avatar_baru) {
            // Jika ganti foto
            $sql = "UPDATE users 
                    SET nama_lengkap = :p_nama, 
                        bio = :p_bio, 
                        avatar_url = :p_avatar 
                    WHERE user_id = :p_uid";
        } else {
            // Jika cuma ganti teks
            $sql = "UPDATE users 
                    SET nama_lengkap = :p_nama, 
                        bio = :p_bio 
                    WHERE user_id = :p_uid";
        }

        // 3. BINDING VARIABEL
        $stmt = oci_parse($conn, $sql);
        
        oci_bind_by_name($stmt, ':p_nama', $nama_lengkap);
        oci_bind_by_name($stmt, ':p_bio', $bio);
        oci_bind_by_name($stmt, ':p_uid', $user_id); // <--- INI KUNCINYA (:p_uid)
        
        if ($avatar_baru) {
            oci_bind_by_name($stmt, ':p_avatar', $avatar_baru);
        }

        // 4. EKSEKUSI
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            // Update Session biar perubahan langsung terasa tanpa relogin
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            if ($avatar_baru) {
                $_SESSION['avatar_url'] = $avatar_baru;
            }
            
            header('Location: index.php?page=profile&success=updated');
            exit();
        } else {
            $e = oci_error($stmt);
            die("Error Update Database: " . $e['message']);
        }

    } else {
        header('Location: index.php?page=dashboard');
        exit();
    }
}