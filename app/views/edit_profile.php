<main class="col-span-6 border-r border-gray-200 bg-white">
    <!-- HEADER BAR -->
    <div class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white z-50">
        <a href="index.php?page=profile" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
        </a>
        <h2 class="text-xl font-bold">Edit Profil</h2>
    </div>

    <form action="index.php?page=update_profile" method="POST" enctype="multipart/form-data">
        <div class="p-8 space-y-8 max-w-2xl mx-auto relative z-10">

            <!-- HEADER FOTO -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Header Profil</label>
                <div class="relative w-full h-40 bg-gray-100 rounded-md shadow-sm">
                    <img id="headerPreview" 
                         src="<?php echo $user_data['HEADER_URL_FIXED']; ?>" 
                         alt="Header" 
                         class="w-full h-full object-cover rounded-md">
                         
                    <label for="header" 
                           class="absolute bottom-3 right-3 bg-black text-white text-sm font-medium px-4 py-2 rounded-md cursor-pointer hover:bg-gray-800 shadow-lg transition z-20">
                        Ganti Header
                        <input type="file" name="header" id="header" accept="image/png, image/jpeg" class="sr-only">
                    </label>
                </div>
                <p class="mt-2 text-xs text-gray-500">Kosongkan jika tidak ganti. (Maks 5MB)</p>
            </div>

            <!-- AVATAR -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Foto Profil (Avatar)</label>
                <div class="mt-2 flex items-center space-x-6">
                    <div class="w-24 h-24 rounded-full overflow-hidden shadow-md bg-gray-100">
                        <img id="avatarPreview" 
                             src="<?php echo htmlspecialchars($user_data['AVATAR_URL_FIXED']); ?>" 
                             alt="Avatar" 
                             class="w-full h-full object-cover">
                    </div>
                    <div>
                        <label for="avatar" 
                               class="cursor-pointer border border-gray-300 bg-white py-2 px-3 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 shadow-sm">
                            Ganti Foto
                            <input type="file" name="avatar" id="avatar" accept="image/png, image/jpeg" class="sr-only">
                        </label>
                        <p class="mt-2 text-xs text-gray-500">Kosongkan jika tidak ganti. (Maks 5MB)</p>
                    </div>
                </div>
            </div>

            <!-- NAMA LENGKAP -->
            <div>
                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="nama_lengkap"
                       value="<?php echo htmlspecialchars($user_data['NAMA_LENGKAP']); ?>"
                       required
                       class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- USERNAME -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username"
                       value="<?php echo htmlspecialchars($user_data['USERNAME']); ?>"
                       required
                       class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- EMAIL (readonly) -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email"
                       value="<?php echo htmlspecialchars($user_data['EMAIL']); ?>"
                       readonly
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed shadow-sm">
                <p class="mt-1 text-xs text-gray-500">Email tidak dapat diubah.</p>
            </div>

            <!-- BIO -->
            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                <textarea name="bio" id="bio" rows="3"
                          class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Ceritakan sedikit tentang dirimu..."><?php echo htmlspecialchars($user_data['BIO'] ?? ''); ?></textarea>
            </div>

            <!-- BUTTONS -->
            <div class="flex justify-end items-center border-t border-gray-200 pt-6 space-x-3">
                <a href="index.php?page=profile" 
                   class="border border-gray-300 bg-white font-bold py-2 px-6 rounded-full hover:bg-gray-100 text-sm transition">
                    Batal
                </a>
                <button type="submit" 
                        class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 text-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>

    <script>
        // Safeguard: element mungkin null if browser blocks; guard with condition
        const avatarInput = document.getElementById('avatar');
        if (avatarInput) {
            avatarInput.addEventListener('change', function(event) {
                const [file] = event.target.files;
                if (file) {
                    const preview = document.getElementById('avatarPreview');
                    preview.src = URL.createObjectURL(file);
                    preview.onload = () => URL.revokeObjectURL(preview.src);
                }
            });
        }

        const headerInput = document.getElementById('header');
        if (headerInput) {
            headerInput.addEventListener('change', function(event) {
                const [file] = event.target.files;
                if (file) {
                    const preview = document.getElementById('headerPreview');
                    preview.src = URL.createObjectURL(file);
                    preview.onload = () => URL.revokeObjectURL(preview.src);
                }
            });
        }
    </script>
</main>

<?php 
require 'app/views/partials/sidebar_kanan.php'; 
?>