<main class="col-span-6 border-r border-gray-200 p-6 h-screen overflow-y-auto custom-scrollbar">

    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Notifikasi</h1>
        </div>
    </div>

    <div class="grid grid-cols-2 w-full mb-6">

        <button onclick="switchTab('unread')" id="btn-unread"
            class="w-full py-3 text-sm font-bold text-black border-b-2 border-black bg-gray-50 transition-colors focus:outline-none cursor-pointer flex items-center justify-center gap-2">
            Belum Dibaca
            <?php if(count($unread_list) > 0): ?>
            <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                <?= count($unread_list) ?>
            </span>
            <?php endif; ?>
        </button>

        <button onclick="switchTab('read')" id="btn-read"
            class="w-full py-3 text-sm font-medium text-gray-500 border-b border-gray-200 bg-white hover:bg-gray-50 hover:text-gray-700 transition-colors focus:outline-none cursor-pointer">
            Sudah Dibaca
        </button>
    </div>

    <div id="content-unread" class="block">
        <?php if (empty($unread_list)): ?>
        <div class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200 mt-2">
            <div class="text-4xl mb-2">📭</div>
            <p class="text-gray-500 font-medium">Tidak ada notifikasi baru.</p>
            <p class="text-gray-400 text-xs mt-1">Istirahatlah sejenak!</p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($unread_list as $notif): renderNotifCard($notif, true); endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div id="content-read" class="hidden">
        <div class="mb-4 p-3 bg-blue-50 text-blue-700 text-xs flex items-center gap-2 rounded border border-blue-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Notifikasi akan hilang otomatis <strong>7 hari</strong> setelah dibaca.</span>
        </div>

        <?php if (empty($read_list)): ?>
        <div class="text-center py-12 mt-2">
            <p class="text-gray-400 italic">Belum ada riwayat notifikasi.</p>
        </div>
        <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($read_list as $notif): renderNotifCard($notif, false); endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</main>

<?php require 'app/views/partials/sidebar_kanan.php'; ?>

<script>
function switchTab(tabName) {
    // 1. Sembunyikan semua konten
    document.getElementById('content-unread').classList.add('hidden');
    document.getElementById('content-read').classList.add('hidden');

    // 2. Definisi Class (Gaya Tampilan)
    // Style Aktif: Border bawah hitam tebal, teks hitam tebal, background agak abu (biar mirip referensi)
    const activeClass =
        "w-full py-3 text-sm font-bold text-black border-b-2 border-black bg-gray-50 transition-colors focus:outline-none cursor-pointer flex items-center justify-center gap-2";

    // Style Tidak Aktif: Border bawah tipis abu, teks abu, background putih
    const inactiveClass =
        "w-full py-3 text-sm font-medium text-gray-500 border-b border-gray-200 bg-white hover:bg-gray-50 hover:text-gray-700 transition-colors focus:outline-none cursor-pointer flex items-center justify-center gap-2";

    // 3. Reset kedua tombol ke style 'inactive' dulu
    document.getElementById('btn-unread').className = inactiveClass;
    document.getElementById('btn-read').className = inactiveClass;

    // 4. Aktifkan tombol yang diklik & Tampilkan kontennya
    document.getElementById('content-' + tabName).classList.remove('hidden');
    document.getElementById('btn-' + tabName).className = activeClass;
}

// LOGIC: Cek URL apakah harus buka tab 'Sudah Dibaca' otomatis (setelah klik notif)
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const openTab = urlParams.get('open_tab');

    if (openTab === 'read') {
        switchTab('read');
    }
});
</script>

<?php
/**
 * Helper Render Kartu (Versi Update: Full Clickable Card)
 */
function renderNotifCard($notif, $is_unread) {
    $current_page = 'index.php?page=notification';
    
    // Link Logika (Mark Read -> Redirect)
    $final_link = $is_unread 
        ? "index.php?page=read-notif&id={$notif['notif_id']}&redirect=" . urlencode($current_page)
        : "#";

    // Style (Tambahkan cursor-pointer)
    $bg_class = $is_unread ? 'bg-blue-50 border-blue-200 shadow-sm' : 'bg-white border-gray-100 opacity-75';
    
    // Ikon
    $icon = '🔔';
    if($notif['type']=='like') $icon='❤️';
    if($notif['type']=='comment') $icon='💬';
    if($notif['type']=='group_invite') $icon='📢';
    
    ?>
<div onclick="window.location.href='<?= $final_link ?>'"
    class="flex gap-4 p-4 rounded-lg border hover:shadow-md transition cursor-pointer relative group <?= $bg_class ?>">

    <div class="text-2xl"><?= $icon ?></div>

    <div class="flex-1">
        <p class="text-gray-800 text-sm">
            <span class="font-bold hover:underline"><?= htmlspecialchars($notif['actor_name']) ?></span>
            <span class="text-gray-700"><?= htmlspecialchars($notif['message']) ?></span>
        </p>
        <p class="text-xs text-gray-400 mt-1"><?= $notif['minutes_ago'] ?> menit yang lalu</p>

        <?php if ($is_unread && $notif['type'] === 'group_invite'): ?>
        <div class="mt-2 flex gap-2 relative z-10">
            <a href="index.php?page=accept-invite&notif_id=<?= $notif['notif_id'] ?>" onclick="event.stopPropagation()"
                class="bg-gray-900 text-white text-xs px-3 py-1 rounded hover:bg-gray-700 transition">
                Terima
            </a>
            <a href="index.php?page=reject-invite&notif_id=<?= $notif['notif_id'] ?>" onclick="event.stopPropagation()"
                class="bg-white border border-gray-300 text-gray-700 text-xs px-3 py-1 rounded hover:bg-red-50 hover:text-red-600 transition">
                Tolak
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
}
?>