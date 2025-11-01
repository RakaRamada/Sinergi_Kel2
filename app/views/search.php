<?php
// File: app/views/search.php
// (Dipanggil oleh index.php, tidak perlu header/footer)

// 1. Ambil nama tab aktif dari URL. Default-nya adalah 'forum'.
$currentTab = $_GET['tab'] ?? 'forum';

// 2. Ambil query pencarian
$query = $_GET['q'] ?? ''; // 'q' untuk query
?>

<main class="col-span-6 border-r border-gray-200">

    <div class="p-4 border-b border-gray-200 sticky top-0 bg-white/80 backdrop-blur-sm z-10">
        <form action="index.php" method="GET" class="relative">
            <input type="hidden" name="page" value="search">

            <input type="text" name="q" placeholder="Cari forum atau orang..." value="<?= htmlspecialchars($query) ?>"
                class="w-full py-2 pl-10 pr-4 border border-gray-300 rounded-full bg-gray-100 focus:outline-none focus:border-blue-300">

            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </form>
    </div>

    <div class="flex border-b border-gray-200">
        <a href="index.php?page=search&tab=forum&q=<?= htmlspecialchars($query) ?>"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'forum') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Forum
        </a>
        <a href="index.php?page=search&tab=orang&q=<?= htmlspecialchars($query) ?>"
            class="flex-1 text-center py-3 font-semibold hover:bg-gray-100 <?php if ($currentTab === 'orang') echo 'border-b-2 border-black text-black font-bold'; else echo 'text-gray-500'; ?>">
            Orang
        </a>
    </div>

    <div>
        <?php if ($currentTab === 'forum'): ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar Forum"
                        class="w-12 h-12 rounded-lg mr-4">
                    <div>
                        <p class="font-bold text-gray-800">Contoh Forum: Web Lanjut</p>
                        <p class="text-sm text-gray-600 mt-1">Ini adalah deskripsi placeholder untuk forum hasil
                            pencarian.</p>
                    </div>
                </div>
                <button
                    class="border border-black bg-black text-white font-bold py-1 px-4 rounded-full hover:bg-gray-700 text-sm">Join</button>
            </div>
        </div>
        <div class="p-4 text-center text-gray-400">
            <p><i>(Placeholder hasil pencarian forum untuk '<?= htmlspecialchars($query) ?>')</i></p>
        </div>

        <?php elseif ($currentTab === 'orang'): ?>
        <div class="p-4 border-b border-gray-200 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <img src="/Sinergi/public/assets/images/user.png" alt="Avatar" class="w-12 h-12 rounded-full mr-4">
                    <div>
                        <p class="font-bold text-gray-800">Contoh User: Budi Dosen</p>
                        <p class="text-sm text-gray-500">@budi_dosen</p>
                        <p class="text-sm text-gray-600 mt-1">Dosen Teknik Multimedia Jaringan...</p>
                    </div>
                </div>
                <button
                    class="border border-black font-bold py-1 px-4 rounded-full hover:bg-gray-100 text-sm">Follow</button>
            </div>
        </div>
        <div class="p-4 text-center text-gray-400">
            <p><i>(Placeholder hasil pencarian orang untuk '<?= htmlspecialchars($query) ?>')</i></p>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php 
require 'partials/sidebar_kanan.php'; 
?>