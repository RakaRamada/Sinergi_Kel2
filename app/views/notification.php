<?php
// File: app/views/notification.php

// --- 1. HELPER WAKTU ---
if (!function_exists('time_elapsed_string_notif')) {
    function time_elapsed_string_notif($datetime, $full = false) {
        try {
            // Sesuaikan timezone dengan server/db
            $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
            $ago = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
        } catch (Exception $e) { return $datetime; }

        $diff = $now->diff($ago);
        $weeks = floor($diff->d / 7);
        $days_left = $diff->d - ($weeks * 7);

        $string = array(
            'y' => 'tahun',
            'm' => 'bulan',
            'w' => 'minggu',
            'd' => 'hari',
            'h' => 'jam',
            'i' => 'menit',
            's' => 'detik',
        );
        $vals = ['y' => $diff->y, 'm' => $diff->m, 'w' => $weeks, 'd' => $days_left, 'h' => $diff->h, 'i' => $diff->i, 's' => $diff->s];

        foreach ($string as $k => &$v) {
            if ($vals[$k]) {
                $v = $vals[$k] . ' ' . $v;
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' yang lalu' : 'Baru saja';
    }
}
?>

<main class="col-span-1 lg:col-span-6 border-r border-gray-200 p-6 h-screen overflow-y-auto custom-scrollbar relative pb-20 lg:pb-0">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Notifikasi</h1>

        <button id="btn-clear-all" onclick="openClearModal()"
            class="hidden text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg transition flex items-center gap-1 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                </path>
            </svg>
            Bersihkan Semua
        </button>
    </div>

    <div class="grid grid-cols-2 w-full mb-6">
        <button onclick="switchTab('unread')" id="btn-tab-unread"
            class="w-full py-3 text-sm font-bold text-black border-b-2 border-black bg-gray-50 transition-colors focus:outline-none cursor-pointer flex items-center justify-center gap-2">
            Belum Dibaca
            <?php if(count($unread_list) > 0): ?>
            <span
                class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full"><?= count($unread_list) ?></span>
            <?php endif; ?>
        </button>
        <button onclick="switchTab('read')" id="btn-tab-read"
            class="w-full py-3 text-sm font-medium text-gray-500 border-b border-gray-200 bg-white hover:bg-gray-50 hover:text-gray-700 transition-colors focus:outline-none cursor-pointer">
            Sudah Dibaca
        </button>
    </div>

    <div id="content-unread" class="block space-y-3">
        <?php if (empty($unread_list)): ?>
        <div class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200 mt-2">
            <div class="text-4xl mb-2">📭</div>
            <p class="text-gray-500 font-medium">Tidak ada notifikasi baru.</p>
        </div>
        <?php else: ?>
        <?php foreach ($unread_list as $notif): renderNotifCard($notif, true); endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="content-read" class="hidden space-y-2">
        <?php if (empty($read_list)): ?>
        <div class="text-center py-12 mt-2">
            <p class="text-gray-400 italic">Belum ada riwayat notifikasi.</p>
        </div>
        <?php else: ?>
        <div class="mb-4 p-3 bg-gray-50 text-gray-700 text-xs flex items-center gap-2 rounded border border-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Notifikasi hilang otomatis dalam <strong>7 hari</strong>.</span>
        </div>
        <?php foreach ($read_list as $notif): renderNotifCard($notif, false); endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<?php require 'app/views/partials/sidebar_kanan.php'; ?>

<div id="clearModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeClearModal()"></div>
    <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <h3 class="text-lg leading-6 font-bold text-gray-900">Bersihkan Riwayat?</h3>
        <div class="mt-2">
            <p class="text-sm text-gray-500">Semua notifikasi yang <strong>sudah dibaca</strong> akan dihapus permanen.
            </p>
        </div>
        <div class="mt-6 flex justify-center gap-3">
            <button onclick="closeClearModal()"
                class="px-4 py-2 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition cursor-pointer">Batal</button>
            <button onclick="confirmClearAll()"
                class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition shadow-lg cursor-pointer">Ya,
                Hapus</button>
        </div>
    </div>
</div>

<!-- Modal Hapus Satu Notifikasi (Modern) -->
<div id="deleteOneModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeDeleteOneModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>
        <h3 class="text-lg leading-6 font-bold text-gray-900">Hapus Notifikasi?</h3>
        <div class="mt-2">
            <p class="text-sm text-gray-500">Notifikasi ini akan dihapus secara permanen.</p>
        </div>
        <div class="mt-6 flex justify-center gap-3">
            <button onclick="closeDeleteOneModal()"
                class="px-4 py-2 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition cursor-pointer">Batal</button>
            <button id="btnConfirmDeleteOne"
                class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition shadow-lg cursor-pointer">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
const hasReadNotif = <?= !empty($read_list) ? 'true' : 'false' ?>;
let deleteNotifId = null; // Simpan ID yang akan dihapus

function switchTab(tabName) {
    document.getElementById('content-unread').classList.add('hidden');
    document.getElementById('content-read').classList.add('hidden');

    const active =
        "w-full py-3 text-sm font-bold text-black border-b-2 border-black bg-gray-50 transition-colors focus:outline-none cursor-pointer flex items-center justify-center gap-2";
    const inactive =
        "w-full py-3 text-sm font-medium text-gray-500 border-b border-gray-200 bg-white hover:bg-gray-50 hover:text-gray-700 transition-colors focus:outline-none cursor-pointer flex items-center justify-center gap-2";

    document.getElementById('btn-tab-unread').className = inactive;
    document.getElementById('btn-tab-read').className = inactive;
    document.getElementById('content-' + tabName).classList.remove('hidden');
    document.getElementById('btn-tab-' + tabName).className = active;

    const btnClear = document.getElementById('btn-clear-all');
    if (tabName === 'read' && hasReadNotif) {
        btnClear.classList.remove('hidden');
        btnClear.classList.add('flex');
    } else {
        btnClear.classList.add('hidden');
        btnClear.classList.remove('flex');
    }
}

// Fungsi buka modal hapus satu notifikasi
function deleteOne(e, notifId) {
    e.stopPropagation(); // Mencegah klik card
    deleteNotifId = notifId; // Simpan ID
    document.getElementById('deleteOneModal').classList.remove('hidden');
}

// Fungsi tutup modal
function closeDeleteOneModal() {
    document.getElementById('deleteOneModal').classList.add('hidden');
    deleteNotifId = null;
}

// Fungsi konfirmasi hapus
function confirmDeleteOne() {
    if (!deleteNotifId) return;

    const fd = new FormData();
    fd.append('notif_id', deleteNotifId);

    fetch('index.php?page=api-delete-notif', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                const el = document.getElementById('notif-card-' + deleteNotifId);
                if (el) {
                    el.style.opacity = '0';
                    el.style.transform = 'translateX(20px)';
                    el.style.transition = 'all 0.3s ease';
                    setTimeout(() => el.remove(), 300);
                }
                closeDeleteOneModal();
            } else {
                closeDeleteOneModal();
                showAlert('Gagal', 'Gagal menghapus: ' + (d.message || 'Error server'));
            }
        })
        .catch(err => {
            console.error(err);
            closeDeleteOneModal();
            showAlert('Error', 'Terjadi kesalahan koneksi.');
        });
}

