<?php
// app/admin/views/detail_report.php
// UI OVERHAUL: Multi-Image Carousel + Modern Modal + Premium Cards

include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/bottom_nav.php';

// Data Hub
$reportId = $report['REPORT_ID'] ?? 0;
$postId = $report['POST_ID'] ?? 0;
$status = $report['STATUS'] ?? 'pending';
$reason = $report['REASON'] ?? '-';
$adminNotes = $report['ADMIN_NOTES'] ?? '-';

// Data Pelapor
$reporterName = $report['REPORTER_NAMA'] ?? 'Unknown';
$reporterUsername = $report['REPORTER_USERNAME'] ?? 'unknown';
$reporterAvatar = $report['REPORTER_AVATAR'] ?? '/Sinergi/public/assets/images/default_avatar.png';

// Data Postingan
$postKonten = $report['POST_KONTEN'] ?? 'Postingan telah dihapus';
$postImage = $report['POST_IMAGE'] ?? null;
$postCreatedAt = $report['POST_CREATED_AT'] ?? '-';

// Pemilik Post
$ownerName = $report['POST_OWNER_NAMA'] ?? 'Unknown';
$ownerUsername = $report['POST_OWNER_USERNAME'] ?? 'unknown';
$ownerAvatar = $report['POST_OWNER_AVATAR'] ?? '/sinergi/public/assets/images/default_avatar.png';
$ownerRole = $report['POST_OWNER_ROLE'] ?? 'User';
$ownerBanned = ($report['POST_OWNER_BANNED_STATUS'] == 1);
$ownerId = $report['POST_OWNER_ID'] ?? 0;

// Stats
$postLikes = $report['POST_LIKES'] ?? 0;
$postComments = $report['POST_COMMENTS'] ?? 0;

$statusColor = [
    'pending' => 'text-red-500 bg-red-50',
    'reviewed' => 'text-yellow-600 bg-yellow-50',
    'resolved' => 'text-green-600 bg-green-50',
    'rejected' => 'text-gray-600 bg-gray-50'
][$status] ?? 'text-gray-500 bg-gray-50';

?>

