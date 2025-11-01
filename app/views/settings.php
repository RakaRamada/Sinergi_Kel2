<?php 
// 1. Panggil header (membuka HTML, body, grid, dan sidebar kiri)
require 'app/views/partials/header.php'; 
?>

<main class="col-span-6 border-r border-gray-200">

    <div
        class="flex items-center space-x-4 p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <h2 class="text-xl font-bold">Pengaturan</h2>
    </div>

    <div class="p-6 space-y-6">

        <div>
            <h3 class="text-lg font-semibold mb-2 text-gray-800">Akun</h3>
            <div class="border border-gray-200 rounded-lg">

                <a href="index.php?page=logout"
                    class="block p-4 text-red-600 font-semibold hover:bg-red-50 rounded-lg transition-colors duration-150">
                    Keluar (Logout)
                </a>

            </div>
        </div>

    </div>
</main>

<?php 
// 3. Panggil sidebar kanan
require 'app/views/partials/sidebar_kanan.php'; 

// 4. Panggil footer
require 'app/views/partials/footer.php'; 
?>