<main class="col-span-6 border-r border-gray-200">
    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="javascript:history.back()" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <h2 class="text-xl font-bold">Buat Group Diskusi Baru</h2>
    </div>

    <div class="p-6">
        <form action="index.php?page=store-group" method="POST" enctype="multipart/form-data" class="space-y-6">

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Nama Group</label>
                <input type="text" name="nama_group"
                    class="w-full border border-gray-300 rounded-lg py-2.5 px-4 focus:ring-2 focus:ring-black focus:border-black transition"
                    placeholder="Contoh: Komunitas Programmer PHP" required>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Jenis Group</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label
                        class="relative flex items-start p-4 border rounded-xl cursor-pointer hover:bg-gray-50 hover:border-gray-300 transition group-type-option">
                        <div class="flex items-center h-5">
                            <input type="radio" name="is_private" value="0"
                                class="h-4 w-4 text-black border-gray-300 focus:ring-black" checked>
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="block font-bold text-gray-900">Publik</span>
                            <span class="block text-gray-500 mt-1">Siapa saja bisa melihat dan bergabung langsung ke
                                grup ini.</span>
                        </div>
                    </label>

                    <label
                        class="relative flex items-start p-4 border rounded-xl cursor-pointer hover:bg-gray-50 hover:border-gray-300 transition group-type-option">
                        <div class="flex items-center h-5">
                            <input type="radio" name="is_private" value="1"
                                class="h-4 w-4 text-black border-gray-300 focus:ring-black">
                        </div>
                        <div class="ml-3 text-sm">
                            <span class="block font-bold text-gray-900">Privat</span>
                            <span class="block text-gray-500 mt-1">Hanya anggota yang bisa melihat isi. Join harus
                                disetujui Admin.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Gambar Sampul/Profil</label>
                <input type="file" name="group_image"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:font-semibold file:bg-black file:text-white hover:file:bg-gray-800 cursor-pointer"
                    accept="image/*">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Deskripsi</label>
                <textarea name="deskripsi" rows="4"
                    class="w-full border border-gray-300 rounded-lg py-2 px-3 focus:ring-2 focus:ring-black focus:border-black transition"
                    placeholder="Jelaskan tujuan group ini..." required></textarea>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit"
                    class="bg-black text-white font-bold rounded-full py-2.5 px-8 hover:bg-gray-800 transition shadow-lg transform hover:-translate-y-0.5 cursor-pointer">
                    Buat Group
                </button>
            </div>

        </form>
    </div>
</main>

<aside class="col-span-4">
    <?php require 'app/views/partials/sidebar_kanan.php'; ?>
</aside>