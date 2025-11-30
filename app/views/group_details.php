<?php
// File: app/views/group_details.php
// Data dikirim dari GroupController: $group_info, $group_members, $group_media, $group_documents
?>

<main class="col-span-6 border-r border-gray-200 relative h-screen overflow-y-auto custom-scrollbar">

    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/95 backdrop-blur-sm z-10">
        <a href="javascript:history.back()" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <h2 class="text-xl font-bold">Info Group</h2>
    </div>

    <div class="p-6 space-y-6 pb-6">

        <div class="flex flex-col items-center">
            <?php
                $image_path = '/Sinergi/public/assets/images/user.png';
                if (!empty($group_info['group_image'])) {
                    $image_path = '/Sinergi/public/uploads/group_profiles/' . htmlspecialchars($group_info['group_image']);
                }
            ?>
            <img src="<?= $image_path ?>" alt="Profil Group"
                class="w-32 h-32 rounded-full mb-4 object-cover border border-gray-200 shadow-sm">

            <div class="flex items-center space-x-2">
                <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($group_info['nama_group']) ?></h2>

                <?php if ($is_creator): ?>
                <a href="index.php?page=edit-group&group_id=<?= $group_info['group_id'] ?>" title="Edit Group"
                    class="text-gray-400 hover:text-gray-800 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </a>
                <?php endif; ?>
            </div>

            <?php 
    $tgl_dibuat = $group_info['created_at'] ?? '';
    // Validasi sederhana: tahun harus > 2000
    if (!empty($tgl_dibuat) && strtotime($tgl_dibuat) > strtotime('2000-01-01')): 
?>
            <p class="text-gray-500 text-sm mt-1">Dibuat pada <?= date('d M Y', strtotime($tgl_dibuat)) ?></p>
            <?php endif; ?>

        </div>


        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 w-full text-left">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 text-left">Deskripsi</h3>
            <?php 
                $deskripsi_raw = $group_info['deskripsi'] ?? null;
                // Ambil data CLOB atau String
                $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
                
                // Bersihkan spasi di awal/akhir
                $deskripsi_clean = trim($deskripsi_string);
                if (empty($deskripsi_clean)) {
                    $deskripsi_clean = 'Tidak ada deskripsi.';
                }
            ?>

            <div class="text-gray-700 text-sm leading-relaxed text-left block whitespace-normal break-words">
                <?= nl2br(htmlspecialchars($deskripsi_clean)) ?>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-gray-800 font-bold text-lg"><?= count($group_members) ?> Anggota</h3>

                <?php if ($is_creator): ?>
                <button onclick="openAddMemberModal()"
                    class="text-sm bg-gray-800 text-white px-4 py-2 rounded-full font-semibold hover:bg-gray-600 transition shadow flex items-center gap-2 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                        </path>
                    </svg>
                    Tambah
                </button>
                <?php endif; ?>
            </div>

            <div class="space-y-1">
                <?php if (!empty($group_members)): ?>
                <?php foreach ($group_members as $member): ?>
                <?php $is_member_admin = ($member['user_id'] == $group_info['created_by_user_id']); ?>

                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition group">
                    <div class="flex items-center space-x-3">
                        <img src="/Sinergi/public/assets/images/user.png"
                            class="w-10 h-10 rounded-full bg-gray-200 object-cover">
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-semibold text-gray-900 text-sm">
                                    <?= htmlspecialchars($member['nama_lengkap']) ?></p>
                                <?php if ($is_member_admin): ?>
                                <span
                                    class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded border border-green-200">Admin
                                    Group</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-500">@<?= htmlspecialchars($member['username']) ?></p>
                        </div>
                    </div>

                    <?php if ($is_creator && !$is_member_admin): ?>
                    <button type="button"
                        onclick="openKickModal(<?= $member['user_id'] ?>, '<?= htmlspecialchars($member['nama_lengkap']) ?>')"
                        class="text-gray-300 hover:text-red-500 p-2 transition opacity-0 group-hover:opacity-100 cursor-pointer"
                        title="Keluarkan">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6">
                            </path>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-gray-500 text-center py-4">Belum ada anggota.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-gray-100 pt-6">
            <h3 class="font-bold text-gray-800 mb-4">Media (<?= count($group_media) ?>)</h3>
            <?php if (!empty($group_media)): ?>
            <div class="grid grid-cols-3 md:grid-cols-4 gap-2">
                <?php foreach ($group_media as $media): ?>
                <?php $media_url = '/Sinergi/public/uploads/group_files/' . htmlspecialchars($media['file_path']); ?>
                <a href="<?= $media_url ?>" target="_blank"
                    class="aspect-square group relative overflow-hidden rounded-lg bg-gray-100">
                    <img src="<?= $media_url ?>" alt="Media"
                        class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition"></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400 italic">Belum ada media yang dibagikan.</p>
            <?php endif; ?>
        </div>

        <div class="border-t border-gray-100 pt-6">
            <h3 class="font-bold text-gray-800 mb-4">Dokumen (<?= count($group_documents) ?>)</h3>
            <?php if (!empty($group_documents)): ?>
            <div class="space-y-2">
                <?php foreach ($group_documents as $doc): ?>
                <?php $doc_url = '/Sinergi/public/uploads/group_files/' . htmlspecialchars($doc['file_path']); ?>
                <a href="<?= $doc_url ?>" download="<?= htmlspecialchars($doc['original_filename']) ?>"
                    class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-200 transition group">
                    <div class="bg-blue-100 p-2 rounded text-blue-600 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-700">
                            <?= htmlspecialchars($doc['original_filename']) ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            <?= htmlspecialchars($doc['sender_nama']) ?> •
                            <?= htmlspecialchars($doc['created_at_formatted']) ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-400 italic">Belum ada dokumen yang dibagikan.</p>
            <?php endif; ?>
        </div>

        <div class="border-t border-gray-100 pt-6 mt-6">
            <a href="index.php?page=exit-group&group_id=<?= $group_info['group_id'] ?>"
                onclick="return confirm('Yakin ingin keluar?');"
                class="flex items-center justify-center text-red-600 font-semibold bg-red-50 hover:bg-red-100 p-3 rounded-lg transition w-full">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
                Keluar dari Group
            </a>
        </div>
    </div>
