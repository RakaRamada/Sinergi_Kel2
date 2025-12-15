<div class="col-span-10 h-[calc(100vh-3px)] bg-gray-50 overflow-y-auto custom-scrollbar relative">

    <div
        class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-200 px-6 py-4 flex items-center gap-4 shadow-sm">
        <a href="index.php?page=group-details&group_id=<?= $group_info['group_id'] ?>"
            class="p-2 rounded-full hover:bg-gray-100 transition text-gray-600 group cursor-pointer" title="Kembali">
            <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                </path>
            </svg>
        </a>
        <h2 class="text-xl font-bold text-gray-900 tracking-tight">Pengaturan Grup</h2>
    </div>

    <div class="max-w-2xl mx-auto px-6 py-10">

        <form action="index.php?page=update-group" method="POST" enctype="multipart/form-data"
            class="space-y-8 bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
            <input type="hidden" name="group_id" value="<?= $group_info['group_id'] ?>">

            <div class="flex flex-col items-center justify-center mb-6">
                <div class="relative cursor-pointer transition transform hover:scale-105"
                    onclick="document.getElementById('group_image').click()">
                    <?php 
                        $imgSrc = !empty($group_info['group_image']) 
                            ? '/Sinergi/public/uploads/group_profiles/' . htmlspecialchars($group_info['group_image']) 
                            : '/Sinergi/public/assets/images/user.png';
                    ?>
                    <img src="<?= $imgSrc ?>" id="previewInfo"
                        class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-md bg-gray-100">
                </div>

                <label class="mt-4 text-sm font-semibold text-gray-300 cursor-pointer hover:text-gray-800 transition"
                    onclick="document.getElementById('group_image').click()">
                    Ubah Foto Grup
                </label>
                <input type="file" name="group_image" id="group_image" class="hidden" accept="image/*"
                    onchange="previewFile(this)">
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Grup</label>
                    <input type="text" name="nama_group" value="<?= htmlspecialchars($group_info['nama_group']) ?>"
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-gray-500 focus:border-gray-500 block w-full p-3 transition"
                        placeholder="Contoh: Komunitas Programmer" required>
                </div>

                <?php 
                    $deskripsi_string = '';
                    if (isset($group_info['deskripsi'])) {
                        if ($group_info['deskripsi'] instanceof OCILob) {
                            $deskripsi_string = $group_info['deskripsi']->read($group_info['deskripsi']->size());
                        } else {
                            $deskripsi_string = $group_info['deskripsi'];
                        }
                    }
                ?>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label>
                    <textarea name="deskripsi" rows="4"
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-gray-500 focus:border-gray-500 block w-full p-3 transition resize-none"
                        placeholder="Jelaskan tujuan grup ini..."
                        required><?= htmlspecialchars($deskripsi_string) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">Jenis Privasi</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <label id="label-public"
                            class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 
                            <?= ($group_info['is_private'] == 0) ? 'bg-gray-50 border-gray-900 ring-1 ring-gray-500' : 'bg-white border-gray-200 hover:border-gray-300 hover:bg-gray-50' ?>">

                            <input type="radio" name="is_private" value="0" class="sr-only"
                                <?= ($group_info['is_private'] == 0) ? 'checked' : '' ?> onchange="updatePrivacyUI(0)">

                            <span class="flex flex-1">
                                <span class="flex flex-col">
                                    <span class="block text-sm font-bold text-gray-900 mb-1">🌐 Publik</span>
                                    <span class="block text-xs text-gray-500">Semua orang bisa melihat dan
                                        bergabung.</span>
                                </span>
                            </span>

                            <svg id="icon-public"
                                class="h-5 w-5 text-gray-600 <?= ($group_info['is_private'] == 0) ? '' : 'hidden' ?>"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </label>

                        <label id="label-private"
                            class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200
                            <?= ($group_info['is_private'] == 1) ? 'bg-gray-50 border-gray-500 ring-1 ring-gray-500' : 'bg-white border-gray-200 hover:border-gray-300 hover:bg-gray-50' ?>">

                            <input type="radio" name="is_private" value="1" class="sr-only"
                                <?= ($group_info['is_private'] == 1) ? 'checked' : '' ?> onchange="updatePrivacyUI(1)">

                            <span class="flex flex-1">
                                <span class="flex flex-col">
                                    <span class="block text-sm font-bold text-gray-900 mb-1">🔒 Privat</span>
                                    <span class="block text-xs text-gray-500">Hanya anggota yang bisa melihat isi
                                        grup.</span>
                                </span>
                            </span>

                            <svg id="icon-private"
                                class="h-5 w-5 text-gray-600 <?= ($group_info['is_private'] == 1) ? '' : 'hidden' ?>"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                        </label>
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="bg-black text-white font-bold rounded-xl py-3 px-8 hover:bg-gray-800 transition shadow-lg shadow-gray-300/50 cursor-pointer transform active:scale-95 duration-150">
                    Simpan Perubahan
                </button>
            </div>
        </form>

        <?php if ($group_info['created_by_user_id'] == $_SESSION['user_id']): ?>
        <div class="mt-10">
            <h3 class="text-sm font-bold text-red-600 uppercase tracking-wider mt-2 mb-3 ml-1">Zona Bahaya</h3>

            <div
                class="bg-white border border-red-200 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-sm hover:border-red-300 transition duration-300">
                <div class="text-center sm:text-left">
                    <h4 class="font-bold text-gray-900 text-base">Hapus Grup Ini</h4>
                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">
                        Tindakan ini <span class="text-red-600 font-bold">permanen</span>. Semua pesan, media, dan data
                        anggota akan hilang selamanya.
                    </p>
                </div>

                <form id="formDeleteGroup" action="index.php?page=delete-group-process" method="POST">
                    <input type="hidden" name="group_id" value="<?= $group_info['group_id'] ?>">

                    <button type="button" onclick="openDeleteGroupModal()"
                        class="whitespace-nowrap px-5 py-2.5 bg-red-50 border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-600 hover:text-white hover:border-red-600 transition duration-200 shadow-sm cursor-pointer flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Hapus Grup
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div id="deleteGroupModal" class="fixed inset-0 z-[9999] hidden transition-opacity duration-300"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">

        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity cursor-pointer"
            onclick="closeDeleteGroupModal()"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">

            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md scale-100 opacity-100 border border-red-100">

                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">

                        <div
                            class="mx-auto flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-12 sm:w-12 animate-pulse">
                            <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>

                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-xl font-bold leading-6 text-gray-900" id="modal-title">Hapus Grup Permanen?
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 leading-relaxed">
                                    Apakah kamu yakin ingin menghapus grup
                                    <strong>"<?= htmlspecialchars($group_info['nama_group']) ?>"</strong>?
                                </p>

                                <div class="mt-3 bg-red-50 border border-red-100 rounded-lg p-3 text-left">
                                    <ul class="list-disc list-inside text-xs text-red-600 font-medium space-y-1">
                                        <li>Semua riwayat chat akan dihapus.</li>
                                        <li>File & foto grup akan hilang.</li>
                                        <li>Semua anggota akan dikeluarkan.</li>
                                        <li>Tindakan ini <u>TIDAK BISA</u> dibatalkan.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-3">
                    <button type="button" id="btnConfirmDeleteGroup"
                        class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-red-200 hover:bg-red-700 sm:w-auto transition transform active:scale-95 cursor-pointer">
                        Ya, Hapus Sekarang
                    </button>
                    <button type="button" onclick="closeDeleteGroupModal()"
                        class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-100 sm:mt-0 sm:w-auto transition cursor-pointer">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 1. Preview Gambar saat upload
