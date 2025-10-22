<?php 
// 1. Panggil header (membuka HTML, body, grid, dan sidebar kiri)
require 'partials/header.php'; 
?>

<main class="col-span-4 border-r border-gray-200 flex flex-col h-screen">
    <?php
    // 1. Ambil nama view aktif dari URL. Default-nya adalah 'teman'.
    $current_view = $_GET['view'] ?? 'teman';
    ?>

    <div class="pt-4  border-b border-gray-200">
        <h2 class="text-xl font-bold ml-4 mb-4">Pesan</h2>

        <div class="flex mt-4">
            <a href="index.php?page=messages&view=teman"
                class="flex-1 text-center pb-2 font-semibold <?php if ($current_view === 'teman') echo 'border-b-2 border-black text-black'; else echo 'text-gray-500'; ?>">
                Teman
            </a>
            <a href="index.php?page=messages&view=komunitas"
                class="flex-1 text-center pb-2 font-semibold <?php if ($current_view === 'komunitas') echo 'border-b-2 border-black text-black'; else echo 'text-gray-500'; ?>">
                Komunitas
            </a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        <?php if ($current_view === 'teman'): ?>

        <a href="#" class="flex items-start p-4 border-b border-gray-200 bg-gray-100">
            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-12 h-12 rounded-full mr-4">
            <div class="flex-1 overflow-hidden">
                <p class="font-bold">empanggawing <span class="text-sm font-normal text-gray-500">• 29 Sep</span></p>
                <p class="text-sm text-gray-600 truncate">Ga dulu dah</p>
            </div>
        </a>
        <a href="#" class="flex items-start p-4 border-b border-gray-200 hover:bg-gray-50">
            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-12 h-12 rounded-full mr-4">
            <div class="flex-1 overflow-hidden">
                <p class="font-bold">Raka Ramada <span class="text-sm font-normal text-gray-500">• 28 Sep</span></p>
                <p class="text-sm text-gray-600 truncate">Santai be</p>
            </div>
        </a>

        <?php else: ?>

        <div class="p-4 text-center text-gray-500">
            <p>Daftar percakapan dari komunitas akan muncul di sini.</p>
        </div>
        <a href="#" class="flex items-start p-4 border-b border-gray-200 hover:bg-gray-50">
            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Komunitas" class="w-12 h-12 rounded-lg mr-4">
            <div class="flex-1 overflow-hidden">
                <p class="font-bold">Ngodang Ngoding</p>
                <p class="text-sm text-gray-600 truncate"><span class="font-semibold">Budi:</span> Oke, deal ya besok
                    pagi.</p>
            </div>
        </a>

        <?php endif; ?>
    </div>
</main>

<aside class="col-span-6 flex flex-col h-screen">
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <div class="flex items-center">
            <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-10 h-10 rounded-full mr-3">
            <div>
                <p class="font-bold">empanggawing</p>
                <p class="text-sm text-gray-500">@hdpjkw99 | Teknik Informatika</p>
            </div>
        </div>
        <button class="bg-black text-white text-sm font-semibold py-1 px-3 rounded-full">View profile</button>
    </div>

    <div class="flex-grow p-6 overflow-y-auto space-y-4">
        <div class="text-center text-xs text-gray-400 my-4">29 Sep 2025, 14:21</div>

        <div class="flex justify-end">
            <div class="bg-blue-500 text-white p-3 rounded-lg max-w-md">Infokan main malam</div>
        </div>
        <div class="flex justify-start">
            <div class="bg-gray-200 p-3 rounded-lg max-w-md">Ga dulu dah</div>
        </div>
    </div>

    <div class="p-4 border-t border-gray-200 bg-white">
        <input type="text" placeholder="Ketik pesan..." class="w-full py-2 px-4 border rounded-full bg-gray-100">
    </div>
</aside>

<?php 
// 2. Panggil footer
require 'partials/footer.php'; 
?>