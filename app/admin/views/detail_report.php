<?php
// app/admin/views/detail_report.php (FIXED - DINAMIS)
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';

// Data dari controller
$reportId = $report['REPORT_ID'] ?? 0;
$postId = $report['POST_ID'] ?? 0;
$status = $report['STATUS'] ?? 'pending';
$reason = $report['REASON'] ?? '-';
$adminNotes = $report['ADMIN_NOTES'] ?? '-';

// Data Pelapor
$reporterName = $report['REPORTER_NAMA'] ?? 'Unknown';
$reporterUsername = $report['REPORTER_USERNAME'] ?? 'unknown';
$reporterAvatar = $report['REPORTER_AVATAR'] ?? '/Sinergi/public/assets/images/default_avatar.png';

// Data Post
$postKonten = $report['POST_KONTEN'] ?? 'Postingan telah dihapus';
$postImage = $report['POST_IMAGE'] ?? null;
$postCreatedAt = $report['POST_CREATED_AT'] ?? '-';

// Data Pemilik Post
$ownerName = $report['POST_OWNER_NAMA'] ?? 'Unknown';
$ownerUsername = $report['POST_OWNER_USERNAME'] ?? 'unknown';
$ownerAvatar = $report['POST_OWNER_AVATAR'] ?? '/Sinergi/public/assets/images/default_avatar.png';
$ownerRole = $report['POST_OWNER_ROLE'] ?? 'User';
$ownerBanned = ($report['POST_OWNER_BANNED_STATUS'] == 1);
$ownerId = $report['POST_OWNER_ID'] ?? 0;

// Stats Post
$postLikes = $report['POST_LIKES'] ?? 0;
$postComments = $report['POST_COMMENTS'] ?? 0;

$statusColor = [
  'pending' => 'text-red-500',
  'reviewed' => 'text-yellow-500',
  'resolved' => 'text-green-500'
][$status] ?? 'text-gray-500';
?>