</main>

<aside class="col-span-4">
    <?php require 'app/views/partials/sidebar_kanan.php'; ?>
</aside>


<div id="addMemberModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeAddMemberModal()"></div>

    <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col h-[600px]">

        <div class="bg-gray-800 text-white px-4 py-3 flex items-center space-x-3">
            <button onclick="closeAddMemberModal()"
                class="hover:bg-white/20 p-1 rounded-full transition cursor-pointer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </button>
            <h3 class="font-bold text-lg">Tambah Anggota</h3>
        </div>

        <div class="p-3 border-b border-gray-100">
            <div class="relative">
                <input type="text" id="searchCandidateInput" placeholder="Cari nama user..." autocomplete="off"
                    class="w-full pl-10 pr-4 py-2 bg-gray-100 border-none rounded-lg text-sm focus:ring-2 focus:ring-gray-800 focus:bg-white transition placeholder-gray-500">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-2" id="candidatesList">
            <div id="loadingSpinner" class="hidden flex justify-center py-8">
                <svg class="animate-spin h-8 w-8 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>

            <div id="resultsContainer" class="space-y-1"></div>

            <div id="emptyState" class="hidden text-center py-10 text-gray-500">
                <p>Tidak ditemukan kontak.</p>
            </div>
        </div>

        <div class="bg-gray-50 px-4 py-2 text-center border-t border-gray-100">
            <p class="text-xs text-gray-400">Klik pada user untuk menambahkan.</p>
        </div>
    </div>
</div>

<div id="confirmAddModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

    <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 text-center">

        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
            <svg class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </div>

        <h3 class="text-lg leading-6 font-bold text-gray-900" id="confirmTitle">Tambahkan Anggota?</h3>
        <div class="mt-2">
            <p class="text-sm text-gray-500" id="confirmMessage">
                Apakah Anda yakin ingin menambahkan user ini ke group?
            </p>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <button type="button" onclick="closeConfirmModal()"
                class="px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="btnConfirmYes"
                class="px-4 py-2 bg-gray-600 text-white text-base font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm transition shadow-lg shadow-gray-500/30 cursor-pointer">
                Ya, Tambahkan
            </button>
        </div>
    </div>
</div>

<div id="kickMemberModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

    <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm bg-white rounded-xl shadow-2xl p-6 text-center border-t-4 border-red-500">

        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h3 class="text-lg leading-6 font-bold text-gray-900">Keluarkan Anggota?</h3>
        <div class="mt-2">
            <p class="text-sm text-gray-500" id="kickMessageText">
                Anda yakin ingin mengeluarkan user ini?
            </p>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <button type="button" onclick="closeKickModal()"
                class="px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none sm:text-sm transition cursor-pointer">
                Batal
            </button>
            <a href="#" id="btnConfirmKick"
                class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-lg hover:bg-red-700 focus:outline-none shadow-lg shadow-red-500/30 sm:text-sm transition cursor-pointer">
                Ya, Keluarkan
            </a>
        </div>
    </div>
</div>

<form id="addMemberForm" action="index.php?page=process-add-member" method="POST" class="hidden">
    <input type="hidden" name="group_id" value="<?= $group_info['group_id'] ?>">
    <input type="hidden" name="target_user_id" id="form_target_id">
    <input type="hidden" name="target_user_name" id="form_target_name">
</form>

<script>
const modal = document.getElementById('addMemberModal');
const searchInput = document.getElementById('searchCandidateInput');
const resultsContainer = document.getElementById('resultsContainer');
const loadingSpinner = document.getElementById('loadingSpinner');
const emptyState = document.getElementById('emptyState');
const groupId = <?= (int)$group_info['group_id'] ?>;

