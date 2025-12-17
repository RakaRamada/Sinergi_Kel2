<?php
// File: app/views/messages.php
// FILE UTAMA (Parent View)

// 1. Ambil Parameter
$current_group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
$active_tab = $_GET['tab'] ?? 'chat'; // Default ke 'chat'

// Helper Tanggal (Biarkan di sini agar bisa dipakai Chat)
if (!function_exists('formatTanggalChat')) {
    function formatTanggalChat($tanggal_iso) {
        if (!$tanggal_iso) return null;
        try {
            $date = new DateTime($tanggal_iso);
            $today = new DateTime();
            $diff = $today->diff($date);
            if ($diff->d == 0) return 'Hari ini';
            if ($diff->d == 1) return 'Kemarin';
            return $date->format('d/m/Y');
        } catch (Exception $e) { return null; }
    }
}
?>

<main
    class="col-span-1 lg:col-span-4 border-r border-gray-200 flex flex-col h-[calc(100vh-64px)] lg:h-screen bg-white <?= $current_group_id ? 'hidden lg:flex' : 'flex' ?>">
    <div class="p-4 border-b border-gray-200 sticky top-0 bg-white z-10">
        <h2 class="text-xl font-bold">Grup Diskusi</h2>
    </div>

    <div class="flex-1 overflow-y-auto custom-scrollbar pb-20 lg:pb-0">
        <?php if (isset($groups) && !empty($groups)): ?>
        <?php foreach ($groups as $grp): ?>
        <?php 
                    $grp_id = $grp['group_id'];
                    $is_active = ($current_group_id === $grp_id);
                    // FIX PATH
                    $grp_img = $grp['group_image'] ?? '';
                    if (!empty($grp_img) && strpos($grp_img, '/') === false) {
                        $grp_img = '/Sinergi/public/uploads/group_profiles/' . $grp_img;
                    }
                    if (empty($grp_img)) $grp_img = '/Sinergi/public/assets/images/user.png';
                ?>
        <a href="index.php?page=messages&group_id=<?= $grp_id ?>&tab=<?= $active_tab ?>"
            class="flex items-center p-4 border-b border-gray-200 hover:bg-gray-50 <?= $is_active ? 'bg-blue-50/50' : '' ?>">
            <img src="<?= $grp_img ?>" class="w-12 h-12 rounded-full mr-3 object-cover border border-gray-200 shrink-0">
            <div class="flex-1 overflow-hidden">
                <p class="font-bold truncate text-gray-800"><?= htmlspecialchars($grp['nama_group']) ?></p>
                <p class="text-xs text-gray-500 truncate">Ketuk untuk membuka</p>
            </div>
        </a>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="p-8 text-center text-gray-500">Belum bergabung dengan grup.</div>
        <?php endif; ?>
    </div>

    <?php 
    $current_role_id = $_SESSION['role_id'] ?? 0;
    // Cek: Jika BUKAN Alumni (3) dan BUKAN Mitra (4), baru tampilkan tombol (Mahasiswa/Dosen/Umum)
    // Asumsi Role 1,2,5 boleh buat grup
    if (!in_array($current_role_id, [3, 4])): 
    ?>
    <div class="p-4 border-t border-gray-200 sticky bottom-0 bg-white hidden lg:block">
        <a href="index.php?page=create-group"
            class="block w-full bg-black text-white text-center py-2 rounded-full font-bold hover:bg-gray-800 transition">
            Buat Grup Baru
        </a>
    </div>
    <!-- Mobile Floating Action Button for Create Group -->
    <a href="index.php?page=create-group"
        class="fixed bottom-24 right-4 bg-black text-white p-4 rounded-full shadow-lg lg:hidden z-30">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
    </a>
    <?php endif; ?>
    <!-- Spacer for Mobile Bottom Nav -->
    <div class="h-20 lg:hidden"></div>
</main>


<aside
    class="col-span-1 lg:col-span-6 flex flex-col h-[calc(100vh-64px)] lg:h-screen relative bg-white <?= $current_group_id ? 'flex' : 'hidden lg:flex' ?>">

    <?php if ($current_group_id && isset($groupInfo)): ?>

    <div
        class="border-b border-gray-200 bg-white z-20 flex justify-between items-center px-4 h-16 shrink-0 sticky top-0">

        <div class="flex items-center min-w-0 mr-2">
            <!-- Mobile Back Button -->
            <a href="index.php?page=messages" class="mr-2 lg:hidden p-1 rounded-full hover:bg-gray-100">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>

            <a href="index.php?page=group-details&group_id=<?= $current_group_id ?>"
                class="flex items-center hover:opacity-80 min-w-0">
                <?php 
                    $header_img = $groupInfo['group_image'] ?? '';
                    if (!empty($header_img) && strpos($header_img, '/') === false) {
                        $header_img = '/Sinergi/public/uploads/group_profiles/' . $header_img;
                    }
                    if (empty($header_img)) $header_img = '/Sinergi/public/assets/images/user.png';
                ?>
                <img src="<?= $header_img ?>" class="w-9 h-9 rounded-full mr-3 object-cover border shrink-0">
                <div class="truncate">
                    <h2 class="font-bold text-sm leading-tight truncate max-w-[120px] sm:max-w-xs">
                        <?= htmlspecialchars($groupInfo['nama_group']) ?>
                    </h2>
                    <p class="text-[10px] text-gray-500 truncate">Ketuk info</p>
                </div>
            </a>
        </div>

        <div class="flex bg-gray-100 p-1 rounded-lg shrink-0">
            <a href="index.php?page=messages&group_id=<?= $current_group_id ?>&tab=chat"
                class="px-3 py-1.5 text-xs font-bold rounded-md transition <?= ($active_tab === 'chat') ? 'bg-white text-black shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                Chat
            </a>
            <a href="index.php?page=messages&group_id=<?= $current_group_id ?>&tab=forum"
                class="px-3 py-1.5 text-xs font-bold rounded-md transition <?= ($active_tab === 'forum') ? 'bg-white text-black shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                Forum
            </a>
        </div>
    </div>

    <div class="flex-1 overflow-hidden relative lg:pb-0">
        <?php 
            if ($active_tab === 'chat') {
                require 'partials/tab_chat.php'; 
            } else {
                require_once __DIR__ . '/../controllers/ForumController.php';
                require 'partials/tab_forum.php';
            }
            ?>
    </div>

    <?php else: ?>
    <div class="flex flex-col items-center justify-center h-full text-gray-400">
        <img src="/Sinergi/public/assets/images/user.png" class="w-24 h-24 opacity-20 grayscale mb-4">
        <p>Pilih grup untuk memulai.</p>
    </div>
    <?php endif; ?>
</aside>

<script>
// Variabel User Login
const CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

// Variabel ID Grup (Ambil dari PHP)
// Pastikan variabel ini ada isinya (Default 0 jika null)
const GROUP_ID = <?= (int)($current_group_id ?? 0) ?>;
const FORUM_ID = GROUP_ID; // Alias biar aman kalau ada script lama yg pake FORUM_ID
</script>

<?php if ($active_tab === 'chat'): ?>
<script src="/Sinergi/public/assets/js/chat_app.js?v=<?= time() ?>"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const box = document.getElementById('chat-box');
    if (box) box.scrollTop = box.scrollHeight;
});
</script>
<?php endif; ?>

<script src="/Sinergi/public/assets/js/sidebar_updater.js"></script>