<main class="flex-1 p-4 sm:p-6 ml-0 lg:ml-20 bg-gray-50 min-h-screen pb-24 lg:pb-6 overflow-x-hidden">
    <div class="max-w-5xl mx-auto">

        <!-- Header Navigation -->
        <div class="mb-6 flex items-center justify-between">
            <a href="/sinergi/index.php?page=admin-dashboard"
                class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 font-medium transition-colors group">
                <div class="p-2 bg-white rounded-full border border-gray-200 group-hover:border-gray-400 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </div>
                Kembali ke Dashboard
            </a>
            
            <span class="text-xs font-mono text-gray-400">REPORT ID: #<?= $reportId ?></span>
        </div>

        <!-- Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT COLUMN: Post Content (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                
                <?php if ($postId): ?>
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden relative">
                    
                    <!-- Post Header -->
                    <div class="p-6 border-b border-gray-50 flex items-start justify-between bg-white">
                        <div class="flex items-center gap-4">
                            <img src="<?= htmlspecialchars($ownerAvatar) ?>" class="w-12 h-12 rounded-full border-2 border-white shadow-sm bg-gray-100 object-cover">
                            <div>
                                <div class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                    @<?= htmlspecialchars($ownerUsername) ?>
                                    <?php if ($ownerBanned): ?>
                                    <span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold tracking-wide border border-red-100">BANNED</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs font-medium text-gray-400 flex items-center gap-1">
                                    <?= htmlspecialchars($ownerRole) ?> • <?= htmlspecialchars($postCreatedAt) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Post Body -->
                    <div class="px-6 py-5">
                        <p class="text-gray-800 leading-relaxed whitespace-pre-wrap text-[16px]"><?= nl2br(htmlspecialchars($postKonten)) ?></p>
                    </div>

                    <!-- CAROUSEL IMPLEMENTATION -->
                    <?php 
                    if ($postImage) {
                        $images = array_filter(explode(',', $postImage), function($value) { return !is_null($value) && $value !== ''; });
                        $images = array_values($images); // Reset keys
                        $count = count($images);
                        
                        if ($count > 0) {
                            $carouselId = 'carousel-' . $postId;
                    ?>
                        <div class="bg-gray-50/50 border-t border-b border-gray-100 relative group select-none">
                            
                            <!-- Carousel Container -->
                            <div id="<?= $carouselId ?>" 
                                 class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide items-center custom-scrollbar"
                                 style="scrollbar-width: none;">
                                
                                <?php foreach($images as $idx => $img): ?>
                                <div class="snap-center shrink-0 w-full flex items-center justify-center bg-gray-50/30 min-h-[300px] max-h-[600px] p-2">
                                    <img src="<?= htmlspecialchars(trim($img)) ?>" 
                                         class="max-h-[500px] max-w-full object-contain rounded-lg shadow-sm cursor-zoom-in transition-transform hover:scale-[1.01]"
                                         onclick="openDetailImage('<?= htmlspecialchars(trim($img)) ?>')">
                                </div>
                                <?php endforeach; ?>

                            </div>

                            <!-- Nav Buttons (Only if > 1 image) -->
                            <?php if ($count > 1): ?>
                            <button onclick="scrollDetailCarousel('<?= $carouselId ?>', -1)" 
                                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/90 p-2.5 rounded-full shadow-lg hover:bg-white text-gray-800 transition-all opacity-0 group-hover:opacity-100 transform hover:scale-110 active:scale-95 cursor-pointer z-10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button onclick="scrollDetailCarousel('<?= $carouselId ?>', 1)" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/90 p-2.5 rounded-full shadow-lg hover:bg-white text-gray-800 transition-all opacity-0 group-hover:opacity-100 transform hover:scale-110 active:scale-95 cursor-pointer z-10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            
                            <!-- Dots Indicator -->
                            <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-1.5 z-10 pointer-events-none">
                                <?php for($i=0; $i<$count; $i++): ?>
                                <div class="w-2 h-2 rounded-full bg-white shadow-sm transition-all opacity-50 ring-1 ring-black/10" id="dot-<?= $carouselId ?>-<?= $i ?>"></div>
                                <?php endfor; ?>
                                <script>
                                    // Simple inline script to highlight first dot
                                    document.getElementById('dot-<?= $carouselId ?>-0').classList.remove('opacity-50');
                                    document.getElementById('dot-<?= $carouselId ?>-0').classList.add('opacity-100', 'scale-125');
                                </script>
                            </div>
                            <?php endif; ?>

                        </div>
                    <?php 
                        }
                    } 
                    ?>

                    <!-- Post Footer Stats -->
                    <div class="px-6 py-4 bg-gray-50/30 flex items-center gap-6 text-sm font-medium text-gray-500 border-t border-gray-100">
                        <div class="flex items-center gap-2" title="Likes">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" /></svg>
                            <span><?= $postLikes ?> Likes</span>
                        </div>
                        <div class="flex items-center gap-2" title="Komentar">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M4.804 21.644A6.707 6.707 0 006 21.75a6.721 6.721 0 003.583-1.029c.774.182 1.584.279 2.417.279 5.322 0 9.75-3.97 9.75-9 0-5.03-4.428-9-9.75-9s-9.75 3.97-9.75 9c0 2.409 1.025 4.562 2.632 6.19l-2.484 1.87a.75.75 0 00.395 1.583z" clip-rule="evenodd" /></svg>
                            <span><?= $postComments ?> Komentar</span>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-8 mb-6 text-center shadow-sm">
                    <div class="w-16 h-16 bg-yellow-100 text-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Konten Tidak Tersedia</h3>
                    <p class="text-gray-600">Postingan ini mungkin telah dihapus oleh pengguna atau admin.</p>
                </div>
                <?php endif; ?>

            </div>

            <!-- RIGHT COLUMN: Report Info & Actions (1/3) -->
            <div class="space-y-6">
                
                <!-- Report Info Card -->
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gray-50 rounded-bl-full -mr-4 -mt-4 opacity-50 z-0 pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">Informasi Laporan</h2>
                        
                        <div class="flex items-center gap-3 mb-6 p-3 bg-gray-50/50 rounded-xl border border-gray-100">
                            <img src="<?= htmlspecialchars($reporterAvatar) ?>" class="w-10 h-10 rounded-full border border-white shadow-sm">
                            <div>
                                <div class="text-xs text-gray-400 font-medium uppercase">Pelapor</div>
                                <div class="font-bold text-gray-900 text-sm">@<?= htmlspecialchars($reporterUsername) ?></div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div class="text-xs text-gray-400 font-medium uppercase mb-1">Status</div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide inline-flex items-center gap-1.5 <?= $statusColor ?>">
                                    <?php if($status == 'resolved'): ?> ✓ <?php endif; ?>
                                    <?= $status ?>
                                </span>
                            </div>
                            
                            <div>
                                <div class="text-xs text-gray-400 font-medium uppercase mb-1">Alasan Pelaporan</div>
                                <div class="font-medium text-gray-800 text-sm bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                                    <?= htmlspecialchars($reason) ?>
                                </div>
                            </div>

                            <?php if ($adminNotes && $adminNotes !== '-'): ?>
                            <div class="pt-4 border-t border-gray-50">
                                <div class="text-xs text-blue-500 font-bold uppercase mb-1">Catatan Admin</div>
                                <p class="text-sm text-gray-600 italic bg-blue-50/30 p-3 rounded-lg border border-blue-50">"<?= htmlspecialchars($adminNotes) ?>"</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ADMIN ACTIONS CARD -->
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
                    <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">Tindakan</h2>
                    
                    <?php if ($status === 'pending'): ?>
                    <div class="space-y-3">
                        
                        <!-- Main Actions -->
                        <button onclick="markResolved(<?= $reportId ?>)"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-black text-white hover:bg-gray-800 rounded-xl font-bold text-sm transition-all shadow-md active:scale-95 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Tandai Selesai
                        </button>

                        <button onclick="markRejected(<?= $reportId ?>)"
                            class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium text-sm transition-all active:scale-95 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Tolak / Abaikan
                        </button>

                        <div class="border-t border-gray-100 my-4"></div>

                        <!-- Destructive Actions -->
                        <?php if ($postId): ?>
                        <button onclick="deletePost(<?= $reportId ?>)"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium transition-colors cursor-pointer group">
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center group-hover:bg-red-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            Hapus Postingan
                        </button>
                        <?php endif; ?>

                        <?php if ($ownerId && !$ownerBanned): ?>
                        <button onclick="banUser(<?= $reportId ?>, <?= $ownerId ?>, '<?= htmlspecialchars($ownerUsername) ?>')"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-orange-600 hover:bg-orange-50 rounded-lg text-sm font-medium transition-colors cursor-pointer group">
                             <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                             </div>
                            Banned User
                        </button>
                        <?php endif; ?>

                    </div>
                    <?php else: ?>
                    <div class="text-center py-6">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-gray-500 text-sm">Laporan telah diproses.</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</main>

<!-- Scripts -->
<script src="/sinergi/public/assets/js/admin-modal.js"></script>

<script>
// --- CAROUSEL LOGIC ---
function scrollDetailCarousel(carouselId, direction) {
    const container = document.getElementById(carouselId);
    if (!container) return;
    const scrollAmount = container.clientWidth;
    container.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
}

function openDetailImage(src) {
    const d = document.createElement('div');
    d.className = "fixed inset-0 bg-black/95 z-[9999] flex items-center justify-center p-4 backdrop-blur-md animate-fade-in";
    d.onclick = () => d.remove();
    d.innerHTML = `<img src="${src}" class="max-w-full max-h-screen object-contain rounded-lg shadow-2xl scale-100 transition-transform cursor-zoom-out">`;
    document.body.appendChild(d);
}

// --- ACTION LOGIC (Promise Based) ---

async function deletePost(reportId) {
    if (await showModernConfirm("Hapus Postingan?", "Postingan akan dihapus permanen.")) {
        sendAction("delete_post", { report_id: reportId });
    }
}

async function banUser(reportId, userId, username) {
    if (await showModernConfirm("Banned User?", `User @${username} tidak akan bisa login.`)) {
        sendAction("ban_user", { report_id: reportId, user_id: userId });
    }
}

async function markResolved(reportId) {
    if (await showModernConfirm("Tandai Selesai?", "Laporan akan ditandai sebagai selesai.")) {
        sendAction("mark_resolved", { report_id: reportId });
    }
}

async function markRejected(reportId) {
    if (await showModernConfirm("Tolak Laporan?", "Laporan akan ditandai sebagai ditolak/diabaikan.")) {
        sendAction("mark_rejected", { report_id: reportId });
    }
}

function sendAction(actionType, data) {
    const formData = new FormData();
    formData.append("action", actionType);
    for (const key in data) formData.append(key, data[key]);

    fetch("/sinergi/index.php?page=admin-api-process", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.status === "success" || resp.status === true) {
                showModernAlert("Berhasil", "Data berhasil diperbarui", "success");
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showModernAlert("Gagal", resp.message, "error");
            }
        })
        .catch(err => showModernAlert("Error", err.message, "error"));
}
</script>

<style>
/* Smooth Scroll Hide */
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.animate-fade-in { animation: fadeIn 0.2s ease-out forwards; }
</style>