<main class="col-span-6 border-r border-gray-200">
    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="javascript:history.back()" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <h2 class="text-xl font-bold">Edit Group</h2>
    </div>

    <div class="p-6">
        <form action="index.php?page=update-group" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="group_id" value="<?= $group_info['group_id'] ?>">

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Nama Group</label>
                <input type="text" name="nama_group" value="<?= htmlspecialchars($group_info['nama_group']) ?>"
                    class="w-full border border-gray-300 rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-black focus:border-black transition"
                    required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Jenis Group</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label
                        class="relative flex items-start p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition">
                        <div class="flex items-center h-5">
                            <input type="radio" name="is_private" value="0" class="h-4 w-4 text-black focus:ring-black"
                                <?= ($group_info['is_private'] == 0) ? 'checked' : '' ?>>
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="block font-bold text-gray-900">Publik</span>
                            <span class="block text-gray-500 mt-1">Terbuka untuk semua user.</span>
                        </div>
                    </label>

                    <label
                        class="relative flex items-start p-4 border rounded-xl cursor-pointer hover:bg-gray-50 transition">
                        <div class="flex items-center h-5">
                            <input type="radio" name="is_private" value="1" class="h-4 w-4 text-black focus:ring-black"
                                <?= ($group_info['is_private'] == 1) ? 'checked' : '' ?>>
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="block font-bold text-gray-900">Privat</span>
                            <span class="block text-gray-500 mt-1">Butuh persetujuan admin untuk join.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Gambar Profil</label>
                <?php if (!empty($group_info['group_image'])): ?>
                <div class="flex items-center mb-3">
                    <img src="/Sinergi/public/uploads/group_profiles/<?= htmlspecialchars($group_info['group_image']) ?>"
                        class="w-20 h-20 rounded-full object-cover border border-gray-200 mr-4">
                    <span class="text-xs text-gray-500">Gambar saat ini.</span>
                </div>
                <?php endif; ?>
                <input type="file" name="group_image"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800 cursor-pointer"
                    accept="image/*">
            </div>

            <?php 
                $deskripsi_raw = $group_info['deskripsi'] ?? null;
                $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
            ?>
            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Deskripsi</label>
                <textarea name="deskripsi" rows="4"
                    class="w-full border border-gray-300 rounded-lg py-2 px-3 focus:ring-2 focus:ring-black focus:border-black transition"
                    required><?= htmlspecialchars($deskripsi_string) ?></textarea>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="bg-black text-white font-bold rounded-full py-2.5 px-8 hover:bg-gray-800 transition shadow-lg">Simpan
                    Perubahan</button>
            </div>
        </form>
    </div>
</main>
<aside class="col-span-4">
    <?php require 'app/views/partials/sidebar_kanan.php'; ?>
</aside>