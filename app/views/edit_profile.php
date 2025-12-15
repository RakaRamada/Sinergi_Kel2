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
                    <img id="headerPreview" src="<?php echo $user_data['HEADER_URL_FIXED']; ?>" alt="Header"
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
                        <img id="avatarPreview" src="<?php echo htmlspecialchars($user_data['AVATAR_URL_FIXED']); ?>"
                            alt="Avatar" class="w-full h-full object-cover">
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
                    value="<?php echo htmlspecialchars($user_data['NAMA_LENGKAP']); ?>" required
                    class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- USERNAME -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username"
                    value="<?php echo htmlspecialchars($user_data['USERNAME']); ?>" required
                    class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <?php 
            // Cek Role untuk label yang sesuai
            $label_induk = '';
            if ($user_data['ROLE_ID'] == 1) $label_induk = 'NIM';
            elseif ($user_data['ROLE_ID'] == 2) $label_induk = 'NIP';
            
            // Tampilkan hanya jika role Mahasiswa atau Dosen
            if ($label_induk): 
            ?>
            <div>
                <label class="block text-sm font-medium text-gray-700"><?= $label_induk ?></label>
                <input type="text" value="<?php echo htmlspecialchars($user_data['NOMOR_INDUK'] ?? '-'); ?>" readonly
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed shadow-sm text-gray-600">
                <p class="mt-1 text-xs text-gray-500"><?= $label_induk ?> tidak dapat diubah.</p>
            </div>
            <?php endif; ?>

            <?php if (in_array($user_data['ROLE_ID'], [1, 3])): ?>
            <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 space-y-4">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                    Status Akademik
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="tahun_masuk" class="block text-sm font-medium text-gray-700">Tahun Masuk</label>
                        <input type="number" name="tahun_masuk" id="tahun_masuk" min="2000" max="<?= date('Y') ?>"
                            value="<?php echo htmlspecialchars($user_data['TAHUN_MASUK'] ?? ''); ?>"
                            placeholder="Contoh: 2021"
                            class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black transition">
                    </div>

                    <div>
                        <label for="role_id" class="block text-sm font-medium text-gray-700">Status Saat Ini</label>
                        <select name="role_id" id="role_id"
                            class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-black focus:border-black transition">
                            <option value="1" <?= $user_data['ROLE_ID'] == 1 ? 'selected' : '' ?>>Mahasiswa Aktif
                            </option>
                            <option value="3" <?= $user_data['ROLE_ID'] == 3 ? 'selected' : '' ?>>Alumni</option>
                        </select>
                    </div>
                </div>
                <p class="text-xs text-gray-500 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Syarat Alumni: Minimal masa studi 3 tahun dari tahun masuk.
                </p>
            </div>
            <?php else: ?>
            <input type="hidden" name="role_id" value="<?= $user_data['ROLE_ID'] ?>">
            <?php endif; ?>

            <!-- EMAIL (readonly) -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['EMAIL']); ?>"
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
                    class="bg-black text-white font-bold py-2 px-6 rounded-full hover:bg-gray-800 text-sm transition cursor-pointer">
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

    <div id="errorModal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeErrorModal()"></div>

        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 text-center animate-fade-in-up">
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-gray-100 mb-4">
                <svg class="h-8 w-8 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modalTitle">Judul Error</h3>
            <div class="mt-2">
                <p class="text-sm text-gray-500" id="modalMessage">Pesan error disini.</p>
            </div>
            <div class="mt-6">
                <button type="button" onclick="closeErrorModal()"
                    class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-3 bg-black text-base font-medium text-white hover:bg-gray-800 focus:outline-none transition cursor-pointer">
                    Mengerti
                </button>
            </div>
        </div>
    </div>

    <script>
    // FUNGSI MODAL
    function openErrorModal(title, message) {
        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalMessage').innerText = message;
        document.getElementById('errorModal').classList.remove('hidden');
    }

    function closeErrorModal() {
        document.getElementById('errorModal').classList.add('hidden');
        // Hapus parameter error dari URL agar tidak muncul lagi saat refresh (opsional)
        const url = new URL(window.location);
        url.searchParams.delete('error');
        window.history.replaceState({}, '', url);
    }

    // AUTO CHECK URL PARAMETER SAAT LOAD
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');

        if (error === 'belum_cukup_umur') {
            openErrorModal(
                'Gagal Mengubah Status',
                'Durasi studi Anda belum mencukupi (Minimal 3 tahun dari Tahun Masuk) untuk menjadi Alumni.'
            );
        } else if (error === 'tahun_required') {
            openErrorModal(
                'Data Belum Lengkap',
                'Mohon isi "Tahun Masuk" terlebih dahulu untuk validasi status Alumni.'
            );
        }
    });
    </script>
</main>

<?php 
require 'app/views/partials/sidebar_kanan.php'; 
?>