function previewFile(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewInfo').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// 2. Logic Update UI Radio Button (Publik/Privat)
function updatePrivacyUI(isValuePrivate) {
    const lblPublic = document.getElementById('label-public');
    const lblPrivate = document.getElementById('label-private');
    const iconPublic = document.getElementById('icon-public');
    const iconPrivate = document.getElementById('icon-private');

    // Style Aktif (Biru)
    const activeClass = ['bg-gray-50', 'border-gray-500', 'ring-1', 'ring-gray-500'];
    // Style Pasif (Putih)
    const inactiveClass = ['bg-white', 'border-gray-200', 'hover:border-gray-300', 'hover:bg-gray-50'];

    if (isValuePrivate == 1) {
        // Set Private Aktif
        lblPrivate.classList.add(...activeClass);
        lblPrivate.classList.remove(...inactiveClass);
        iconPrivate.classList.remove('hidden');

        // Set Public Pasif
        lblPublic.classList.remove(...activeClass);
        lblPublic.classList.add(...inactiveClass);
        iconPublic.classList.add('hidden');
    } else {
        // Set Public Aktif
        lblPublic.classList.add(...activeClass);
        lblPublic.classList.remove(...inactiveClass);
        iconPublic.classList.remove('hidden');

        // Set Private Pasif
        lblPrivate.classList.remove(...activeClass);
        lblPrivate.classList.add(...inactiveClass);
        iconPrivate.classList.add('hidden');
    }
}

// --- LOGIC MODAL HAPUS GRUP ---
const deleteGroupModal = document.getElementById('deleteGroupModal');

function openDeleteGroupModal() {
    deleteGroupModal.classList.remove('hidden');
    // Animasi Masuk
    const panel = deleteGroupModal.querySelector('div[class*="transform"]');
    panel.classList.remove('scale-95', 'opacity-0');
    panel.classList.add('scale-100', 'opacity-100');
}

function closeDeleteGroupModal() {
    deleteGroupModal.classList.add('hidden');
}

// Aksi Tombol Konfirmasi
document.getElementById('btnConfirmDeleteGroup').addEventListener('click', function() {
    // Submit Form Asli
    document.getElementById('formDeleteGroup').submit();
});
</script>