function openAddMemberModal() {
    modal.classList.remove('hidden');
    searchInput.value = '';
    searchInput.focus();
    fetchCandidates('');
}

function closeAddMemberModal() {
    modal.classList.add('hidden');
}

let timeout = null;
searchInput.addEventListener('input', function() {
    clearTimeout(timeout);
    const query = this.value;
    resultsContainer.innerHTML = '';
    loadingSpinner.classList.remove('hidden');
    emptyState.classList.add('hidden');

    timeout = setTimeout(() => {
        fetchCandidates(query);
    }, 300);
});

function fetchCandidates(query) {
    fetch(`index.php?page=api-search-candidates&group_id=${groupId}&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            loadingSpinner.classList.add('hidden');
            resultsContainer.innerHTML = '';

            if (data.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            } else {
                emptyState.classList.add('hidden');
            }

            data.forEach(user => {
                // --- LOGIKA GAMBAR PROFIL DINAMIS ---
                // 1. Set default ke gambar user.png
                let avatarPath = '/Sinergi/public/assets/images/user.png';

                // 2. Cek apakah user punya foto_profil di database
                // Pastikan property-nya sesuai dengan JSON (huruf kecil karena array_change_key_case)
                if (user.foto_profil && user.foto_profil !== null && user.foto_profil !== '') {
                    // Jika ada, arahkan ke folder uploads
                    avatarPath = '/Sinergi/public/uploads/user_profiles/' + escapeHtml(user.foto_profil);
                }
                // ------------------------------------

                const item = document.createElement('div');
                item.className =
                    'flex items-center p-3 hover:bg-gray-100 rounded-lg cursor-pointer transition';
                item.onclick = () => submitAddMember(user.user_id, user.nama_lengkap);

                // Gunakan variable ${avatarPath} pada tag <img>
                item.innerHTML = `
                    <img src="${avatarPath}" class="w-10 h-10 rounded-full bg-gray-200 mr-3 object-cover border border-gray-200">
                    <div class="flex-1 border-b border-gray-100 pb-2">
                        <p class="font-bold text-gray-800 text-sm">${escapeHtml(user.nama_lengkap)}</p>
                        <p class="text-xs text-gray-500">@${escapeHtml(user.username)}</p>
                        ${ (user.foto_profil) ? '' : '' }
                    </div>
                `;
                resultsContainer.appendChild(item);
            });
        })
        .catch(err => {
            console.error('Error:', err);
            loadingSpinner.classList.add('hidden');
        });
}

// --- VARIABEL GLOBAL BARU ---
let selectedUserId = null;
let selectedUserName = null;
const confirmModal = document.getElementById('confirmAddModal');
const confirmMessage = document.getElementById('confirmMessage');
const btnConfirmYes = document.getElementById('btnConfirmYes');

// --- FUNGSI BARU: Trigger Modal Konfirmasi ---
// Fungsi ini dipanggil saat user klik nama di list pencarian
function submitAddMember(userId, userName) {
    // 1. Simpan data sementara
    selectedUserId = userId;
    selectedUserName = userName;

    // 2. Update teks modal biar personal
    confirmMessage.innerHTML =
        `Apakah Anda yakin ingin menambahkan <strong>${escapeHtml(userName)}</strong> ke group ini?`;

    // 3. Tampilkan Modal Konfirmasi
    confirmModal.classList.remove('hidden');
}

// --- FUNGSI BARU: Tutup Modal Konfirmasi ---
function closeConfirmModal() {
    confirmModal.classList.add('hidden');
    selectedUserId = null; // Reset
}

// --- EVENT LISTENER: Tombol "Ya, Tambahkan" ---
btnConfirmYes.addEventListener('click', function() {
    if (selectedUserId && selectedUserName) {
        // Isi form hidden
        document.getElementById('form_target_id').value = selectedUserId;
        document.getElementById('form_target_name').value = selectedUserName;

        // Submit form
        document.getElementById('addMemberForm').submit();
    }
});

// --- LOGIKA MODAL KICK ---
const kickModal = document.getElementById('kickMemberModal');
const kickMessageText = document.getElementById('kickMessageText');
const btnConfirmKick = document.getElementById('btnConfirmKick');

// Fungsi membuka modal kick
function openKickModal(userId, userName) {
    // Update teks
    kickMessageText.innerHTML = `Yakin ingin mengeluarkan <strong>${userName}</strong> dari group?`;

    // Update Link Href pada tombol "Ya"
    // Format URL: index.php?page=kick-member&group_id=XXX&user_id=YYY&name=ZZZ
    // groupId diambil dari variabel global PHP di atas (pastikan ada const groupId = ...)
    const link =
        `index.php?page=kick-member&group_id=${groupId}&user_id=${userId}&name=${encodeURIComponent(userName)}`;

    btnConfirmKick.setAttribute('href', link);

    // Tampilkan modal
    kickModal.classList.remove('hidden');
}

function closeKickModal() {
    kickModal.classList.add('hidden');
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>