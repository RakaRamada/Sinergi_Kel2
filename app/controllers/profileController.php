<?php
// app/controllers/ProfileController.php

require_once __DIR__ . '/../../config/koneksi.php';

function showProfile() {
    global $conn;
    
    // 1. WAJIB: Mulai session di SINI
    // 2. Tentukan ID User yang akan ditampilkan
    $profile_user_id = 0; // Default
    $current_user_id = 0; // Default

    // Cek apakah user sedang login
    if (isset($_SESSION['id_users'])) {
        $current_user_id = (int)$_SESSION['id_users'];
    }

    // Cek apakah ada ID di URL (melihat profil orang lain)
    if (isset($_GET['id'])) {
        $profile_user_id = (int)$_GET['id'];
    } else {
        // Jika tidak ada ID di URL, tampilkan profil sendiri
        $profile_user_id = $current_user_id;
    }

    // 3. Validasi Kritis:
    //    Jika $profile_user_id masih 0 (artinya user belum login DAN tidak ada ID di URL)
    //    maka kita tidak tahu profil siapa yang harus ditampilkan.
    if ($profile_user_id == 0) {
        // Lempar ke halaman login
        header('Location: index.php?page=login&error=Harap login untuk melihat profil');
        exit;
    }

    // 4. Tentukan apakah ini profil kita sendiri?
    $is_my_profile = ($profile_user_id == $current_user_id);

    // 5. Query untuk mengambil data user
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
    
    // Cek jika eksekusi berhasil
    if (!oci_execute($stmt)) {
        $e = oci_error($stmt);
        echo "Error Query: " . $e['message'];
        exit;
    }
    
    $profile_data = oci_fetch_assoc($stmt);

    // 6. Validasi Data
    if (!$profile_data) {
        // Jika user dengan ID tsb tidak ditemukan
        echo "Profil tidak ditemukan (ID: $profile_user_id).";
        exit;
    }

    // 7. Siapkan URL Avatar
    $default_avatar_dir = '/Sinergi/public/assets/images/';
    $default_avatar_file = '/Sinergi/public/assets/images/user.png';
    
    // Gunakan AVATAR_URL_FIXED sebagai variabel baru yang aman
    if (empty($profile_data['AVATAR_URL']) || $profile_data['AVATAR_URL'] == $default_avatar_dir) {
        $profile_data['AVATAR_URL_FIXED'] = $default_avatar_file;
    } else {
        $profile_data['AVATAR_URL_FIXED'] = $profile_data['AVATAR_URL'];
    }
    
    // (Ini masih statis, Anda bisa menambahkannya ke tabel 'users' nanti)
    $profile_data['HEADER_URL_FIXED'] = '/Sinergi/public/assets/images/sore.jpg'; 

    
    // 8. Panggil file View (Sekarang $profile_data dan $is_my_profile PASTI ada isinya)
    require __DIR__ . '/../views/profile.php';

    // 9. Bersihkan statement
    oci_free_statement($stmt);
    oci_close($conn);
}
?>
