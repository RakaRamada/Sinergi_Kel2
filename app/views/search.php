<?php 
require 'partials/header.php'; 
?>

<main class="col-span-6 border-r border-gray-200">
    <?php
    // 1. Ambil nama tab aktif dari URL. Default-nya adalah 'ramai'.
    $currentTab = $_GET['tab'] ?? 'ramai';
    ?>

    <div class="p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <div class="relative">
            <input type="text" placeholder="Cari..."
                class="w-full py-2 pl-10 pr-4 border border-gray-300 rounded-full bg-gray-100 focus:outline-none focus:border-blue-300">
            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </div>
    </div>

    <div class="flex border-b border-gray-200">
        <a href="index.php?page=search&tab=ramai"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'ramai') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Ramai
        </a>
        <a href="index.php?page=search&tab=terbaru"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'terbaru') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Terbaru
        </a>
        <a href="index.php?page=search&tab=orang"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'orang') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Orang
        </a>
        <a href="index.php?page=search&tab=media"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'media') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Media
        </a>
    </div>

    <div>
        <?php if ($currentTab === 'ramai'): ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <p class="font-semibold text-gray-700">Hasil pencarian 'Ramai' akan ditampilkan di sini.</p>
        </div>

        <?php elseif ($currentTab === 'terbaru'): ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <p class="font-semibold text-gray-700">Hasil pencarian 'Terbaru' akan ditampilkan di sini.</p>
        </div>

        <?php elseif ($currentTab === 'orang'): ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-12 h-12 rounded-full mr-4">
                    <div>
                        <p class="font-bold text-gray-800">User12345678</p>
                        <p class="text-sm text-gray-500">@crazykiller99</p>
                        <p class="text-sm text-gray-600 mt-1">Dosen Teknik Multimedia Jaringan. Lorem ipsum dolor sit
                            amet...</p>
                    </div>
                </div>
                <button
                    class="border border-black font-bold py-1 px-4 rounded-full hover:bg-gray-100 text-sm">Follow</button>
            </div>
        </div>

        <?php elseif ($currentTab === 'media'): ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <p class="font-semibold text-gray-700">Hasil pencarian 'Media' (gambar/video) akan ditampilkan di sini.</p>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php 
require 'partials/sidebar_kanan.php'; 

require 'partials/footer.php'; 
?>