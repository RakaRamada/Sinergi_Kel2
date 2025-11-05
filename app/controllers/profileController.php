<?php
// app/controllers/ProfileController.php

require_once __DIR__ . '/../../config/koneksi.php';

function showProfile() {
    global $conn;
    
    // 1. Cek session
    $profile_user_id = 0;
    $current_user_id = 0;

    if (isset($_SESSION['id_users'])) {
        $current_user_id = (int)$_SESSION['id_users'];
    }

    if (isset($_GET['id'])) {
        $profile_user_id = (int)$_GET['id'];
    } else {
        $profile_user_id = $current_user_id;
    }

    if ($profile_user_id == 0) {
        header('Location: index.php?page=login&error=Harap login untuk melihat profil');
        exit;
    }

    $is_my_profile = ($profile_user_id == $current_user_id);

    // 5. Query untuk mengambil data user
    // --- DIPERBAIKI: Menghapus u.nim ---
    $sql = "
        SELECT 
            u.id_users, 
            u.username, 
            u.nama_lengkap, 
            u.email, 
            u.avatar_url,
            (SELECT COUNT(*) FROM postingan p WHERE p.id_user = u.id_users) AS total_postingan
        FROM 
            users u 
        WHERE 
            u.id_users = :id_bv
    ";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_bv', $profile_user_id);
    
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "Error Query: " . $e['message'];
        exit;
    }
    
    $profile_data = oci_fetch_assoc($stmt);

    if (!$profile_data) {
        echo "Profil tidak ditemukan (ID: $profile_user_id).";
        exit;
    }

    // 7. Siapkan URL Avatar
    $default_avatar_dir = '/Sinergi/public/assets/images/';
    $default_avatar_file = '/Sinergi/public/assets/images/user.png';
    
    if (empty($profile_data['AVATAR_URL']) || $profile_data['AVATAR_URL'] == $default_avatar_dir) {
        $profile_data['AVATAR_URL_FIXED'] = $default_avatar_file;
    } else {
        $profile_data['AVATAR_URL_FIXED'] = $profile_data['AVATAR_URL'];
    }
    
    $profile_data['HEADER_URL_FIXED'] = '/Sinergi/public/assets/images/sore.jpg'; 

    
    // 8. Panggil file View
    require __DIR__ . '/../views/profile.php';

    // 9. Bersihkan statement
    oci_free_statement($stmt);
}


// --- FUNGSI BARU 1: Menampilkan Form Edit ---
function showEditProfileForm() {
    global $conn;

    // 1. Pastikan user login
    if (!isset($_SESSION['id_users'])) {
        header('Location: index.php?page=login');
        exit;
    }
    $current_user_id = (int)$_SESSION['id_users'];

    // 2. Ambil data HANYA untuk user yang sedang login
    // --- DIPERBAIKI: Menghapus nim dari query (Ini yang menyebabkan error ORA-00904) ---
    $sql = "SELECT id_users, username, nama_lengkap, email, avatar_url FROM users WHERE id_users = :id_bv";
    
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_bv', $current_user_id);

    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "Error Query: " . $e['message'];
        exit;
    }

    $user_data = oci_fetch_assoc($stmt);

    if (!$user_data) {
        echo "User tidak ditemukan.";
        exit;
    }

    // 3. Siapkan URL Avatar
    $default_avatar_dir = '/Sinergi/public/assets/images/';
    $default_avatar_file = '/Sinergi/public/assets/images/user.png';
    
    if (empty($user_data['AVATAR_URL']) || $user_data['AVATAR_URL'] == $default_avatar_dir) {
        $user_data['AVATAR_URL_FIXED'] = $default_avatar_file;
    } else {
        $user_data['AVATAR_URL_FIXED'] = $user_data['AVATAR_URL'];
    }

    // 4. Panggil view form edit
    require __DIR__ . '/../views/edit_profile.php';

    // 5. Bersihkan statement
    oci_free_statement($stmt);
}


// --- FUNGSI BARU 2: Memproses Update Profil ---
function processProfileUpdate() {
    global $conn;

    // 1. Pastikan user login
    if (!isset($_SESSION['id_users'])) {
        die("Aksi tidak diizinkan. Silakan login.");
    }
    $current_user_id = (int)$_SESSION['id_users'];

    // 2. Ambil data dari form
    // --- DIPERBAIKI: Menghapus nim ---
    $nama_lengkap = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    
    // Validasi sederhana
    if (empty($nama_lengkap) || empty($username)) {
        die("Nama dan Username tidak boleh kosong.");
    }

    $new_avatar_db_path = null; 

    // 3. Logika Upload Avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        
        $target_dir_server = $_SERVER['DOCUMENT_ROOT'] . '/Sinergi/public/uploads/avatars/';
        $target_dir_db = '/Sinergi/public/uploads/avatars/';

        if (!is_dir($target_dir_server)) {
            mkdir($target_dir_server, 0777, true);
        }

        $file_info = pathinfo($_FILES['avatar']['name']);
        $file_extension = strtolower($file_info['extension']);
        $new_filename = "user_" . $current_user_id . "_" . time() . "." . $file_extension;
        
        $target_file_server = $target_dir_server . $new_filename;
        $new_avatar_db_path = $target_dir_db . $new_filename;

        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $max_size = 5 * 1024 * 1024; // 5 MB

        if (in_array($file_extension, $allowed_ext) && $_FILES['avatar']['size'] <= $max_size) {
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file_server)) {
                $new_avatar_db_path = null;
                echo "Error: Gagal memindahkan file.";
            }
        } else {
            $new_avatar_db_path = null; 
            echo "Error: File tidak valid (hanya .jpg, .jpeg, .png, maks 5MB).";
        }
    }

    // 4. Buat Kueri UPDATE
    // --- DIPERBAIKI: Menghapus nim dari query ---
    if ($new_avatar_db_path !== null) {
        // Jika ada avatar baru
        $sql = "UPDATE users 
                SET nama_lengkap = :nama_bv, 
                    username = :user_bv, 
                    avatar_url = :avatar_bv 
                WHERE id_users = :id_bv";
    } else {
        // Jika tidak ada avatar baru
        $sql = "UPDATE users 
                SET nama_lengkap = :nama_bv, 
                    username = :user_bv 
                WHERE id_users = :id_bv";
    }

    $stmt = oci_parse($conn, $sql);

    // 5. Bind parameter
    // --- DIPERBAIKI: Menghapus nim ---
    oci_bind_by_name($stmt, ':nama_bv', $nama_lengkap);
    oci_bind_by_name($stmt, ':user_bv', $username);
    oci_bind_by_name($stmt, ':id_bv', $current_user_id);
    
    if ($new_avatar_db_path !== null) {
        oci_bind_by_name($stmt, ':avatar_bv', $new_avatar_db_path);
    }

    // 6. Eksekusi dan Commit
    if (oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
        oci_commit($conn);
    } else {
        oci_rollback($conn);
        $e = oci_error($stmt);
        echo "Error Update: " . $e['message'];
        exit;
    }

    // 7. Bersihkan statement
    oci_free_statement($stmt);

    // 8. Redirect kembali ke halaman profil
    header('Location: index.php?page=profile&status=update_sukses');
    exit;
}
?>

