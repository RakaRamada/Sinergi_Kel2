<?php
// File: app/controllers/GroupController.php

require_once __DIR__ . '/../models/GroupModel.php';
// --- TAMBAHAN BARU ---
// Kita butuh ini untuk memanggil getMediaByGroupId dan getDocumentsByGroupId
require_once __DIR__ . '/../models/MessageModel.php';
// --- AKHIR TAMBAHAN BARU ---


/**
 * Menampilkan halaman/view 'Buat Group'
 */
function showCreateForm() {
    // Memuat file view (form) yang kita buat di Langkah 3
    require 'app/views/create_group.php';
}

/**
 * Menyimpan data group baru dari form
 */
function storeGroup() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        $nama_group = trim($_POST['nama_group']);
        $deskripsi = trim($_POST['deskripsi']);
        $creator_user_id = (int)$_SESSION['user_id'];
        $group_image_file = $_FILES['group_image'];
        $image_name_to_db = null; 

        if (empty($nama_group) || empty($deskripsi)) {
            header('Location: index.php?page=create-group&error=empty');
            exit();
        }

        // Proses Upload Gambar (Jika ada)
        if (isset($group_image_file) && $group_image_file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/uploads/group_profiles/'; 
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // Coba buat folder jika tidak ada
            }
            
            $image_extension = strtolower(pathinfo($group_image_file['name'], PATHINFO_EXTENSION));
            $image_name_to_db = 'group_' . uniqid() . '.' . $image_extension;
            $upload_path = $upload_dir . $image_name_to_db;

            if (!move_uploaded_file($group_image_file['tmp_name'], $upload_path)) {
                header('Location: index.php?page=create-group&error=upload_failed');
                exit();
            }
        }

        // Panggil Model (Langkah 6)
        $new_group_id = createGroup($nama_group, $deskripsi, $creator_user_id, $image_name_to_db);

        if ($new_group_id) {
            // Otomatis join ke group yang baru dibuat
            
            // --- KITA HARUS AMBIL NAMA DI SINI JUGA ---
            $nama_asli = $_SESSION['nama_lengkap'] ?? '';
            $nama_untuk_tes = preg_replace('/[[:cntrl:]\s]/u', '', $nama_asli);
            if (empty($nama_untuk_tes)) {
                $user_nama = 'Seseorang';
            } else {
                $user_nama = trim($nama_asli);
            }
            // ----------------------------------------
            
            joinGroup($creator_user_id, $new_group_id, $user_nama); // <--- SEKARANG DENGAN 3 PARAMETER
            
            // Berhasil! Arahkan ke halaman pesan
            header('Location: index.php?page=messages&success=group_created');
            exit();
        } else {
            header('Location: index.php?page=create-group&error=db_error');
            exit();
        }
    } else {
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menangani permintaan user untuk bergabung ke sebuah group.
 * Dipanggil oleh router 'page=join-group'.
 */
function handleJoinGroup() {
    // 0. Pastikan session aktif untuk mendapatkan ID user
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN group_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['group_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_GET['group_id'];

        // --- PERBAIKAN LOGIKA FINAL ---
        // 1. Ambil nama asli dari session
        $nama_asli = $_SESSION['nama_lengkap'] ?? '';
        
        // 2. Buat versi bersih HANYA untuk tes (hapus SEMUA spasi & karakter aneh/control)
        //    [[:cntrl:]] -> semua control character (termasuk null byte \0)
        //    \s          -> semua whitespace (termasuk spasi "ajaib" \u)
        $nama_untuk_tes = preg_replace('/[[:cntrl:]\s]/u', '', $nama_asli);

        // 3. Cek: Apakah versi bersihnya itu KOSONG?
        if (empty($nama_untuk_tes)) {
            // Jika ya, nama itu pasti "kosong" atau spasi "ajaib". Gunakan default.
            $user_nama = 'Seseorang';
        } else {
            // Jika tidak, nama itu valid. Gunakan nama ASLI (tapi trim spasi biasa).
            $user_nama = trim($nama_asli);
        }
        // ---------------------------------

        // 4. Panggil fungsi Model 'joinGroup' (yang sudah kita buat)
        $success = joinGroup($user_id, $group_id, $user_nama);

        if ($success) {
            // 5. Berhasil! Arahkan user ke halaman group yang baru dia ikuti
            header('Location: index.php?page=messages&group_id=' . $group_id);
            exit();
        } else {
            // 6. Gagal (mungkin karena error DB)
            // Kembali ke halaman search dengan pesan error
            header('Location: index.php?page=search&tab=group&error=join_failed');
            exit();
        }

    } else {
        // 7. Jika tidak login atau tidak ada group_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menampilkan halaman detail group (info, anggota, dll.)
 */
function showGroupDetails() {
    // 0. Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN group_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['group_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_GET['group_id'];

        // 2. Panggil Model untuk data dasar group
        $group_info = getGroupById($group_id);

        // 3. Panggil Model untuk daftar anggota
        $group_members = getGroupMembers($group_id); 

        // 4. Cek apakah user ini adalah pembuat group (untuk tombol 'Edit')
        $is_creator = false;
        if ($group_info && isset($group_info['created_by_user_id'])) {
            $is_creator = ($group_info['created_by_user_id'] == $user_id);
        }

        // --- TAMBAHAN BARU ---
        // 5. Panggil Model untuk media dan dokumen
        $group_media = getMediaByGroupId($group_id);
        $group_documents = getDocumentsByGroupId($group_id);
        // --- AKHIR TAMBAHAN BARU ---


        // 6. Muat file view
        //    (Variabel $group_media dan $group_documents otomatis akan
        //     tersedia di dalam file view)
        require 'app/views/group_details.php';

    } else {
        // Jika tidak login atau tidak ada group_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menangani permintaan user untuk keluar dari sebuah group.
 * Dipanggil oleh router 'page=exit-group'.
 */
function handleExitGroup() {
    // 0. Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN group_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['group_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_GET['group_id'];

        // --- PERBAIKAN LOGIKA FINAL ---
        // 1. Ambil nama asli dari session
        $nama_asli = $_SESSION['nama_lengkap'] ?? '';
        
        // 2. Buat versi bersih HANYA untuk tes (hapus SEMUA spasi & karakter aneh/control)
        $nama_untuk_tes = preg_replace('/[[:cntrl:]\s]/u', '', $nama_asli);

        // 3. Cek: Apakah versi bersihnya itu KOSONG?
        if (empty($nama_untuk_tes)) {
            $user_nama = 'Seseorang';
        } else {
            $user_nama = trim($nama_asli);
        }
        // ---------------------------------

        // 4. Panggil fungsi Model 'leaveGroup' (tambahkan parameter $user_nama)
        $success = leaveGroup($user_id, $group_id, $user_nama);

        // 5. Arahkan kembali ke halaman pesan utama (bukan ke detail group)
        if ($success) {
            header('Location: index.php?page=messages&success=group_left');
            exit();
        } else {
            // Jika gagal, kembalikan ke halaman detail
            header('Location: index.php?page=group-details&group_id=' . $group_id . '&error=exit_failed');
            exit();
        }

    } else {
        // 6. Jika tidak login atau tidak ada group_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menampilkan form untuk mengedit group.
 */
function showEditForm() {
    // 0. Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN group_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['group_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_GET['group_id'];

        // 2. Panggil Model untuk data dasar group
        $group_info = getGroupById($group_id);

        // 3. Keamanan: Pastikan user ini adalah pembuat group
        if ($group_info && isset($group_info['created_by_user_id']) && $group_info['created_by_user_id'] == $user_id) {
            
            // 4. Muat file view (yang sudah kita buat)
            require 'app/views/edit_group.php';

        } else {
            // Jika bukan pembuat, tendang dia!
            header('Location: index.php?page=group-details&group_id=' . $group_id . '&error=not_creator');
            exit();
        }

    } else {
        // Jika tidak login atau tidak ada group_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Memproses data dari form 'Edit Group' dan menyimpannya.
 * Dipanggil oleh router 'page=update-group'.
 */
function handleUpdateGroup() {
    // === PANGGIL SESSION_START() DI AWAL ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // === AKHIR PERBAIKAN ===

    // 1. Validasi: Pastikan user login & ini adalah request POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        // 2. Ambil data dari form
        $user_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_POST['group_id'];
        $nama_group = trim($_POST['nama_group']);
        $deskripsi = trim($_POST['deskripsi']);
        $group_image_file = $_FILES['group_image']; 

        // 3. Validasi Keamanan: Cek apakah user ini adalah pembuat group
        $group_info = getGroupById($group_id); 
        if (!$group_info || $group_info['created_by_user_id'] != $user_id) {
            // Jika bukan pembuat, atau group tidak ada, tendang ke login
            header('Location: index.php?page=login');
            exit();
        }
        
        // 4. Validasi data
        if (empty($nama_group) || empty($deskripsi) || empty($group_id)) {
            header('Location: index.php?page=edit-group&group_id=' . $group_id . '&error=empty');
            exit();
        }

        $image_name_to_db = null; 

        // 5. Proses Upload Gambar (Jika ada gambar BARU)
        if (isset($group_image_file) && $group_image_file['error'] === UPLOAD_ERR_OK) {
            
            $upload_dir = '/Sinergi/public/uploads/group_profiles/'; 
            
            $image_extension = strtolower(pathinfo($group_image_file['name'], PATHINFO_EXTENSION));
            $image_name_to_db = 'group_' . uniqid() . '.' . $image_extension;
            $upload_path = $upload_dir . $image_name_to_db;

            if (move_uploaded_file($group_image_file['tmp_name'], $upload_path)) {
                // Hapus gambar lama JIKA ADA
                if (!empty($group_info['group_image'])) {
                    @unlink($upload_dir . $group_info['group_image']);
                }
            } else {
                header('Location: index.php?page=edit-group&group_id=' . $group_id . '&error=upload_failed');
                exit();
            }
        }
        
        // 6. Panggil Model untuk UPDATE
        $success = updateGroup($group_id, $nama_group, $deskripsi, $image_name_to_db);

        if ($success) {
            // 7. Berhasil! Arahkan kembali ke halaman info group
            header('Location: index.php?page=group-details&group_id=' . $group_id . '&success=updated');
            exit();
        } else {
            // Gagal menyimpan ke DB
            header('Location: index.php?page=edit-group&group_id=' . $group_id . '&error=db_error');
            exit();
        }

    } else {
        // Jika bukan POST atau tidak login (karena session_start() hilang), tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menampilkan halaman pencarian user untuk ditambahkan ke group.
 * Hanya bisa diakses oleh Pembuat Group (Admin).
 */
function showAddMemberForm() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
        header('Location: index.php?page=dashboard');
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $group_id = (int)$_GET['group_id'];
    $search = $_GET['q'] ?? '';

    // 1. Cek apakah user adalah Admin Group
    $group = getGroupById($group_id);
    if (!$group || $group['created_by_user_id'] != $user_id) {
        // Bukan admin? Tendang balik.
        header("Location: index.php?page=group-details&group_id=$group_id&error=not_admin");
        exit();
    }

    // 2. Ambil daftar user yang BELUM masuk group
    // Kita perlu require UserModel untuk data user (opsional, tapi fungsi getAvailable ada di GroupModel)
    $available_users = getUsersAvailableForGroup($group_id, $search);

    require 'app/views/add_member.php';
}

/**
 * Memproses penambahan anggota oleh Admin.
 */
function handleAddMemberProcess() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $admin_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_POST['group_id'];
        $target_user_id = (int)$_POST['target_user_id'];
        $target_user_name = $_POST['target_user_name'];

        // 1. Validasi Admin
        $group = getGroupById($group_id);
        if (!$group || $group['created_by_user_id'] != $admin_id) {
            // Kalau bukan admin, balikin ke detail group dengan error
            header("Location: index.php?page=group-details&group_id=$group_id&error=not_admin");
            exit();
        }

        // SIAPKAN PESAN KUSTOM
        $pesan_khusus = "$target_user_name telah ditambahkan oleh Admin Group.";

        // 2. Masukkan User (Panggil joinGroup dengan parameter ke-4)
        $success = joinGroup($target_user_id, $group_id, $target_user_name, $pesan_khusus);

        // --- PERBAIKAN DI SINI ---
        // Jangan redirect ke 'add-member', tapi ke 'group-details'
        if ($success) {
            header("Location: index.php?page=group-details&group_id=$group_id&success=member_added");
        } else {
            header("Location: index.php?page=group-details&group_id=$group_id&error=failed_add");
        }
        exit();
    }
}

/**
 * Memproses Kick Member.
 */
function handleKickMember() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_GET['group_id']) || !isset($_GET['user_id'])) {
        header('Location: index.php?page=dashboard');
        exit();
    }

    $admin_id = (int)$_SESSION['user_id'];
    $group_id = (int)$_GET['group_id'];
    $target_id = (int)$_GET['user_id'];
    $target_name = $_GET['name'] ?? 'Member';

    // 1. Validasi Admin
    $group = getGroupById($group_id);
    if (!$group || $group['created_by_user_id'] != $admin_id) {
        header("Location: index.php?page=group-details&group_id=$group_id&error=not_admin");
        exit();
    }

    // 2. Jangan biarkan admin kick diri sendiri
    if ($admin_id == $target_id) {
        header("Location: index.php?page=group-details&group_id=$group_id&error=cannot_kick_self");
        exit();
    }

    // 3. Lakukan Kick
    kickMember($group_id, $target_id, $target_name);

    header("Location: index.php?page=group-details&group_id=$group_id&success=kicked");
    exit();
}

/**
 * API: Mengembalikan daftar user yang bisa ditambahkan dalam format JSON.
 * Dipanggil oleh JavaScript di Modal saat mengetik pencarian.
 */
function searchCandidatesAPI() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Cek Admin
    if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
        echo json_encode([]);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $group_id = (int)$_GET['group_id'];
    $keyword = $_GET['q'] ?? '';

    // Validasi Admin
    $group = getGroupById($group_id);
    if (!$group || $group['created_by_user_id'] != $user_id) {
        echo json_encode([]);
        exit;
    }

    // Ambil data dari Model
    $candidates = getUsersAvailableForGroup($group_id, $keyword);
    
    // Kirim sebagai JSON
    header('Content-Type: application/json');
    echo json_encode($candidates);
    exit;
}

?>