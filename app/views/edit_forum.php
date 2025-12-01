<?php
// File: app/views/edit_forum.php
// Konten ini dipanggil oleh index.php (Rombakan Besar)
// Variabel $forum_info dikirim oleh ForumController.php
?>

<main class="col-span-6 border-r border-gray-200">

    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="javascript:history.back()" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
        </a>
        <h2 class="text-xl font-bold">Edit Forum: <?= htmlspecialchars($forum_info['nama_forum']) ?></h2>
    </div>

    <div class="p-6">
        <form action="index.php?page=update-forum" method="POST" enctype="multipart/form-data" class="space-y-4">

            <input type="hidden" name="forum_id" value="<?= $forum_info['forum_id'] ?>">

            <div>
                <label for="nama_forum" class="block text-sm font-medium text-gray-700 mb-1">Nama Forum (Nama
                    Grup)</label>
                <input type="text" id="nama_forum" name="nama_forum"
                    value="<?= htmlspecialchars($forum_info['nama_forum']) ?>"
                    class="w-full border border-gray-300 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
            </div>

            <div>
                <label for="forum_image" class="block text-sm font-medium text-gray-700 mb-1">Gambar Profil Forum
                    (Opsional)</label>

                <?php if (!empty($forum_info['forum_image'])): ?>
                <div class="flex items-center mb-2">
                    <img src="/Sinergi/public/uploads/forum_profiles/<?= htmlspecialchars($forum_info['forum_image']) ?>"
                        alt="Profil Saat Ini" class="w-32 h-32 rounded-full object-cover mr-4 mt-4">
                    <span class="text-sm text-gray-500">Gambar saat ini. Pilih file baru di bawah untuk
                        menggantinya.</span>
                </div>
                <?php endif; ?>

                <input type="file" id="forum_image" name="forum_image"
                    class="w-full text-sm text-gray-900 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-1 file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                    accept="image/png, image/jpeg, image/gif">
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat (Deskripsi
                    Grup)</label>
                <?php 
                    // Logika CLOB untuk deskripsi
                    $deskripsi_raw = $forum_info['deskripsi'] ?? null;
                    $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
                ?>
                <textarea id="deskripsi" name="deskripsi" rows="4"
                    class="w-full border border-gray-300 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required><?= htmlspecialchars($deskripsi_string) ?></textarea>
            </div>

            <div class="text-right">
                <button type="submit"
                    class="bg-gray-900 text-white font-bold rounded-full py-2 px-6 hover:bg-gray-700 transition-colors duration-150">
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</main>

<aside class="col-span-4">
    <?php
        require 'app/views/partials/sidebar_kanan.php'; 
    ?>
</aside>