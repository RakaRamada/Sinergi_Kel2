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
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition"
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
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block w-full p-3 transition resize-none"
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

                <form action="index.php?page=delete-group-process" method="POST" onsubmit="return confirmDeleteGroup()">
                    <input type="hidden" name="group_id" value="<?= $group_info['group_id'] ?>">
                    <button type="submit"
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

// 3. Konfirmasi Hapus Grup
function confirmDeleteGroup() {
    return confirm(
        "PERINGATAN KERAS:\n\nApakah Anda yakin ingin menghapus grup ini beserta SELURUH isinya?\n\nTindakan ini TIDAK BISA dibatalkan."
    );
}
</script>