<?php 
// Variabel $user_data (bukan $profile_data)
// otomatis ada dari fungsi showEditProfileForm() di Controller
require 'app/views/partials/header.php'; 
?>

<main class="col-span-6 border-r border-gray-200">
    <div class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="index.php?page=profile" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
        </a>
        <div>
            <h2 class="text-xl font-bold">Edit Profil</h2>
        </div>
    </div>

    <form action="index.php?page=update_profile" method="POST" enctype="multipart/form-data">
        
        <div class="p-8 space-y-6 max-w-lg mx-auto">
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Foto Profil (Avatar)</label>
                <div class="mt-2 flex items-center space-x-6">
                    
                    <div class="w-24 h-24 rounded-full overflow-hidden shadow-md flex-shrink-0 bg-gray-100">
                        <img id="avatarPreview" 
                             src="<?php echo $user_data['AVATAR_URL_FIXED']; ?>" 
                             alt="Avatar" 
                             class="w-full h-full object-cover">
                    </div>

                    <div>
                        <label for="avatar" class="cursor-pointer rounded-md border border-gray-300 bg-white py-2 px-3 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <span>Ganti Foto</span>
                            <input type="file" name="avatar" id="avatar" accept="image/png, image/jpeg" class="sr-only">
                        </label>
                        <p class="mt-2 text-xs text-gray-500">Kosongkan jika tidak ganti. (Maks 5MB)</p>
                    </div>
                </div>
            </div>
            <div>
                <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" id="nama_lengkap" 
                       value="<?php echo htmlspecialchars($user_data['NAMA_LENGKAP']); ?>" 
                       required
                       class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" 
                       value="<?php echo htmlspecialchars($user_data['USERNAME']); ?>"
                       required
                       class="mt-1 block w-full bg-white px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" 
                       value="<?php echo htmlspecialchars($user_data['EMAIL']); ?>"
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed" 
                       readonly>
                <p class="mt-1 text-xs text-gray-500">Email tidak dapat diubah.</p>
            </div>

            <div class="flex justify-end items-center border-t border-gray-200 pt-6 mt-6 space-x-3">
                <a href="index.php?page=profile" class="border border-gray-300 bg-white font-bold py-2 px-6 rounded-full hover:bg-gray-100 text-sm">
                    Batal
                </a>
                <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-full hover:bg-blue-700 text-sm">
                    Simpan Perubahan
                </button>
            </div>
            </div> </form>

    <script>
        document.getElementById('avatar').addEventListener('change', function(event) {
            const [file] = event.target.files;
            if (file) {
                const preview = document.getElementById('avatarPreview');
                // Buat URL sementara untuk file yang dipilih
                preview.src = URL.createObjectURL(file);
                // Hapus URL dari memori setelah gambar dimuat
                preview.onload = () => URL.revokeObjectURL(preview.src); 
            }
        });
    </script>
    </main>

<?php 
require 'app/views/partials/sidebar_kanan.php'; 
require 'app/views/partials/footer.php'; 
?>