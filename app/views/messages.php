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

<main class="col-span-4 border-r border-gray-200 flex flex-col h-screen">
    <div class="p-4 border-b border-gray-200">
        <h2 class="text-xl font-bold mb-4">Grup Diskusi Anda</h2>
    </div>

    <div class="flex-1 overflow-y-auto custom-scrollbar">
        <?php if (isset($groups) && !empty($groups)): ?>
        <?php foreach ($groups as $grp): ?>
        <?php 
                    $grp_id = $grp['group_id'];
                    $is_active = ($current_group_id === $grp_id);
                    $grp_img = !empty($grp['group_image']) ? '/Sinergi/public/uploads/group_profiles/' . $grp['group_image'] : '/Sinergi/public/assets/images/user.png';
                ?>
        <a href="index.php?page=messages&group_id=<?= $grp_id ?>&tab=<?= $active_tab ?>"
            class="flex items-center p-4 border-b border-gray-200 hover:bg-gray-50 <?= $is_active ? 'bg-blue-50/50' : '' ?>">
            <img src="<?= $grp_img ?>" class="w-12 h-12 rounded-full mr-3 object-cover border border-gray-200">
            <div class="flex-1 overflow-hidden">
                <p class="font-bold truncate text-gray-800"><?= htmlspecialchars($grp['nama_group']) ?></p>
                <p class="text-xs text-gray-500 truncate">Klik untuk membuka</p>
            </div>
        </a>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="p-8 text-center text-gray-500">Belum bergabung dengan grup.</div>
        <?php endif; ?>
    </div>

    <div class="p-4 border-t border-gray-200">
        <a href="index.php?page=create-group"
            class="block w-full bg-black text-white text-center py-2 rounded-full font-bold">Buat Grup Baru</a>
    </div>
</main>


<aside class="col-span-6 flex flex-col h-screen relative bg-white">

    <?php if ($current_group_id && isset($groupInfo)): ?>

    <div class="border-b border-gray-200 bg-white z-20 flex justify-between items-center px-4 h-16 shrink-0">

        <a href="index.php?page=group-details&group_id=<?= $current_group_id ?>"
            class="flex items-center hover:opacity-80 min-w-0 mr-4">
            <?php $header_img = !empty($groupInfo['group_image']) ? '/Sinergi/public/uploads/group_profiles/' . $groupInfo['group_image'] : '/Sinergi/public/assets/images/user.png'; ?>
            <img src="<?= $header_img ?>" class="w-9 h-9 rounded-full mr-3 object-cover border">
            <div class="truncate">
                <h2 class="font-bold text-sm leading-tight truncate"><?= htmlspecialchars($groupInfo['nama_group']) ?>
                </h2>
                <p class="text-[10px] text-gray-500 truncate">Ketuk info</p>
            </div>
        </a>

        <div class="flex bg-gray-100 p-1 rounded-lg shrink-0">
            <a href="index.php?page=messages&group_id=<?= $current_group_id ?>&tab=chat"
                class="px-4 py-1.5 text-xs font-bold rounded-md transition <?= ($active_tab === 'chat') ? 'bg-white text-black shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                Chat
            </a>
            <a href="index.php?page=messages&group_id=<?= $current_group_id ?>&tab=forum"
                class="px-4 py-1.5 text-xs font-bold rounded-md transition <?= ($active_tab === 'forum') ? 'bg-white text-black shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
                Forum
            </a>
        </div>
    </div>

    <div class="flex-1 overflow-hidden relative">
        <?php 
            if ($active_tab === 'chat') {
                // Load Partial Chat
                require 'partials/tab_chat.php'; 
            } else {
                // Load Partial Forum (Bubble Style)
                // Kita perlu data postingan dulu dari Controller/Model
                // (Cara cepat: Panggil Helper Controller di sini)
                require_once __DIR__ . '/../controllers/ForumController.php';
                // $forum_posts = getForumTabData($current_group_id);
                
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
<script src="/Sinergi/public/assets/js/chat_app.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const box = document.getElementById('chat-box');
    if (box) box.scrollTop = box.scrollHeight;
});
</script>
<?php endif; ?>

<script src="/Sinergi/public/assets/js/sidebar_updater.js"></script>