// Event listener untuk tombol konfirmasi
document.getElementById('btnConfirmDeleteOne').addEventListener('click', confirmDeleteOne);

function openClearModal() {
    document.getElementById('clearModal').classList.remove('hidden');
}

function closeClearModal() {
    document.getElementById('clearModal').classList.add('hidden');
}

function confirmClearAll() {
    fetch('index.php?page=api-clear-all-notif', {
            method: 'POST'
        })
        .then(r => r.json()).then(d => {
            if (d.status === 'success') location.reload();
            else showAlert('Gagal', 'Gagal membersihkan: ' + d.message);
        })
        .catch(err => showAlert('Error', 'Koneksi error'));
}

// Mini alert helper (opsional, sebagai pengganti alert() bawaan)
function showAlert(title, message) {
    // Fallback ke alert bawaan browser
    alert(title + ': ' + message);
}

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_tab') === 'read') switchTab('read');
});
</script>

<?php
function renderNotifCard($notif, $is_unread) {
    $target_url = ""; 
    $final_link = "#";

    // Prioritas 1: Invite (Tidak ada link klik, hanya tombol)
    if ($notif['type'] == 'group_invite') { 
        $target_url = ""; 
    } 
    // Prioritas 2: Postingan Forum (LEBIH SPESIFIK) -> Cek ini DULU sebelum cek Group ID
    elseif (!empty($notif['related_forum_post_id'])) {
        $target_url = "index.php?page=forum-post-detail&post_id=" . $notif['related_forum_post_id'];
    } 
    // Prioritas 3: Postingan Dashboard (Personal Feed)
    elseif (!empty($notif['related_post_id'])) {
        $target_url = "index.php?page=post-detail&id=" . $notif['related_post_id'];
    } 
    // Prioritas 4: Grup Umum (Hanya jika TIDAK ADA forum_post_id)
    elseif (!empty($notif['related_group_id'])) {
        $target_url = "index.php?page=group-details&group_id=" . $notif['related_group_id'];
    }

    // --- 2. LOGIKA GENERATE LINK FINAL ---
    if (!empty($target_url)) {
        // Kasus A: Punya Tujuan Jelas
        if ($is_unread) {
            // Baca dulu -> Redirect ke tujuan
            $final_link = "index.php?page=read-notif&id={$notif['notif_id']}&redirect=" . urlencode($target_url);
        } else {
            // Langsung ke tujuan
            $final_link = $target_url;
        }
    } else {
        // Kasus B: Tidak Punya Tujuan (Info Promote/Demote/Bug ID)
        if ($is_unread && $notif['type'] != 'group_invite') {
            // SOLUSI DARI KAMU:
            // Cukup tandai "Sudah Dibaca", lalu refresh halaman notifikasi ini lagi.
            $current_page = "index.php?page=notification";
            $final_link = "index.php?page=read-notif&id={$notif['notif_id']}&redirect=" . urlencode($current_page);
        }
    }

    $bg_class = $is_unread ? 'bg-gray-50 border-gray-200 shadow-sm' : 'bg-white border-gray-100 opacity-75';
    // Cursor pointer hanya muncul jika ada link yang bisa diklik
    $cursor_class = ($final_link !== '#') ? 'cursor-pointer hover:shadow-md' : 'cursor-default';
    
    // --- ICON ---
    $icon = '🔔';
    switch ($notif['type']) {
        case 'like': $icon = '❤️'; break;
        case 'comment': $icon = '💬'; break;
        case 'group_invite': $icon = '📩'; break;
        case 'info': $icon = 'ℹ️'; break; 
    }
    
    $waktu = time_elapsed_string_notif($notif['time_str']); 
    
    // Encode link untuk JS
    $js_link = json_encode($final_link);
    ?>

<div id="notif-card-<?= $notif['notif_id'] ?>" onclick='handleClick(<?= $js_link ?>)'
    class="flex gap-4 p-4 rounded-lg border transition relative group <?= $bg_class ?> <?= $cursor_class ?>">

    <div class="text-2xl shrink-0"><?= $icon ?></div>

    <div class="flex-1 min-w-0">
        <p class="text-gray-800 text-sm leading-snug pr-6">
            <span class="font-bold hover:underline"><?= htmlspecialchars($notif['actor_name']) ?></span>
            <span class="text-gray-700"><?= htmlspecialchars($notif['message']) ?></span>
        </p>
        <p class="text-xs text-gray-400 mt-1"><?= $waktu ?></p>

        <?php if ($is_unread && $notif['type'] === 'group_invite'): ?>
        <div class="mt-2 flex gap-2 relative z-10">
            <a href="index.php?page=accept-invite&notif_id=<?= $notif['notif_id'] ?>" onclick="event.stopPropagation()"
                class="bg-gray-900 text-white text-xs px-3 py-1 rounded hover:bg-gray-700 transition cursor-pointer">Terima</a>
            <a href="index.php?page=reject-invite&notif_id=<?= $notif['notif_id'] ?>" onclick="event.stopPropagation()"
                class="bg-white border border-gray-300 text-gray-700 text-xs px-3 py-1 rounded hover:bg-red-50 hover:text-red-600 transition cursor-pointer">Tolak</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$is_unread): ?>
    <button onclick="deleteOne(event, <?= $notif['notif_id'] ?>)"
        class="absolute top-2 right-2 p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-full transition opacity-0 group-hover:opacity-100 cursor-pointer"
        title="Hapus Notifikasi">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
            </path>
        </svg>
    </button>
    <?php endif; ?>
</div>
<?php } ?>

<script>
function handleClick(url) {
    if (url && url !== '#') {
        window.location.href = url;
    }
}
</script>