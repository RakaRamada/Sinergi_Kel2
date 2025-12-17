<?php
// File: app/controllers/GroupController.php
// VERSI FINAL: Integrasi dengan GroupModel Cerdas (Auto-Chat)

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../models/GroupModel.php';
require_once __DIR__ . '/../models/MessageModel.php';
require_once __DIR__ . '/../models/NotificationModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class GroupController { 

    private $conn;
    private $groupModel;
    private $messageModel;
    private $notificationModel;
    private $userModel;

    public function __construct($dbConnection) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->conn = $dbConnection;
        
        $this->groupModel = new GroupModel($dbConnection);
        $this->messageModel = new MessageModel($dbConnection);
        $this->notificationModel = new NotificationModel($dbConnection);
        $this->userModel = new UserModel($dbConnection);
    }

    // --- HELPER: CEK HAK AKSES (OWNER / ADMIN) ---
    private function canManageGroup($user_id, $group_id) {
        $role = $this->groupModel->getUserRole($user_id, $group_id);
        return ($role === 'owner' || $role === 'admin');
    }

    // --- CREATE GROUP ---
    public function showCreateForm() {
        // 1. CEK LOGIN
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login'); exit();
        }

        // 2. CEK ROLE (Hanya Mhs/Dosen & Admin)
        $role_id = $_SESSION['role_id'] ?? 0;
        if (in_array($role_id, [3, 4])) {
            header('Location: index.php?page=messages&error=restricted_access'); 
            exit();
        }

        // ============================================================
        // 3. COPY LOGIKA DARI NOTIFICATION CONTROLLER
        // ============================================================
        
        // Ambil data pakai nama variabel yang dikenali sidebar ($recommended...)
        // Kita pakai $this->userModel karena sudah ada di construct GroupController
        $recommendedUsers = $this->userModel->getTopActiveUsers(5); 
        $recommendedGroups = $this->groupModel->getPopularGroups(5);

        // --- LOGIKA FIX PATH GAMBAR (PENTING AGAR GAMBAR MUNCUL) ---
        // Loop User
        if (is_array($recommendedUsers)) {
            foreach ($recommendedUsers as &$u) {
                if(empty($u['avatar_url'])) {
                    $u['avatar_url'] = '/Sinergi/public/assets/images/user.png';
                } elseif(strpos($u['avatar_url'], '/') === false) {
                    $u['avatar_url'] = '/Sinergi/public/uploads/avatars/' . $u['avatar_url'];
                }
            }
        }

        // Loop Grup
        if (is_array($recommendedGroups)) {
            foreach ($recommendedGroups as &$g) {
                $img = $g['group_image'] ?? '';
                if (!empty($img)) {
                    $g['group_image'] = '/Sinergi/public/uploads/group_profiles/' . $img;
                } else {
                    $g['group_image'] = '/Sinergi/public/assets/images/user.png'; // Default
                }
            }
        }

        // ============================================================
        // 4. PANGGIL VIEW
        // ============================================================
        // Variabel $recommendedUsers & $recommendedGroups otomatis mengalir ke sini
        require __DIR__ . '/../views/create_group.php';
    }

    public function storeGroup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {

            $role_id = $_SESSION['role_id'] ?? 0;
            if (in_array($role_id, [3, 4])) {
                header('Location: index.php?page=messages&error=restricted_access'); 
                exit();
            }
            $nama_group = trim($_POST['nama_group']);
            $deskripsi = trim($_POST['deskripsi']);
            $is_private = isset($_POST['is_private']) ? (int)$_POST['is_private'] : 0;
            $creator_user_id = (int)$_SESSION['user_id'];
            $group_image_file = $_FILES['group_image'];
            $image_name_to_db = null; 

            if (empty($nama_group) || empty($deskripsi)) {
                header('Location: index.php?page=create-group&error=empty'); exit();
            }

            if (isset($group_image_file) && $group_image_file['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../public/uploads/group_profiles/'; 
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $ext = strtolower(pathinfo($group_image_file['name'], PATHINFO_EXTENSION));
                $image_name_to_db = 'group_' . uniqid() . '.' . $ext;
                move_uploaded_file($group_image_file['tmp_name'], $upload_dir . $image_name_to_db);
            }

            $new_group_id = $this->groupModel->createGroup($nama_group, $deskripsi, $creator_user_id, $image_name_to_db, $is_private);
            
            if ($new_group_id) {
                // Join Creator sebagai Owner
                // Pesan chat "Berhasil dibuat" tetap kita trigger manual khusus Creator agar pesannya beda
                $this->groupModel->joinGroup($creator_user_id, $new_group_id, 'active', 'owner');
                // Kita override pesan default "telah bergabung" dengan pesan khusus:
                $this->messageModel->createSystemMessage($new_group_id, $creator_user_id, 'join', 'Group berhasil dibuat.');
                
                header('Location: index.php?page=messages&success=group_created');
            } else {
                header('Location: index.php?page=create-group&error=db_error');
            }
            exit();
        } else {
            header('Location: index.php?page=login'); exit();
        }
    }

    // --- JOIN & LEAVE ---
    public function handleJoinGroup() {
        if (isset($_SESSION['user_id']) && isset($_GET['group_id'])) {
            $user_id = (int)$_SESSION['user_id'];
            $group_id = (int)$_GET['group_id'];
            
            // 1. Ambil Role ID User
            $role_id = (int)($_SESSION['role_id'] ?? 0);

            $group = $this->groupModel->getGroupById($group_id);
            
            if (!$group) { header('Location: index.php?page=search&tab=group&error=not_found'); exit(); }

            $is_private_group = ($group['is_private'] == 1);
            
            // 2. LOGIKA KEAMANAN EKSTERNAL (RBAC)
            // Role: 3 = Alumni, 4 = Mitra
            $is_external_user = in_array($role_id, [3, 4]);

            // Tentukan Status Join:
            // Jika user Eksternal -> WAJIB 'pending' (Request dulu)
            // Jika user Internal (Mhs/Dosen) -> Ikuti settingan grup (Publik=active, Privat=pending)
            if ($is_external_user) {
                $status = 'pending';
            } else {
                $status = $is_private_group ? 'pending' : 'active';
            }
            
            // 3. Eksekusi Join ke Model
            $success = $this->groupModel->joinGroup($user_id, $group_id, $status, 'member');

            if ($success) {
                // 4. Redirect sesuai STATUS AKHIR (Bukan settingan grup lagi)
                if ($status === 'active') {
                    // Berhasil Join Langsung -> Masuk ke Chat
                    header('Location: index.php?page=messages&group_id=' . $group_id);
                } else {
                    // Masuk Waiting List -> Balik ke Search dengan pesan sukses
                    header('Location: index.php?page=search&tab=group&q=' . urlencode($_GET['q'] ?? '') . '&success=requested');
                }
            } else {
                header('Location: index.php?page=search&tab=group&error=join_failed');
            }
            exit();
        }
        header('Location: index.php?page=login'); exit();
    }

    public function handleExitGroup() {
        if (isset($_SESSION['user_id']) && isset($_GET['group_id'])) {
            $user_id = (int)$_SESSION['user_id'];
            $group_id = (int)$_GET['group_id'];
            
            // Cek Role: Owner tidak boleh keluar sembarangan
            $myRole = $this->groupModel->getUserRole($user_id, $group_id);
            if ($myRole === 'owner') {
                header("Location: index.php?page=group-details&group_id=$group_id&error=owner_cannot_leave");
                exit();
            }

            // Pesan LEAVE tetap manual di sini, karena Model leaveGroup hanya delete data
            $nama = $_SESSION['nama_lengkap'] ?? 'Seseorang';
            $this->messageModel->createSystemMessage($group_id, $user_id, 'leave', "$nama telah keluar dari group.");
            
            $this->groupModel->leaveGroup($user_id, $group_id);

            header('Location: index.php?page=messages&success=group_left');
            exit();
        }
    }

    // --- MEMBER MANAGEMENT (Add/Kick/Promote) ---

    public function handleAddMemberProcess() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actor_id = (int)$_SESSION['user_id'];
            $group_id = (int)$_POST['group_id'];
            $target_user_id = (int)$_POST['target_user_id'];

            // Validasi: Hanya Admin/Owner yg boleh add
            if (!$this->canManageGroup($actor_id, $group_id)) {
                header("Location: index.php?page=group-details&group_id=$group_id&error=not_admin"); exit();
            }

            $group = $this->groupModel->getGroupById($group_id);

            // CLEAN CODE: Cukup panggil Model. Chat "Bergabung" otomatis terkirim.
            $success = $this->groupModel->joinGroup($target_user_id, $group_id, 'active', 'member');

            if ($success) {
                // Notifikasi Lonceng tetap perlu dibuat manual (beda dengan chat)
                
                // --- PERBAIKAN DISINI ---
                // Parameter ke-5 harus array
                $this->notificationModel->createNotification(
                    $target_user_id, 
                    $actor_id, 
                    'group_invite', 
                    "Anda telah ditambahkan ke grup " . $group['nama_group'], 
                    ['group_id' => $group_id] // <-- Dibungkus array
                );

                header("Location: index.php?page=group-details&group_id=$group_id&success=member_added");
            } else {
                header("Location: index.php?page=group-details&group_id=$group_id&error=failed_add");
            }
            exit();
        }
    }

    public function handleKickMember() {
        if (!isset($_GET['group_id']) || !isset($_GET['user_id'])) exit();
        
        $actor_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_GET['group_id'];
        $target_id = (int)$_GET['user_id'];
        $target_name = $_GET['name'] ?? 'Member';

        $actorRole = $this->groupModel->getUserRole($actor_id, $group_id);
        $targetRole = $this->groupModel->getUserRole($target_id, $group_id);
        
        $allowed = false;
        if ($actorRole === 'owner' && $target_id !== $actor_id) $allowed = true;
        if ($actorRole === 'admin' && $targetRole === 'member') $allowed = true;

        if (!$allowed) {
            header("Location: index.php?page=group-details&group_id=$group_id&error=unauthorized_kick"); exit();
        }

        // Pesan KICK tetap manual
        $this->messageModel->createSystemMessage($group_id, $target_id, 'leave', "$target_name dikeluarkan oleh Pengurus.");
        $this->groupModel->kickMember($group_id, $target_id);
        
        header("Location: index.php?page=group-details&group_id=$group_id&success=kicked");
        exit();
    }

    public function handleRoleChange() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $actor_id = (int)$_SESSION['user_id'];
            $group_id = (int)$_POST['group_id'];
            $target_id = (int)$_POST['target_user_id'];
            $action = $_POST['action']; 

            // Hanya Owner yang boleh ganti role
            $actorRole = $this->groupModel->getUserRole($actor_id, $group_id);
            if ($actorRole !== 'owner') {
                header("Location: index.php?page=group-details&group_id=$group_id&error=unauthorized"); exit();
            }

            $new_role = ($action === 'promote') ? 'admin' : 'member';
            
            if ($this->groupModel->updateMemberRole($group_id, $target_id, $new_role)) {
                $msg = ($new_role === 'admin') ? "Anda sekarang adalah Admin grup." : "Anda sekarang adalah anggota biasa.";
                $this->messageModel->createSystemMessage($group_id, $target_id, 'join', "Status member diperbarui oleh Owner.");
                
                // Notif tipe 'info'
                $this->notificationModel->createNotification($target_id, $actor_id, 'info', $msg, null, $group_id);
                
                header("Location: index.php?page=group-details&group_id=$group_id&success=role_updated");
            } else {
                header("Location: index.php?page=group-details&group_id=$group_id&error=failed");
            }
            exit();
        }
    }

    // --- UTILS & SEARCH ---
    
    public function showAddMemberForm() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) exit();
        $group_id = (int)$_GET['group_id'];
        
        if (!$this->canManageGroup($_SESSION['user_id'], $group_id)) exit();

        $search = $_GET['q'] ?? '';
        $available_users = $this->groupModel->getUsersAvailableForGroup($group_id, $search);
        require __DIR__ . '/../views/add_member.php';
    }

    public function searchCandidatesAPI() {
        $group_id = (int)$_GET['group_id'];
        $keyword = $_GET['q'] ?? '';
        $candidates = $this->groupModel->getUsersAvailableForGroup($group_id, $keyword);
        
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($candidates);
        exit;
    }

    // --- EDIT & DELETE GROUP ---

    public function showEditForm() {
        $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
        $user_id = (int)$_SESSION['user_id'];

        if ($this->canManageGroup($user_id, $group_id)) {
            $group_info = $this->groupModel->getGroupById($group_id);
            require __DIR__ . '/../views/edit_group.php';
        } else {
            header('Location: index.php?page=dashboard&error=unauthorized'); 
            exit();
        }
    }

    public function handleUpdateGroup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $user_id = (int)$_SESSION['user_id'];
            $group_id = (int)$_POST['group_id'];
            
            if (!$this->canManageGroup($user_id, $group_id)) {
                header('Location: index.php?page=dashboard&error=unauthorized'); exit();
            }

            $nama_group = trim($_POST['nama_group']);
            $deskripsi = trim($_POST['deskripsi']);
            $is_private = isset($_POST['is_private']) ? (int)$_POST['is_private'] : 0;
            $group_image_file = $_FILES['group_image']; 
            
            $image_name_to_db = null; 
            if (isset($group_image_file) && $group_image_file['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../../public/uploads/group_profiles/';
                $ext = strtolower(pathinfo($group_image_file['name'], PATHINFO_EXTENSION));
                $image_name_to_db = 'group_' . uniqid() . '.' . $ext;
                move_uploaded_file($group_image_file['tmp_name'], $upload_dir . $image_name_to_db);
            }
            
            $this->groupModel->updateGroup($group_id, $nama_group, $deskripsi, $image_name_to_db, $is_private);
            header('Location: index.php?page=group-details&group_id=' . $group_id . '&success=updated');
            exit();
        }
    }

    public function handleDeleteGroup() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // 1. Cek Login
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        // 2. Ambil ID Grup (Prioritaskan dari POST karena pakai Form)
        $group_id = 0;
        if (isset($_POST['group_id'])) {
            $group_id = (int)$_POST['group_id'];
        } elseif (isset($_GET['id'])) {
            $group_id = (int)$_GET['id'];
        }

        // Validasi ID
        if ($group_id <= 0) {
            echo "<h3>Error: ID Grup Invalid</h3>";
            echo "<p>Pastikan form mengirim name='group_id'.</p>";
            exit;
        }

        $user_id = (int)$_SESSION['user_id'];

        // 3. EKSEKUSI HAPUS (Kirim 2 Parameter: ID Grup & ID User)
        // --- INI YANG BIKIN ERROR TADI (Dulu cuma $group_id) ---
        $success = $this->groupModel->deleteGroup($group_id, $user_id); 
        // -------------------------------------------------------

        if ($success) {
            // Sukses Hapus -> Redirect ke Halaman Pesan
            header('Location: index.php?page=messages&success=group_deleted');
        } else {
            // Gagal Hapus -> Kembalikan dan kasih pesan
            echo "<script>
                    alert('Gagal menghapus! Pastikan Anda adalah Owner grup ini.'); 
                    window.location.href='index.php?page=edit-group&group_id=$group_id';
                  </script>";
        }
        exit;
    }

    // --- REQUEST & INVITE ---

    public function handleGroupRequest() {
        $group_id = (int)$_GET['group_id'];
        $target_id = (int)$_GET['user_id'];
        $action = $_GET['action']; 
        
        if (!$this->canManageGroup($_SESSION['user_id'], $group_id)) exit();

        $success = $this->groupModel->processJoinRequest($target_id, $group_id, $action);
        if ($success && $action === 'approve') {
             $this->messageModel->createSystemMessage($group_id, $target_id, 'join', "Permintaan bergabung disetujui.");
        }
        header("Location: index.php?page=group-details&group_id=$group_id&view=members&success=processed");
        exit();
    }

    public function handleSendInvite() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $admin_id = $_SESSION['user_id'];
            $group_id = (int)$_POST['group_id'];
            $target_user_id = (int)$_POST['target_user_id'];
            
            // Cek hak akses admin/owner
            if (!$this->canManageGroup($admin_id, $group_id)) exit();

            $group = $this->groupModel->getGroupById($group_id);
            
            // Eksekusi invite di database
            $success = $this->groupModel->inviteUserToGroup($target_user_id, $group_id);

            if ($success) {
                $msg = "Mengundang Anda bergabung ke grup: " . $group['nama_group'];
                
                // --- PERBAIKAN DISINI ---
                // Parameter ke-5 harus berupa ARRAY yang berisi referensi ID (group_id)
                $this->notificationModel->createNotification(
                    $target_user_id, 
                    $admin_id, 
                    'group_invite', 
                    $msg, 
                    ['group_id' => $group_id] // <-- Dibungkus array
                );
                
                header("Location: index.php?page=group-details&group_id=$group_id&view=members&success=invited");
            } else {
                header("Location: index.php?page=group-details&group_id=$group_id&view=members&error=already_invited_or_member");
            }
            exit();
        }
    }

    public function showGroupDetails() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
            header('Location: index.php?page=login'); exit();
        }
        $user_id = (int)$_SESSION['user_id'];
        $group_id = (int)$_GET['group_id'];
        $role_id = (int)($_SESSION['role_id'] ?? 0); // Ambil Role User

        // 1. Ambil Info Dasar Grup (Asli dari DB)
        $group_info = $this->groupModel->getGroupById($group_id);
        if (!$group_info) {
            header('Location: index.php?page=search&tab=group&error=not_found'); exit();
        }
        
        // --- LOGIKA BARU: PAKSA PRIVAT UNTUK EKSTERNAL ---
        // Jika Alumni (3) atau Mitra (4), kita manipulasi data group_info
        // seolah-olah grup ini adalah PRIVATE, apapun status aslinya.
        $is_external_user = in_array($role_id, [3, 4]);
        
        if ($is_external_user) {
            $group_info['is_private'] = 1; // Override status jadi Privat
        }
        // --------------------------------------------------

        // 2. Cek Status Member User Ini
        $is_member = $this->groupModel->isGroupMember($user_id, $group_id);
        
        // 3. Cek Apakah Super Admin (Role ID 5)
        $is_global_admin = ($role_id == 5);

        // 4. LOGIKA KUNCI (LOCK)
        // Sekarang logika ini akan otomatis mengunci grup publik bagi Alumni
        // karena $group_info['is_private'] sudah kita ubah jadi 1 di atas.
        $is_locked = ($group_info['is_private'] == 1 && !$is_member && !$is_global_admin);

        // 5. Ambil Konten Sensitif HANYA Jika TIDAK Terkunci
        if (!$is_locked) {
            $group_members = $this->groupModel->getGroupMembers($group_id);
            $group_media = $this->messageModel->getMediaByGroupId($group_id);
            $group_documents = $this->messageModel->getDocumentsByGroupId($group_id);
        } else {
            $group_members = [];
            $group_media = [];
            $group_documents = [];
        }

        // 6. Data Management
        $can_manage = $this->canManageGroup($user_id, $group_id);
        $pending_members = [];
        
        // PERBAIKAN: Ambil pending members untuk SEMUA grup (publik/privat)
        // Karena user eksternal (Alumni/Mitra) bisa request join ke grup publik
        // yang bagi mereka diperlakukan sebagai private
        if ($can_manage) {
            $pending_members = $this->groupModel->getPendingMembers($group_id);
        }
        
        $myRole = $this->groupModel->getUserRole($user_id, $group_id);
        $is_owner = ($myRole === 'owner');

        $view = $_GET['view'] ?? 'diskusi';
        
        require __DIR__ . '/../views/group_details.php';
    }
}
?>