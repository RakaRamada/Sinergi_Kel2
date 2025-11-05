<?php 
// Variabel $profile_data dan $is_my_profile
// otomatis ada dari ProfileController.php
require 'app/views/partials/header.php'; 
?>

<main class="col-span-6 border-r border-gray-200">
    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <a href="index.php?page=dashboard" title="Kembali" class="p-2 rounded-full hover:bg-gray-200">
            <img src="/Sinergi/public/assets/icons/arrow-left.svg" alt="Kembali" class="w-6 h-6">
        </a>
        <div>
            <!-- Data Dinamis -->
            <h2 class="text-xl font-bold"><?php echo htmlspecialchars($profile_data['NAMA_LENGKAP']); ?></h2>
            <p class="text-sm text-gray-500"><?php echo $profile_data['TOTAL_POSTINGAN']; ?> postingan</p>
        </div>
    </div>

    <div>
        <div class="relative">
            <!-- Data Dinamis (Header masih statis, ganti jika Anda menambahkannya ke DB) -->
            <img src="<?php echo $profile_data['HEADER_URL_FIXED']; ?>" alt=" Header Profil" class="w-full h-48 object-cover">

            <!-- Data Dinamis -->
            <img src="<?php echo $profile_data['AVATAR_URL_FIXED']; ?>" alt="Avatar"
                 class="w-32 h-32 rounded-full absolute -bottom-16 left-6 border-4 border-white object-cover">
        </div>

        <div class="text-right p-4 border-b border-gray-200">
            <?php if ($is_my_profile): ?>
            <!-- Link ini sudah benar, mengarah ke router 'edit_profile' -->
            <a href="index.php?page=edit_profile" class="border border-gray-300 font-bold py-2 px-4 rounded-full hover:bg-gray-100 text-sm">
                Edit profile
            </a>
            <?php else: ?>
            <button class="bg-black text-white font-bold py-2 px-4 rounded-full hover:bg-gray-800 text-sm">
                Follow
            </button>
            <?php endif; ?>
        </div>

        <div class="p-4 pt-20">
            <!-- Data Dinamis -->
            <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($profile_data['NAMA_LENGKAP']); ?></h2>
            
            <!-- --- DIPERBAIKI: Menghapus NIM (Ini baris 53 Anda) --- -->
            <p class="text-gray-500 mb-2">
                @<?php echo htmlspecialchars($profile_data['USERNAME']); ?>
            </p>
            
            <!-- (Anda bisa tambahkan kolom 'bio' di tabel users untuk ini) -->
            <p class="text-gray-700 mb-4">Mahasiswa Teknik Infomatika. Lorem ipsum dolor sit amet, consectetur
                adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
            
            <!-- (Data Follower/Following statis, perlu tabel baru nanti) -->
            <div class="flex space-x-6 text-sm">
                <a href="#" class="hover:underline"><span class="font-bold text-black">2.920</span> <span
                        class="text-gray-500">Mengikuti</span></a>
                <a href="#" class="hover:underline"><span class="font-bold text-black">1.242</span> <span
                        class="text-gray-500">Pengikut</span></a>
            </div>
        </div>

    </div>

</main>

<?php 
require 'app/views/partials/sidebar_kanan.php'; 
require 'app/views/partials/footer.php'; 
?>