<main class="flex-1 p-6 ml-0 lg:ml-20">
    <div class="max-w-4xl mx-auto">
        <div class="mb-4">
            <<<<<<< HEAD <a href="/Sinergi/app/admin/views/dashboard.php"
                class="inline-flex items-center gap-2 text-sm text-gray-600 hover:underline">
                =======
                <a href="/Sinergi/index.php?page=admin-dashboard"
                    class="inline-flex items-center gap-2 text-sm text-gray-600 hover:underline">
                    >>>>>>> origin/raka
                    ← Kembali ke daftar laporan
                </a>
        </div>

        <<<<<<< HEAD <div class="bg-white border rounded-lg shadow-sm p-6 mb-6">
            <!-- post header -->
            <div class="flex items-start gap-4 mb-4">
                <img src="/Sinergi/public/assets/images/default_avatar.png" alt="avatar" class="w-12 h-12 rounded-full">
                <div>
                    <div class="font-semibold">@User1234345 <span class="text-xs text-gray-500">| Mahasiswa</span></div>
                    <div class="text-xs text-gray-500">2 jam yang lalu</div>
                </div>
            </div>

            <!-- post content -->
            <div class="mb-4 text-gray-700">
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore
                    et dolore magna aliqua...</p>
            </div>

            <!-- post image -->
            <div class="mb-4">
                <img src="/Sinergi/public/assets/images/default.jpg" alt="post" class="w-full rounded-lg border">
            </div>

            <!-- post stats -->
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div class="flex items-center gap-6">
                    <div>❤️ 1,3rb</div>
                    <div>💬 76</div>
                    <div>🔁 200</div>
                </div>
                <div class="text-xs text-gray-500">2 jam yang lalu</div>
            </div>
    </div>

    <!-- report info -->
    <div class="bg-white border rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold mb-3">Informasi Laporan</h2>
        <p><strong>Pelapor:</strong> @UserPelapor</p>
        <p><strong>Jenis Laporan:</strong> Konten Kekerasan</p>
        <p><strong>Deskripsi:</strong> Mengandung gambar yang tidak pantas.</p>
        <p><strong>Status:</strong> <span class="text-red-500 font-medium">Pending</span></p>

        <div class="mt-4 flex gap-3">
            <button class="px-4 py-2 bg-red-600 text-white rounded" id="btn-delete">Hapus Postingan</button>
            <button class="px-4 py-2 bg-yellow-500 text-black rounded" id="btn-mark-done">Tandai Selesai</button>
            <button class="px-4 py-2 border rounded" id="btn-block">Blokir User</button>
        </div>
        =======
        <?php if ($postId): ?>
        <!-- Post Content -->
        <div class="bg-white border rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start gap-4 mb-4">
                <img src="<?= htmlspecialchars($ownerAvatar) ?>" alt="avatar"
                    class="w-12 h-12 rounded-full object-cover border">
                <div>
                    <div class="font-semibold">
                        @<?= htmlspecialchars($ownerUsername) ?>
                        <span class="text-xs text-gray-500">| <?= htmlspecialchars($ownerRole) ?></span>
                        <?php if ($ownerBanned): ?>
                        <span class="ml-2 text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">🚫 BANNED</span>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($postCreatedAt) ?></div>
                </div>
            </div>

            <div class="mb-4 text-gray-700">
                <p><?= nl2br(htmlspecialchars($postKonten)) ?></p>
            </div>

            <?php if ($postImage): ?>
            <div class="mb-4">
                <img src="<?= htmlspecialchars($postImage) ?>" alt="post image"
                    class="w-full rounded-lg border max-h-96 object-contain">
            </div>
            <?php endif; ?>

            <div class="flex items-center justify-between text-sm text-gray-600 pt-3 border-t">
                <div class="flex items-center gap-6">
                    <div>❤️ <?= $postLikes ?></div>
                    <div>💬 <?= $postComments ?></div>
                </div>
                <div class="text-xs text-gray-500"><?= htmlspecialchars($postCreatedAt) ?></div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <p class="text-yellow-800">⚠️ Postingan telah dihapus dari sistem.</p>
        </div>
        <?php endif; ?>

        <!-- Report Info -->
        <div class="bg-white border rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                📋 Informasi Laporan
                <span class="text-xs <?= $statusColor ?> uppercase font-bold">(<?= htmlspecialchars($status) ?>)</span>
            </h2>

            <div class="space-y-3 mb-6">
                <div class="flex items-start gap-3">
                    <img src="<?= htmlspecialchars($reporterAvatar) ?>" alt="pelapor"
                        class="w-10 h-10 rounded-full border">
                    <div>
                        <p class="text-sm text-gray-600">Pelapor:</p>
                        <p class="font-semibold">@<?= htmlspecialchars($reporterUsername) ?>
                            (<?= htmlspecialchars($reporterName) ?>)</p>
                    </div>
                </div>

                <div>
                    <p class="text-sm text-gray-600">Jenis Laporan:</p>
                    <p class="font-medium"><?= htmlspecialchars($reason) ?></p>
                </div>

                <?php if ($adminNotes && $adminNotes !== '-'): ?>
                <div>
                    <p class="text-sm text-gray-600">Catatan Admin:</p>
                    <p class="font-medium text-blue-600"><?= htmlspecialchars($adminNotes) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <?php if ($status === 'pending'): ?>
            <div class="mt-6 flex flex-wrap gap-3">
                <?php if ($postId): ?>
                <button onclick="deletePost(<?= $reportId ?>)"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                    🗑️ Hapus Postingan
                </button>
                <?php endif; ?>

                <?php if ($ownerId && !$ownerBanned): ?>
                <button onclick="banUser(<?= $reportId ?>, <?= $ownerId ?>, '<?= htmlspecialchars($ownerUsername) ?>')"
                    class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition">
                    ⛔ Banned User
                </button>
                <?php endif; ?>

                <button onclick="markResolved(<?= $reportId ?>)"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                    ✓ Tandai Selesai
                </button>

                <button onclick="markRejected(<?= $reportId ?>)"
                    class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 transition">
                    ✗ Tolak Laporan
                </button>
            </div>
            <?php else: ?>
            <div class="bg-gray-50 p-3 rounded">
                <p class="text-sm text-gray-600">Laporan ini sudah diproses dengan status: <strong
                        class="<?= $statusColor ?>"><?= strtoupper($status) ?></strong></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.getElementById('btn-delete').addEventListener('click', function() {
    if (confirm('Hapus postingan ini (dummy)?')) {
        alert('Postingan dihapus (dummy). Hubungkan API untuk eksekusi nyata.');
    }
});
document.getElementById('btn-mark-done').addEventListener('click', function() {
    alert('Laporan ditandai selesai (dummy).');
});
document.getElementById('btn-block').addEventListener('click', function() {
    if (confirm('Blokir user ini (dummy)?')) {
        alert('User diblokir (dummy).');
    }
});
</script>

=======
function deletePost(reportId) {
if (!confirm('Yakin ingin menghapus postingan ini?')) return;
sendAction('delete_post', { report_id: reportId });
}

function banUser(reportId, userId, username) {
if (!confirm(`Yakin ingin BANNED user @${username}?`)) return;
sendAction('ban_user', { report_id: reportId, user_id: userId });
}

function markResolved(reportId) {
if (!confirm('Tandai laporan sebagai selesai?')) return;
sendAction('mark_resolved', { report_id: reportId });
}

function markRejected(reportId) {
if (!confirm('Tolak laporan ini?')) return;
sendAction('mark_rejected', { report_id: reportId });
}

function sendAction(actionType, data) {
const formData = new FormData();
formData.append('action', actionType);
for (const key in data) formData.append(key, data[key]);

fetch('/Sinergi/index.php?page=admin-api-process', { method: 'POST', body: formData })
.then(res => res.json())
.then(resp => {
if (resp.status === 'success' || resp.status === true) {
alert('✓ Berhasil diproses!');
window.location.reload();
} else {
alert('❌ Gagal: ' + resp.message);
}
})
.catch(err => alert('Error: ' + err.message));
}
</script>
>>>>>>> origin/raka