<?php
// File: app/controllers/ForumController.php

require_once __DIR__ . '/../models/ForumModel.php';
// --- TAMBAHAN BARU ---
// Kita butuh ini untuk memanggil getMediaByForumId dan getDocumentsByForumId
require_once __DIR__ . '/../models/MessageModel.php';
// --- AKHIR TAMBAHAN BARU ---


/**
 * Menampilkan halaman/view 'Buat Forum'
 */
function showCreateForm() {
    // Memuat file view (form) yang kita buat di Langkah 3
    require 'app/views/create_forum.php';
}

/**
 * Menyimpan data forum baru dari form
 */
function storeForum() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        $nama_forum = trim($_POST['nama_forum']);
        $deskripsi = trim($_POST['deskripsi']);
        $creator_user_id = (int)$_SESSION['user_id'];
        $forum_image_file = $_FILES['forum_image'];
        $image_name_to_db = null; 

        if (empty($nama_forum) || empty($deskripsi)) {
            header('Location: index.php?page=create-forum&error=empty');
            exit();
        }

        // Proses Upload Gambar (Jika ada)
        if (isset($forum_image_file) && $forum_image_file['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/uploads/forum_profiles/'; 
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // Coba buat folder jika tidak ada
            }
            
            $image_extension = strtolower(pathinfo($forum_image_file['name'], PATHINFO_EXTENSION));
            $image_name_to_db = 'forum_' . uniqid() . '.' . $image_extension;
            $upload_path = $upload_dir . $image_name_to_db;

            if (!move_uploaded_file($forum_image_file['tmp_name'], $upload_path)) {
                header('Location: index.php?page=create-forum&error=upload_failed');
                exit();
            }
        }

        // Panggil Model (Langkah 6)
        $new_forum_id = createForum($nama_forum, $deskripsi, $creator_user_id, $image_name_to_db);

        if ($new_forum_id) {
            // Otomatis join ke forum yang baru dibuat
            
            // --- KITA HARUS AMBIL NAMA DI SINI JUGA ---
            $nama_asli = $_SESSION['nama_lengkap'] ?? '';
            $nama_untuk_tes = preg_replace('/[[:cntrl:]\s]/u', '', $nama_asli);
            if (empty($nama_untuk_tes)) {
                $user_nama = 'Seseorang';
            } else {
                $user_nama = trim($nama_asli);
            }
            // ----------------------------------------
            
            joinForum($creator_user_id, $new_forum_id, $user_nama); // <--- SEKARANG DENGAN 3 PARAMETER
            
            // Berhasil! Arahkan ke halaman pesan
            header('Location: index.php?page=messages&success=forum_created');
            exit();
        } else {
            header('Location: index.php?page=create-forum&error=db_error');
            exit();
        }
    } else {
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menangani permintaan user untuk bergabung ke sebuah forum.
 * Dipanggil oleh router 'page=join-forum'.
 */
function handleJoinForum() {
    // 0. Pastikan session aktif untuk mendapatkan ID user
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN forum_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['forum_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_GET['forum_id'];

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

        // 4. Panggil fungsi Model 'joinForum' (yang sudah kita buat)
        $success = joinForum($user_id, $forum_id, $user_nama);

        if ($success) {
            // 5. Berhasil! Arahkan user ke halaman forum yang baru dia ikuti
            header('Location: index.php?page=messages&forum_id=' . $forum_id);
            exit();
        } else {
            // 6. Gagal (mungkin karena error DB)
            // Kembali ke halaman search dengan pesan error
            header('Location: index.php?page=search&tab=forum&error=join_failed');
            exit();
        }

    } else {
        // 7. Jika tidak login atau tidak ada forum_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menampilkan halaman detail forum (info, anggota, dll.)
 */
function showForumDetails() {
    // 0. Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN forum_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['forum_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_GET['forum_id'];

        // 2. Panggil Model untuk data dasar forum
        $forum_info = getForumById($forum_id);

        // 3. Panggil Model untuk daftar anggota
        $forum_members = getForumMembers($forum_id); 

        // 4. Cek apakah user ini adalah pembuat forum (untuk tombol 'Edit')
        $is_creator = false;
        if ($forum_info && isset($forum_info['created_by_user_id'])) {
            $is_creator = ($forum_info['created_by_user_id'] == $user_id);
        }

        // --- TAMBAHAN BARU ---
        // 5. Panggil Model untuk media dan dokumen
        $forum_media = getMediaByForumId($forum_id);
        $forum_documents = getDocumentsByForumId($forum_id);
        // --- AKHIR TAMBAHAN BARU ---


        // 6. Muat file view
        //    (Variabel $forum_media dan $forum_documents otomatis akan
        //     tersedia di dalam file view)
        require 'app/views/forum_details.php';

    } else {
        // Jika tidak login atau tidak ada forum_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menangani permintaan user untuk keluar dari sebuah forum.
 * Dipanggil oleh router 'page=exit-forum'.
 */
function handleExitForum() {
    // 0. Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN forum_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['forum_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_GET['forum_id'];

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

        // 4. Panggil fungsi Model 'leaveForum' (tambahkan parameter $user_nama)
        $success = leaveForum($user_id, $forum_id, $user_nama);

        // 5. Arahkan kembali ke halaman pesan utama (bukan ke detail forum)
        if ($success) {
            header('Location: index.php?page=messages&success=forum_left');
            exit();
        } else {
            // Jika gagal, kembalikan ke halaman detail
            header('Location: index.php?page=forum-details&forum_id=' . $forum_id . '&error=exit_failed');
            exit();
        }

    } else {
        // 6. Jika tidak login atau tidak ada forum_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menampilkan form untuk mengedit forum.
 */
function showEditForm() {
    // 0. Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 1. Validasi: Pastikan user login DAN forum_id ada di URL
    if (isset($_SESSION['user_id']) && isset($_GET['forum_id'])) {
        
        $user_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_GET['forum_id'];

        // 2. Panggil Model untuk data dasar forum
        $forum_info = getForumById($forum_id);

        // 3. Keamanan: Pastikan user ini adalah pembuat forum
        if ($forum_info && isset($forum_info['created_by_user_id']) && $forum_info['created_by_user_id'] == $user_id) {
            
            // 4. Muat file view (yang sudah kita buat)
            require 'app/views/edit_forum.php';

        } else {
            // Jika bukan pembuat, tendang dia!
            header('Location: index.php?page=forum-details&forum_id=' . $forum_id . '&error=not_creator');
            exit();
        }

    } else {
        // Jika tidak login atau tidak ada forum_id, tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Memproses data dari form 'Edit Forum' dan menyimpannya.
 * Dipanggil oleh router 'page=update-forum'.
 */
function handleUpdateForum() {
    // === PANGGIL SESSION_START() DI AWAL ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // === AKHIR PERBAIKAN ===

    // 1. Validasi: Pastikan user login & ini adalah request POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
        
        // 2. Ambil data dari form
        $user_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_POST['forum_id'];
        $nama_forum = trim($_POST['nama_forum']);
        $deskripsi = trim($_POST['deskripsi']);
        $forum_image_file = $_FILES['forum_image']; 

        // 3. Validasi Keamanan: Cek apakah user ini adalah pembuat forum
        $forum_info = getForumById($forum_id); 
        if (!$forum_info || $forum_info['created_by_user_id'] != $user_id) {
            // Jika bukan pembuat, atau forum tidak ada, tendang ke login
            header('Location: index.php?page=login');
            exit();
        }
        
        // 4. Validasi data
        if (empty($nama_forum) || empty($deskripsi) || empty($forum_id)) {
            header('Location: index.php?page=edit-forum&forum_id=' . $forum_id . '&error=empty');
            exit();
        }

        $image_name_to_db = null; 

        // 5. Proses Upload Gambar (Jika ada gambar BARU)
        if (isset($forum_image_file) && $forum_image_file['error'] === UPLOAD_ERR_OK) {
            
            $upload_dir = 'public/uploads/forum_profiles/'; 
            
            $image_extension = strtolower(pathinfo($forum_image_file['name'], PATHINFO_EXTENSION));
            $image_name_to_db = 'forum_' . uniqid() . '.' . $image_extension;
            $upload_path = $upload_dir . $image_name_to_db;

            if (move_uploaded_file($forum_image_file['tmp_name'], $upload_path)) {
                // Hapus gambar lama JIKA ADA
                if (!empty($forum_info['forum_image'])) {
                    @unlink($upload_dir . $forum_info['forum_image']);
                }
            } else {
                header('Location: index.php?page=edit-forum&forum_id=' . $forum_id . '&error=upload_failed');
                exit();
            }
        }
        
        // 6. Panggil Model untuk UPDATE
        $success = updateForum($forum_id, $nama_forum, $deskripsi, $image_name_to_db);

        if ($success) {
            // 7. Berhasil! Arahkan kembali ke halaman info forum
            header('Location: index.php?page=forum-details&forum_id=' . $forum_id . '&success=updated');
            exit();
        } else {
            // Gagal menyimpan ke DB
            header('Location: index.php?page=edit-forum&forum_id=' . $forum_id . '&error=db_error');
            exit();
        }

    } else {
        // Jika bukan POST atau tidak login (karena session_start() hilang), tendang ke login
        header('Location: index.php?page=login');
        exit();
    }
}

/**
 * Menampilkan halaman pencarian user untuk ditambahkan ke forum.
 * Hanya bisa diakses oleh Pembuat Forum (Admin).
 */
function showAddMemberForm() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION['user_id']) || !isset($_GET['forum_id'])) {
        header('Location: index.php?page=dashboard');
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $forum_id = (int)$_GET['forum_id'];
    $search = $_GET['q'] ?? '';

    // 1. Cek apakah user adalah Admin Forum
    $forum = getForumById($forum_id);
    if (!$forum || $forum['created_by_user_id'] != $user_id) {
        // Bukan admin? Tendang balik.
        header("Location: index.php?page=forum-details&forum_id=$forum_id&error=not_admin");
        exit();
    }

    // 2. Ambil daftar user yang BELUM masuk forum
    // Kita perlu require UserModel untuk data user (opsional, tapi fungsi getAvailable ada di ForumModel)
    $available_users = getUsersAvailableForForum($forum_id, $search);

    require 'app/views/add_member.php';
}

/**
 * Memproses penambahan anggota oleh Admin.
 */
function handleAddMemberProcess() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $admin_id = (int)$_SESSION['user_id'];
        $forum_id = (int)$_POST['forum_id'];
        $target_user_id = (int)$_POST['target_user_id'];
        $target_user_name = $_POST['target_user_name'];

        // 1. Validasi Admin
        $forum = getForumById($forum_id);
        if (!$forum || $forum['created_by_user_id'] != $admin_id) {
            // Kalau bukan admin, balikin ke detail forum dengan error
            header("Location: index.php?page=forum-details&forum_id=$forum_id&error=not_admin");
            exit();
        }

        // SIAPKAN PESAN KUSTOM
        $pesan_khusus = "$target_user_name telah ditambahkan oleh Admin Forum.";

        // 2. Masukkan User (Panggil joinForum dengan parameter ke-4)
        $success = joinForum($target_user_id, $forum_id, $target_user_name, $pesan_khusus);

        // --- PERBAIKAN DI SINI ---
        // Jangan redirect ke 'add-member', tapi ke 'forum-details'
        if ($success) {
            header("Location: index.php?page=forum-details&forum_id=$forum_id&success=member_added");
        } else {
            header("Location: index.php?page=forum-details&forum_id=$forum_id&error=failed_add");
        }
        exit();
    }
}

/**
 * Memproses Kick Member.
 */
function handleKickMember() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!isset($_GET['forum_id']) || !isset($_GET['user_id'])) {
        header('Location: index.php?page=dashboard');
        exit();
    }

    $admin_id = (int)$_SESSION['user_id'];
    $forum_id = (int)$_GET['forum_id'];
    $target_id = (int)$_GET['user_id'];
    $target_name = $_GET['name'] ?? 'Member';

    // 1. Validasi Admin
    $forum = getForumById($forum_id);
    if (!$forum || $forum['created_by_user_id'] != $admin_id) {
        header("Location: index.php?page=forum-details&forum_id=$forum_id&error=not_admin");
        exit();
    }

    // 2. Jangan biarkan admin kick diri sendiri
    if ($admin_id == $target_id) {
        header("Location: index.php?page=forum-details&forum_id=$forum_id&error=cannot_kick_self");
        exit();
    }

    // 3. Lakukan Kick
    kickMember($forum_id, $target_id, $target_name);

    header("Location: index.php?page=forum-details&forum_id=$forum_id&success=kicked");
    exit();
}

/**
 * API: Mengembalikan daftar user yang bisa ditambahkan dalam format JSON.
 * Dipanggil oleh JavaScript di Modal saat mengetik pencarian.
 */
function searchCandidatesAPI() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Cek Admin
    if (!isset($_SESSION['user_id']) || !isset($_GET['forum_id'])) {
        echo json_encode([]);
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $forum_id = (int)$_GET['forum_id'];
    $keyword = $_GET['q'] ?? '';

    // Validasi Admin
    $forum = getForumById($forum_id);
    if (!$forum || $forum['created_by_user_id'] != $user_id) {
        echo json_encode([]);
        exit;
    }

    // Ambil data dari Model
    $candidates = getUsersAvailableForForum($forum_id, $keyword);
    
    // Kirim sebagai JSON
    header('Content-Type: application/json');
    echo json_encode($candidates);
    exit;
}

?>